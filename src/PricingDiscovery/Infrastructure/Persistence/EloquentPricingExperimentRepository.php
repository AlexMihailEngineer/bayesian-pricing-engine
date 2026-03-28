<?php

declare(strict_types=1);

namespace Bayesian\PricingDiscovery\Infrastructure\Persistence;

use Bayesian\PricingDiscovery\Infrastructure\Persistence\Eloquent\PricingExperimentEloquentModel;
use Bayesian\PricingDiscovery\Domain\Model\PricingExperiment;
use Bayesian\PricingDiscovery\Domain\Repository\PricingExperimentRepositoryInterface;

class EloquentPricingExperimentRepository implements PricingExperimentRepositoryInterface
{
	public function __construct(
		private readonly ExperimentHydrator $hydrator
	) {}

	public function findByProductIdAndPrice(string $productId, string $price): ?PricingExperiment
	{
		$eloquent = PricingExperimentEloquentModel::where('product_id', $productId)
			->where('price_point', $price)
			->first();

		return $eloquent ? $this->hydrator->toDomain($eloquent) : null;
	}

	public function save(PricingExperiment $experiment): void
	{
		// Use updateOrCreate to ensure we either update existing Bayesian priors 
		// or initialize a new experiment record.
		PricingExperimentEloquentModel::updateOrCreate(
			['id' => $experiment->getExperimentId()],
			$this->hydrator->toEloquent($experiment)
		);
	}
}
