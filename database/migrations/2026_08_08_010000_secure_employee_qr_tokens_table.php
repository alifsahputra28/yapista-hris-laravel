<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_qr_tokens', function (Blueprint $table): void {
            $table->renameColumn('token', 'token_hash');
        });

        Schema::table('employee_qr_tokens', function (Blueprint $table): void {
            $table->text('token_encrypted')->nullable()->after('token_hash');
        });

        DB::table('employee_qr_tokens')
            ->orderBy('id')
            ->chunkById(100, function ($tokens): void {
                foreach ($tokens as $token) {
                    $rawToken = (string) $token->token_hash;

                    DB::table('employee_qr_tokens')
                        ->where('id', $token->id)
                        ->update([
                            'token_hash' => hash('sha256', $rawToken),
                            'token_encrypted' => Crypt::encryptString($rawToken),
                        ]);
                }
            });
    }

    public function down(): void
    {
        DB::table('employee_qr_tokens')
            ->orderBy('id')
            ->chunkById(100, function ($tokens): void {
                foreach ($tokens as $token) {
                    if (blank($token->token_encrypted)) {
                        continue;
                    }

                    DB::table('employee_qr_tokens')
                        ->where('id', $token->id)
                        ->update([
                            'token_hash' => Crypt::decryptString($token->token_encrypted),
                        ]);
                }
            });

        Schema::table('employee_qr_tokens', function (Blueprint $table): void {
            $table->dropColumn('token_encrypted');
        });

        Schema::table('employee_qr_tokens', function (Blueprint $table): void {
            $table->renameColumn('token_hash', 'token');
        });
    }
};
