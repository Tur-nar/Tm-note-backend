<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\NoteLinkController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/email/verify/resend', [AuthController::class, 'resendVerificationEmail'])
        ->middleware('throttle:6,1');
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'profile']);

    Route::middleware('verified')->group(function () {
        // Trash bulk operation (must be before apiResource to avoid route conflict)
        Route::delete('notes/trash/empty', [NoteController::class, 'emptyTrash']);

        // Standard CRUD (index, store, show, update, destroy)
        Route::apiResource('notes', NoteController::class);

        // Note actions
        Route::patch('notes/{note}/archive', [NoteController::class, 'toggleArchive']);
        Route::patch('notes/{note}/pin', [NoteController::class, 'togglePin']);
        Route::patch('notes/{note}/position', [NoteController::class, 'updatePosition']);
        Route::patch('notes/{id}/restore', [NoteController::class, 'restore']);
        Route::delete('notes/{id}/force', [NoteController::class, 'forceDelete']);

        // Note links (canvas connections)
        Route::apiResource('note-links', NoteLinkController::class)->only(['index', 'store', 'destroy']);

        // Tags CRUD (no show route needed)
        Route::apiResource('tags', TagController::class)->except(['show']);
    });
});
