<?php

namespace App\Http\Controllers\Api\Proyecto;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Response\ApiResponse;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProjectFileController extends Controller
{
    /**
     * se encarga de los archivos de cada proyecto
     *
     *que suba archivos y que valide extencion y tamaño
     *guardar el archivo y registrar sus metadatos
     *listar y descargar los archivos de un proyecto
     *eliminar el archivo
     */

    /**
     * Display a listing of the resource.
     */
    public function index(string $projectId)
    {
        $this->activeProject($projectId);

        $files = ProjectFile::with('project')
            ->where('project_id', $projectId)
            ->where('estado', true)
            ->latest()
            ->paginate(10);
        $pagination = [
            'current_page' => $files->currentPage(),
            'last_page' => $files->lastPage(),
            'per_page' => $files->perPage(),
            'total' => $files->total(),
        ];

        return ApiResponse::success('Listado de archivos de proyectos', 200, $files->getCollection(), $pagination);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, string $projectId)
    {
        $project = $this->activeProject($projectId);
        $data = $request->validate([
            'file' => [
                'required',
                'file',
                'max:10240',
                'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,zip,jpg,jpeg,png',
            ],
            'file_type' => ['sometimes', 'string', 'max:20'],
        ]);

        $upload = $data['file'];
        $path = $upload->store('projects/'.$project->id.'/files', 'local');

        if ($path === false) {
            abort(500, 'No se pudo guardar el archivo del proyecto.');
        }

        $file = ProjectFile::create([
            'project_id' => $project->id,
            'file_type' => $data['file_type'] ?? $this->fileType($upload->getClientOriginalExtension()),
            'original_name' => $this->originalName($upload->getClientOriginalName()),
            'file_path' => $path,
            'file_size_kb' => (int) ceil($upload->getSize() / 1024),
            'extension' => strtolower($upload->getClientOriginalExtension()),
        ]);

        return ApiResponse::success('Archivo subido correctamente', 201, $file->load('project'));
    }

    /**
     * Display the specified resource.
     */
    public function show(string $projectId, string $id)
    {
        $file = $this->activeFile($projectId, $id);
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('local');

        abort_unless($disk->exists($file->file_path), 404, 'El archivo no está disponible.');

        return $disk->download($file->file_path, $file->original_name);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $projectId, string $id)
    {
        $file = $this->activeFile($projectId, $id);
        $data = $request->validate([
            'file' => [
                'sometimes',
                'required',
                'file',
                'max:10240',
                'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,zip,jpg,jpeg,png',
            ],
            'file_type' => ['sometimes', 'required', 'string', 'max:20'],
        ]);

        $oldPath = $file->file_path;

        if (isset($data['file'])) {
            $upload = $data['file'];
            $path = $upload->store('projects/'.$file->project_id.'/files', 'local');

            if ($path === false) {
                abort(500, 'No se pudo guardar el archivo del proyecto.');
            }

            $file->file_path = $path;
            $file->original_name = $this->originalName($upload->getClientOriginalName());
            $file->file_size_kb = (int) ceil($upload->getSize() / 1024);
            $file->extension = strtolower($upload->getClientOriginalExtension());
            $file->file_type = $data['file_type'] ?? $this->fileType($upload->getClientOriginalExtension());
        } elseif (isset($data['file_type'])) {
            $file->file_type = $data['file_type'];
        }

        $file->save();

        if (isset($data['file']) && $oldPath !== $file->file_path) {
            $disk = Storage::disk('local');
            if ($disk->exists($oldPath) && ! $disk->delete($oldPath)) {
                abort(500, 'No se pudo eliminar la versión anterior del archivo.');
            }
        }

        return ApiResponse::success('Archivo actualizado correctamente', 200, $file->fresh()->load('project'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $projectId, string $id)
    {
        $file = $this->activeFile($projectId, $id);
        $disk = Storage::disk('local');
        if ($disk->exists($file->file_path) && ! $disk->delete($file->file_path)) {
            abort(500, 'No se pudo eliminar el archivo del almacenamiento.');
        }

        $file->estado = false;
        $file->save();

        return ApiResponse::success('Archivo eliminado correctamente', 200, $file);
    }

    private function activeProject(string $projectId): Project
    {
        return Project::where('estado', true)->findOrFail($projectId);
    }

    private function activeFile(string $projectId, string $id): ProjectFile
    {
        return ProjectFile::where('estado', true)
            ->where('project_id', $projectId)
            ->whereHas('project', fn ($project) => $project->where('estado', true))
            ->findOrFail($id);
    }

    private function fileType(string $extension): string
    {
        return match (strtolower($extension)) {
            'jpg', 'jpeg', 'png' => 'image',
            'zip' => 'archive',
            default => 'document',
        };
    }

    private function originalName(string $name): string
    {
        return basename(str_replace('\\', '/', $name));
    }
}
