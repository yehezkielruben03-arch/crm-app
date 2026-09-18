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
        Schema::table('rfq_items', function (Blueprint $table) {
            $table->decimal('biaya_kirim', 15, 2)->default(0)->after('price_after_margin'); // Ongkir/Biaya transport
            $table->decimal('fee_eu', 15, 2)->default(0)->after('biaya_kirim'); // Fee End User
            $table->string('margin_type', 20)->nullable()->after('fee_eu'); // percentage, nominal
            $table->decimal('margin_value', 15, 2)->default(0)->after('margin_type');
            $table->decimal('custom_ceiling', 15, 2)->default(0)->after('margin_value'); // Angka pembulatan custom
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete()->after('custom_ceiling');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rfq_items', function (Blueprint $table) {
            $table->dropForeign(['vendor_id']);
            $table->dropColumn([
                'biaya_kirim',
                'fee_eu',
                'margin_type',
                'margin_value',
                'custom_ceiling',
                'vendor_id'
            ]);
        });
    }
};
