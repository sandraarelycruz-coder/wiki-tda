<?php

use App\Models\Carrera;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([PermissionSeeder::class, RolSeeder::class]);

    $admin = User::factory()->create(['role' => 'admin']);
    $admin->assignRole('admin');

    $this->actingAs($admin, 'api');
});

test('user creation stores the selected career and role without returning the password', function () {
    $carrera = Carrera::create([
        'name' => 'Ingenieria de prueba',
        'code' => 'IDP',
    ]);

    $response = $this->postJson('/api/usuarios', [
        'name' => 'Usuario de prueba',
        'email' => 'usuario@example.test',
        'password' => 'Password123',
        'role' => 'teacher',
        'carrera_id' => $carrera->id,
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.email', 'usuario@example.test')
        ->assertJsonPath('data.carrera_id', $carrera->id)
        ->assertJsonMissingPath('data.password');

    $user = User::where('email', 'usuario@example.test')->firstOrFail();
    expect($user->hasRole('teacher'))->toBeTrue();
});

test('user listing can filter by role and career', function () {
    $carrera = Carrera::create([
        'name' => 'Ingenieria de prueba',
        'code' => 'IDP',
    ]);
    $teacher = User::factory()->create([
        'role' => 'teacher',
        'carrera_id' => $carrera->id,
    ]);
    $teacher->assignRole('teacher');

    $this->getJson('/api/usuarios?role=teacher&carrera_id=' . $carrera->id)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $teacher->id);
});

test('user update changes career and synchronizes the assigned role', function () {
    $newCareer = Carrera::create([
        'name' => 'Ingenieria de prueba',
        'code' => 'IDP',
    ]);
    $user = User::factory()->create(['role' => 'student']);
    $user->assignRole('student');

    $this->patchJson('/api/usuarios/' . $user->id, [
        'role' => 'teacher',
        'carrera_id' => $newCareer->id,
    ])
        ->assertOk()
        ->assertJsonPath('data.role', 'teacher')
        ->assertJsonPath('data.carrera_id', $newCareer->id);

    $user->refresh();
    expect($user->hasRole('teacher'))->toBeTrue()
        ->and($user->hasRole('student'))->toBeFalse();
});

test('user can be deleted', function () {
    $user = User::factory()->create();

    $this->deleteJson('/api/usuarios/' . $user->id)
        ->assertOk();

    $this->assertDatabaseMissing('users', ['id' => $user->id]);
});
