<?php

declare(strict_types=1);

namespace Bayesian\MarketIngestion\Application\CommandHandler;

use Bayesian\MarketIngestion\Application\Command\IngestMarketSignal;
use Bayesian\Shared\Domain\Event\MarketSignalReceived;
use Illuminate\Support\Facades\Event;

class IngestMarketSignalHandler
{
	public function handle(IngestMarketSignal $command): void
	{
		// 1 = Success (Alpha), 0 = Failure (Beta)
		$isConversion = ($command->converted === 1);

		Event::dispatch(new MarketSignalReceived(
			transactionId: $command->transactionId,
			productId: $command->productId,
			isConversion: $isConversion,
			price: $command->price,
			occurredAt: $command->occurredAt
		));
	}
}
