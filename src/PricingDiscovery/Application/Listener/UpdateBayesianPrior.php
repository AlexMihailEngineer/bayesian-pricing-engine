<?php

declare(strict_types=1);

namespace Bayesian\PricingDiscovery\Application\Listener;

use Bayesian\Shared\Domain\Event\MarketSignalReceived;
use Bayesian\PricingDiscovery\Domain\Repository\PricingExperimentRepositoryInterface;
use Bayesian\PricingDiscovery\Domain\Model\PricingExperiment;
use Illuminate\Support\Str;

class UpdateBayesianPrior
{
	public function __construct(
		private readonly PricingExperimentRepositoryInterface $repository
	) {}

	/**
	 * Handle the incoming market signal.
	 */
	public function handle(MarketSignalReceived $event): void
	{
		// 1. Check if we already have an experiment for this specific Product & Price
		$experiment = $this->repository->findByProductIdAndPrice(
			$event->productId,
			$event->price
		);

		// 2. If it's a new price point, initialize with a Uniform Prior (α=1, β=1)
		// This represents our "Initial Belief" before seeing any data.
		if (!$experiment) {
			$experiment = PricingExperiment::start(
				Str::uuid()->toString(),
				$event->productId,
				$event->price
			);
		}

		// 3. Apply the Bayesian Posterior Update Logic
		if ($event->isConversion) {
			$experiment->recordConversion(); // Increments Alpha
		} else {
			$experiment->recordBounce();     // Increments Beta
		}

		// 4. Save the updated mathematical state
		$this->repository->save($experiment);
	}
}
