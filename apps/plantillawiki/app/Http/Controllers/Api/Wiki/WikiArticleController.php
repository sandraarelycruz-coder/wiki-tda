<?php

namespace App\Http\Controllers\Api\Wiki;

use App\Http\Controllers\Controller;
use App\Models\WikiArticle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class WikiArticleController extends Controller
{
    /**
     * Lista artículos visibles para el usuario y permite filtrar por categoría o publicación.
     */
    public function index(Request $request)
    {
        $filters = $request->validate([
            'category_id' => ['sometimes', 'integer', 'exists:wiki_categories,id'],
            'is_published' => ['sometimes', 'boolean'],
            'estado' => ['sometimes', 'boolean'],
        ]);

        $query = WikiArticle::with(['category', 'author', 'tags']);
        $userId = $request->user()?->id;

        // Los visitantes solo pueden consultar artículos publicados y activos.
        $query->where(function ($visibility) use ($userId) {
            $visibility->where(function ($published) {
                $published->where('is_published', true)->where('estado', true);
            });

            if ($userId !== null) {
                $visibility->orWhere('author_id', $userId);
            }
        });

        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (array_key_exists('is_published', $filters)) {
            $query->where('is_published', $filters['is_published']);
        }

        if (array_key_exists('estado', $filters)) {
            $query->where('estado', $filters['estado']);
        }

        return response()->json($query->latest()->get(), 200);
    }

    /**
     * Muestra un artículo si está publicado o pertenece al usuario autenticado.
     */
    public function show(Request $request, string $id)
    {
        $query = WikiArticle::with(['category', 'author', 'tags']);
        $userId = $request->user()?->id;

        $query->where(function ($visibility) use ($userId) {
            $visibility->where(function ($published) {
                $published->where('is_published', true)->where('estado', true);
            });

            if ($userId !== null) {
                $visibility->orWhere('author_id', $userId);
            }
        });

        $article = $query->findOrFail($id);

        return response()->json($article, 200);
    }

    /**
     * Crea un artículo asignando como autor al usuario autenticado y sincronizando sus etiquetas.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => [
                'required',
                'integer',
                Rule::exists('wiki_categories', 'id')->where('estado', true),
            ],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'is_published' => ['sometimes', 'boolean'],
            'estado' => ['sometimes', 'boolean'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => [
                'integer',
                'distinct',
                Rule::exists('wiki_tags', 'id')->where('estado', true),
            ],
        ]);

        $tagIds = $data['tags'] ?? [];
        $hasEstado = array_key_exists('estado', $data);
        $estado = $data['estado'] ?? null;
        unset($data['tags'], $data['estado']);

        $article = DB::transaction(function () use ($data, $tagIds, $request, $hasEstado, $estado) {
            $data['author_id'] = $request->user()->id;
            $data['slug'] = $this->uniqueSlug($data['title']);
            $article = WikiArticle::create($data);

            if ($hasEstado) {
                $article->estado = $estado;
                $article->save();
            }

            if ($tagIds !== []) {
                $article->tags()->sync($tagIds);
            }

            return $article;
        });

        return response()->json([
            'message' => 'Artículo creado correctamente',
            'data' => $article->load(['category', 'author', 'tags']),
        ], 201);
    }

    /**
     * Actualiza los campos enviados y, si corresponde, reemplaza las etiquetas del artículo.
     */
    public function update(Request $request, string $id)
    {
        $article = WikiArticle::findOrFail($id);
        $this->authorizeArticleChange($request, $article);

        $data = $request->validate([
            'category_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('wiki_categories', 'id')->where('estado', true),
            ],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'content' => ['sometimes', 'required', 'string'],
            'is_published' => ['sometimes', 'boolean'],
            'estado' => ['sometimes', 'boolean'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => [
                'integer',
                'distinct',
                Rule::exists('wiki_tags', 'id')->where('estado', true),
            ],
        ]);

        $tagIds = $data['tags'] ?? null;
        $hasEstado = array_key_exists('estado', $data);
        $estado = $data['estado'] ?? null;
        unset($data['tags'], $data['estado']);

        if (isset($data['title'])) {
            $data['slug'] = $this->uniqueSlug($data['title'], $article->id);
        }

        DB::transaction(function () use ($article, $data, $tagIds, $hasEstado, $estado) {
            $article->fill($data);

            // estado existe en la tabla, aunque no forma parte de la asignación masiva del modelo.
            if ($hasEstado) {
                $article->estado = $estado;
            }

            $article->save();

            if ($tagIds !== null) {
                $article->tags()->sync($tagIds);
            }
        });

        return response()->json([
            'message' => 'Artículo actualizado correctamente',
            'data' => $article->fresh()->load(['category', 'author', 'tags']),
        ], 200);
    }

    /**
     * Elimina el artículo; la clave foránea elimina también sus asociaciones con etiquetas.
     */
    public function destroy(Request $request, string $id)
    {
        $article = WikiArticle::findOrFail($id);
        $this->authorizeArticleChange($request, $article);
        $article->delete();

        return response()->json([
            'message' => 'Artículo eliminado correctamente',
        ], 200);
    }

    /**
     * Permite modificar o eliminar artículos a su autor o a un administrador existente.
     */
    private function authorizeArticleChange(Request $request, WikiArticle $article): void
    {
        abort_unless(
            $request->user()->id === $article->author_id
                || $request->user()->hasRole('admin'),
            403,
            'No tienes permiso para modificar este artículo.'
        );
    }

    /**
     * Genera un slug dentro del límite de la columna y evita duplicados, incluso al editar.
     */
    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $baseSlug = Str::limit(Str::slug($title), 250, '');
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'articulo';
        $slug = $baseSlug;
        $suffix = 1;

        while (
            WikiArticle::where('slug', $slug)
                ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $ending = '-' . $suffix++;
            $slug = Str::limit($baseSlug, 280 - strlen($ending), '') . $ending;
        }

        return $slug;
    }
}
