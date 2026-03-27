<?php

use Bayesian\PricingDiscovery\Infrastructure\Http\PricingQueryController;
use Bayesian\PricingDiscovery\Infrastructure\Http\PricingRecommendationController;
use Illuminate\Support\Facades\Route;

Route::get('/pricing/{productId}', [PricingQueryController::class, 'index']);
Route::get('/recommendation/{productId}', [PricingRecommendationController::class, 'show']);
