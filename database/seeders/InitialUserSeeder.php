<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class InitialUserSeeder extends Seeder
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
            [
                'name' => 'Pegawai Demo',
                'email' => 'pegawai@yapista.test',
                'role' => 'pegawai',
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => Hash::make('password123'),
                    'role' => $user['role'],
                    'status' => 'active',
                ],
            );
        }
    }
}
