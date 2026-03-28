<?php

declare(strict_types=1);

namespace Bayesian\PricingDiscovery\Infrastructure\Persistence;

use Bayesian\PricingDiscovery\Infrastructure\Persistence\Eloquent\PricingExperimentEloquentModel;
use Bayesian\PricingDiscovery\Domain\Model\PricingExperiment;
use Bayesian\PricingDiscovery\Domain\ValueObject\Alpha;
use Bayesian\PricingDiscovery\Domain\ValueObject\Beta;
use Brick\Math\BigDecimal;

class ExperimentHydrator
{
	/**
	 * Transform Eloquent Data (Persistence) -> Domain Aggregate (Logic)
	 */
	public function toDomain(PricingExperimentEloquentModel $eloquent): PricingExperiment
	{
		return new PricingExperiment(
			experimentId: $eloquent->id,
			productId: $eloquent->product_id,
			alpha: Alpha::fromString($eloquent->alpha),
			beta: Beta::fromString($eloquent->beta),
			pricePoint: BigDecimal::of($eloquent->price_point)
		);
	}

	/**
	 * Transform Domain Aggregate (Logic) -> Raw Array for Eloquent (Persistence)
	 */
	public function toEloquent(PricingExperiment $domain): array
	{
		return [
			'id' => $domain->getExperimentId(),
			'product_id' => $domain->getProductId(),
			'alpha' => $domain->getAlphaAsString(),
			'beta' => $domain->getBetaAsString(),
			'price_point' => $domain->getPricePoint(),
		];
	}
}
