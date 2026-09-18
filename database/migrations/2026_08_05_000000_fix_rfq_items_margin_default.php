<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Roadmap 2c: default margin 1.10 -> 1.25 (spec terbaru)
        Schema::table('rfq_items', function (Blueprint $table) {
            $table->decimal('margin', 8, 2)->default(1.25)->change();
        });
    }

    public function down(): void
    {
        Schema::table('rfq_items', function (Blueprint $table) {
            $table->decimal('margin', 8, 2)->default(1.10)->change();
        });
    }
};
