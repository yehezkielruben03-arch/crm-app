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
            if (!Schema::hasColumn('rfqs', 'revision_by')) {
                $table->foreignId('revision_by')->nullable()->after('revision_notes')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('rfqs', 'revision_at')) {
                $table->timestamp('revision_at')->nullable()->after('revision_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rfqs', function (Blueprint $table) {
            if (Schema::hasColumn('rfqs', 'revision_by')) {
                $table->dropForeign(['revision_by']);
                $table->dropColumn('revision_by');
            }
            if (Schema::hasColumn('rfqs', 'revision_at')) {
                $table->dropColumn('revision_at');
            }
        });
    }
};
