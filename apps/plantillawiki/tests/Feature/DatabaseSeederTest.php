<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('database seeder can run repeatedly without duplicating users, roles, or permissions', function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    expect(User::count())->toBe(4)
        ->and(Role::count())->toBe(4)
        ->and(Permission::count())->toBe(12)
        ->and(User::where('email', 'admin@gmail.com')->firstOrFail()->hasRole('admin'))->toBeTrue();
});
