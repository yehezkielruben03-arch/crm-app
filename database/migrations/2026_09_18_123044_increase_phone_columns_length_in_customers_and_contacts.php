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
        Schema::table('customers', function (Blueprint $table) {
            $table->string('phone', 100)->nullable()->change();
            $table->string('office_phone', 100)->nullable()->change();
            $table->string('whatsapp', 100)->nullable()->change();
            $table->string('cp_phone', 100)->nullable()->change();
        });

        Schema::table('customer_contacts', function (Blueprint $table) {
            $table->string('phone', 100)->nullable()->change();
            $table->string('office_phone', 100)->nullable()->change();
            $table->string('whatsapp', 100)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_contacts', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->change();
            $table->string('office_phone', 20)->nullable()->change();
            $table->string('whatsapp', 20)->nullable()->change();
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->string('phone', 30)->nullable()->change();
            $table->string('office_phone', 30)->nullable()->change();
            $table->string('whatsapp', 30)->nullable()->change();
            $table->string('cp_phone', 30)->nullable()->change();
        });
    }
};
