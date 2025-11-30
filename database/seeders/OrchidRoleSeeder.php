<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Orchid\Platform\Models\Role;

class OrchidRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Создаём роли из ТЗ (для Orchid)
        Role::firstOrCreate([
            'slug' => 'admin',
            'name' => 'Администратор',
            'permissions' => [
                'platform.index' => true,
                'platform.systems.roles' => true,
                'platform.systems.users' => true,
            ],
        ]);

        Role::firstOrCreate([
            'slug' => 'moderator',
            'name' => 'Модератор компании',
            'permissions' => [
                'platform.index' => true,
            ],
        ]);

        Role::firstOrCreate([
            'slug' => 'subscriber',
            'name' => 'Подписчик',
            'permissions' => [],
        ]);
    }
}