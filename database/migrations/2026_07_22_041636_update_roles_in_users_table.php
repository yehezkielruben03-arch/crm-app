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
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('role', 'old_role');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('Sales Marketing')->after('old_role');
        });

        // Copy and map data
        \Illuminate\Support\Facades\DB::table('users')->get()->each(function ($user) {
            $newRole = $user->old_role;
            if ($user->old_role === 'Admin') {
                $newRole = 'Admin Purchase';
            } elseif ($user->old_role === 'Sales') {
                $newRole = 'Sales Marketing';
            }
            \Illuminate\Support\Facades\DB::table('users')
                ->where('id', $user->id)
                ->update(['role' => $newRole]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('old_role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('role', 'old_role');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['Super Admin', 'Admin', 'Sales'])->default('Sales')->after('old_role');
        });

        \Illuminate\Support\Facades\DB::table('users')->get()->each(function ($user) {
            $oldRole = $user->old_role;
            if ($user->old_role === 'Admin Purchase') {
                $oldRole = 'Admin';
            } elseif ($user->old_role === 'Sales Marketing') {
                $oldRole = 'Sales';
            }
            \Illuminate\Support\Facades\DB::table('users')
                ->where('id', $user->id)
                ->update(['role' => $oldRole]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('old_role');
        });
    }
};
