<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('employee_documents', 'employee_education_id')) {
            Schema::table('employee_documents', function (Blueprint $table) {
                $table->unsignedBigInteger('employee_education_id')->nullable();
            });
        }

        if (! Schema::hasColumn('employee_documents', 'employee_certification_id')) {
            Schema::table('employee_documents', function (Blueprint $table) {
                $table->unsignedBigInteger('employee_certification_id')->nullable();
            });
        }

        if (! Schema::hasColumn('employee_documents', 'document_slot')) {
            Schema::table('employee_documents', function (Blueprint $table) {
                $table->string('document_slot', 100)->default('primary');
            });
        }

        if (! $this->hasForeignKey('employee_documents_employee_education_id_foreign')) {
            Schema::table('employee_documents', function (Blueprint $table) {
                $table->foreign('employee_education_id')
                    ->references('id')
                    ->on('employee_educations')
                    ->restrictOnDelete();
            });
        }

        if (! $this->hasForeignKey('employee_documents_employee_certification_id_foreign')) {
            Schema::table('employee_documents', function (Blueprint $table) {
                $table->foreign('employee_certification_id')
                    ->references('id')
                    ->on('employee_certifications')
                    ->restrictOnDelete();
            });
        }

        // MySQL may use the old composite unique index to support employee_id's foreign key.
        if (! Schema::hasIndex('employee_documents', 'employee_documents_employee_id_index')) {
            Schema::table('employee_documents', function (Blueprint $table) {
                $table->index('employee_id');
            });
        }

        if (Schema::hasIndex('employee_documents', 'employee_documents_employee_id_document_type_unique')) {
            Schema::table('employee_documents', function (Blueprint $table) {
                $table->dropUnique('employee_documents_employee_id_document_type_unique');
            });
        }

        if (! Schema::hasIndex('employee_documents', 'employee_documents_employee_type_slot_unique')) {
            Schema::table('employee_documents', function (Blueprint $table) {
                $table->unique(
                    ['employee_id', 'document_type', 'document_slot'],
                    'employee_documents_employee_type_slot_unique'
                );
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('employee_documents', 'employee_documents_employee_type_slot_unique')) {
            Schema::table('employee_documents', function (Blueprint $table) {
                $table->dropUnique('employee_documents_employee_type_slot_unique');
            });
        }

        if (! Schema::hasIndex('employee_documents', 'employee_documents_employee_id_document_type_unique')) {
            Schema::table('employee_documents', function (Blueprint $table) {
                $table->unique(['employee_id', 'document_type']);
            });
        }

        if ($this->hasForeignKey('employee_documents_employee_education_id_foreign')) {
            Schema::table('employee_documents', function (Blueprint $table) {
                $table->dropForeign(['employee_education_id']);
            });
        }

        if ($this->hasForeignKey('employee_documents_employee_certification_id_foreign')) {
            Schema::table('employee_documents', function (Blueprint $table) {
                $table->dropForeign(['employee_certification_id']);
            });
        }

        Schema::table('employee_documents', function (Blueprint $table) {
            $table->dropColumn(['employee_education_id', 'employee_certification_id', 'document_slot']);
        });

        if (Schema::hasIndex('employee_documents', 'employee_documents_employee_id_index')) {
            Schema::table('employee_documents', function (Blueprint $table) {
                $table->dropIndex('employee_documents_employee_id_index');
            });
        }
    }

    private function hasForeignKey(string $name): bool
    {
        return collect(Schema::getForeignKeys('employee_documents'))
            ->contains(fn (array $foreignKey): bool => $foreignKey['name'] === $name);
    }
};
