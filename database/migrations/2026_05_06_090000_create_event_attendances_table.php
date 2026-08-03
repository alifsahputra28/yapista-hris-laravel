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
        if (Schema::hasTable('event_attendances')) {
            return;
        }

        Schema::create('event_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            $table->foreignId('qr_token_id')->nullable()->constrained('employee_qr_tokens')->nullOnDelete();
            $table->foreignId('scanned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('scanned_at')->nullable();
            $table->string('attendance_status')->default('present')->index();
            $table->string('scan_method')->default('barcode')->index();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'employee_id']);
            $table->index(['event_id', 'attendance_status']);
            $table->index(['employee_id', 'attendance_status']);
            $table->index('qr_token_id');
            $table->index('scanned_by');
            $table->index('scanned_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_attendances');
    }
};
