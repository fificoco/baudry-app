<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MapController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/map', [MapController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', fn () => redirect()->route('dashboard'));
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('index');
    Route::patch('/map-style', [AdminController::class, 'updateMapStyle'])->name('map-style.update');

    Route::post('/cities', [AdminController::class, 'storeCity'])->name('cities.store');
    Route::patch('/cities/{city}', [AdminController::class, 'updateCity'])->name('cities.update');
    Route::patch('/cities/{city}/coordinates', [AdminController::class, 'updateCityCoordinates'])->name('cities.coordinates');
    Route::delete('/cities/{city}', [AdminController::class, 'destroyCity'])->name('cities.destroy');

    Route::post('/agencies', [AdminController::class, 'storeAgency'])->name('agencies.store');
    Route::patch('/agencies/{agency}', [AdminController::class, 'updateAgency'])->name('agencies.update');
    Route::delete('/agencies/{agency}', [AdminController::class, 'destroyAgency'])->name('agencies.destroy');

    Route::post('/zones', [AdminController::class, 'storeZone'])->name('zones.store');
    Route::patch('/zones/{zone}', [AdminController::class, 'updateZone'])->name('zones.update');
    Route::delete('/zones/{zone}', [AdminController::class, 'destroyZone'])->name('zones.destroy');

    Route::post('/users', [AdminController::class, 'storeUser'])->name('users.store');
    Route::patch('/users/{user}/role', [AdminController::class, 'updateUserRole'])->name('users.role');
    Route::delete('/users/{user}', [AdminController::class, 'destroyUser'])->name('users.destroy');
});

require __DIR__.'/auth.php';
