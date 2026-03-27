<?php

declare(strict_types=1);

namespace Bayesian\PricingDiscovery\Infrastructure\Laravel;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Bayesian\Shared\Domain\Event\MarketSignalReceived;
use Bayesian\PricingDiscovery\Application\Listener\UpdateBayesianPrior;
use Bayesian\PricingDiscovery\Domain\Repository\PricingExperimentRepositoryInterface;
use Bayesian\PricingDiscovery\Infrastructure\Persistence\EloquentPricingExperimentRepository;

class PricingDiscoveryServiceProvider extends ServiceProvider
{
	/**
	 * Register any application services.
	 */
	public function register(): void
	{
		// Bind the Interface to the Eloquent Implementation
		$this->app->bind(
			PricingExperimentRepositoryInterface::class,
			EloquentPricingExperimentRepository::class
		);
	}

	/**
	 * Bootstrap any application services.
	 */
	public function boot(): void
	{
		// Register the Event to Listener mapping
		Event::listen(
			MarketSignalReceived::class,
			UpdateBayesianPrior::class
		);
	}
}
