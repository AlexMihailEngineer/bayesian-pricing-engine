<?php

declare(strict_types=1);

namespace Bayesian\PricingDiscovery\Infrastructure\Http;

use App\Http\Controllers\Controller;
use App\Models\PricingExperimentEloquentModel;
use Illuminate\Http\JsonResponse;

class PricingRecommendationController extends Controller
{
	public function show(string $productId): JsonResponse
	{
		$experiments = PricingExperimentEloquentModel::where('product_id', $productId)->get();

		if ($experiments->isEmpty()) {
			return response()->json(['error' => 'No data'], 404);
		}

		$recommendations = $experiments->map(function ($exp) {
			$alpha = (float) $exp->alpha;
			$beta = (float) $exp->beta;
			$price = (float) $exp->price_point;

			// Expected Conversion Rate (μ)
			$mean = $alpha / ($alpha + $beta);

			// Expected Revenue = Price * Expected Conversion Rate
			$expectedRevenue = $price * $mean;

			// Statistical Variance (Uncertainty)
			$variance = ($alpha * $beta) / (pow($alpha + $beta, 2) * ($alpha + $beta + 1));
			$stdDev = sqrt($variance);

			return [
				'price' => $price,
				'alpha' => $alpha,
				'beta' => $beta,
				'expected_conversion_rate' => round($mean * 100, 2),
				'expected_revenue' => round($expectedRevenue, 2),
				'uncertainty_index' => round($stdDev * 100, 2), // Higher = we need more data
			];
		});

		// The "Optimal Price" is the one with the highest Expected Revenue
		$optimal = $recommendations->sortByDesc('expected_revenue')->first();

		return response()->json([
			'product_id' => $productId,
			'optimal_price' => $optimal['price'],
			'all_experiments' => $recommendations->values(),
		]);
	}
}
