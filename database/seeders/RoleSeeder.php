<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['admin' => 'Administrator', 'manager' => 'Manager', 'cashier' => 'Cashier'] as $code => $name) {
            Role::updateOrCreate(['code' => $code], ['name' => $name]);
        }
    }
}
