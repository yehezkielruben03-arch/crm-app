<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE customers MODIFY COLUMN status ENUM('Pending','Active','Inactive','Rejected','Lead') NOT NULL DEFAULT 'Pending'");
        } elseif ($driver === 'sqlite') {
            // SQLite tidak support ALTER COLUMN, jadi kita buat ulang tabelnya
            Schema::table('customers', function (Blueprint $table) {
                // Hapus constraint lama dan buat ulang kolom status
                $table->string('status')->default('Pending')->change();
            });
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE customers MODIFY COLUMN status ENUM('Pending','Active','Inactive','Rejected') NOT NULL DEFAULT 'Pending'");
        }
    }
};
