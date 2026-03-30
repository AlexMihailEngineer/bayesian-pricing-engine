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
		if (!isset($fields['product_id'], $fields['price_point'])) {
			throw new InvalidArgumentException("Missing required fields (product_id, price_point) in stream payload.");
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
