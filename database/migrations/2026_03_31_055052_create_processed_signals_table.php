<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('processed_signals', function (Blueprint $table) {
            // signal_id is our unique anchor for idempotency
            $table->string('signal_id', 64)->primary();
            $table->timestamp('processed_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('processed_signals');
    }
};
