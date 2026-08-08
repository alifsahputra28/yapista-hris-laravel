<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->text('nik_encrypted')->nullable()->after('nik');
            $table->char('nik_lookup', 64)->nullable()->unique()->after('nik_encrypted');
            $table->timestamp('nik_migrated_at')->nullable()->after('nik_lookup');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropUnique(['nik_lookup']);
            $table->dropColumn(['nik_encrypted', 'nik_lookup', 'nik_migrated_at']);
        });
    }
};
