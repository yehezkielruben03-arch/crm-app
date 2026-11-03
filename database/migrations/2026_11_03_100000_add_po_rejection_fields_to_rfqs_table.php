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
        Schema::table('rfqs', function (Blueprint $table) {
            $table->text('po_rejection_note')->nullable()->after('po_file_path');
            $table->timestamp('po_rejected_at')->nullable()->after('po_rejection_note');
            $table->foreignId('po_rejected_by')->nullable()->after('po_rejected_at')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rfqs', function (Blueprint $table) {
            $table->dropForeign(['po_rejected_by']);
            $table->dropColumn(['po_rejection_note', 'po_rejected_at', 'po_rejected_by']);
        });
    }
};
