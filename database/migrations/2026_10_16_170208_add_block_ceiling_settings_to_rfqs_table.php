<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rfqs', function (Blueprint $table) {
            $table->boolean('enable_material_ceiling')->default(true)->after('jasa_package_unit');
            $table->decimal('material_ceiling_step', 15, 2)->default(500000)->after('enable_material_ceiling');
            $table->decimal('material_custom_total', 15, 2)->nullable()->after('material_ceiling_step');

            $table->boolean('enable_jasa_ceiling')->default(true)->after('material_custom_total');
            $table->decimal('jasa_ceiling_step', 15, 2)->default(500000)->after('enable_jasa_ceiling');
            $table->decimal('jasa_custom_total', 15, 2)->nullable()->after('jasa_ceiling_step');
        });
    }

    public function down(): void
    {
        Schema::table('rfqs', function (Blueprint $table) {
            $table->dropColumn([
                'enable_material_ceiling',
                'material_ceiling_step',
                'material_custom_total',
                'enable_jasa_ceiling',
                'jasa_ceiling_step',
                'jasa_custom_total',
            ]);
        });
    }
};
