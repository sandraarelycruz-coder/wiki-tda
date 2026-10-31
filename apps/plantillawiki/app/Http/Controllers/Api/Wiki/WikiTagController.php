<?php

namespace App\Http\Controllers\Api\Wiki;

use App\Http\Controllers\Controller;
use App\Models\WikiTag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WikiTagController extends Controller
{
    /** Lista las etiquetas con el número de artículos publicados asociados. */
    public function index(Request $request)
    {
        $filters = $request->validate([
            'name' => ['sometimes', 'string', 'max:50'],
            'estado' => ['sometimes', 'boolean'],
        ]);

        $publishedArticles = fn ($query) => $query
            ->where('is_published', true)
            ->where('estado', true);

        $query = WikiTag::query()
            ->withCount(['articles' => $publishedArticles]);

        if (isset($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }

        if (array_key_exists('estado', $filters)) {
            $query->where('estado', $filters['estado']);
        }

        return response()->json($query->orderBy('name')->get(), 200);
    }

    /** Crea una etiqueta y genera automáticamente un slug único. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'estado' => ['sometimes', 'boolean'],
        ]);

        $data['slug'] = $this->uniqueSlug($data['name']);
        $hasEstado = array_key_exists('estado', $data);
        $estado = $data['estado'] ?? null;
        unset($data['estado']);
        $tag = WikiTag::create($data);

        // estado es un campo existente que no está en la lista fillable del modelo.
        if ($hasEstado) {
            $tag->estado = $estado;
            $tag->save();
        }

        return response()->json([
            'message' => 'Etiqueta creada correctamente',
            'data' => $tag,
        ], 201);
    }

    /** Muestra una etiqueta y sus artículos publicados. */
    public function show(string $id)
    {
        $tag = WikiTag::with([
            'articles' => fn ($query) => $query
                ->where('is_published', true)
                ->where('estado', true)
                ->with(['category', 'author']),
        ])->findOrFail($id);

        return response()->json($tag, 200);
    }

    /** Actualiza el nombre o estado de una etiqueta y regenera su slug si cambia el nombre. */
    public function update(Request $request, string $id)
    {
        $tag = WikiTag::findOrFail($id);
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:50'],
            'estado' => ['sometimes', 'boolean'],
        ]);

        if (isset($data['name'])) {
            $data['slug'] = $this->uniqueSlug($data['name'], (int) $tag->id);
        }

        $hasEstado = array_key_exists('estado', $data);
        $estado = $data['estado'] ?? null;
        unset($data['estado']);
        $tag->fill($data);

        if ($hasEstado) {
            $tag->estado = $estado;
        }

        $tag->save();

        return response()->json([
            'message' => 'Etiqueta actualizada correctamente',
            'data' => $tag->fresh(),
        ], 200);
    }

    /** Elimina la etiqueta; la clave foránea elimina las asociaciones de la tabla pivote. */
    public function destroy(string $id)
    {
        $tag = WikiTag::findOrFail($id);
        $tag->delete();

        return response()->json([
            'message' => 'Etiqueta eliminada correctamente',
        ], 200);
    }

    /** Crea un slug único, respetando el máximo de caracteres de la columna. */
    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::limit(Str::slug($name), 50, '');
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'etiqueta';
        $slug = $baseSlug;
        $suffix = 1;

        while (
            WikiTag::where('slug', $slug)
                ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $ending = '-' . $suffix++;
            $slug = Str::limit($baseSlug, 60 - strlen($ending), '') . $ending;
        }

        return $slug;
    }
}
