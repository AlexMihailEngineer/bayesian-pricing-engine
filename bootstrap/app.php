<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
	->withRouting(
		web: __DIR__ . '/../routes/web.php',
		api: __DIR__ . '/../routes/api.php',
		commands: __DIR__ . '/../routes/console.php',
		health: '/up',
	)
	->withMiddleware(function (Middleware $middleware): void {
		//
	})
	->withCommands([
		\Bayesian\MarketIngestion\Infrastructure\Console\IngestCsvToRedis::class,
		\Bayesian\MarketIngestion\Infrastructure\Console\ConsumeMarketSignals::class,
		\Bayesian\MarketIngestion\Infrastructure\Console\SimulateMarketTraffic::class,
	])
	->withExceptions(function (Exceptions $exceptions): void {
		//
	})->create();
