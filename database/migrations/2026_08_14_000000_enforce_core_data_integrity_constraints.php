<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->assertDataCanBeConstrained();

        Schema::table('institutions', function (Blueprint $table): void {
            $table->unique('name', 'institutions_name_unique');
        });

        Schema::table('positions', function (Blueprint $table): void {
            $table->unique(['institution_id', 'name'], 'positions_institution_name_unique');
            $table->dropForeign(['institution_id']);
        });

        Schema::table('positions', function (Blueprint $table): void {
            $table->unsignedBigInteger('institution_id')->nullable(false)->change();
        });

        Schema::table('positions', function (Blueprint $table): void {
            $table->foreign('institution_id')->references('id')->on('institutions')->restrictOnDelete();
        });

        Schema::table('employees', function (Blueprint $table): void {
            $table->unique('user_id', 'employees_user_id_unique');
        });

        Schema::table('event_attendances', function (Blueprint $table): void {
            $table->dropForeign(['event_id']);
            $table->foreign('event_id')->references('id')->on('events')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('event_attendances', function (Blueprint $table): void {
            $table->dropForeign(['event_id']);
            $table->foreign('event_id')->references('id')->on('events')->cascadeOnDelete();
        });

        Schema::table('employees', function (Blueprint $table): void {
            $table->dropUnique('employees_user_id_unique');
        });

        Schema::table('positions', function (Blueprint $table): void {
            $table->dropForeign(['institution_id']);
        });

        Schema::table('positions', function (Blueprint $table): void {
            $table->unsignedBigInteger('institution_id')->nullable()->change();
        });

        Schema::table('positions', function (Blueprint $table): void {
            $table->foreign('institution_id')->references('id')->on('institutions')->nullOnDelete();
            $table->dropUnique('positions_institution_name_unique');
        });

        Schema::table('institutions', function (Blueprint $table): void {
            $table->dropUnique('institutions_name_unique');
        });
    }

    private function assertDataCanBeConstrained(): void
    {
        $duplicateInstitutions = DB::table('institutions')
            ->selectRaw('LOWER(TRIM(name)) AS normalized_name')
            ->groupByRaw('LOWER(TRIM(name))')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        $duplicatePositions = DB::table('positions')
            ->selectRaw('institution_id, LOWER(TRIM(name)) AS normalized_name')
            ->groupByRaw('institution_id, LOWER(TRIM(name))')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        $duplicateEmployeeUsers = DB::table('employees')
            ->whereNotNull('user_id')
            ->select('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($duplicateInstitutions || $duplicatePositions || $duplicateEmployeeUsers) {
            throw new \RuntimeException('Constraint integritas tidak dapat diterapkan karena masih ada data duplikat. Jalankan audit integritas terlebih dahulu.');
        }

        if (DB::table('positions')->whereNull('institution_id')->exists()) {
            throw new \RuntimeException('Constraint integritas tidak dapat diterapkan karena masih ada jabatan tanpa unit kerja.');
        }
    }
};
