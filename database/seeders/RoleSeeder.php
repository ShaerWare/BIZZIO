<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Создаём роли из ТЗ
        Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Moderator', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Subscriber', 'guard_name' => 'web']);
    }
}