<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $rows = DB::table('rfq_items')
            ->whereBetween('margin', [1.01, 1.99])
            ->get();

        foreach ($rows as $row) {
            $percent = round(((float) $row->margin - 1) * 100, 2);
            DB::table('rfq_items')->where('id', $row->id)->update(['margin' => $percent]);
        }
    }

    public function down(): void
    {
        $rows = DB::table('rfq_items')
            ->whereBetween('margin', [1.01, 1.99])
            ->get();

        foreach ($rows as $row) {
            $multiplier = round(((float) $row->margin / 100) + 1, 2);
            DB::table('rfq_items')->where('id', $row->id)->update(['margin' => $multiplier]);
        }
    }
};
