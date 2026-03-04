<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateCityCoordinatesRequest;
use App\Http\Resources\CityCoordinateCorrectionResource;
use App\Http\Resources\CityResource;
use App\Models\City;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CityController extends Controller
{
    /**
     * GET /api/v1/cities?search=&postal_code=&per_page=
     * Recherche de villes par nom ou code postal.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = City::query()->where('is_active', true);

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($postalCode = $request->input('postal_code')) {
            $query->where('postal_code', 'like', "{$postalCode}%");
        }

        $cities = $query
            ->orderBy('name')
            ->paginate($request->integer('per_page', 50));

        return CityResource::collection($cities);
    }

    /**
     * GET /api/v1/cities/{city}
     * Détail d'une ville.
     */
    public function show(City $city): CityResource
    {
        return new CityResource($city);
    }

    /**
     * PATCH /api/v1/cities/{city}/coordinates
     * Met à jour les coordonnées GPS d'une ville (auth + rôle dispatcher/admin).
     * Journalise la correction dans city_coordinate_corrections.
     */
    public function updateCoordinates(UpdateCityCoordinatesRequest $request, City $city): CityResource
    {
        // Enregistrer l'historique avant modification
        $city->coordinateCorrections()->create([
            'old_lat'    => $city->lat,
            'old_lng'    => $city->lng,
            'new_lat'    => $request->input('lat'),
            'new_lng'    => $request->input('lng'),
            'updated_by' => $request->user()->id,
            'reason'     => $request->input('reason'),
        ]);

        // Mettre à jour les coordonnées
        $city->update([
            'lat' => $request->input('lat'),
            'lng' => $request->input('lng'),
        ]);

        return new CityResource($city->fresh());
    }

    /**
     * GET /api/v1/cities/{city}/corrections
     * Historique des corrections (admin uniquement).
     */
    public function corrections(Request $request, City $city): AnonymousResourceCollection|JsonResponse
    {
        if (! $request->user()?->isAdmin()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $corrections = $city->coordinateCorrections()->with('updatedBy')->get();

        return CityCoordinateCorrectionResource::collection($corrections);
    }
}
