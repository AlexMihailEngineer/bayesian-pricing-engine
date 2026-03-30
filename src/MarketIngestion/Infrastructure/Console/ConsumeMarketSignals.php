<?php

declare(strict_types=1);

namespace Bayesian\MarketIngestion\Infrastructure\Console;

use Bayesian\MarketIngestion\Infrastructure\Adapter\RedisStreamSalesDataAdapter;
use Bayesian\MarketIngestion\Application\CommandHandler\IngestMarketSignalHandler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use InvalidArgumentException;
use Throwable;

class ConsumeMarketSignals extends Command
{
	protected $signature = 'market:consume-signals {--consumer=worker-1}';
	protected $description = 'Consumes sales data from the Redis market_signals stream';

	private const STREAM_NAME = 'market_signals';
	private const GROUP_NAME = 'sales_workers';

	public function __construct(
		private readonly RedisStreamSalesDataAdapter $adapter,
		private readonly IngestMarketSignalHandler $handler
	) {
		parent::__construct();
	}

	public function handle(): void
	{
		$this->ensureConsumerGroupExists();
		$consumerName = $this->option('consumer');

		$this->info("Consumer [{$consumerName}] is listening to stream: " . self::STREAM_NAME);
		$this->drainPendingMessages($consumerName);

		while (true) {
			try {
				// BLOCK 2000: Wait up to 2 seconds for new data (reduces CPU usage)
				// '>' : Read only new messages that haven't been delivered to other consumers
				$results = Redis::executeRaw([
					'XREADGROUP',
					'GROUP',
					self::GROUP_NAME,
					$consumerName,
					'BLOCK',
					'2000',
					'COUNT',
					'10',
					'STREAMS',
					self::STREAM_NAME,
					'>'
				]);

				if (empty($results)) {
					continue;
				}

				foreach ($results[0][1] as $message) {
					$messageId = $message[0];
					$fields = $this->parseRedisFields($message[1]);

					$this->processSignal($messageId, $fields);
				}
			} catch (Throwable $e) {
				$this->error("Error in consumer loop: " . $e->getMessage());
				sleep(1); // Brief pause before retry to prevent log flooding
			}
		}
	}

	private function drainPendingMessages(string $consumerName): void
	{
		while (true) {
			$results = Redis::executeRaw([
				'XREADGROUP',
				'GROUP',
				self::GROUP_NAME,
				$consumerName,
				'COUNT',
				'10',
				'STREAMS',
				self::STREAM_NAME,
				'0'
			]);

			if (empty($results)) {
				return;
			}

			foreach ($results[0][1] as $message) {
				$messageId = $message[0];
				$fields = $this->parseRedisFields($message[1]);

				$this->processSignal($messageId, $fields);
			}
		}
	}

	private function processSignal(string $id, array $fields): void
	{
		// Force terminal output immediately upon reading
		$this->info("--> Incoming: {$id} | Product: {$fields['product_id']}");
		try {
			// 1. Map raw Redis data to the Application Command via the Adapter (ACL)
			$command = $this->adapter->map($fields);

			// 2. Dispatch to the Handler (UpdateBayesianPrior)
			$this->handler->handle($command);

			// 3. Acknowledge (ACK) only after successful processing
			Redis::executeRaw(['XACK', self::STREAM_NAME, self::GROUP_NAME, $id]);

			$this->line("<info>Processed:</info> Signal {$id} for Product {$fields['product_id']}");
		} catch (InvalidArgumentException $e) {
			$this->warn("Skipping malformed signal {$id}: " . $e->getMessage());
			// In production, move to a Dead Letter Stream here
		} catch (Throwable $e) {
			$this->error("Failed to process signal {$id}: " . $e->getMessage());
		}
	}

	private function ensureConsumerGroupExists(): void
	{
		try {
			// MKSTREAM ensures the stream is created if it doesn't exist
			Redis::executeRaw(['XGROUP', 'CREATE', self::STREAM_NAME, self::GROUP_NAME, '$', 'MKSTREAM']);
		} catch (Throwable $e) {
			// Group already exists, ignore error
		}
	}

	private function parseRedisFields(array $rawFields): array
	{
		$fields = [];
		for ($i = 0; $i < count($rawFields); $i += 2) {
			$fields[$rawFields[$i]] = $rawFields[$i + 1];
		}
		return $fields;
	}
}
