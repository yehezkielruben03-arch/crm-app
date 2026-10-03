<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('rfq_price_histories', 'approved_at')) {
            Schema::table('rfq_price_histories', function (Blueprint $table) {
                $table->timestamp('approved_at')->nullable()->after('history_data');
            });
        }

        // Tandai history RFQ yang sudah disetujui sebelumnya sebagai approved
        try {
            DB::table('rfq_price_histories')
                ->whereIn('rfq_id', function ($query) {
                    $query->select('id')->from('rfqs')->whereIn('status', [
                        'Approved',
                        'Quotation Created',
                        'Quotation Sent',
                        'PO Pending Admin',
                        'PO Pending Leader',
                        'GOAL'
                    ]);
                })
                ->whereNull('approved_at')
                ->update(['approved_at' => DB::raw('created_at')]);
        } catch (\Throwable $e) {
            // Abaikan jika driver database atau data lama berbeda
        }

        // Reset RFQ yang belum pernah di-approve agar draf awalnya tetap Versi 1
        try {
            $unapprovedRfqIds = DB::table('rfqs')
                ->whereIn('status', ['Pending Admin', 'Pending Leader'])
                ->pluck('id');

            foreach ($unapprovedRfqIds as $rfqId) {
                $latestId = DB::table('rfq_price_histories')
                    ->where('rfq_id', $rfqId)
                    ->orderByDesc('version')
                    ->value('id');

                if ($latestId) {
                    DB::table('rfq_price_histories')
                        ->where('rfq_id', $rfqId)
                        ->where('id', '!=', $latestId)
                        ->delete();

                    DB::table('rfq_price_histories')
                        ->where('id', $latestId)
                        ->update(['version' => 1, 'approved_at' => null]);
                }
            }
        } catch (\Throwable $e) {
            // Abaikan jika tabel kosong
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('rfq_price_histories', 'approved_at')) {
            Schema::table('rfq_price_histories', function (Blueprint $table) {
                $table->dropColumn('approved_at');
            });
        }
    }
};
