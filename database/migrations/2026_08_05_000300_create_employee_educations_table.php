<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_educations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('education_level');
            $table->string('institution_name');
            $table->string('major')->nullable();
            $table->unsignedSmallInteger('start_year')->nullable();
            $table->unsignedSmallInteger('graduation_year')->nullable();
            $table->text('certificate_number')->nullable();
            $table->string('degree_prefix', 50)->nullable();
            $table->string('degree_suffix', 100)->nullable();
            $table->boolean('is_highest')->default(false);
            $table->timestamps();

            $table->index('employee_id');
            $table->index('education_level');
            $table->index('graduation_year');
            $table->index('is_highest');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_educations');
    }
};
