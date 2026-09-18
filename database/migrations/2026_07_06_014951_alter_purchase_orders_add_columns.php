<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Tambah kolom hanya jika belum ada (tidak menyebabkan error jika sebagian sudah ditambahkan)
        if (!Schema::hasColumn('purchase_orders', 'subtotal')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->decimal('subtotal', 15, 2)->default(0)->after('total_amount');
            });
        }
        if (!Schema::hasColumn('purchase_orders', 'tax_amount')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->decimal('tax_amount', 15, 2)->default(0)->after('subtotal');
            });
        }
        if (!Schema::hasColumn('purchase_orders', 'grand_total')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->decimal('grand_total', 15, 2)->default(0)->after('tax_amount');
            });
        }
        if (!Schema::hasColumn('purchase_orders', 'estimated_cost')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->decimal('estimated_cost', 15, 2)->nullable()->after('grand_total');
            });
        }
        if (!Schema::hasColumn('purchase_orders', 'received_date')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->date('received_date')->nullable()->after('po_date');
            });
        }
        if (!Schema::hasColumn('purchase_orders', 'due_date')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->date('due_date')->nullable()->after('received_date');
            });
        }
        if (!Schema::hasColumn('purchase_orders', 'rejection_reason')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->text('rejection_reason')->nullable()->after('notes');
            });
        }

        // Fix status ENUM supaya sinkron dengan model constants (Pending/Goal/Tidak Goal)
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE purchase_orders MODIFY COLUMN status 
                ENUM('Pending','Goal','Tidak Goal') 
                NOT NULL DEFAULT 'Pending'");
        }
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn([
                'subtotal', 'tax_amount', 'grand_total', 'estimated_cost',
                'received_date', 'due_date', 'rejection_reason',
            ]);
        });

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE purchase_orders MODIFY COLUMN status 
                ENUM('Pending','Goal','Tidak Goal') 
                NOT NULL DEFAULT 'Pending'");
        }
    }
};
