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

	private const MAX_RETRIES = 3;

	private const DLS_STREAM_NAME = 'market_signals_dead_letters';
	private const RETRY_KEY_PREFIX = 'signal_retries:';

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

				if (empty($results) || empty($results[0][1])) {
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

			if (empty($results) || empty($results[0][1])) {
				$this->info("No more pending messages. Moving to real-time listening.");
				break;
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
		$this->info("--> Incoming: {$id} | Product: " . ($fields['product_id'] ?? 'UNKNOWN'));

		try {
			// 1. Map raw Redis data to the Application Command via the Adapter (ACL)
			$command = $this->adapter->map($fields);

			// 2. Dispatch to the Handler
			$this->handler->handle($command);

			// 3. Acknowledge (ACK) only after successful processing
			Redis::executeRaw(['XACK', self::STREAM_NAME, self::GROUP_NAME, $id]);

			// 4. Clean up any retry tracking counters
			Redis::del(self::RETRY_KEY_PREFIX . $id);

			$this->line("<info>Processed:</info> Signal {$id}");
		} catch (InvalidArgumentException $e) {
			// Deterministic Failure: Malformed data will never succeed. Route to DLS immediately.
			$this->warn("Malformed signal {$id}: " . $e->getMessage() . " -> Routing directly to DLS.");
			$this->moveToDeadLetterStream($id, $fields, $e);
		} catch (Throwable $e) {
			// Transient Failure: Track retries, move to DLS if threshold exceeded.
			$this->error("Failed to process signal {$id}: " . $e->getMessage());
			$this->handleRetryOrDeadLetter($id, $fields, $e);
		}
	}

	private function handleRetryOrDeadLetter(string $id, array $fields, Throwable $e): void
	{
		$retryKey = self::RETRY_KEY_PREFIX . $id;

		// Increment the failure count atomically
		$attempt = Redis::incr($retryKey);

		if ($attempt === 1) {
			// Set an expiration (e.g., 24 hours) to prevent memory leaks for resolved keys
			Redis::expire($retryKey, 86400);
		}

		if ($attempt >= self::MAX_RETRIES) {
			$this->error("Signal {$id} exceeded max retries ({$attempt}). Moving to DLS.");
			$this->moveToDeadLetterStream($id, $fields, $e);
			Redis::del($retryKey); // Clean up the tracker
		} else {
			$this->warn("Signal {$id} failed (Attempt {$attempt}/" . self::MAX_RETRIES . "). Retrying on next loop.");
			// Do NOT XACK here. The message remains in the PEL and will be picked up by the drainPendingMessages loop.
		}
	}

	private function moveToDeadLetterStream(string $id, array $fields, Throwable $e): void
	{
		// 1. Append debugging metadata to the original payload
		$dlsPayload = array_merge($fields, [
			'_original_id' => $id,
			'_error_message' => $e->getMessage(),
			'_failed_at' => now()->toIso8601String(),
			'_exception_class' => get_class($e),
		]);

		// 2. Flatten associative array for the XADD raw command
		$xaddArgs = ['XADD', self::DLS_STREAM_NAME, '*'];
		foreach ($dlsPayload as $key => $value) {
			$xaddArgs[] = $key;
			$xaddArgs[] = is_array($value) ? json_encode($value) : (string) $value;
		}

		// 3. Write to the Dead Letter Stream
		Redis::executeRaw($xaddArgs);

		// 4. ACK the original stream to remove it from the PEL and stop the infinite loop
		Redis::executeRaw(['XACK', self::STREAM_NAME, self::GROUP_NAME, $id]);
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
