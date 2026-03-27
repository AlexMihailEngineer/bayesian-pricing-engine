<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('pricing_experiments', function (Blueprint $table) {
			// Using UUID for the ID as planned in the Hydrator
			$table->uuid('id')->primary();

			$table->string('product_id')->index();

			// Decimal(Total Digits, Decimal Places)
			// 30, 20 ensures we don't lose the "long tail" of the distribution
			$table->decimal('alpha', 30, 20);
			$table->decimal('beta', 30, 20);
			$table->decimal('price_point', 30, 20);

			$table->timestamps();

			// Indexing the unique combination of product and price
			$table->unique(['product_id', 'price_point']);
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('pricing_experiments');
	}
};
