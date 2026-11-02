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
            $table->text('admin_note')->nullable()->after('notes');
            $table->timestamp('admin_note_updated_at')->nullable()->after('admin_note');
            $table->foreignId('admin_note_by')->nullable()->after('admin_note_updated_at')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rfqs', function (Blueprint $table) {
            $table->dropForeign(['admin_note_by']);
            $table->dropColumn(['admin_note', 'admin_note_updated_at', 'admin_note_by']);
        });
    }
};
