<?php

declare(strict_types=1);

use App\Controllers\Api;
use App\Controllers\AuthController;
use App\Controllers\FileController;
use App\Controllers\HomeController;
use App\Controllers\NoteController;
use App\Controllers\NotebookController;
use App\Controllers\SearchController;
use App\Controllers\SettingsController;
use App\Controllers\TagController;
use App\Controllers\TrashController;

/** @var App\Core\Router $router */

// ── Auth ────────────────────────────────────────────────────────────
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register']);
$router->post('/logout', [AuthController::class, 'logout']);

// ── Web app ─────────────────────────────────────────────────────────
$router->get('/', [HomeController::class, 'index']);
$router->get('/notes', [NoteController::class, 'index']);
$router->post('/notes', [NoteController::class, 'store']);
$router->get('/notes/{id}', [NoteController::class, 'show']);
$router->patch('/notes/{id}', [NoteController::class, 'update']);
$router->delete('/notes/{id}', [NoteController::class, 'destroy']);
$router->post('/notes/{id}/restore', [NoteController::class, 'restore']);
$router->delete('/notes/{id}/forever', [NoteController::class, 'destroyForever']);
$router->post('/notes/{id}/tags', [NoteController::class, 'addTag']);
$router->delete('/notes/{id}/tags/{tagId}', [NoteController::class, 'removeTag']);
$router->post('/notes/{id}/attachments', [NoteController::class, 'uploadAttachment']);
$router->delete('/attachments/{id}', [NoteController::class, 'deleteAttachment']);

$router->get('/notebooks', [NotebookController::class, 'index']);
$router->post('/notebooks', [NotebookController::class, 'store']);
$router->get('/notebooks/{id}', [NotebookController::class, 'show']);
$router->patch('/notebooks/{id}', [NotebookController::class, 'update']);
$router->delete('/notebooks/{id}', [NotebookController::class, 'destroy']);
$router->post('/notebooks/{id}/unlock', [NotebookController::class, 'unlock']);

$router->get('/tags', [TagController::class, 'index']);
$router->get('/tags/{name}', [TagController::class, 'show']);

$router->get('/search', [SearchController::class, 'index']);
$router->get('/trash', [TrashController::class, 'index']);
$router->get('/settings', [SettingsController::class, 'index']);

$router->get('/files/{id}', [FileController::class, 'show']);

// ── JSON API (Bearer token) ─────────────────────────────────────────
$router->post('/api/auth/register', [Api\ApiAuthController::class, 'register']);
$router->post('/api/auth/login', [Api\ApiAuthController::class, 'login']);
$router->post('/api/auth/logout', [Api\ApiAuthController::class, 'logout']);
$router->get('/api/me', [Api\ApiAuthController::class, 'me']);

$router->get('/api/notebooks', [Api\ApiNotebookController::class, 'index']);
$router->post('/api/notebooks', [Api\ApiNotebookController::class, 'store']);
$router->patch('/api/notebooks/{id}', [Api\ApiNotebookController::class, 'update']);
$router->delete('/api/notebooks/{id}', [Api\ApiNotebookController::class, 'destroy']);
$router->get('/api/notebooks/{id}/notes', [Api\ApiNotebookController::class, 'notes']);
$router->post('/api/notebooks/{id}/unlock', [Api\ApiNotebookController::class, 'unlock']);

$router->get('/api/notes', [Api\ApiNoteController::class, 'index']);
$router->post('/api/notes', [Api\ApiNoteController::class, 'store']);
$router->get('/api/notes/{id}', [Api\ApiNoteController::class, 'show']);
$router->patch('/api/notes/{id}', [Api\ApiNoteController::class, 'update']);
$router->delete('/api/notes/{id}', [Api\ApiNoteController::class, 'destroy']);
$router->post('/api/notes/{id}/restore', [Api\ApiNoteController::class, 'restore']);
$router->post('/api/notes/{id}/tags', [Api\ApiNoteController::class, 'addTag']);
$router->delete('/api/notes/{id}/tags/{tagId}', [Api\ApiNoteController::class, 'removeTag']);
$router->post('/api/notes/{id}/attachments', [Api\ApiNoteController::class, 'uploadAttachment']);

$router->get('/api/tags', [Api\ApiTagController::class, 'index']);
$router->get('/api/tags/{name}/notes', [Api\ApiTagController::class, 'notes']);

$router->get('/api/search', [Api\ApiSearchController::class, 'index']);
$router->get('/api/trash', [Api\ApiNoteController::class, 'trash']);
