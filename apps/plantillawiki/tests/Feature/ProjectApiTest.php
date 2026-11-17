<?php

use App\Models\Carrera;
use App\Models\Event;
use App\Models\EventSubject;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createProjectFixture(): array
{
    $carrera = Carrera::create([
        'name' => 'Ingenieria de prueba',
        'code' => 'IDP',
    ]);
    $teacher = User::factory()->create();
    $coordinator = User::factory()->create();
    $event = Event::create([
        'coordinador_id' => $coordinator->id,
        'cicle_name' => 'Ciclo de prueba',
        'start_date' => '2026-01-01',
    ]);
    $subject = EventSubject::create([
        'event_id' => $event->id,
        'teacher_id' => $teacher->id,
        'carrera_id' => $carrera->id,
        'subject_name' => 'Materia de prueba',
    ]);

    return [$subject, $teacher, $coordinator];
}

test('projects can be created, listed, updated, and deactivated', function () {
    [$subject] = createProjectFixture();

    $this->postJson('/api/proyectos', [
        'event_subject_id' => $subject->id,
        'title' => 'Proyecto de prueba',
        'description' => 'Descripción',
    ])
        ->assertCreated()
        ->assertJsonPath('data.status', 'pending');

    $project = Project::firstOrFail();

    $this->getJson('/api/proyectos?event_subject_id='.$subject->id)
        ->assertOk()
        ->assertJsonPath('data.0.id', $project->id);

    $this->patchJson('/api/proyectos/'.$project->id, [
        'title' => 'Proyecto actualizado',
    ])
        ->assertOk()
        ->assertJsonPath('data.title', 'Proyecto actualizado');

    $this->patchJson('/api/proyectos/'.$project->id.'/status', [
        'status' => 'in_progress',
    ])
        ->assertOk()
        ->assertJsonPath('data.status', 'in_progress');

    $this->deleteJson('/api/proyectos/'.$project->id)
        ->assertOk()
        ->assertJsonPath('data.estado', false);
});

test('project members can be added, assigned as leader, removed, and reactivated', function () {
    [$subject] = createProjectFixture();
    $project = Project::create([
        'event_subject_id' => $subject->id,
        'title' => 'Proyecto de prueba',
    ]);
    $student = User::factory()->create();

    $this->postJson('/api/proyectos/'.$project->id.'/integrantes', [
        'student_id' => $student->id,
    ])->assertCreated();

    $this->putJson('/api/proyectos/'.$project->id.'/lider', [
        'student_id' => $student->id,
    ])
        ->assertOk()
        ->assertJsonPath('data.is_leader', true);

    $this->postJson('/api/proyectos/'.$project->id.'/integrantes', [
        'student_id' => $student->id,
    ])
        ->assertCreated()
        ->assertJsonPath('data.is_leader', true);

    $anotherStudent = User::factory()->create();

    $this->postJson('/api/proyectos/'.$project->id.'/integrantes', [
        'student_id' => $anotherStudent->id,
    ])->assertCreated();

    $this->putJson('/api/proyectos/'.$project->id.'/lider', [
        'student_id' => $anotherStudent->id,
    ])
        ->assertOk()
        ->assertJsonPath('data.is_leader', true);

    expect(ProjectMember::where('project_id', $project->id)
        ->where('student_id', $student->id)
        ->value('is_leader'))->toBeFalse();

    $this->deleteJson('/api/proyectos/'.$project->id.'/integrantes/'.$anotherStudent->id)
        ->assertOk()
        ->assertJsonPath('data.estado', false)
        ->assertJsonPath('data.is_leader', false);

    $this->postJson('/api/proyectos/'.$project->id.'/integrantes', [
        'student_id' => $anotherStudent->id,
    ])->assertCreated();

    expect(ProjectMember::where('project_id', $project->id)
        ->where('student_id', $anotherStudent->id)
        ->count())->toBe(1);
    expect(ProjectMember::where('project_id', $project->id)
        ->where('student_id', $anotherStudent->id)
        ->value('estado'))->toBe(1);
});

test('repository URL can be updated or cleared', function () {
    [$subject] = createProjectFixture();
    $project = Project::create([
        'event_subject_id' => $subject->id,
        'title' => 'Proyecto de prueba',
    ]);

    $this->patchJson('/api/proyectos/'.$project->id.'/repositorio', [
        'repository_url' => 'https://github.com/example/project',
    ])
        ->assertOk()
        ->assertJsonPath('data.repository_url', 'https://github.com/example/project');

    $this->patchJson('/api/proyectos/'.$project->id.'/repositorio', [
        'repository_url' => null,
    ])
        ->assertOk()
        ->assertJsonPath('data.repository_url', null);
});
