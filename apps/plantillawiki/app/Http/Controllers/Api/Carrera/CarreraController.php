<?php

namespace App\Http\Controllers\Api\Carrera;

use App\Http\Controllers\Controller;
use App\Models\Carrera;
use App\Response\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CarreraController extends Controller
{
    /**
     * se encarga de todas las carreras 
     * 
     * crud de carreras 
     * verifica que el id de la carrera sea unico 
     * impide eliminar una carrera si tiene materias asoaciadas
     */

    
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filters = $request->validate([
            'name' => ['sometimes', 'string', 'max:150'],
        ]);

        $query = Carrera::with('users');

        if (isset($filters['name'])) {
            $query->whereRaw('LOWER(name) LIKE LOWER(?)', ['%' . $filters['name'] . '%']);
        }

        $carrerasPaginated = $query->paginate(10);
        $paginationData = [
            'current_page' => $carrerasPaginated->currentPage(),
            'last_page' => $carrerasPaginated->lastPage(),
            'per_page' => $carrerasPaginated->perPage(),
            'total' => $carrerasPaginated->total(),
        ];

        $carrerasFormated = $carrerasPaginated->getCollection()->map(function ($carrera) {
            return [
                'id' => $carrera->id,
                'name' => $carrera->name,
                'code' => $carrera->code,
                'estado' => $carrera->estado,
                'users' => $carrera->users->map(fn ($user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ]),
            ];
        });

        return ApiResponse::success('Listado de carreras', 200, $carrerasFormated, $paginationData);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'min:3', 'max:150'],
            'code' => ['required', 'string', 'min:3', 'max:20', 'unique:carreras,code'],
        ], [
            'name.required' => 'Los nombres son requeridos',
            'code.required' => 'El código es requerido',
            'code.max' => 'El código no debe ser mayor que 20 caracteres.',
            'code.unique' => 'El código ya existe',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first(), 422);
        }

        $carrera = Carrera::create($validator->validated());

        return ApiResponse::success('Carrera creada correctamente', 201, $carrera);
    }
    

    //obtener por id
    public function showById(Request $request, $id)
    {
        $carrera = Carrera::with('users')->findOrFail($id);

        return ApiResponse::success('Carrera encontrada', 200, $carrera);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $carrera = Carrera::findOrFail($id);
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'min:3', 'max:150'],
            'code' => [
                'required',
                'string',
                'min:3',
                'max:20',
                Rule::unique('carreras', 'code')->ignore($carrera->id),
            ],
        ], [
            'name.required' => 'El nombre es requerido',
            'code.required' => 'El código es requerido',
            'code.max' => 'El código no debe ser mayor que 20 caracteres.',
            'code.unique' => 'El código ya existe',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first(), 422);
        }

        $carrera->update($validator->validated());

        return ApiResponse::success('Carrera actualizada exitosamente', 200, $carrera->fresh());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id)
    {
        $carrera = Carrera::findOrFail($id);
        $carrera->estado = ! $carrera->estado;
        $carrera->save();

        return ApiResponse::success('Estado de la carrera actualizado correctamente', 200, $carrera);
    }
}
