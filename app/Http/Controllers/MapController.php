<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Models\AppSetting;

class MapController extends Controller
{
    private const MAP_ZOOM_DEFAULTS = [
        'map_zoom_city_mobile' => 11,
        'map_zoom_city_tablet' => 12,
        'map_zoom_city_desktop' => 13,
        'map_zoom_agency_mobile' => 10,
        'map_zoom_agency_tablet' => 11,
        'map_zoom_agency_desktop' => 12,
    ];

    public function index()
    {
        $activeAgency = Agency::where('is_active', true)
            ->with(['deliveryZones' => fn($q) => $q->where('is_active', true)->orderBy('order_index')])
            ->first();

        if (! $activeAgency) {
            $activeAgency = Agency::with(['deliveryZones' => fn($q) => $q->where('is_active', true)->orderBy('order_index')])
                ->orderBy('name')
                ->first();
        }

        $mapStyle = AppSetting::getValue('map_style', 'light');
        if (! in_array($mapStyle, ['light', 'dark', 'osm', 'voyager'], true)) {
            $mapStyle = 'light';
        }

        $mapZooms = [];
        foreach (self::MAP_ZOOM_DEFAULTS as $key => $default) {
            $raw = AppSetting::getValue($key, (string) $default);
            $value = is_numeric($raw) ? (float) $raw : (float) $default;
            $mapZooms[$key] = min(20, max(3, $value));
        }

        return view('map', [
            'activeAgency' => $activeAgency,
            'user'         => auth()->user(),
            'mapStyle'     => $mapStyle,
            'mapZooms'     => $mapZooms,
        ]);
    }
}
