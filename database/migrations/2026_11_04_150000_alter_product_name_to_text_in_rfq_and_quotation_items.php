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
        if (Schema::hasTable('rfq_items') && Schema::hasColumn('rfq_items', 'product_name')) {
            Schema::table('rfq_items', function (Blueprint $table) {
                $table->text('product_name')->change();
            });
        }

        if (Schema::hasTable('quotation_items') && Schema::hasColumn('quotation_items', 'product_name')) {
            Schema::table('quotation_items', function (Blueprint $table) {
                $table->text('product_name')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('rfq_items') && Schema::hasColumn('rfq_items', 'product_name')) {
            Schema::table('rfq_items', function (Blueprint $table) {
                $table->string('product_name', 150)->change();
            });
        }

        if (Schema::hasTable('quotation_items') && Schema::hasColumn('quotation_items', 'product_name')) {
            Schema::table('quotation_items', function (Blueprint $table) {
                $table->string('product_name', 255)->change();
            });
        }
    }
};
