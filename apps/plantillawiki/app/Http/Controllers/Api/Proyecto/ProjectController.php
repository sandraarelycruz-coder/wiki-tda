<?php

namespace App\Http\Controllers\Api\Proyecto;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Response\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    /**
     * se encarga de los proyectos de cada materia de el evento
     *
     * crud de proyectos
     * actualizar el estado de un proyecto
     * agregar y quitar miembros de un equipo de proyecto
     * designar el lider de el proyecto
     * guardar y actualizar la url de el repositorio
     */

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filters = $request->validate([
            'event_subject_id' => ['sometimes', 'integer', 'exists:event_subjects,id'],
            'status' => ['sometimes', 'string', 'max:20'],
            'estado' => ['sometimes', 'boolean'],
        ]);

        $query = Project::with([
            'eventSubject.event',
            'eventSubject.teacher',
            'eventSubject.carrera',
            'members' => fn ($query) => $query->where('estado', true)->with('student'),
        ]);

        foreach (['event_subject_id', 'status', 'estado'] as $field) {
            if (array_key_exists($field, $filters)) {
                $query->where($field, $filters[$field]);
            }
        }

        $projects = $query->orderBy('title')->paginate(10);
        $pagination = [
            'current_page' => $projects->currentPage(),
            'last_page' => $projects->lastPage(),
            'per_page' => $projects->perPage(),
            'total' => $projects->total(),
        ];

        return ApiResponse::success('Listado de proyectos', 200, $projects->getCollection(), $pagination);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'event_subject_id' => [
                'required',
                'integer',
                Rule::exists('event_subjects', 'id')->where('estado', true),
            ],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['sometimes', 'nullable', 'string'],
            'repository_url' => ['sometimes', 'nullable', 'url', 'max:255'],
            'status' => ['sometimes', 'string', 'max:20'],
        ]);

        $project = Project::create($data);

        return ApiResponse::success(
            'Proyecto creado correctamente',
            201,
            $project->fresh()->load([
                'eventSubject',
                'members' => fn ($query) => $query->where('estado', true)->with('student'),
            ])
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $project = Project::with([
            'eventSubject.event',
            'eventSubject.teacher',
            'eventSubject.carrera',
            'members' => fn ($query) => $query->where('estado', true)->with('student'),
            'files',
        ])->findOrFail($id);

        return ApiResponse::success('Proyecto encontrado', 200, $project);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $project = Project::findOrFail($id);

        $data = $request->validate([
            'event_subject_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('event_subjects', 'id')->where('estado', true),
            ],
            'title' => ['sometimes', 'required', 'string', 'max:200'],
            'description' => ['sometimes', 'nullable', 'string'],
            'repository_url' => ['sometimes', 'nullable', 'url', 'max:255'],
            'status' => ['sometimes', 'required', 'string', 'max:20'],
        ]);

        $project->update($data);

        return ApiResponse::success(
            'Proyecto actualizado correctamente',
            200,
            $project->fresh()->load([
                'eventSubject',
                'members' => fn ($query) => $query->where('estado', true)->with('student'),
            ])
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $project = Project::findOrFail($id);
        $project->estado = false;
        $project->save();

        return ApiResponse::success('Proyecto desactivado correctamente', 200, $project);
    }

    /**
     * Actualiza el estado de trabajo del proyecto.
     */
    public function updateStatus(Request $request, string $id)
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'max:20'],
        ]);

        $project = Project::findOrFail($id);
        $project->update($data);

        return ApiResponse::success('Estado del proyecto actualizado correctamente', 200, $project);
    }

    /**
     * Actualiza o elimina la URL del repositorio del proyecto.
     */
    public function updateRepository(Request $request, string $id)
    {
        $data = $request->validate([
            'repository_url' => ['present', 'nullable', 'url', 'max:255'],
        ]);

        $project = Project::findOrFail($id);
        $project->update($data);

        return ApiResponse::success('Repositorio actualizado correctamente', 200, $project);
    }

    /**
     * Agrega un estudiante al proyecto o reactiva una membresía previamente removida.
     */
    public function addMember(Request $request, string $id)
    {
        $data = $request->validate([
            'student_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', 'student')],
        ]);

        $project = Project::findOrFail($id);
        $member = ProjectMember::firstOrNew([
            'project_id' => $project->id,
            'student_id' => $data['student_id'],
        ]);
        if (! $member->exists || ! $member->estado) {
            $member->is_leader = false;
        }

        $member->estado = true;
        $member->save();

        return ApiResponse::success(
            'Integrante agregado correctamente',
            201,
            $member->fresh()->load('student')
        );
    }

    /**
     * Desactiva la membresía de un estudiante en el proyecto.
     */
    public function removeMember(string $id, string $studentId)
    {
        $project = Project::findOrFail($id);
        $member = $project->members()
            ->where('student_id', $studentId)
            ->where('estado', true)
            ->firstOrFail();

        $member->estado = false;
        $member->is_leader = false;
        $member->save();

        return ApiResponse::success('Integrante removido correctamente', 200, $member);
    }

    /**
     * Designa como líder a un integrante activo del proyecto.
     */
    public function assignLeader(Request $request, string $id)
    {
        $data = $request->validate([
            'student_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', 'student')],
        ]);

        $member = DB::transaction(function () use ($id, $data) {
            $project = Project::whereKey($id)->lockForUpdate()->firstOrFail();
            $member = $project->members()
                ->where('student_id', $data['student_id'])
                ->where('estado', true)
                ->firstOrFail();

            $project->members()->where('estado', true)->update(['is_leader' => false]);
            $member->is_leader = true;
            $member->save();

            return $member;
        });

        return ApiResponse::success(
            'Líder del proyecto actualizado correctamente',
            200,
            $member->fresh()->load('student')
        );
    }
}
