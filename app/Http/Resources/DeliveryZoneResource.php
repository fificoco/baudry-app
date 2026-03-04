<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryZoneResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'agency_id'    => $this->agency_id,
            'name'         => $this->name,
            'min_radius_m' => $this->min_radius_m,
            'max_radius_m' => $this->max_radius_m,
            'color_hex'    => $this->color_hex,
            'order_index'  => $this->order_index,
            'is_active'    => $this->is_active,
        ];
    }
}
