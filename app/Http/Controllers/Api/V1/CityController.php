<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateCityCoordinatesRequest;
use App\Http\Resources\CityCoordinateCorrectionResource;
use App\Http\Resources\CityResource;
use App\Models\City;
use App\Models\Department;
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
        $activeDepartmentIds = Department::query()
            ->where('is_active', true)
            ->pluck('id');

        $query = City::query()
            ->where('is_active', true)
            ->whereIn('department_id', $activeDepartmentIds);

        if ($search = $request->input('search')) {
            $search = trim((string) $search);
            $escapedSearch = $this->escapeLike($search);
            $containsPattern = "%{$escapedSearch}%";
            $prefixPattern = "{$escapedSearch}%";
            $wordPrefixPattern = "% {$escapedSearch}%";

            $query->where(function ($searchQuery) use ($containsPattern) {
                $searchQuery->where('name', 'like', $containsPattern)
                    ->orWhereRaw("REPLACE(name, '-', ' ') LIKE ?", [$containsPattern]);
            });

            $query->orderByRaw(
                "CASE
                    WHEN name LIKE ? THEN 0
                    WHEN REPLACE(name, '-', ' ') LIKE ? THEN 0
                    WHEN name LIKE ? THEN 1
                    WHEN REPLACE(name, '-', ' ') LIKE ? THEN 1
                    ELSE 2
                END",
                [$prefixPattern, $prefixPattern, $wordPrefixPattern, $wordPrefixPattern]
            );
        }

        if ($postalCode = $request->input('postal_code')) {
            $query->where('postal_code', 'like', "{$postalCode}%");
        }

        $perPage = max(1, min(50, $request->integer('per_page', 20)));

        $cities = $query
            ->with('department:id,code,name,is_active')
            ->orderBy('name')
            ->limit($perPage)
            ->get();

        return CityResource::collection($cities);
    }

    /**
     * GET /api/v1/cities/{city}
     * Détail d'une ville.
     */
    public function show(City $city): CityResource
    {
        $city->loadMissing('department:id,code,name,is_active');
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

        return new CityResource($city->fresh()->load('department:id,code,name,is_active'));
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

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
