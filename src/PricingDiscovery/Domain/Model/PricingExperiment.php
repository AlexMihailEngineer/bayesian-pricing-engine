<?php

declare(strict_types=1);

namespace Bayesian\PricingDiscovery\Domain\Model;

use Bayesian\PricingDiscovery\Domain\ValueObject\Alpha;
use Bayesian\PricingDiscovery\Domain\ValueObject\Beta;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

class PricingExperiment
{
	public function __construct(
		private readonly string $experimentId,
		private readonly string $productId,
		private Alpha $alpha,
		private Beta $beta,
		private readonly BigDecimal $pricePoint
	) {}

	/**
	 * Initializes a new pricing experiment using a uniform prior (alpha=1, beta=1).
	 */
	public static function start(string $experimentId, string $productId, string $pricePoint): self
	{
		return new self(
			$experimentId,
			$productId,
			Alpha::fromString('1.0'),
			Beta::fromString('1.0'),
			BigDecimal::of($pricePoint)
		);
	}

	/**
	 * The Bayesian Update: A conversion strictly increments the Alpha parameter.
	 */
	public function recordConversion(): void
	{
		$this->alpha = $this->alpha->increment();
	}

	/**
	 * The Bayesian Update: A non-conversion (bounce) strictly increments the Beta parameter.
	 */
	public function recordBounce(): void
	{
		$this->beta = $this->beta->increment();
	}

	/**
	 * Calculates the expected probability of conversion: E[X] = alpha / (alpha + beta)
	 */
	public function getExpectedConversionRate(int $scale = 10): BigDecimal
	{
		$total = $this->alpha->value->plus($this->beta->value);

		return $this->alpha->value->dividedBy($total, $scale, RoundingMode::HALF_UP);
	}

	/**
	 * Returns the expected conversion rate as a precise string for safe 
	 * serialization, event broadcasting, or persistence.
	 */
	public function getExpectedValueAsString(int $scale = 10): string
	{
		return (string) $this->getExpectedConversionRate($scale);
	}

	public function getExperimentId(): string
	{
		return $this->experimentId;
	}
	public function getProductId(): string
	{
		return $this->productId;
	}
	public function getPricePoint(): string
	{
		return (string) $this->pricePoint;
	}
	public function getAlphaAsString(): string
	{
		return $this->alpha->toString();
	}
	public function getBetaAsString(): string
	{
		return $this->beta->toString();
	}
}
