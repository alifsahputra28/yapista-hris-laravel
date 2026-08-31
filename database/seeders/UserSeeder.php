<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\Support\SyntheticSeed;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SyntheticSeed::guard();
        $users = [
            [
                'name' => 'Super Admin',
                'email' => 'admin@yapista.test',
                'role' => 'super_admin',
            ],
            [
                'name' => 'HR Admin',
                'email' => 'hr@yapista.test',
                'role' => 'hr_admin',
            ],
            [
                'name' => 'Panitia Scanner',
                'email' => 'panitia@yapista.test',
                'role' => 'panitia',
            ],
        ];

        $hasNewAccounts = false;
        foreach ($users as $user) {
            $hasNewAccounts = ! SyntheticSeed::user($user['email'], $user['role']) || $hasNewAccounts;
        }
        if ($hasNewAccounts) {
            SyntheticSeed::password();
        }

        foreach ($users as $user) {
            if (SyntheticSeed::user($user['email'], $user['role'])) {
                continue;
            }

            User::firstOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => Hash::make(SyntheticSeed::password()),
                    'role' => $user['role'],
                    'status' => 'active',
                ],
            );

        }
    }
}
