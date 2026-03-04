<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\DeliveryZone;
use Illuminate\Database\Seeder;

class AgencySeeder extends Seeder
{
    public function run(): void
    {
        $agencies = [
            [
                'name'   => 'Agence Principale',
                'lat'    => 48.8566,
                'lng'    => 2.3522,
                'is_active' => true,
                'zones'  => [
                    ['name' => 'Zone 1 — 0 à 15 km',  'min_radius_m' => 0,     'max_radius_m' => 15000,  'color_hex' => '#2ecc71', 'order_index' => 1],
                    ['name' => 'Zone 2 — 15 à 30 km', 'min_radius_m' => 15000, 'max_radius_m' => 30000,  'color_hex' => '#f39c12', 'order_index' => 2],
                    ['name' => 'Zone 3 — 30 à 50 km', 'min_radius_m' => 30000, 'max_radius_m' => 50000,  'color_hex' => '#e74c3c', 'order_index' => 3],
                ],
            ],
        ];

        foreach ($agencies as $agencyData) {
            $zones = $agencyData['zones'];
            unset($agencyData['zones']);

            $agency = Agency::create($agencyData);

            foreach ($zones as $zone) {
                $agency->deliveryZones()->create($zone);
            }
        }
    }
}
