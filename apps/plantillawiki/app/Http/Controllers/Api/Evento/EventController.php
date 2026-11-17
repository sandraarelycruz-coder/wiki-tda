<?php

namespace App\Http\Controllers\Api\Evento;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Response\ApiResponse;
use Illuminate\Http\Request;

class EventController extends Controller
{
    /**
     * Gestiona los eventos y permite consultar el evento activo con sus materias.
     */
    public function index(Request $request)
    {
        $filters = $request->validate([
            'cicle_name' => ['sometimes', 'string', 'max:50'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $query = Event::with('coordinador');

        if (isset($filters['cicle_name'])) { 
            $query->where('cicle_name', 'like', '%' . $filters['cicle_name'] . '%');
        }

        if (array_key_exists('is_active', $filters)) {
            $query->where('is_active', $filters['is_active']);
        }

        $events = $query->orderByDesc('start_date')->paginate(10);
        $pagination = [
            'current_page' => $events->currentPage(),
            'last_page' => $events->lastPage(),
            'per_page' => $events->perPage(),
            'total' => $events->total(),
        ];

        return ApiResponse::success(
            'Listado de eventos',
            200,
            $events->getCollection(),
            $pagination
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'coordinador_id' => ['required', 'integer', 'exists:users,id'],
            'cicle_name' => ['required', 'string', 'max:50'],
            'start_date' => ['required', 'date'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $event = Event::create($data);

        return ApiResponse::success('Evento creado correctamente', 201, $event->load('coordinador'));
    }

    public function show(string $id)
    {
        $event = Event::with([
            'coordinador',
            'eventSubjects.teacher',
            'eventSubjects.carrera',
        ])->findOrFail($id);

        return ApiResponse::success('Evento encontrado', 200, $event);
    }

    public function update(Request $request, string $id)
    {
        $event = Event::findOrFail($id);

        $data = $request->validate([
            'coordinador_id' => ['sometimes', 'required', 'integer', 'exists:users,id'],
            'cicle_name' => ['sometimes', 'required', 'string', 'max:50'],
            'start_date' => ['sometimes', 'required', 'date'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $event->update($data);

        return ApiResponse::success(
            'Evento actualizado correctamente',
            200,
            $event->fresh()->load('coordinador')
        );
    }

    public function destroy(string $id)
    {
        $event = Event::findOrFail($id);
        $event->estado = false;
        $event->is_active = false;
        $event->save();

        return ApiResponse::success('Evento desactivado correctamente', 200, $event);
    }

    public function active()
    {
        $events = Event::with([
            'coordinador',
            'eventSubjects.teacher',
            'eventSubjects.carrera',
        ])
            ->where('estado', true)
            ->where('is_active', true)
            ->orderByDesc('start_date')
            ->get();

        return ApiResponse::success('Eventos activos con sus materias', 200, $events);
    }
}
