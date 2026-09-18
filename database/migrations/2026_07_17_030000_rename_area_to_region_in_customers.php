<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('customers', 'area') && !Schema::hasColumn('customers', 'region')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->renameColumn('area', 'region');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('customers', 'region') && !Schema::hasColumn('customers', 'area')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->renameColumn('region', 'area');
            });
        }
    }
};
