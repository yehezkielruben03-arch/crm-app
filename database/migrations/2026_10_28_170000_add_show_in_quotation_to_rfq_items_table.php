<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rfq_items', function (Blueprint $table) {
            if (!Schema::hasColumn('rfq_items', 'show_in_quotation')) {
                $table->boolean('show_in_quotation')
                    ->default(true)
                    ->after('is_bundle');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rfq_items', function (Blueprint $table) {
            if (Schema::hasColumn('rfq_items', 'show_in_quotation')) {
                $table->dropColumn('show_in_quotation');
            }
        });
    }
};
