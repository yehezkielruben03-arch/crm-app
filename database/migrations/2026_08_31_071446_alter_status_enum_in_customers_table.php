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
        // Karena modify ENUM bawaan Doctrine sering bermasalah, kita pakai Raw SQL
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE customers MODIFY COLUMN status ENUM('Prospect', 'Pending', 'Active', 'Inactive', 'Rejected', 'Lead', 'Blacklist') DEFAULT 'Prospect'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE customers MODIFY COLUMN status ENUM('Pending', 'Active', 'Inactive', 'Rejected', 'Lead') DEFAULT 'Pending'");
    }
};
