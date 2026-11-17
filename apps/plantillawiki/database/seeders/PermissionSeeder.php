<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $permisos = [
            [
                'id' => 1,
                'name' => 'create_user',
                'guard_name' => 'api',
            ],
            [
                'id' => 2,
                'name' => 'edit_user',
                'guard_name' => 'api',
            ],
            [
                'id' => 3,
                'name' => 'delete_user',
                'guard_name' => 'api',
            ],
            [
                'id' => 4,
                'name' => 'view_user',
                'guard_name' => 'api',
            ],

            [
                'id' => 5,
                'name' => 'create_rol',
                'guard_name' => 'api',
            ],
            [
                'id' => 6,
                'name' => 'edit_rol',
                'guard_name' => 'api',
            ],
            [
                'id' => 7,
                'name' => 'delete_rol',
                'guard_name' => 'api',
            ],
            [
                'id' => 8,
                'name' => 'view_rol',
                'guard_name' => 'api',
            ],

            [
                'id' => 9,
                'name' => 'create_permission',
                'guard_name' => 'api',
            ],
            [
                'id' => 10,
                'name' => 'edit_permission',
                'guard_name' => 'api',
            ],
            [
                'id' => 11,
                'name' => 'delete_permission',
                'guard_name' => 'api',
            ],
            [
                'id' => 12,
                'name' => 'view_permission',
                'guard_name' => 'api',
            ],
        ];

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate([
                'name' => $permiso['name'],
                'guard_name' => $permiso['guard_name'],
            ]);
        }
    }
}
