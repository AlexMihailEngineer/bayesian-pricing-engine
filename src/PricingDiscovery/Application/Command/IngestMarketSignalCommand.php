<?php

declare(strict_types=1);

namespace Bayesian\PricingDiscovery\Application\Command;

use DateTimeImmutable;

/**
 * Application Command: Ingest Market Signal
 *
 * This immutable object carries the normalized data from the Anti-Corruption Layer
 * into the Application core. It must contain no framework dependencies (like Eloquent)
 * and should only hold the primitive data required to execute the use case.
 */
readonly class IngestMarketSignalCommand
{
    public function __construct(
        public string $productId,
        public string $price, // The specific price point observed
        public bool $isConversion,
        public string $signalId,
        public DateTimeImmutable $occurredAt
    ) {}
}
