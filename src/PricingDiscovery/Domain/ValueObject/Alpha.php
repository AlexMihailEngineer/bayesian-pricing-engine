<?php

declare(strict_types=1);

namespace Bayesian\PricingDiscovery\Domain\ValueObject;

use Brick\Math\BigDecimal;
use InvalidArgumentException;

readonly class Alpha
{
	public function __construct(
		public BigDecimal $value
	) {
		if ($this->value->isLessThanOrEqualTo(0)) {
			throw new InvalidArgumentException('Alpha parameter (conversions) must be strictly positive.');
		}
	}

	public static function fromString(string|int|float $value): self
	{
		return new self(BigDecimal::of($value));
	}

	public function increment(): self
	{
		return new self($this->value->plus(BigDecimal::one()));
	}

	public function toString(): string
	{
		return (string) $this->value;
	}
}
