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
        // Add area to customers
        if (!Schema::hasColumn('customers', 'area')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->string('area', 100)->nullable()->after('industry');
            });
        }

        // Create purchase_order_items table
        if (!Schema::hasTable('purchase_order_items')) {
            Schema::create('purchase_order_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
                $table->string('item_name');
                $table->integer('quantity');
                $table->decimal('unit_price', 15, 2);
                $table->decimal('subtotal', 15, 2);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
        
        if (Schema::hasColumn('customers', 'area')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn('area');
            });
        }
    }
};
