<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rfqs', function (Blueprint $table) {
            $table->string('material_package_unit', 30)->default('Lot')->nullable()->after('payment_term_days');
            $table->string('jasa_package_unit', 30)->default('Lot')->nullable()->after('material_package_unit');
        });
    }

    public function down(): void
    {
        Schema::table('rfqs', function (Blueprint $table) {
            $table->dropColumn(['material_package_unit', 'jasa_package_unit']);
        });
    }
};
