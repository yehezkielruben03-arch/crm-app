<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rfqs', function (Blueprint $table) {
            $table->string('payment_term_type', 50)->default('tempo')->nullable()->after('tax_type');
            $table->unsignedInteger('payment_term_days')->default(14)->nullable()->after('payment_term_type');
        });
    }

    public function down(): void
    {
        Schema::table('rfqs', function (Blueprint $table) {
            $table->dropColumn(['payment_term_type', 'payment_term_days']);
        });
    }
};
