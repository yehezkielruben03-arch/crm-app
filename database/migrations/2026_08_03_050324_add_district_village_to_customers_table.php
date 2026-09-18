<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Tambah kolom district (Kecamatan) dan village (Kelurahan/Desa)
 * pada tabel customers.
 *
 * Kolom ini dibutuhkan untuk fitur Dependent Select Wilayah Indonesia
 * yang baru diimplementasikan di form Customer (Phase 1).
 *
 * Kenapa setelah 'city' dan sebelum 'postal_code'?
 * → Agar urutan kolom di database mencerminkan hierarki:
 *   Provinsi → Kota → Kecamatan → Kelurahan → Kode Pos → Alamat
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Kecamatan - ditempatkan setelah kolom 'city'
            $table->string('district')->nullable()->after('city');
            // Kelurahan/Desa - ditempatkan setelah kolom 'district'
            $table->string('village')->nullable()->after('district');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['district', 'village']);
        });
    }
};
