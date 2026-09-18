<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        // ── Fix: hapus duplikat 'Admin' dari ENUM role (hanya MySQL) ──
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role
                ENUM('Super Admin', 'Admin', 'Sales')
                NOT NULL DEFAULT 'Sales'");
        }

        // ── Index untuk query yang sering dijalankan ──
        // customers: filter status, search nama perusahaan
        Schema::table('customers', function (Blueprint $table) {
            $table->index('status', 'idx_customers_status');
            $table->index('company_name', 'idx_customers_company_name');
        });

        // sales_orders: filter status, filter tanggal
        if (Schema::hasTable('sales_orders')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                $table->index('status', 'idx_sales_orders_status');
                $table->index('order_date', 'idx_sales_orders_order_date');
            });
        }

        // purchase_orders: filter status, filter tanggal, composite sales+status
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->index('status', 'idx_purchase_orders_status');
            $table->index('po_date', 'idx_purchase_orders_po_date');
            $table->index(['sales_id', 'status'], 'idx_purchase_orders_sales_status');
        });

        // notifications: unread count per user, filter tipe
        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['user_id', 'read_at'], 'idx_notifications_user_read');
            $table->index('type', 'idx_notifications_type');
        });
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role
                ENUM('Super Admin', 'Admin', 'Sales')
                NOT NULL DEFAULT 'Sales'");
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('idx_customers_status');
            $table->dropIndex('idx_customers_company_name');
        });

        if (Schema::hasTable('sales_orders')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                $table->dropIndex('idx_sales_orders_status');
                $table->dropIndex('idx_sales_orders_order_date');
            });
        }

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropIndex('idx_purchase_orders_status');
            $table->dropIndex('idx_purchase_orders_po_date');
            $table->dropIndex('idx_purchase_orders_sales_status');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('idx_notifications_user_read');
            $table->dropIndex('idx_notifications_type');
        });
    }
};
