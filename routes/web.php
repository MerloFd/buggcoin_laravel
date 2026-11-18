<?php

use Illuminate\Support\Facades\Route;

// Single-user app: no public landing page. Guests are sent to login by the auth middleware.
Route::redirect('/', '/dashboard')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
