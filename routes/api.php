<?php

use App\Http\Controllers\Api\V1\AgencyController;
use App\Http\Controllers\Api\V1\CityController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — prefix /api/v1 (défini dans bootstrap/app.php)
|--------------------------------------------------------------------------
*/

// Agences + zones (lecture publique)
Route::get('/agencies', [AgencyController::class, 'index']);
Route::get('/agencies/{agency}/zones', [AgencyController::class, 'zones']);

// Villes (lecture publique)
Route::get('/cities', [CityController::class, 'index']);
Route::get('/cities/{city}', [CityController::class, 'show']);

// Routes protégées (auth requise + rôle)
Route::middleware('auth:sanctum')->group(function () {
    // Mise à jour coordonnées GPS (dispatcher ou admin)
    Route::patch('/cities/{city}/coordinates', [CityController::class, 'updateCoordinates']);

    // Historique corrections (admin seulement)
    Route::get('/cities/{city}/corrections', [CityController::class, 'corrections']);
});
