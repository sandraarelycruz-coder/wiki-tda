<?php

use App\Models\Carrera;
use App\Models\Event;
use App\Models\EventSubject;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function createProjectForFileTests(): Project
{
    $carrera = Carrera::create([
        'name' => 'Ingenieria de archivos',
        'code' => 'IDA-'.(Carrera::count() + 1),
    ]);
    $teacher = User::factory()->create();
    $coordinator = User::factory()->create();
    $event = Event::create([
        'coordinador_id' => $coordinator->id,
        'cicle_name' => 'Ciclo de archivos',
        'start_date' => '2026-01-01',
    ]);
    $subject = EventSubject::create([
        'event_id' => $event->id,
        'teacher_id' => $teacher->id,
        'carrera_id' => $carrera->id,
        'subject_name' => 'Materia de archivos',
    ]);

    return Project::create([
        'event_subject_id' => $subject->id,
        'title' => 'Proyecto con archivos',
    ]);
}

test('project files can be uploaded, listed, downloaded, replaced, and deleted', function () {
    Storage::fake('local');
    $project = createProjectForFileTests();

    $this->post('/api/proyectos/'.$project->id.'/archivos', [
        'file' => UploadedFile::fake()->createWithContent('informe.txt', 'contenido original'),
    ])
        ->assertCreated()
        ->assertJsonPath('data.original_name', 'informe.txt')
        ->assertJsonPath('data.file_type', 'document')
        ->assertJsonPath('data.extension', 'txt');

    $file = ProjectFile::firstOrFail();
    Storage::disk('local')->assertExists($file->file_path);

    $this->getJson('/api/proyectos/'.$project->id.'/archivos')
        ->assertOk()
        ->assertJsonPath('data.0.id', $file->id);

    $this->get('/api/proyectos/'.$project->id.'/archivos/'.$file->id)
        ->assertOk()
        ->assertHeader('content-disposition', 'attachment; filename=informe.txt');

    $oldPath = $file->file_path;
    $this->put('/api/proyectos/'.$project->id.'/archivos/'.$file->id, [
        'file' => UploadedFile::fake()->createWithContent('actualizado.txt', 'contenido actualizado'),
    ])
        ->assertOk()
        ->assertJsonPath('data.original_name', 'actualizado.txt');

    Storage::disk('local')->assertMissing($oldPath);
    Storage::disk('local')->assertExists($file->fresh()->file_path);

    $this->deleteJson('/api/proyectos/'.$project->id.'/archivos/'.$file->id)
        ->assertOk()
        ->assertJsonPath('data.estado', false);

    Storage::disk('local')->assertMissing($file->file_path);
    $this->getJson('/api/proyectos/'.$project->id.'/archivos')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

test('project files reject unsupported extensions and cannot be accessed through another project', function () {
    Storage::fake('local');
    $project = createProjectForFileTests();
    $otherProject = createProjectForFileTests();

    $this->post('/api/proyectos/'.$project->id.'/archivos', [
        'file' => UploadedFile::fake()->createWithContent('script.php', '<?php echo "no";'),
    ])->assertUnprocessable();

    $this->post('/api/proyectos/'.$project->id.'/archivos', [
        'file' => UploadedFile::fake()->createWithContent('informe.txt', 'contenido'),
    ])->assertCreated();

    $file = ProjectFile::firstOrFail();

    $this->get('/api/proyectos/'.$otherProject->id.'/archivos/'.$file->id)
        ->assertNotFound();

    $this->deleteJson('/api/proyectos/'.$otherProject->id.'/archivos/'.$file->id)
        ->assertNotFound();

    expect($file->fresh()->estado)->toBe(1);
});

test('project files can be replaced or deactivated when their stored file is missing', function () {
    Storage::fake('local');
    $project = createProjectForFileTests();

    $this->post('/api/proyectos/'.$project->id.'/archivos', [
        'file' => UploadedFile::fake()->createWithContent('informe.txt', 'contenido'),
    ])->assertCreated();

    $file = ProjectFile::firstOrFail();
    Storage::disk('local')->delete($file->file_path);

    $this->put('/api/proyectos/'.$project->id.'/archivos/'.$file->id, [
        'file' => UploadedFile::fake()->createWithContent('actualizado.txt', 'nuevo contenido'),
    ])
        ->assertOk()
        ->assertJsonPath('data.original_name', 'actualizado.txt');

    $updatedFile = $file->fresh();
    Storage::disk('local')->delete($updatedFile->file_path);

    $this->deleteJson('/api/proyectos/'.$project->id.'/archivos/'.$updatedFile->id)
        ->assertOk()
        ->assertJsonPath('data.estado', false);
});
