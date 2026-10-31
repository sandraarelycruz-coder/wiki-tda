<?php

namespace App\Http\Controllers\Api\Wiki;

use App\Http\Controllers\Controller;
use App\Models\WikiCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class WikiCategoryController extends Controller
{
    /**
     * Lista las categorías y permite filtrar por nombre o estado.
     */
    public function index(Request $request)
    {
        $filters = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'estado' => ['sometimes', 'boolean'],
        ]);

        $query = WikiCategory::query()->withCount([
            'articles' => fn ($articles) => $articles
                ->where('is_published', true)
                ->where('estado', true),
        ]);

        if (isset($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }

        if (array_key_exists('estado', $filters)) {
            $query->where('estado', $filters['estado']);
        }

        return response()->json($query->orderBy('name')->get(), 200);
    }

    /** Crea una categoría y genera automáticamente un slug único. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'estado' => ['sometimes', 'boolean'],
        ]);

        $data['slug'] = $this->uniqueSlug($data['name']);
        $hasEstado = array_key_exists('estado', $data);
        $estado = $data['estado'] ?? null;
        unset($data['estado']);
        $category = WikiCategory::create($data);

        // estado es un campo existente que no está en la lista fillable del modelo.
        if ($hasEstado) {
            $category->estado = $estado;
            $category->save();
        }

        return response()->json([
            'message' => 'Categoría creada correctamente',
            'data' => $category,
        ], 201);
    }

    /** Muestra una categoría con sus artículos publicados y las relaciones de cada artículo. */
    public function show(string $id)
    {
        $category = WikiCategory::with([
            'articles' => fn ($query) => $query
                ->where('is_published', true)
                ->where('estado', true)
                ->with(['author', 'tags']),
        ])->findOrFail($id);

        return response()->json($category, 200);
    }

    /** Actualiza los campos enviados y regenera el slug si cambia el nombre. */
    public function update(Request $request, string $id)
    {
        $category = WikiCategory::findOrFail($id);
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'description' => ['sometimes', 'nullable', 'string'],
            'estado' => ['sometimes', 'boolean'],
        ]);

        if (isset($data['name'])) {
            $data['slug'] = $this->uniqueSlug($data['name'], (int) $category->id);
        }

        $hasEstado = array_key_exists('estado', $data);
        $estado = $data['estado'] ?? null;
        unset($data['estado']);
        $category->fill($data);

        if ($hasEstado) {
            $category->estado = $estado;
        }

        $category->save();

        return response()->json([
            'message' => 'Categoría actualizada correctamente',
            'data' => $category->fresh()->loadCount('articles'),
        ], 200);
    }

    /** Elimina una categoría solo cuando no tiene artículos relacionados. */
    public function destroy(string $id)
    {
        $category = WikiCategory::findOrFail($id);

        if ($category->articles()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar una categoría que tiene artículos asociados.',
            ], 409);
        }

        $category->delete();

        return response()->json([
            'message' => 'Categoría eliminada correctamente',
        ], 200);
    }

    /** Crea un slug único, respetando el máximo de caracteres de la columna. */
    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::limit(Str::slug($name), 100, '');
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'categoria';
        $slug = $baseSlug;
        $suffix = 1;

        while (
            WikiCategory::where('slug', $slug)
                ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $ending = '-' . $suffix++;
            $slug = Str::limit($baseSlug, 120 - strlen($ending), '') . $ending;
        }

        return $slug;
    }
}
