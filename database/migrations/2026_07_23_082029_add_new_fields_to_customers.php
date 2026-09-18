<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('brand_name', 150)->nullable()->after('company_name');
            $table->string('customer_type', 50)->nullable()->after('brand_name');
            $table->string('nib', 30)->nullable()->after('npwp');
            $table->string('country', 100)->nullable()->default('Indonesia')->after('postal_code');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['brand_name', 'customer_type', 'nib', 'country']);
        });
    }
};
