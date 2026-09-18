<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ORIGINAL_COLS = [
        'id', 'po_number', 'customer_id', 'sales_id',
        'po_date', 'total_amount', 'file_attachment', 'status',
        'approved_by', 'approved_at', 'notes', 'created_at', 'updated_at',
        'subtotal', 'tax_amount', 'grand_total', 'estimated_cost',
        'received_date', 'due_date', 'rejection_reason',
        'change_request_reason',
    ];

    private static function getSoCols(): array
    {
        $soCols = [];
        if (Schema::hasColumn('purchase_orders', 'sales_order_id')) {
            $soCols[] = 'sales_order_id';
        }
        if (Schema::hasColumn('purchase_orders', 'so_reference')) {
            $soCols[] = 'so_reference';
        }
        return $soCols;
    }

    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            $this->mysqlUp();
        } elseif ($driver === 'sqlite') {
            $this->sqliteUp();
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            $this->mysqlDown();
        } elseif ($driver === 'sqlite') {
            $this->sqliteDown();
        }
    }

    private function mysqlUp(): void
    {
        if (Schema::hasColumn('purchase_orders', 'sales_order_id')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->dropForeign(['sales_order_id']);
                $table->dropColumn(['sales_order_id', 'so_reference']);
            });
        }

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->foreignId('rfq_id')->nullable()->after('change_request_reason')
                  ->constrained('rfqs')->nullOnDelete();
            $table->string('rfq_reference', 50)->nullable()->after('rfq_id');
        });
    }

    private function mysqlDown(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['rfq_id']);
            $table->dropColumn(['rfq_id', 'rfq_reference']);
        });

        if (Schema::hasTable('sales_orders')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->foreignId('sales_order_id')->nullable()->after('sales_id')
                      ->constrained('sales_orders')->nullOnDelete();
                $table->string('so_reference', 50)->nullable()->after('sales_order_id');
            });
        }
    }

    private function sqliteUp(): void
    {
        DB::statement("PRAGMA foreign_keys=OFF");

        $hasSo = Schema::hasColumn('purchase_orders', 'sales_order_id');
        DB::statement("CREATE TABLE purchase_orders_v2 (
            \"id\" integer primary key autoincrement not null,
            \"po_number\" varchar not null,
            \"customer_id\" integer not null,
            \"sales_id\" integer not null,
            " . ($hasSo ? "\"sales_order_id\" integer," : "") . "
            \"po_date\" date not null,
            \"received_date\" date,
            \"due_date\" date,
            \"total_amount\" numeric not null,
            \"subtotal\" numeric not null default '0',
            \"tax_amount\" numeric not null default '0',
            \"estimated_cost\" numeric,
            \"grand_total\" numeric not null default '0',
            \"file_attachment\" varchar,
            \"status\" varchar check (\"status\" in ('Pending', 'Goal', 'Tidak Goal', 'Revisi')) not null default 'Pending',
            \"approved_by\" integer,
            \"approved_at\" datetime,
            \"notes\" text,
            \"rejection_reason\" text,
            \"change_request_reason\" text,
            \"rfq_id\" integer,
            \"rfq_reference\" varchar,
            \"created_at\" datetime,
            \"updated_at\" datetime,
            foreign key(\"customer_id\") references \"customers\"(\"id\") on delete cascade,
            foreign key(\"sales_id\") references \"users\"(\"id\") on delete cascade,
            foreign key(\"approved_by\") references \"users\"(\"id\") on delete set null,
            foreign key(\"rfq_id\") references \"rfqs\"(\"id\") on delete set null
        )");

        $cols = implode(', ', self::ORIGINAL_COLS);
        DB::statement("INSERT INTO purchase_orders_v2 ({$cols}) SELECT {$cols} FROM purchase_orders");

        Schema::drop('purchase_orders');
        DB::statement("ALTER TABLE purchase_orders_v2 RENAME TO purchase_orders");

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->index('status', 'idx_purchase_orders_status');
            $table->index('po_date', 'idx_purchase_orders_po_date');
            $table->index(['sales_id', 'status'], 'idx_purchase_orders_sales_status');
        });

        DB::statement("PRAGMA foreign_keys=ON");
    }

    private function sqliteDown(): void
    {
        DB::statement("PRAGMA foreign_keys=OFF");

        // Current table has: ORIGINAL_COLS + rfq_id + rfq_reference
        // Target table should have: ORIGINAL_COLS (no rfq_id, rfq_reference)
        // If sales_orders table exists, also add sales_order_id + so_reference (as nullable NULL)
        $hasSo = Schema::hasTable('sales_orders');
        DB::statement("CREATE TABLE purchase_orders_old (
            \"id\" integer primary key autoincrement not null,
            \"po_number\" varchar not null,
            \"customer_id\" integer not null,
            \"sales_id\" integer not null,
            " . ($hasSo ? "\"sales_order_id\" integer," : "") . "
            \"po_date\" date not null,
            \"received_date\" date,
            \"due_date\" date,
            \"total_amount\" numeric not null,
            \"subtotal\" numeric not null default '0',
            \"tax_amount\" numeric not null default '0',
            \"estimated_cost\" numeric,
            \"grand_total\" numeric not null default '0',
            \"file_attachment\" varchar,
            \"status\" varchar check (\"status\" in ('Pending', 'Goal', 'Tidak Goal', 'Revisi')) not null default 'Pending',
            \"approved_by\" integer,
            \"approved_at\" datetime,
            \"notes\" text,
            \"rejection_reason\" text,
            \"change_request_reason\" text,
            \"created_at\" datetime,
            \"updated_at\" datetime,
            foreign key(\"customer_id\") references \"customers\"(\"id\") on delete cascade,
            foreign key(\"sales_id\") references \"users\"(\"id\") on delete cascade,
            " . ($hasSo ? "foreign key(\"sales_order_id\") references \"sales_orders\"(\"id\") on delete set null," : "") . "
            foreign key(\"approved_by\") references \"users\"(\"id\") on delete set null
        )");

        // Select only columns that exist in current table (no SO columns — they were dropped in sqliteUp)
        $selectCols = implode(', ', self::ORIGINAL_COLS);
        DB::statement("INSERT INTO purchase_orders_old ({$selectCols}) SELECT {$selectCols} FROM purchase_orders");

        Schema::drop('purchase_orders');
        DB::statement("ALTER TABLE purchase_orders_old RENAME TO purchase_orders");

        DB::statement("PRAGMA foreign_keys=ON");
    }
};
