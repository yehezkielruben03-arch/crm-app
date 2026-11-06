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
            if (!Schema::hasColumn('rfqs', 'payment_term_dp_percent')) {
                $table->unsignedSmallInteger('payment_term_dp_percent')->nullable()->after('payment_term_days');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rfqs', function (Blueprint $table) {
            if (Schema::hasColumn('rfqs', 'payment_term_dp_percent')) {
                $table->dropColumn('payment_term_dp_percent');
            }
        });
    }
};
