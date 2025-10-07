<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserRoleSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'RH Manager',
                'email' => 'rh@example.com',
                'role' => User::ROLE_RH_MANAGER,
            ],
            [
                'name' => 'Colaborador',
                'email' => 'colab@example.com',
                'role' => User::ROLE_COLABORADOR,
            ],
            [
                'name' => 'Admin',
                'email' => 'admin@example.com',
                'role' => User::ROLE_ADMIN,
            ],
        ];

        foreach ($users as $data) {
            User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('secret123'),
                    'role' => $data['role'],
                ],
            );
        }
    }
}
