<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Tambah parent_id dan is_bundle untuk mendukung PC Rakitan dan item bundling
    public function up(): void
    {
        Schema::table('rfq_items', function (Blueprint $table) {
            $table->foreignId('parent_id')
                ->nullable()
                ->after('rfq_id')
                ->constrained('rfq_items')
                ->cascadeOnDelete();

            $table->boolean('is_bundle')
                ->default(false)
                ->after('parent_id');
        });
    }

    public function down(): void
    {
        Schema::table('rfq_items', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['parent_id', 'is_bundle']);
        });
    }
};
