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
        if (Schema::hasColumn('employees', 'nup')) {
            return;
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->string('nup', 10)->nullable()->unique();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('employees', 'nup')) {
            return;
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->dropUnique(['nup']);
            $table->dropColumn('nup');
        });
    }
};
