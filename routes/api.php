<?php

use Bayesian\PricingDiscovery\Infrastructure\Http\PricingQueryController;
use Illuminate\Support\Facades\Route;

Route::get('/pricing/{productId}', [PricingQueryController::class, 'index']);
