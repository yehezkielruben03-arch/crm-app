<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // Penerima notif
            $table->string('type');          // Jenis: 'po_approved', 'po_rejected', dll
            $table->string('title');         // Judul singkat
            $table->text('message');         // Isi pesan
            $table->string('link')->nullable(); // Link tujuan kalau diklik
            $table->timestamp('read_at')->nullable(); // Null = belum dibaca
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
