<?php

declare(strict_types=1);

namespace Tests\Unit\PricingDiscovery;

use PHPUnit\Framework\TestCase;
use Bayesian\PricingDiscovery\Domain\Model\PricingExperiment;
use Brick\Math\BigDecimal;

class BayesianUpdateTest extends TestCase
{
	public function test_it_initializes_with_a_uniform_prior(): void
	{
		$experiment = PricingExperiment::start(
			experimentId: 'test-uuid',
			productId: 'P-101',
			pricePoint: '19.99'
		);

		// Assert numerical equality rather than string equality
		$this->assertTrue(BigDecimal::of($experiment->getAlphaAsString())->isEqualTo(1));
		$this->assertTrue(BigDecimal::of($experiment->getBetaAsString())->isEqualTo(1));

		// Expected value E[X] = 1 / (1 + 1) = 0.5
		$this->assertTrue($experiment->getExpectedConversionRate()->isEqualTo(0.5));
	}

	public function test_it_updates_posterior_parameters_correctly(): void
	{
		$experiment = PricingExperiment::start('id', 'P-101', '19.99');

		// Act: 3 Conversions (α: 1 + 3 = 4)
		$experiment->recordConversion();
		$experiment->recordConversion();
		$experiment->recordConversion();

		// Act: 1 Bounce (β: 1 + 1 = 2)
		$experiment->recordBounce();

		// Assert numerical values
		$this->assertTrue(BigDecimal::of($experiment->getAlphaAsString())->isEqualTo(4));
		$this->assertTrue(BigDecimal::of($experiment->getBetaAsString())->isEqualTo(2));

		/**
		 * Math Check:
		 * E[X] = α / (α + β) = 4 / 6 ≈ 0.6667
		 */
		$expectedRate = BigDecimal::of(4)->dividedBy(6, 4, \Brick\Math\RoundingMode::HALF_UP);

		$this->assertTrue(
			$experiment->getExpectedConversionRate(4)->isEqualTo($expectedRate)
		);
	}
}
