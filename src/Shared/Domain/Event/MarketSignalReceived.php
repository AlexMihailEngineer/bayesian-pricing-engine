<?php

declare(strict_types=1);

namespace Bayesian\Shared\Domain\Event;

/**
 * Shared Domain Event: Dispatched by MarketIngestion, 
 * consumed by PricingDiscovery to update Bayesian priors.
 */
readonly class MarketSignalReceived
{
	public function __construct(
		public string $transactionId, // From CSV 'Transaction_ID'
		public string $productId,     // From CSV 'Product_ID'
		public bool $isConversion,    // Based on 'Purchase Probability'
		public string $price,         // Exact string for brick/math
		public string $occurredAt     // From CSV 'Purchase_Timestamp'
	) {}
}
