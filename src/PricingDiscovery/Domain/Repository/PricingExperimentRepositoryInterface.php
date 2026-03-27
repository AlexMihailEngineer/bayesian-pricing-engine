<?php

declare(strict_types=1);

namespace Bayesian\PricingDiscovery\Domain\Repository;

use Bayesian\PricingDiscovery\Domain\Model\PricingExperiment;

interface PricingExperimentRepositoryInterface
{
	public function findByProductIdAndPrice(string $productId, string $price): ?PricingExperiment;

	public function save(PricingExperiment $experiment): void;
}
