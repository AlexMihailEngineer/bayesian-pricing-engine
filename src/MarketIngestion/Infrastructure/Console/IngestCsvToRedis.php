<?php

namespace Bayesian\MarketIngestion\Infrastructure\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use League\Csv\Reader;							

class IngestCsvToRedis extends Command
{
	protected $signature = 'market:ingest-csv {path=ecommerce_dynamic_pricing_dataset.csv}';
	protected $description = 'Streams historical CSV data into the Redis market_signals stream';

	public function handle(): void
	{
		$path = $this->argument('path');

		if (!file_exists($path)) {
			$this->error("File not found at: {$path}");
			return;
		}

		$csv = Reader::from($path, 'r');
		$csv->setHeaderOffset(0);

		$this->info("Starting ingestion of market signals...");

		$count = 0;
		foreach ($csv->getRecords() as $record) {
			// Mapping the CSV columns to the Redis stream structure
			Redis::executeRaw([
				'XADD',
				'market_signals',
				'*', // Auto-generate Redis ID
				'transaction_id',
				(string) $record['Transaction_ID'],
				'product_id',
				(string) $record['Product_ID'],
				'price',
				(string) $record['Price'],
				'converted',
				(int) $record['Purchase Probability'],
				'occurred_at',
				(string) $record['Purchase_Timestamp']
			]);

			$count++;
			if ($count % 100 === 0) {
				$this->line("Processed {$count} signals...");
			}
		}

		$this->info("Successfully populated Redis with {$count} signals.");
	}
}
