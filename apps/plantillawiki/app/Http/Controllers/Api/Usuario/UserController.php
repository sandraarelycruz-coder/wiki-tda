<?php

namespace App\Http\Controllers\Api\Usuario;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Response\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Gestiona usuarios, sus carreras y sus roles.
     */
    public function index(Request $request)
    {
        $filters = $request->validate([
            'name' => ['sometimes', 'string', 'max:150'],
            'role' => ['sometimes', 'string', 'max:20'],
            'carrera_id' => ['sometimes', 'integer', 'exists:carreras,id'],
        ]);

        $query = User::with(['carrera', 'roles']);

        if (isset($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }

        if (isset($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (isset($filters['carrera_id'])) {
            $query->where('carrera_id', $filters['carrera_id']);
        }

        $users = $query->orderBy('id')->paginate(10);
        $pagination = [
            'current_page' => $users->currentPage(),
            'last_page' => $users->lastPage(),
            'per_page' => $users->perPage(),
            'total' => $users->total(),
        ];

        return ApiResponse::success(
            'Listado de usuarios',
            200,
            $users->getCollection(),
            $pagination
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => [
                'required',
                'string',
                'max:20',
                Rule::exists('roles', 'name')->where('guard_name', 'api'),
            ],
            'carrera_id' => ['nullable', 'integer', 'exists:carreras,id'],
            'carnet_or_code' => ['nullable', 'string', 'max:50'],
        ]);

        $user = User::create($data);
        $user->syncRoles($data['role']);

        return ApiResponse::success(
            'Usuario creado correctamente',
            201,
            $user->fresh()->load(['carrera', 'roles'])
        );
    }

    public function show(string $id)
    {
        $user = User::with(['carrera', 'roles'])->findOrFail($id);

        return ApiResponse::success('Usuario encontrado', 200, $user);
    }

    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:150',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'password' => ['sometimes', 'required', 'string', 'min:8'],
            'role' => [
                'sometimes',
                'required',
                'string',
                'max:20',
                Rule::exists('roles', 'name')->where('guard_name', 'api'),
            ],
            'carrera_id' => ['sometimes', 'nullable', 'integer', 'exists:carreras,id'],
            'carnet_or_code' => ['sometimes', 'nullable', 'string', 'max:50'],
        ]);

        $user->update($data);

        if (array_key_exists('role', $data)) {
            $user->syncRoles($data['role']);
        }

        return ApiResponse::success(
            'Usuario actualizado correctamente',
            200,
            $user->fresh()->load(['carrera', 'roles'])
        );
    }

    public function destroy(string $id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return ApiResponse::success('Usuario eliminado correctamente', 200, [
            'id' => (int) $id,
        ]);
    }
}
