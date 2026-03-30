<?php

declare(strict_types=1);

namespace Bayesian\PricingDiscovery\Application\CommandHandler;

use Bayesian\PricingDiscovery\Application\Command\IngestMarketSignalCommand;
use Bayesian\PricingDiscovery\Domain\Repository\PricingExperimentRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

class IngestMarketSignalHandler
{
    public function __construct(
        private readonly PricingExperimentRepositoryInterface $repository
    ) {}

    public function handle(IngestMarketSignalCommand $command): void
    {
        DB::transaction(function () use ($command) {

            // Fetch the specific prior for this Product + Price combination
            $experiment = $this->repository->findByProductIdAndPrice(
                $command->productId,
                $command->price
            );

            if (!$experiment) {
                // Depending on business rules, you might initialize a new Beta(1,1) state here
                // instead of throwing an exception, allowing organic discovery of new price points.
                throw new \DomainException(
                    "Pricing experiment not found for product: {$command->productId} at price: {$command->price}"
                );
            }

            // Execute the Conjugate Update
            if ($command->isConversion) {
                $experiment->recordConversion();
            } else {
                $experiment->recordBounce();
            }

            // Persist the posterior state
            $this->repository->save($experiment);

            // Broadcast the updated expected value for this specific price
            Event::dispatch('pricing.belief_state_updated', [
                'product_id' => $experiment->getProductId(),
                'price' => $command->price,
                'expected_conversion_rate' => $experiment->getExpectedValueAsString(),
                'alpha' => $experiment->getAlphaAsString(),
                'beta' => $experiment->getBetaAsString(),
            ]);
        });
    }
}
