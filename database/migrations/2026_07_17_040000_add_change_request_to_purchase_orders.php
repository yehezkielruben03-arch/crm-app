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
    ];

    private static function buildCols(): array
    {
        $cols = self::ORIGINAL_COLS;
        if (Schema::hasColumn('purchase_orders', 'sales_order_id')) {
            $cols[] = 'sales_order_id';
        }
        if (Schema::hasColumn('purchase_orders', 'so_reference')) {
            $cols[] = 'so_reference';
        }
        return $cols;
    }

    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->text('change_request_reason')->nullable()->after('rejection_reason');
            });
            DB::statement("ALTER TABLE purchase_orders MODIFY COLUMN status
                ENUM('Pending','Goal','Tidak Goal','Revisi')
                NOT NULL DEFAULT 'Pending'");
        } elseif ($driver === 'sqlite') {
            $this->rebuildSqliteUp();
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE purchase_orders MODIFY COLUMN status
                ENUM('Pending','Goal','Tidak Goal')
                NOT NULL DEFAULT 'Pending'");
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->dropColumn('change_request_reason');
            });
        } elseif ($driver === 'sqlite') {
            $this->rebuildSqliteDown();
        }
    }

    private function rebuildSqliteUp(): void
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
            \"total_amount\" numeric not null,
            \"file_attachment\" varchar,
            \"status\" varchar check (\"status\" in ('Pending', 'Goal', 'Tidak Goal', 'Revisi')) not null default 'Pending',
            \"approved_by\" integer,
            \"approved_at\" datetime,
            \"notes\" text,
            \"created_at\" datetime,
            \"updated_at\" datetime,
            \"subtotal\" numeric not null default '0',
            \"tax_amount\" numeric not null default '0',
            \"grand_total\" numeric not null default '0',
            \"estimated_cost\" numeric,
            \"received_date\" date,
            \"due_date\" date,
            " . ($hasSo ? "\"so_reference\" varchar," : "") . "
            \"rejection_reason\" text,
            \"change_request_reason\" text,
            foreign key(\"customer_id\") references \"customers\"(\"id\") on delete cascade,
            foreign key(\"sales_id\") references \"users\"(\"id\") on delete cascade,
            " . ($hasSo ? "foreign key(\"sales_order_id\") references \"sales_orders\"(\"id\") on delete set null," : "") . "
            foreign key(\"approved_by\") references \"users\"(\"id\") on delete set null
        )");

        $cols = implode(', ', self::buildCols());
        DB::statement("INSERT INTO purchase_orders_v2 ({$cols}) SELECT {$cols} FROM purchase_orders");

        Schema::drop('purchase_orders');
        DB::statement("ALTER TABLE purchase_orders_v2 RENAME TO purchase_orders");

        DB::statement("PRAGMA foreign_keys=ON");
    }

    private function rebuildSqliteDown(): void
    {
        DB::statement("PRAGMA foreign_keys=OFF");

        $hasSo = Schema::hasColumn('purchase_orders', 'sales_order_id');
        DB::statement("CREATE TABLE purchase_orders_old (
            \"id\" integer primary key autoincrement not null,
            \"po_number\" varchar not null,
            \"customer_id\" integer not null,
            \"sales_id\" integer not null,
            " . ($hasSo ? "\"sales_order_id\" integer," : "") . "
            \"po_date\" date not null,
            \"total_amount\" numeric not null,
            \"file_attachment\" varchar,
            \"status\" varchar check (\"status\" in ('Pending', 'Goal', 'Tidak Goal')) not null default 'Pending',
            \"approved_by\" integer,
            \"approved_at\" datetime,
            \"notes\" text,
            \"created_at\" datetime,
            \"updated_at\" datetime,
            \"subtotal\" numeric not null default '0',
            \"tax_amount\" numeric not null default '0',
            \"grand_total\" numeric not null default '0',
            \"estimated_cost\" numeric,
            \"received_date\" date,
            \"due_date\" date,
            " . ($hasSo ? "\"so_reference\" varchar," : "") . "
            \"rejection_reason\" text,
            foreign key(\"customer_id\") references \"customers\"(\"id\") on delete cascade,
            foreign key(\"sales_id\") references \"users\"(\"id\") on delete cascade,
            " . ($hasSo ? "foreign key(\"sales_order_id\") references \"sales_orders\"(\"id\") on delete set null," : "") . "
            foreign key(\"approved_by\") references \"users\"(\"id\") on delete set null
        )");

        $cols = implode(', ', self::buildCols());
        DB::statement("INSERT INTO purchase_orders_old ({$cols}) SELECT {$cols} FROM purchase_orders");

        Schema::drop('purchase_orders');
        DB::statement("ALTER TABLE purchase_orders_old RENAME TO purchase_orders");

        DB::statement("PRAGMA foreign_keys=ON");
    }
};
