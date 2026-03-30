<?php

declare(strict_types=1);

namespace Bayesian\MarketIngestion\Infrastructure\Adapter;

use Bayesian\MarketIngestion\Application\Command\IngestMarketSignal;
use InvalidArgumentException;

class RedisStreamSalesDataAdapter
{
	/**
	 * Maps a raw Redis stream message to an IngestMarketSignal command.
	 */
	public function map(array $message): IngestMarketSignal
	{
		$this->validate($message);

		return new IngestMarketSignal(
			transactionId: (string) $message['transaction_id'],
			productId: (string) $message['product_id'],
			price: (string) $message['price'],
			converted: (int)    $message['converted'],
			occurredAt: (string) $message['occurred_at']
		);
	}

	private function validate(array $data): void
	{
		$requiredFields = [
			'transaction_id',
			'product_id',
			'price',
			'converted',
			'occurred_at'
		];

		foreach ($requiredFields as $field) {
			if (!isset($data[$field]) || $data[$field] === '') {
				throw new InvalidArgumentException("Missing or empty required field: {$field}");
			}
		}
	}
}
