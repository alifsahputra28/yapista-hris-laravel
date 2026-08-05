<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->text('family_card_number')->nullable();
            $table->string('religion', 30)->nullable();
            $table->string('marital_status', 30)->nullable();
            $table->string('nationality', 100)->nullable();
            $table->string('blood_type', 3)->nullable();
            $table->string('whatsapp_number', 30)->nullable();
            $table->text('identity_address')->nullable();
            $table->boolean('domicile_same_as_identity')->nullable();
            $table->string('domicile_province', 100)->nullable();
            $table->string('domicile_city', 100)->nullable();
            $table->string('domicile_district', 100)->nullable();
            $table->string('domicile_village', 100)->nullable();
            $table->string('domicile_postal_code', 10)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'family_card_number',
                'religion',
                'marital_status',
                'nationality',
                'blood_type',
                'whatsapp_number',
                'identity_address',
                'domicile_same_as_identity',
                'domicile_province',
                'domicile_city',
                'domicile_district',
                'domicile_village',
                'domicile_postal_code',
            ]);
        });
    }
};
