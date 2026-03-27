<?php

declare(strict_types=1);

namespace Bayesian\MarketIngestion\Application\Command;

readonly class IngestMarketSignal
{
	public function __construct(
		public string $transactionId,
		public string $productId,
		public string $price,
		public int $converted, // Mapping 'Purchase Probability' (0 or 1)
		public string $occurredAt // Mapping 'Purchase_Timestamp'
	) {}
}
