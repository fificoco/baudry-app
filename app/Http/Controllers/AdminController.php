<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Models\AppSetting;
use App\Models\City;
use App\Models\Department;
use App\Models\DeliveryZone;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminController extends Controller
{
    private const MAP_STYLE_OPTIONS = [
        'light' => 'Clair (CARTO Light)',
        'dark' => 'Sombre (CARTO Dark)',
        'osm' => 'OpenStreetMap Standard',
        'voyager' => 'Voyager',
    ];

    private const MAP_ZOOM_DEFAULTS = [
        'map_zoom_city_mobile' => 11,
        'map_zoom_city_tablet' => 12,
        'map_zoom_city_desktop' => 13,
        'map_zoom_agency_mobile' => 10,
        'map_zoom_agency_tablet' => 11,
        'map_zoom_agency_desktop' => 12,
    ];

    public function index(Request $request): View|RedirectResponse
    {
        $activeAgency = Agency::where('is_active', true)->orderBy('name')->first();
        $currentMapStyle = AppSetting::getValue('map_style', 'light');
        $perPage = 25;
        $cityEditId = $request->integer('city_edit');

        if ($cityEditId) {
            $cityToEdit = City::query()->select('id', 'name')->find($cityEditId);

            if ($cityToEdit) {
                $position = City::query()
                    ->where(function ($query) use ($cityToEdit) {
                        $query
                            ->where('name', '<', $cityToEdit->name)
                            ->orWhere(function ($nestedQuery) use ($cityToEdit) {
                                $nestedQuery
                                    ->where('name', $cityToEdit->name)
                                    ->where('id', '<=', $cityToEdit->id);
                            });
                    })
                    ->count();

                $targetPage = max(1, (int) ceil($position / $perPage));
                $currentPage = max(1, $request->integer('page', 1));

                if ($currentPage !== $targetPage) {
                    return redirect()->route('admin.index', [
                        'page' => $targetPage,
                        'city_edit' => $cityToEdit->id,
                    ]);
                }
            }
        }

        if (! array_key_exists($currentMapStyle, self::MAP_STYLE_OPTIONS)) {
            $currentMapStyle = 'light';
        }

        $currentMapZooms = $this->getMapZoomSettings();

        return view('admin.index', [
            'users'       => User::orderBy('name')->get(),
            'cities'      => City::orderBy('name')->orderBy('id')->paginate($perPage)->withQueryString(),
            'departments' => Department::orderBy('code')->paginate(20, ['*'], 'departments_page')->withQueryString(),
            'agencies'    => Agency::with(['deliveryZones' => fn($q) => $q->orderBy('order_index')])->orderBy('name')->get(),
            'zones'       => DeliveryZone::when($activeAgency, fn($q) => $q->where('agency_id', $activeAgency->id))
                ->orderBy('order_index')
                ->get(),
            'cityEditId' => $cityEditId,
            'activeAgency' => $activeAgency,
            'roles'       => ['admin', 'dispatcher', 'viewer'],
            'mapStyleOptions' => self::MAP_STYLE_OPTIONS,
            'currentMapStyle' => $currentMapStyle,
            'currentMapZooms' => $currentMapZooms,
        ]);
    }

    public function searchCities(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:1', 'max:255'],
        ]);

        $term = trim($validated['q']);

        $cities = City::query()
            ->where('is_active', true)
            ->whereHas('department', fn($query) => $query->where('is_active', true))
            ->where(function ($query) use ($term) {
                $query
                    ->where('name', 'like', "%{$term}%")
                    ->orWhere('postal_code', 'like', "%{$term}%");
            })
            ->orderBy('name')
            ->orderBy('id')
            ->limit(15)
            ->with('department:id,code,name,is_active')
            ->get(['id', 'name', 'postal_code', 'department_id', 'lat', 'lng', 'is_active']);

        return response()->json([
            'data' => $cities->map(fn(City $city) => [
                'id' => $city->id,
                'name' => $city->name,
                'postal_code' => $city->postal_code,
                'department' => $city->department?->code,
                'lat' => $city->lat,
                'lng' => $city->lng,
                'is_active' => (bool) $city->is_active,
                'edit_url' => route('admin.index', ['city_edit' => $city->id]),
            ])->values(),
        ]);
    }

    public function updateMapStyle(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'map_style' => ['required', 'in:'.implode(',', array_keys(self::MAP_STYLE_OPTIONS))],
        ]);

        AppSetting::setValue('map_style', $validated['map_style']);

        return back()->with('success', 'Style de carte mis à jour.');
    }

    public function updateMapZoom(Request $request): RedirectResponse
    {
        $normalizedInputs = [];
        foreach (array_keys(self::MAP_ZOOM_DEFAULTS) as $key) {
            $raw = trim((string) $request->input($key, ''));
            $normalizedInputs[$key] = str_replace(',', '.', $raw);
        }
        $request->merge($normalizedInputs);

        $validated = $request->validate([
            'map_zoom_city_mobile' => ['required', 'numeric', 'between:3,20'],
            'map_zoom_city_tablet' => ['required', 'numeric', 'between:3,20'],
            'map_zoom_city_desktop' => ['required', 'numeric', 'between:3,20'],
            'map_zoom_agency_mobile' => ['required', 'numeric', 'between:3,20'],
            'map_zoom_agency_tablet' => ['required', 'numeric', 'between:3,20'],
            'map_zoom_agency_desktop' => ['required', 'numeric', 'between:3,20'],
        ]);

        foreach (self::MAP_ZOOM_DEFAULTS as $key => $default) {
            $value = (float) ($validated[$key] ?? $default);
            $clamped = min(20, max(3, $value));
            AppSetting::setValue($key, $this->formatZoomSetting($clamped));
        }

        return back()->with('success', 'Zooms de carte mis à jour.');
    }

    private function getMapZoomSettings(): array
    {
        $settings = [];

        foreach (self::MAP_ZOOM_DEFAULTS as $key => $default) {
            $raw = AppSetting::getValue($key, (string) $default);
            $value = is_numeric($raw) ? (float) $raw : (float) $default;
            $settings[$key] = min(20, max(3, $value));
        }

        return $settings;
    }

    private function formatZoomSetting(float $value): string
    {
        $formatted = number_format($value, 2, '.', '');
        return rtrim(rtrim($formatted, '0'), '.');
    }

    public function storeCity(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:10'],
            'lat'         => ['required', 'numeric', 'between:-90,90'],
            'lng'         => ['required', 'numeric', 'between:-180,180'],
            'is_active'   => ['nullable', 'boolean'],
        ]);

        $normalizedName = trim((string) $validated['name']);
        $normalizedPostalCode = trim((string) $validated['postal_code']);
        $department = $this->resolveDepartmentFromPostalCode($normalizedPostalCode);

        if (! $department) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Département introuvable à partir du code postal.',
                ], 422);
            }

            return back()->with('error', 'Département introuvable à partir du code postal.');
        }

        $cityAlreadyExists = City::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($normalizedName)])
            ->whereRaw('LOWER(postal_code) = ?', [mb_strtolower($normalizedPostalCode)])
            ->exists();

        if ($cityAlreadyExists) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Cette ville existe déjà avec ce code postal.',
                ], 422);
            }

            return back()->with('error', 'Cette ville existe déjà avec ce code postal.');
        }

        $city = City::create([
            ...$validated,
            'name' => $normalizedName,
            'postal_code' => $normalizedPostalCode,
            'department_id' => $department->id,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Ville ajoutée.',
                'data'    => $city,
            ], 201);
        }

        return back()->with('success', 'Ville ajoutée.');
    }

    public function updateCity(Request $request, City $city): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:10'],
            'lat'         => ['nullable', 'numeric', 'between:-90,90'],
            'lng'         => ['nullable', 'numeric', 'between:-180,180'],
            'is_active'   => ['nullable', 'boolean'],
        ]);

        $normalizedName = trim((string) $validated['name']);
        $normalizedPostalCode = trim((string) $validated['postal_code']);
        $department = $this->resolveDepartmentFromPostalCode($normalizedPostalCode);

        if (! $department) {
            return back()->with('error', 'Département introuvable à partir du code postal.');
        }

        $cityAlreadyExists = City::query()
            ->whereKeyNot($city->id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($normalizedName)])
            ->whereRaw('LOWER(postal_code) = ?', [mb_strtolower($normalizedPostalCode)])
            ->exists();

        if ($cityAlreadyExists) {
            return back()->with('error', 'Une autre ville existe déjà avec ce code postal.');
        }

        $city->update([
            ...$validated,
            'name' => $normalizedName,
            'postal_code' => $normalizedPostalCode,
            'department_id' => $department->id,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        return back()->with('success', 'Ville mise à jour.');
    }

    public function updateCityCoordinates(Request $request, City $city): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'lat'    => ['required', 'numeric', 'between:-90,90'],
            'lng'    => ['required', 'numeric', 'between:-180,180'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $city->coordinateCorrections()->create([
            'old_lat'    => $city->lat,
            'old_lng'    => $city->lng,
            'new_lat'    => $validated['lat'],
            'new_lng'    => $validated['lng'],
            'updated_by' => $request->user()->id,
            'reason'     => $validated['reason'] ?? null,
        ]);

        $city->update([
            'lat' => $validated['lat'],
            'lng' => $validated['lng'],
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Coordonnées mises à jour.',
                'data'    => $city->fresh(),
            ]);
        }

        return back()->with('success', 'Coordonnées mises à jour.');
    }

    public function destroyCity(City $city): RedirectResponse
    {
        $city->delete();

        return back()->with('success', 'Ville supprimée.');
    }

    public function updateDepartment(Request $request, Department $department): RedirectResponse
    {
        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $department->update([
            'is_active' => (bool) $validated['is_active'],
        ]);

        return back()->with('success', 'Département mis à jour.');
    }

    public function storeAgency(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'lat'       => ['required', 'numeric', 'between:-90,90'],
            'lng'       => ['required', 'numeric', 'between:-180,180'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $isFirstAgency = ! Agency::query()->exists();
        $isActive = $isFirstAgency || (bool) ($validated['is_active'] ?? false);

        if ($isActive) {
            Agency::query()->update(['is_active' => false]);
        }

        Agency::create([
            'name'      => $validated['name'],
            'lat'       => $validated['lat'],
            'lng'       => $validated['lng'],
            'is_active' => $isActive,
        ]);

        $this->ensureActiveAgencyExists();

        return back()->with('success', 'Agence ajoutée.');
    }

    public function updateAgency(Request $request, Agency $agency): RedirectResponse
    {
        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'lat'       => ['required', 'numeric', 'between:-90,90'],
            'lng'       => ['required', 'numeric', 'between:-180,180'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $isActive = (bool) ($validated['is_active'] ?? false);

        if ($isActive) {
            Agency::whereKeyNot($agency->id)->update(['is_active' => false]);
        }

        $agency->update([
            'name'      => $validated['name'],
            'lat'       => $validated['lat'],
            'lng'       => $validated['lng'],
            'is_active' => $isActive,
        ]);

        $this->ensureActiveAgencyExists();

        return back()->with('success', 'Agence mise à jour.');
    }

    public function destroyAgency(Agency $agency): RedirectResponse
    {
        $agency->delete();

        $this->ensureActiveAgencyExists();

        return back()->with('success', 'Agence supprimée.');
    }

    private function ensureActiveAgencyExists(): void
    {
        if (Agency::where('is_active', true)->exists()) {
            return;
        }

        $firstAgency = Agency::orderBy('name')->orderBy('id')->first();

        if ($firstAgency) {
            $firstAgency->update(['is_active' => true]);
        }
    }

    public function storeZone(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'min_radius_km' => ['required', 'numeric', 'min:0'],
            'max_radius_km' => ['required', 'numeric', 'gt:min_radius_km'],
            'color_hex'     => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $activeAgency = Agency::where('is_active', true)->orderBy('name')->first();
        if (! $activeAgency) {
            return back()->with('error', 'Aucune agence active: active une agence avant de gérer les zones.');
        }

        $nextOrder = DeliveryZone::where('agency_id', $activeAgency->id)->max('order_index');
        $nextOrder = ($nextOrder ?? 0) + 1;
        $minRadiusMeters = (int) round(((float) $validated['min_radius_km']) * 1000);
        $maxRadiusMeters = (int) round(((float) $validated['max_radius_km']) * 1000);

        DeliveryZone::create([
            'agency_id'     => $activeAgency->id,
            'name'          => 'Zone '.$nextOrder,
            'min_radius_m'  => $minRadiusMeters,
            'max_radius_m'  => $maxRadiusMeters,
            'color_hex'     => $validated['color_hex'],
            'order_index'   => $nextOrder,
            'is_active'     => true,
        ]);

        return back()->with('success', 'Zone ajoutée.');
    }

    public function updateZone(Request $request, DeliveryZone $zone): RedirectResponse
    {
        $validated = $request->validate([
            'min_radius_km' => ['required', 'numeric', 'min:0'],
            'max_radius_km' => ['required', 'numeric', 'gt:min_radius_km'],
            'color_hex'     => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $minRadiusMeters = (int) round(((float) $validated['min_radius_km']) * 1000);
        $maxRadiusMeters = (int) round(((float) $validated['max_radius_km']) * 1000);

        $zone->update([
            'name'          => 'Zone '.$zone->order_index,
            'min_radius_m'  => $minRadiusMeters,
            'max_radius_m'  => $maxRadiusMeters,
            'color_hex'     => $validated['color_hex'],
            'is_active'     => true,
        ]);

        return back()->with('success', 'Zone mise à jour.');
    }

    public function destroyZone(DeliveryZone $zone): RedirectResponse
    {
        $zone->delete();

        return back()->with('success', 'Zone supprimée.');
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role'     => ['required', 'in:admin,dispatcher,viewer'],
        ]);

        User::create($validated);

        return back()->with('success', 'Utilisateur créé.');
    }

    public function updateUserRole(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'in:admin,dispatcher,viewer'],
        ]);

        $user->update($validated);

        return back()->with('success', 'Rôle utilisateur mis à jour.');
    }

    public function destroyUser(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->id === $user->id) {
            return back()->with('error', 'Tu ne peux pas supprimer ton propre compte.');
        }

        $user->delete();

        return back()->with('success', 'Utilisateur supprimé.');
    }

    private function resolveDepartmentFromPostalCode(string $postalCode): ?Department
    {
        $departmentCode = $this->extractDepartmentCode($postalCode);

        if (! $departmentCode) {
            return null;
        }

        return Department::firstOrCreate(
            ['code' => $departmentCode],
            ['name' => 'Département '.$departmentCode, 'is_active' => true],
        );
    }

    private function extractDepartmentCode(string $postalCode): ?string
    {
        $normalized = strtoupper(trim($postalCode));

        if ($normalized === '') {
            return null;
        }

        $normalized = preg_replace('/[^A-Z0-9]/', '', $normalized) ?? '';

        if ($normalized === '') {
            return null;
        }

        if (preg_match('/^(97|98)\d{1,3}$/', $normalized) === 1) {
            return substr($normalized, 0, 3);
        }

        return substr($normalized, 0, min(2, strlen($normalized)));
    }
}
