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
        if (Schema::hasTable('rfq_items')) {
            Schema::table('rfq_items', function (Blueprint $table) {
                if (Schema::hasColumn('rfq_items', 'link_referensi')) {
                    $table->text('link_referensi')->nullable()->change();
                }
                if (Schema::hasColumn('rfq_items', 'unit')) {
                    $table->string('unit', 100)->nullable()->change();
                }
            });
        }

        if (Schema::hasTable('quotation_items')) {
            Schema::table('quotation_items', function (Blueprint $table) {
                if (Schema::hasColumn('quotation_items', 'unit')) {
                    $table->string('unit', 100)->nullable()->change();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('rfq_items')) {
            Schema::table('rfq_items', function (Blueprint $table) {
                if (Schema::hasColumn('rfq_items', 'link_referensi')) {
                    $table->string('link_referensi', 500)->nullable()->change();
                }
                if (Schema::hasColumn('rfq_items', 'unit')) {
                    $table->string('unit', 30)->nullable()->change();
                }
            });
        }

        if (Schema::hasTable('quotation_items')) {
            Schema::table('quotation_items', function (Blueprint $table) {
                if (Schema::hasColumn('quotation_items', 'unit')) {
                    $table->string('unit', 30)->nullable()->change();
                }
            });
        }
    }
};
