<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_contacts', function (Blueprint $table) {
            $table->string('division', 100)->nullable()->after('position');
            $table->string('office_phone', 20)->nullable()->after('phone');
            $table->string('whatsapp', 20)->nullable()->after('office_phone');
            $table->string('preferred_contact', 20)->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('customer_contacts', function (Blueprint $table) {
            $table->dropColumn(['division', 'office_phone', 'whatsapp', 'preferred_contact']);
        });
    }
};
