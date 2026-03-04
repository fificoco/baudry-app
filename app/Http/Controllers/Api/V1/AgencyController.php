<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AgencyResource;
use App\Http\Resources\DeliveryZoneResource;
use App\Models\Agency;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AgencyController extends Controller
{
    /**
     * GET /api/v1/agencies
     * Liste toutes les agences actives.
     */
    public function index(): AnonymousResourceCollection
    {
        $agencies = Agency::where('is_active', true)->get();

        return AgencyResource::collection($agencies);
    }

    /**
     * GET /api/v1/agencies/{agency}/zones
     * Liste les zones de livraison d'une agence.
     */
    public function zones(Agency $agency): AnonymousResourceCollection
    {
        $zones = $agency->deliveryZones()->where('is_active', true)->get();

        return DeliveryZoneResource::collection($zones);
    }
}
