<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_histories', function (Blueprint $table) {
            $table->id();
            $table->morphs('approvable'); // approvable_id + approvable_type (customer / sales_order / purchase_order)
            $table->foreignId('actor_id')->constrained('users')->cascadeOnDelete(); // Admin/Manager yang approve
            $table->enum('action', ['Submitted', 'Approved', 'Rejected', 'Revision Requested']);
            $table->text('comment')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_histories');
    }
};
