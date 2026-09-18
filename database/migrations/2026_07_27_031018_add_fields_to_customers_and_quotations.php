<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('division')->nullable()->after('area_category');
            $table->string('office_phone')->nullable()->after('phone');
            $table->string('whatsapp')->nullable()->after('office_phone');
            $table->string('preferred_contact')->nullable()->after('whatsapp');
        });

        Schema::table('quotations', function (Blueprint $table) {
            $table->date('valid_until')->nullable()->after('status');
        });

        // Migrate existing status values: Pending/Lead → Prospect, Rejected → Blacklist
        DB::table('customers')->where('status', 'Pending')->update(['status' => 'Prospect']);
        DB::table('customers')->where('status', 'Lead')->update(['status' => 'Prospect']);
        DB::table('customers')->where('status', 'Rejected')->update(['status' => 'Blacklist']);

        // Update default valid_until for existing quotations (7 days after created_at)
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("UPDATE quotations SET valid_until = DATE_ADD(created_at, INTERVAL 7 DAY) WHERE valid_until IS NULL");
        } else {
            DB::statement("UPDATE quotations SET valid_until = DATE(created_at, '+7 days') WHERE valid_until IS NULL");
        }
    }

    public function down(): void
    {
        // Reverse data migration (restore from Prospect won't know if it was Pending or Lead)
        // We only reverse columns, not data
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['division', 'office_phone', 'whatsapp', 'preferred_contact']);
        });

        Schema::table('quotations', function (Blueprint $table) {
            $table->dropColumn('valid_until');
        });
    }
};
