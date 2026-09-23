<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Skip on SQLite (testing) - ENUM MODIFY not supported
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        // MySQL/MariaDB: Modify ENUM column
        DB::statement("ALTER TABLE customers MODIFY COLUMN status ENUM('Prospect', 'Pending', 'Active', 'Inactive', 'Rejected', 'Lead', 'Blacklist') DEFAULT 'Prospect'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE customers MODIFY COLUMN status ENUM('Pending', 'Active', 'Inactive', 'Rejected', 'Lead') DEFAULT 'Pending'");
    }
};
