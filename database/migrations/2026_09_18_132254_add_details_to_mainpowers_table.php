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
        Schema::table('mainpowers', function (Blueprint $table) {
            $table->decimal('uang_makan', 15, 2)->default(0)->after('thr');
            $table->decimal('bpjs_kes_percent', 5, 2)->default(4.00)->after('bpjs_kes');
            $table->decimal('bpjs_tk_percent', 5, 2)->default(5.70)->after('bpjs_tk');
            $table->decimal('lembur_per_jam', 15, 2)->default(0)->after('total');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mainpowers', function (Blueprint $table) {
            $table->dropColumn(['uang_makan', 'bpjs_kes_percent', 'bpjs_tk_percent', 'lembur_per_jam']);
        });
    }
};
