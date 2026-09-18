<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_permissions', function (Blueprint $table) {
            // Sama persis dengan PHP native (setup_db.php)
            $table->string('role_name', 50);
            $table->string('permission_name', 100);
            $table->primary(['role_name', 'permission_name']); // Composite PK, cegah duplikat
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
};
