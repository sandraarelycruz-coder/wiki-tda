<?php

namespace App\Http\Controllers\Api\Evento;

use App\Http\Controllers\Controller;
use App\Models\EventSubject;
use App\Response\ApiResponse;
use Illuminate\Http\Request;

class EventSubjectController extends Controller
{
    /**
     * Gestiona las materias asociadas a un evento.
     */
    public function index(Request $request)
    {
        $filters = $request->validate([
            'event_id' => ['sometimes', 'integer', 'exists:events,id'],
            'teacher_id' => ['sometimes', 'integer', 'exists:users,id'],
            'carrera_id' => ['sometimes', 'integer', 'exists:carreras,id'],
        ]);

        $query = EventSubject::with(['event', 'teacher', 'carrera', 'projects']);

        foreach (['event_id', 'teacher_id', 'carrera_id'] as $field) {
            if (array_key_exists($field, $filters)) {
                $query->where($field, $filters[$field]);
            }
        }

        $subjects = $query->orderBy('subject_name')->paginate(10);
        $pagination = [
            'current_page' => $subjects->currentPage(),
            'last_page' => $subjects->lastPage(),
            'per_page' => $subjects->perPage(),
            'total' => $subjects->total(),
        ];

        return ApiResponse::success('Listado de materias del evento', 200, $subjects->getCollection(), $pagination);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'event_id' => ['required', 'integer', 'exists:events,id'],
            'teacher_id' => ['required', 'integer', 'exists:users,id'],
            'carrera_id' => ['required', 'integer', 'exists:carreras,id'],
            'subject_name' => ['required', 'string', 'max:150'],
        ]);

        $subject = EventSubject::create($data);

        return ApiResponse::success('Materia creada correctamente', 201, $subject->load(['event', 'teacher', 'carrera']));
    }

    public function show(string $id)
    {
        $subject = EventSubject::with(['event', 'teacher', 'carrera', 'projects'])->findOrFail($id);

        return ApiResponse::success('Materia encontrada', 200, $subject);
    }

    public function update(Request $request, string $id)
    {
        $subject = EventSubject::findOrFail($id);

        $data = $request->validate([
            'event_id' => ['sometimes', 'required', 'integer', 'exists:events,id'],
            'teacher_id' => ['sometimes', 'required', 'integer', 'exists:users,id'],
            'carrera_id' => ['sometimes', 'required', 'integer', 'exists:carreras,id'],
            'subject_name' => ['sometimes', 'required', 'string', 'max:150'],
        ]);

        $subject->update($data);

        return ApiResponse::success('Materia actualizada correctamente', 200, $subject->fresh()->load(['event', 'teacher', 'carrera']));
    }

    public function destroy(string $id)
    {
        $subject = EventSubject::findOrFail($id);
        $subject->estado = false;
        $subject->save();

        return ApiResponse::success('Materia desactivada correctamente', 200, $subject);
    }
}
