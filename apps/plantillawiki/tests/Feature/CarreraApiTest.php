<?php

use App\Models\Carrera;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('carrera update returns a validation response for an empty name', function () {
    $carrera = Carrera::create([
        'name' => 'Ingenieria de prueba',
        'code' => 'IDP',
    ]);

    $response = $this->putJson('/api/carreras/' . $carrera->id, [
        'name' => '',
        'code' => null,
    ]);

    $response
        ->assertUnprocessable()
        ->assertJsonPath('message', 'El nombre es requerido');
});

test('carrera listing includes each related user name and email', function () {
    $carrera = Carrera::create([
        'name' => 'Ingenieria de prueba',
        'code' => 'IDP',
    ]);
    $user = User::factory()->create([
        'carrera_id' => $carrera->id,
        'name' => 'Usuario de prueba',
        'email' => 'usuario@example.test',
    ]);

    $this->getJson('/api/carreras?name=INGENIERIA')
        ->assertOk()
        ->assertJsonPath('data.0.users.0.id', $user->id)
        ->assertJsonPath('data.0.users.0.name', $user->name)
        ->assertJsonPath('data.0.users.0.email', $user->email);
});

test('carrera creation requires a code that fits the database column', function () {
    $this->postJson('/api/carreras/crear', [
        'name' => 'Ingenieria de prueba',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('message', 'El código es requerido');

    $this->postJson('/api/carreras/crear', [
        'name' => 'Ingenieria de prueba',
        'code' => str_repeat('A', 21),
    ])
        ->assertUnprocessable()
        ->assertJsonPath('message', 'El código no debe ser mayor que 20 caracteres.');
});

test('carrera detail returns not found for an unknown id', function () {
    $this->getJson('/api/carreras/999')
        ->assertNotFound();
});
