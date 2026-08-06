<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('profile_review_status')->default('draft')->index();
            $table->timestamp('profile_submitted_at')->nullable();
            $table->timestamp('profile_reviewed_at')->nullable();
            $table->foreignId('profile_reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('profile_review_note')->nullable();
            $table->json('profile_rejected_sections')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['profile_reviewed_by']);
            $table->dropIndex(['profile_review_status']);
            $table->dropColumn([
                'profile_review_status',
                'profile_submitted_at',
                'profile_reviewed_at',
                'profile_reviewed_by',
                'profile_review_note',
                'profile_rejected_sections',
            ]);
        });
    }
};
