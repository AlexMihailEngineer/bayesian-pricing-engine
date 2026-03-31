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
            // 1. Idempotency Check
            $isNew = DB::table('processed_signals')->insertOrIgnore([
                'signal_id' => $command->signalId,
                'processed_at' => now(),
            ]);

            if ($isNew === 0) return;

            // 2. Fetch with Lock
            $experiment = $this->repository->findByProductIdAndPrice(
                $command->productId,
                $command->price
            );

            if (!$experiment) throw new \DomainException("Experiment not found.");

            // 3. Mathematical Update
            $command->isConversion ? $experiment->recordConversion() : $experiment->recordBounce();

            // 4. Persistence
            $this->repository->save($experiment);

            // 5. Dispatch only after the DB confirms the save
            DB::afterCommit(function () use ($experiment, $command) {
                Event::dispatch('pricing.belief_state_updated', [
                    'product_id' => $experiment->getProductId(),
                    'price' => $command->price,
                    'expected_conversion_rate' => $experiment->getExpectedValueAsString(),
                    'alpha' => $experiment->getAlphaAsString(),
                    'beta' => $experiment->getBetaAsString(),
                ]);
            });
        });
    }
}
