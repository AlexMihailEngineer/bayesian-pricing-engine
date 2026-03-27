<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Bayesian\MarketIngestion\Infrastructure\Adapter\CsvSalesDataAdapter;
use Bayesian\MarketIngestion\Application\CommandHandler\IngestMarketSignalHandler;
use Throwable;

class BayesianSimulateCommand extends Command
{
	/**
	 * The name and signature of the console command.
	 * We allow an optional 'file' argument, defaulting to 'signals.csv'.
	 */
	protected $signature = 'bayesian:simulate {file=ecommerce_dynamic_pricing_dataset.csv}';

	protected $description = 'Ingest historical market data to update Bayesian probability distributions';

	public function handle(
		CsvSalesDataAdapter $adapter,
		IngestMarketSignalHandler $handler
	): int {
		$filePath = base_path($this->argument('file'));

		if (!file_exists($filePath)) {
			$this->error("The file {$filePath} was not found. Please ensure it is in the project root.");
			return self::FAILURE;
		}

		$this->info("Starting Bayesian Inference loop for: " . basename($filePath));
		$this->newLine();

		$bar = $this->output->createProgressBar();
		$bar->start();

		try {
			$count = 0;
			// Use the Generator for memory-efficient streaming
			foreach ($adapter->streamSignals($filePath) as $command) {
				$handler->handle($command);
				$count++;
				if ($count > 10) break;
				$bar->advance();
			}

			$bar->finish();
			$this->newLine(2);
			$this->info("✓ Successfully processed {$count} market signals.");
			$this->info("The Bayesian priors (α/β) have been updated in Postgres.");

			return self::SUCCESS;
		} catch (Throwable $e) {
			$this->newLine();
			$this->error("Inference failed: " . $e->getMessage());
			return self::FAILURE;
		}
	}
}
