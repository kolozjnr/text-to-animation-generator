<?php

use App\Http\Controllers\DocumentController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/convert', [DocumentController::class, 'index'])->name('convert');
    Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');

    Route::get('/lessons', function () {
        return Inertia::render('Lessons/Index');
    })->name('lessons.index');

    Route::get('/profile', function () {
        return Inertia::render('Profile');
    })->name('profile');
});
