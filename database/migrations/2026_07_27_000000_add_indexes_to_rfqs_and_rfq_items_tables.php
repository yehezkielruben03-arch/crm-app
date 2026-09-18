<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Tambah Indexes ke tabel rfqs dan rfq_items
 *
 * WHY (Kenapa ini penting?):
 * DashboardController, AnalyticsController, RfqController, CustomerController
 * semuanya sering menjalankan query dengan filter:
 *   - WHERE sales_id = ?
 *   - WHERE status = 'GOAL'
 *   - WHERE sales_id = ? AND status = 'GOAL' (paling sering!)
 *   - WHERE rfq_date >= ?
 *   - WHERE customer_id = ?
 *
 * Tanpa index, setiap query harus membaca SEMUA baris dari tabel satu per satu
 * (disebut "Full Table Scan"). Dengan 10.000+ baris, ini sangat lambat.
 *
 * Dengan index, MySQL membuat "daftar isi" seperti buku — langsung loncat ke baris
 * yang dibutuhkan tanpa baca semuanya. Query bisa 100x lebih cepat.
 *
 * INDEX YANG DIPILIH (berdasarkan analisis query di codebase):
 * 1. idx_rfqs_status          → filter status (WHERE status = 'GOAL')
 * 2. idx_rfqs_sales_id        → filter per sales (WHERE sales_id = ?)
 * 3. idx_rfqs_rfq_date        → filter tanggal (WHERE rfq_date >= ?)
 * 4. idx_rfqs_customer_id     → filter per customer (WHERE customer_id = ?)
 * 5. idx_rfqs_sales_status    → COMPOSITE: paling sering dipakai di dashboard sales
 *                               (WHERE sales_id = ? AND status = 'GOAL')
 * 6. idx_rfqs_sales_date      → COMPOSITE: untuk grafik monthly per sales
 *                               (WHERE sales_id = ? AND rfq_date BETWEEN ...)
 * 7. idx_rfq_items_rfq_id     → join dari rfqs ke rfq_items (WHERE rfq_id = ?)
 *                               (ForeignKey otomatis tidak selalu buat index di MySQL)
 */
return new class extends Migration
{
    /**
     * Daftar index yang akan dibuat.
     * Format: [tabel, [kolom], nama_index]
     * Dipisah jadi array agar down() bisa drop dengan aman.
     */
    private array $rfqIndexes = [
        ['status',             'idx_rfqs_status'],
        ['sales_id',           'idx_rfqs_sales_id'],
        ['rfq_date',           'idx_rfqs_rfq_date'],
        ['customer_id',        'idx_rfqs_customer_id'],
    ];

    private array $rfqCompositeIndexes = [
        [['sales_id', 'status'],   'idx_rfqs_sales_status'],  // Paling sering: dashboard sales
        [['sales_id', 'rfq_date'], 'idx_rfqs_sales_date'],    // Grafik monthly per sales
    ];

    public function up(): void
    {
        // ── Indexes untuk tabel rfqs ───────────────────────────────────────────
        Schema::table('rfqs', function (Blueprint $table) {

            // Index kolom tunggal — buat hanya jika belum ada (idempotent)
            foreach ($this->rfqIndexes as [$column, $name]) {
                if (!$this->indexExists('rfqs', $name)) {
                    $table->index($column, $name);
                }
            }

            // Composite indexes — lebih efisien daripada dua index terpisah
            // untuk query yang selalu memakai KEDUA kolom secara bersamaan
            foreach ($this->rfqCompositeIndexes as [$columns, $name]) {
                if (!$this->indexExists('rfqs', $name)) {
                    $table->index($columns, $name);
                }
            }
        });

        // ── Index untuk tabel rfq_items ────────────────────────────────────────
        // rfq_id adalah foreign key — beberapa driver DB tidak otomatis
        // membuat index untuk FK, sehingga JOIN menjadi lambat tanpa ini.
        if (!$this->indexExists('rfq_items', 'idx_rfq_items_rfq_id')) {
            Schema::table('rfq_items', function (Blueprint $table) {
                $table->index('rfq_id', 'idx_rfq_items_rfq_id');
            });
        }
    }

    public function down(): void
    {
        // Hapus index rfqs (single)
        Schema::table('rfqs', function (Blueprint $table) {
            foreach ($this->rfqIndexes as [$column, $name]) {
                if ($this->indexExists('rfqs', $name)) {
                    $table->dropIndex($name);
                }
            }

            // Hapus composite indexes
            foreach ($this->rfqCompositeIndexes as [$columns, $name]) {
                if ($this->indexExists('rfqs', $name)) {
                    $table->dropIndex($name);
                }
            }
        });

        // Hapus index rfq_items
        if ($this->indexExists('rfq_items', 'idx_rfq_items_rfq_id')) {
            Schema::table('rfq_items', function (Blueprint $table) {
                $table->dropIndex('idx_rfq_items_rfq_id');
            });
        }
    }

    /**
     * Helper: cek apakah sebuah index sudah ada di tabel.
     *
     * Kenapa perlu ini? Karena jika kita coba buat index yang sudah ada,
     * MySQL akan error. Dengan pengecekan ini, migration aman dijalankan
     * berulang kali (disebut "idempotent" — tidak rusak meski dijalankan 2x).
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            // SQLite: cek via pragma
            $indexes = DB::select("PRAGMA index_list('{$table}')");
            foreach ($indexes as $index) {
                if ($index->name === $indexName) {
                    return true;
                }
            }
            return false;
        }

        // MySQL / MariaDB / PostgreSQL: cek via information_schema
        $dbName   = DB::getDatabaseName();
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
