<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WelcomeController;
use App\Http\Controllers\NoteController;

Route::get('/', [WelcomeController::class, 'welcome'])->name('welcome');

Route::resource('notes', NoteController::class)->names('notes');