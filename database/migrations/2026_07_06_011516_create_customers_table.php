<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('company_code', 20)->unique()->nullable(); // Generated after approval
            $table->string('company_name', 150);
            $table->string('industry', 100)->nullable();
            $table->string('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('website', 255)->nullable();
            $table->string('npwp', 30)->nullable();
            $table->string('company_scale', 20)->nullable(); // Enterprise / Medium / SME
            $table->string('area_category', 50)->nullable(); // Kawasan Industri / CBD / Ruko / dll
            $table->string('area', 100)->nullable(); // Menggantikan region, untuk company code
            $table->string('email', 100)->nullable(); // Email perusahaan
            $table->string('phone', 30)->nullable(); // Telp perusahaan
            $table->string('cp_name', 100)->nullable(); // Nama kontak
            $table->string('cp_position', 100)->nullable(); // Jabatan kontak
            $table->string('cp_email', 100)->nullable(); // Email bisnis kontak
            $table->string('cp_phone', 30)->nullable(); // No Telp/WA kontak
            $table->enum('status', ['Pending', 'Active', 'Inactive', 'Rejected', 'Lead'])->default('Pending');
            $table->foreignId('sales_id')->nullable()->constrained('users')->nullOnDelete(); // Sales yang handle
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); // Yang submit
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete(); // Yang approve
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
