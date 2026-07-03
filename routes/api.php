<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CanvasShareController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\NoteLinkController;
use App\Http\Controllers\NoteShareController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\TagController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

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

    // Broadcasting auth for presence channels (Sanctum cookie auth)
    Broadcast::routes(['middleware' => ['auth:sanctum']]);

    Route::middleware('verified')->group(function () {
        // Trash bulk operation (must be before apiResource to avoid route conflict)
        Route::delete('notes/trash/empty', [NoteController::class, 'emptyTrash']);

        // Standard CRUD (index, store, show, update, destroy)
        Route::apiResource('notes', NoteController::class)->names('api.notes');

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

        // ── Note Sharing ──
        Route::prefix('note-shares')->group(function () {
            Route::get('/', [NoteShareController::class, 'index']);
            Route::get('/received', [NoteShareController::class, 'sharedWithMe']);
            Route::post('/', [NoteShareController::class, 'store']);
            Route::patch('/{noteShare}', [NoteShareController::class, 'update']);
            Route::delete('/{noteShare}', [NoteShareController::class, 'destroy']);
        });

        // Accept/decline share invitations
        Route::post('/note-shares/accept/{token}', [NoteShareController::class, 'accept']);
        Route::post('/note-shares/decline/{token}', [NoteShareController::class, 'decline']);

        // Shared note access (permission-checked, uses {noteId} to avoid NoteController binding conflict)
        Route::get('/shared-notes/{noteId}', [NoteShareController::class, 'showSharedNote']);
        Route::put('/shared-notes/{noteId}', [NoteShareController::class, 'updateSharedNote']);

        // ── Canvas Sharing ──
        Route::prefix('canvas-shares')->group(function () {
            Route::get('/', [CanvasShareController::class, 'index']);
            Route::get('/received', [CanvasShareController::class, 'received']);
            Route::post('/', [CanvasShareController::class, 'store']);
            Route::delete('/{canvasShare}', [CanvasShareController::class, 'destroy']);
        });

        Route::post('/canvas-shares/accept/{token}', [CanvasShareController::class, 'accept']);
        Route::post('/canvas-shares/decline/{token}', [CanvasShareController::class, 'decline']);
        Route::get('/shared-canvas/{ownerId}', [CanvasShareController::class, 'sharedCanvas']);

        // ── Notifications ──
        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index']);
            Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
            Route::patch('/{notification}/read', [NotificationController::class, 'markRead']);
            Route::post('/mark-all-read', [NotificationController::class, 'markAllRead']);
        });
    });
});

