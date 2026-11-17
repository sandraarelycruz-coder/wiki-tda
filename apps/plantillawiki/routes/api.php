<?php

use App\Http\Controllers\Api\Carrera\CarreraController;
use App\Http\Controllers\Api\Evento\EventController;
use App\Http\Controllers\Api\Evento\EventSubjectController;
use App\Http\Controllers\Api\Proyecto\ProjectController;
use App\Http\Controllers\Api\Proyecto\ProjectFileController;
use App\Http\Controllers\Api\Usuario\UserController;
use App\Http\Controllers\Api\Wiki\WikiArticleController;
use App\Http\Controllers\Api\Wiki\WikiCategoryController;
use App\Http\Controllers\Api\Wiki\WikiTagController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RolesController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/login', [LoginController::class, 'login']);
    Route::middleware('auth:api')->group(function () {
        Route::post('/logout', [LoginController::class, 'logout']);
        Route::post('/refresh', [LoginController::class, 'refresh']);
        Route::post('/me', [LoginController::class, 'me']);
        Route::prefix('roles')->group(function () {
            Route::get('/', [RolesController::class, 'index']);
            Route::post('/', [RolesController::class, 'store']);
            Route::put('/{id}', [RolesController::class, 'update']);
            Route::delete('/{id}', [RolesController::class, 'destroy']);
        });
        Route::prefix('permissions')->group(function () {
            Route::get('/', [RolesController::class, 'getPermissions']);
        });
    });
});

Route::middleware('auth:api')->prefix('usuarios')->group(function () {
    Route::get('/', [UserController::class, 'index'])->middleware('permission:view_user');
    Route::post('/', [UserController::class, 'store'])->middleware('permission:create_user');
    Route::get('/{id}', [UserController::class, 'show'])->middleware('permission:view_user');
    Route::match(['put', 'patch'], '/{id}', [UserController::class, 'update'])->middleware('permission:edit_user');
    Route::delete('/{id}', [UserController::class, 'destroy'])->middleware('permission:delete_user');
});

Route::prefix('carreras')->group(function () {
    Route::get('/', [CarreraController::class, 'index']);
    Route::post('/crear', [CarreraController::class, 'store']);
    Route::get('/{id}', [CarreraController::class, 'showById']);
    Route::put('/{id}', [CarreraController::class, 'update']);
    Route::delete('/{id}', [CarreraController::class, 'destroy']);
});

Route::prefix('eventos')->group(function () {
    Route::get('/', [EventController::class, 'index']);
    Route::get('/activo', [EventController::class, 'active']);
    Route::post('/', [EventController::class, 'store']);
    Route::get('/{id}', [EventController::class, 'show']);
    Route::match(['put', 'patch'], '/{id}', [EventController::class, 'update']);
    Route::delete('/{id}', [EventController::class, 'destroy']);

    Route::get('/{event_id}/materias', [EventSubjectController::class, 'index']);
    Route::post('/{event_id}/materias', [EventSubjectController::class, 'store']);
    Route::get('/{event_id}/materias/{id}', [EventSubjectController::class, 'show']);
    Route::match(['put', 'patch'], '/{event_id}/materias/{id}', [EventSubjectController::class, 'update']);
    Route::delete('/{event_id}/materias/{id}', [EventSubjectController::class, 'destroy']);
});

Route::prefix('proyectos')->group(function () {
    Route::get('/', [ProjectController::class, 'index']);
    Route::post('/', [ProjectController::class, 'store']);
    Route::get('/{id}', [ProjectController::class, 'show']);
    Route::match(['put', 'patch'], '/{id}', [ProjectController::class, 'update']);
    Route::delete('/{id}', [ProjectController::class, 'destroy']);
    Route::patch('/{id}/status', [ProjectController::class, 'updateStatus']);
    Route::patch('/{id}/repositorio', [ProjectController::class, 'updateRepository']);
    Route::post('/{id}/integrantes', [ProjectController::class, 'addMember']);
    Route::delete('/{id}/integrantes/{studentId}', [ProjectController::class, 'removeMember']);
    Route::put('/{id}/lider', [ProjectController::class, 'assignLeader']);

    Route::get('/{projectId}/archivos', [ProjectFileController::class, 'index']);
    Route::post('/{projectId}/archivos', [ProjectFileController::class, 'store']);
    Route::get('/{projectId}/archivos/{id}', [ProjectFileController::class, 'show']);
    Route::match(['put', 'patch'], '/{projectId}/archivos/{id}', [ProjectFileController::class, 'update']);
    Route::delete('/{projectId}/archivos/{id}', [ProjectFileController::class, 'destroy']);
});

Route::prefix('wiki')->group(function () {
    Route::get('/articles', [WikiArticleController::class, 'index'])->name('wiki.articles.index');
    Route::get('/articles/{id}', [WikiArticleController::class, 'show'])->name('wiki.articles.show');
    Route::get('/categories', [WikiCategoryController::class, 'index'])->name('wiki.categories.index');
    Route::get('/categories/{id}', [WikiCategoryController::class, 'show'])->name('wiki.categories.show');
    Route::get('/tags', [WikiTagController::class, 'index'])->name('wiki.tags.index');
    Route::get('/tags/{id}', [WikiTagController::class, 'show'])->name('wiki.tags.show');

    // La escritura del contenido Wiki requiere un token válido del guard api existente.
    Route::middleware('auth:api')->group(function () {
        Route::post('/articles', [WikiArticleController::class, 'store'])->name('wiki.articles.store');
        Route::match(['put', 'patch'], '/articles/{id}', [WikiArticleController::class, 'update'])->name('wiki.articles.update');
        Route::delete('/articles/{id}', [WikiArticleController::class, 'destroy'])->name('wiki.articles.destroy');

        Route::post('/categories', [WikiCategoryController::class, 'store'])->name('wiki.categories.store');
        Route::match(['put', 'patch'], '/categories/{id}', [WikiCategoryController::class, 'update'])->name('wiki.categories.update');
        Route::delete('/categories/{id}', [WikiCategoryController::class, 'destroy'])->name('wiki.categories.destroy');

        Route::post('/tags', [WikiTagController::class, 'store'])->name('wiki.tags.store');
        Route::match(['put', 'patch'], '/tags/{id}', [WikiTagController::class, 'update'])->name('wiki.tags.update');
        Route::delete('/tags/{id}', [WikiTagController::class, 'destroy'])->name('wiki.tags.destroy');
    });
});
