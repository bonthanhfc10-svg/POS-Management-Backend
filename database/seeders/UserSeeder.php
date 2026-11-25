<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Admin User',
                'email' => 'admin@pos.test',
                'password' => 'password',
                'role' => User::ROLE_ADMIN,
            ],
            [
                'name' => 'Manager User',
                'email' => 'manager@pos.test',
                'password' => 'password',
                'role' => User::ROLE_MANAGER,
            ],
            [
                'name' => 'Cashier User',
                'email' => 'cashier@pos.test',
                'password' => 'password',
                'role' => User::ROLE_CASHIER,
            ],
        ];

        foreach ($users as $user) {
            User::firstOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => Hash::make($user['password']),
                    'role_id' => Role::where('code', $user['role'])->value('id'),
                ]
            );
        }
    }
}
