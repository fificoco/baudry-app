<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'postal_code' => $this->postal_code,
            'department'  => $this->department ? [
                'id' => $this->department->id,
                'code' => $this->department->code,
                'name' => $this->department->name,
                'is_active' => (bool) $this->department->is_active,
            ] : null,
            'lat'         => $this->lat,
            'lng'         => $this->lng,
            'is_active'   => $this->is_active,
        ];
    }
}
