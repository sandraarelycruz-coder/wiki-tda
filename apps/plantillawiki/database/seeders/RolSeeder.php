<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $roles = [
            [
                'id' => 1,
                'name' => 'admin',
                'guard_name' => 'api',
            ],
            [
                'id' => 2,
                'name' => 'coordinador',
                'guard_name' => 'api',
            ],
            [
                'id' => 3,
                'name' => 'teacher',
                'guard_name' => 'api',
            ],
            [
                'id' => 4,
                'name' => 'student',
                'guard_name' => 'api',
            ],
        ];

        foreach ($roles as $rol) {
            $role = Role::firstOrCreate(
                [
                    'name' => $rol['name'],
                    'guard_name' => $rol['guard_name'],
                ],
                ['id' => $rol['id']]
            );

            if ($role->name == 'admin') {
                $role->syncPermissions([
                    'create_user',
                    'edit_user',
                    'delete_user',
                    'view_user',
                    'create_rol',
                    'edit_rol',
                    'delete_rol',
                    'view_rol',
                    'create_permission',
                    'edit_permission',
                    'delete_permission',
                    'view_permission',
                ]);
            }
            if ($role->name == 'coordinador') {
                $role->syncPermissions([
                    'create_user',
                    'edit_user',
                    'delete_user',
                    'view_user',
                ]);
            }
            if ($role->name == 'teacher') {
                $role->syncPermissions([
                    'create_user',
                    'edit_user',
                    'view_user',
                ]);
            }
            if ($role->name == 'student') {
                $role->syncPermissions([
                    'view_user',
                ]);
            }
        }

    }
}
