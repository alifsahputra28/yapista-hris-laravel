<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_administrative_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('bank_name')->nullable();
            $table->text('bank_account_number')->nullable();
            $table->string('bank_account_holder')->nullable();
            $table->string('tax_status')->nullable();
            $table->text('tax_identification_number')->nullable();
            $table->boolean('nik_used_as_tax_id')->nullable();
            $table->string('ptkp_status')->nullable();
            $table->string('bpjs_health_status')->nullable();
            $table->text('bpjs_health_number')->nullable();
            $table->string('bpjs_employment_status')->nullable();
            $table->text('bpjs_employment_number')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_administrative_details');
    }
};
