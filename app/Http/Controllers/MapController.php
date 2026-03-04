<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Models\AppSetting;

class MapController extends Controller
{
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

        return view('map', [
            'activeAgency' => $activeAgency,
            'user'         => auth()->user(),
            'mapStyle'     => $mapStyle,
        ]);
    }
}
