<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_id')->constrained('users')->cascadeOnDelete();
            $table->tinyInteger('target_month'); // 1-12
            $table->year('target_year');
            $table->decimal('target_amount', 15, 2);
            $table->decimal('actual_amount', 15, 2)->default(0); // Auto-calculated dari PO
            $table->timestamps();
            $table->unique(['sales_id', 'target_month', 'target_year']); // 1 target per sales per bulan
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_targets');
    }
};
