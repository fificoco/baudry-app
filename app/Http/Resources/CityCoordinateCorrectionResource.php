<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CityCoordinateCorrectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'city_id'    => $this->city_id,
            'old_lat'    => $this->old_lat,
            'old_lng'    => $this->old_lng,
            'new_lat'    => $this->new_lat,
            'new_lng'    => $this->new_lng,
            'reason'     => $this->reason,
            'updated_by' => $this->updatedBy?->name,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
