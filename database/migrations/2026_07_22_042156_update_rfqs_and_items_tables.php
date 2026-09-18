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
        // 1. Alter rfqs table
        Schema::table('rfqs', function (Blueprint $table) {
            $table->renameColumn('status', 'old_status');
        });

        Schema::table('rfqs', function (Blueprint $table) {
            $table->string('status')->default('Pending Admin')->after('old_status');
            $table->date('need_date')->nullable()->after('rfq_date');
            $table->string('type', 50)->nullable()->after('need_date');
            $table->foreignId('customer_contact_id')->nullable()->after('customer_id')->constrained('customer_contacts')->nullOnDelete();
        });

        // Copy and map data for rfqs
        \Illuminate\Support\Facades\DB::table('rfqs')->get()->each(function ($rfq) {
            $newStatus = 'Pending Admin';
            if ($rfq->old_status === 'Converted') {
                $newStatus = 'Quotation Created';
            } elseif ($rfq->old_status === 'Cancelled') {
                $newStatus = 'Cancelled';
            }
            \Illuminate\Support\Facades\DB::table('rfqs')
                ->where('id', $rfq->id)
                ->update(['status' => $newStatus]);
        });

        Schema::table('rfqs', function (Blueprint $table) {
            $table->dropColumn('old_status');
        });

        // 2. Alter rfq_items table
        Schema::table('rfq_items', function (Blueprint $table) {
            $table->decimal('hpp', 15, 2)->default(0)->after('description');
            $table->decimal('ongkir_pedia', 15, 2)->default(0)->after('hpp');
            $table->decimal('ongkir_pelanggan', 15, 2)->default(0)->after('ongkir_pedia');
            $table->decimal('margin', 8, 2)->default(1.25)->after('ongkir_pelanggan');
            $table->integer('ceiling')->default(10000)->after('margin');
            $table->integer('validity_days')->default(7)->after('ceiling');
            $table->decimal('price_after_margin', 15, 2)->default(0)->after('validity_days');
        });
    }

    public function down(): void
    {
        Schema::table('rfq_items', function (Blueprint $table) {
            $table->dropColumn([
                'hpp', 'ongkir_pedia', 'ongkir_pelanggan', 'margin', 'ceiling', 'validity_days', 'price_after_margin'
            ]);
        });

        Schema::table('rfqs', function (Blueprint $table) {
            $table->dropForeign(['customer_contact_id']);
            $table->dropColumn(['customer_contact_id', 'need_date', 'type', 'status']);
            $table->enum('status', ['Draft', 'Converted', 'Cancelled'])->default('Draft');
        });
    }
};
