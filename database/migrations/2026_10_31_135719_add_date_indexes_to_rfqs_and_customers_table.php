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
        // ── Indexes untuk tabel rfqs ───────────────────────────────────────────
        Schema::table('rfqs', function (Blueprint $table) {
            if (!$this->indexExists('rfqs', 'idx_rfqs_created_at')) {
                $table->index('created_at', 'idx_rfqs_created_at');
            }
            if (!$this->indexExists('rfqs', 'idx_rfqs_updated_at')) {
                $table->index('updated_at', 'idx_rfqs_updated_at');
            }
            if (!$this->indexExists('rfqs', 'idx_rfqs_sales_created_at')) {
                $table->index(['sales_id', 'created_at'], 'idx_rfqs_sales_created_at');
            }
        });

        // ── Indexes untuk tabel customers ──────────────────────────────────────
        Schema::table('customers', function (Blueprint $table) {
            if (!$this->indexExists('customers', 'idx_customers_created_at')) {
                $table->index('created_at', 'idx_customers_created_at');
            }
            if (!$this->indexExists('customers', 'idx_customers_sales_created_at')) {
                $table->index(['sales_id', 'created_at'], 'idx_customers_sales_created_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rfqs', function (Blueprint $table) {
            if ($this->indexExists('rfqs', 'idx_rfqs_created_at')) {
                $table->dropIndex('idx_rfqs_created_at');
            }
            if ($this->indexExists('rfqs', 'idx_rfqs_updated_at')) {
                $table->dropIndex('idx_rfqs_updated_at');
            }
            if ($this->indexExists('rfqs', 'idx_rfqs_sales_created_at')) {
                $table->dropIndex('idx_rfqs_sales_created_at');
            }
        });

        Schema::table('customers', function (Blueprint $table) {
            if ($this->indexExists('customers', 'idx_customers_created_at')) {
                $table->dropIndex('idx_customers_created_at');
            }
            if ($this->indexExists('customers', 'idx_customers_sales_created_at')) {
                $table->dropIndex('idx_customers_sales_created_at');
            }
        });
    }

    /**
     * Helper: cek apakah sebuah index sudah ada di tabel (Idempotent).
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('{$table}')");
            foreach ($indexes as $index) {
                if ($index->name === $indexName) {
                    return true;
                }
            }
            return false;
        }

        $dbName = DB::getDatabaseName();
        $existing = DB::select("
            SELECT COUNT(*) as cnt
            FROM information_schema.statistics
            WHERE table_schema = ?
              AND table_name   = ?
              AND index_name   = ?
        ", [$dbName, $table, $indexName]);

        return ($existing[0]->cnt ?? 0) > 0;
    }
};
