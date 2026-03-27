<?php

declare(strict_types=1);

namespace Bayesian\MarketIngestion\Infrastructure\Adapter;

use Bayesian\MarketIngestion\Application\Command\IngestMarketSignal;
use League\Csv\Reader;
use Generator;

class CsvSalesDataAdapter
{
	public function streamSignals(string $filePath): Generator
	{
		$csv = Reader::from($filePath);
		$csv->setHeaderOffset(0);

		foreach ($csv->getRecords() as $record) {
			yield new IngestMarketSignal(
				transactionId: (string) $record['Transaction_ID'],
				productId: (string) $record['Product_ID'],
				price: (string) $record['Price'],
				converted: (int) $record['Purchase Probability'],
				occurredAt: (string) $record['Purchase_Timestamp']
			);
		}
	}
}
