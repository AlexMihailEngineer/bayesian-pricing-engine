<?php

declare(strict_types=1);

namespace Bayesian\MarketIngestion\Infrastructure\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class SimulateMarketTraffic extends Command
{
    protected $signature = 'market:simulate {--price=15.50} {--count=100} {--delay=100} {--rate=0.2}';
    protected $description = 'Populates the Redis stream with synthetic sales data';

    public function handle(): void
    {
        $count = (int) $this->option('count');
        $delay = (int) $this->option('delay'); // Microseconds
        $trueRate = (float) $this->option('rate');

        $this->info("Generating $count market signals...");

        for ($i = 0; $i < $count; $i++) {
            $signal = [
                'transaction_id' => bin2hex(random_bytes(8)),
                'product_id' => 'P' . rand(1000, 1010), // Limit products to see priors update
                'price' => $this->option('price'),
                'converted' => (mt_rand(0, 1000) / 1000) <= $trueRate ? 1 : 0,
                'occurred_at' => now()->toDateTimeString(),
            ];

            // XADD stream_name ID ( * for auto-generated) key value ...
            Redis::executeRaw([
                'XADD',
                'market_signals',
                '*',
                'transaction_id',
                $signal['transaction_id'],
                'product_id',
                $signal['product_id'],
                'price',
                $signal['price'],
                'converted',
                $signal['converted'],
                'occurred_at',
                $signal['occurred_at']
            ]);

            if ($delay > 0) {
                usleep($delay);
            }
        }

        $this->info("Stream populated successfully.");
    }
}
