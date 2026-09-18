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
        Schema::table('rfqs', function (Blueprint $table) {
            if (!Schema::hasColumn('rfqs', 'priority')) {
                $table->string('priority')->nullable()->default('Normal')->after('need_date');
            }
        });

        Schema::table('rfq_items', function (Blueprint $table) {
            if (!Schema::hasColumn('rfq_items', 'category')) {
                $table->string('category')->nullable()->after('rfq_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rfqs', function (Blueprint $table) {
            if (Schema::hasColumn('rfqs', 'priority')) {
                $table->dropColumn('priority');
            }
        });

        Schema::table('rfq_items', function (Blueprint $table) {
            if (Schema::hasColumn('rfq_items', 'category')) {
                $table->dropColumn('category');
            }
        });
    }
};
