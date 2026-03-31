<?php

declare(strict_types=1);

namespace Bayesian\MarketIngestion\Infrastructure\Adapter;

// IMPORT THE COMMAND FROM THE TARGET BOUNDED CONTEXT
use Bayesian\PricingDiscovery\Application\Command\IngestMarketSignalCommand;
use DateTimeImmutable;
use InvalidArgumentException;

class RedisStreamSalesDataAdapter
{
	public function map(array $fields): IngestMarketSignalCommand
	{
		// Check if keys exist AND contain non-whitespace characters
		$productId = trim($fields['product_id'] ?? '');
		$pricePoint = trim($fields['price_point'] ?? '');

		if ($productId === '' || $pricePoint === '') {
			throw new InvalidArgumentException(
				"Missing or empty required fields (product_id, price_point) in stream payload."
			);
		}

		return new IngestMarketSignalCommand(
			productId: (string) $fields['product_id'],
			price: (string) $fields['price_point'],
			isConversion: (bool) ($fields['is_conversion'] ?? false),
			signalId: (string) ($fields['signal_id'] ?? uniqid('sig_', true)),
			occurredAt: new DateTimeImmutable($fields['timestamp'] ?? 'now')
		);
	}
}
