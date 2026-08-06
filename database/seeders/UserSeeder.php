<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
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

        foreach ($users as $user) {
            $account = User::firstOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => Hash::make('password123'),
                    'role' => $user['role'],
                    'status' => 'active',
                ],
            );

            $account->fill([
                'name' => $user['name'],
                'role' => $user['role'],
                'status' => 'active',
            ])->save();
        }
    }
}
