<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_family_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('full_name');
            $table->string('relationship');
            $table->text('nik')->nullable();
            $table->string('birth_place')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('gender')->nullable();
            $table->string('occupation', 150)->nullable();
            $table->boolean('is_dependent')->default(false);
            $table->string('bpjs_status')->nullable();
            $table->timestamps();

            $table->index('employee_id');
            $table->index('relationship');
            $table->index('is_dependent');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_family_members');
    }
};
