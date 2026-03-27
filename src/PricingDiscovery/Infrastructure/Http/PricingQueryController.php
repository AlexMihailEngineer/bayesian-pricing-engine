<?php

declare(strict_types=1);

namespace Bayesian\PricingDiscovery\Infrastructure\Http;

use App\Http\Controllers\Controller;
use Bayesian\PricingDiscovery\Domain\Repository\PricingExperimentRepositoryInterface;
use App\Models\PricingExperimentEloquentModel;
use Illuminate\Http\JsonResponse;

class PricingQueryController extends Controller
{
	public function __construct(
		private readonly PricingExperimentRepositoryInterface $repository
	) {}

	/**
	 * Get all price experiments for a specific product.
	 */
	public function index(string $productId): JsonResponse
	{
		// For the PoC, we can use the Eloquent model directly for simple lists
		// or the Repository if we need full Domain Object hydration.
		$experiments = PricingExperimentEloquentModel::where('product_id', $productId)
			->orderBy('price_point', 'asc')
			->get();

		if ($experiments->isEmpty()) {
			return response()->json(['error' => 'No pricing data found for this product'], 404);
		}

		$data = $experiments->map(function ($exp) {
			$alpha = (float) $exp->alpha;
			$beta = (float) $exp->beta;

			return [
				'price' => $exp->price_point,
				'alpha' => $alpha,
				'beta' => $beta,
				// Expected Conversion Rate: E[X] = alpha / (alpha + beta)
				'expected_conversion_rate' => $alpha / ($alpha + $beta),
				// Sample size gives the user confidence in the "strength" of the belief
				'sample_size' => $alpha + $beta - 2,
			];
		});

		return response()->json([
			'product_id' => $productId,
			'experiments' => $data
		]);
	}
}
