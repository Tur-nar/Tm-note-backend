<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\AuthController;

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

        // apiResource creates ALL 5 RESTful routes automatically:
        // GET    /api/notes         → NoteController@index
        // POST   /api/notes         → NoteController@store
        // GET    /api/notes/{note}  → NoteController@show
        // PUT    /api/notes/{note}  → NoteController@update
        // DELETE /api/notes/{note}  → NoteController@destroy
    Route::middleware('verified')->group(function () {
        Route::apiResource('notes', NoteController::class);
        Route::patch('notes/{note}/archive', [NoteController::class, 'toggleArchive']);
        Route::patch('notes/{note}/pin', [NoteController::class, 'togglePin']);
    });
});
