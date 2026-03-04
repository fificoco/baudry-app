<x-layouts.map>
    @php
        $zonesPayload = $activeAgency
            ? $activeAgency->deliveryZones->map(function ($zone) {
                return [
                    'id' => $zone->id,
                    'rmin' => $zone->min_radius_m,
                    'rmax' => $zone->max_radius_m,
                    'color' => $zone->color_hex,
                ];
            })->values()
            : collect();
    @endphp

    <div id="map"></div>

    <header class="app-header">
        <div class="logo-wrap">
            <img src="{{ Vite::asset('resources/images/logoBaudry.png') }}" alt="Logo Baudry" class="logo" />
        </div>

        <div class="top-right-actions">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="account-btn" title="Déconnexion" aria-label="Déconnexion">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M10 17L15 12L10 7" stroke="#111111" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M15 12H4" stroke="#111111" stroke-width="4" stroke-linecap="round"/>
                        <path d="M20 4V20" stroke="#111111" stroke-width="4" stroke-linecap="round"/>
                    </svg>
                </button>
            </form>
            @if(auth()->user()?->isAdmin())
                <a href="{{ route('admin.index') }}" class="account-btn admin-a-btn" title="Admin" aria-label="Admin">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M10.325 4.317C10.751 2.561 13.249 2.561 13.675 4.317C13.95 5.451 15.24 5.988 16.262 5.425C17.845 4.552 19.612 6.319 18.739 7.902C18.176 8.924 18.713 10.214 19.847 10.489C21.603 10.915 21.603 13.413 19.847 13.839C18.713 14.114 18.176 15.404 18.739 16.426C19.612 18.009 17.845 19.776 16.262 18.903C15.24 18.34 13.95 18.877 13.675 20.011C13.249 21.767 10.751 21.767 10.325 20.011C10.05 18.877 8.76 18.34 7.738 18.903C6.155 19.776 4.388 18.009 5.261 16.426C5.824 15.404 5.287 14.114 4.153 13.839C2.397 13.413 2.397 10.915 4.153 10.489C5.287 10.214 5.824 8.924 5.261 7.902C4.388 6.319 6.155 4.552 7.738 5.425C8.76 5.988 10.05 5.451 10.325 4.317Z" stroke="#111111" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="12" cy="12" r="3" stroke="#111111" stroke-width="2.6"/>
                    </svg>
                </a>
            @endif
            <button id="burgerBtn" class="burger-btn" type="button" aria-label="Ouvrir le menu">
                <span class="burger-lines" aria-hidden="true">
                    <span></span>
                    <span></span>
                    <span></span>
                </span>
            </button>
        </div>
    </header>

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-content">
        @if($activeAgency)
            <div class="agency-label">Saisir les information:</div>
            <select class="agency-select hidden" id="agency" aria-hidden="true" tabindex="-1">
                <option
                    value="{{ $activeAgency->lat }},{{ $activeAgency->lng }}"
                    data-id="{{ $activeAgency->id }}"
                    data-name="{{ $activeAgency->name }}"
                    data-zones='@json($zonesPayload)'>
                    {{ $activeAgency->name }}
                </option>
            </select>
        @else
            <div class="agency-label">Saisir les information:</div>
        @endif

        <div class="city-cp-row">
            <input id="cpInput" list="suggestions-cp" placeholder="Postal" type="text" autocomplete="off" />
            <input id="villeInput" list="suggestions-ville" placeholder="Ville" type="text" autocomplete="off" />
        </div>
        <datalist id="suggestions-ville"></datalist>
        <datalist id="suggestions-cp"></datalist>

        <input id="adresseInput" placeholder="Adresse" type="text" autocomplete="off" />
        <div id="result-zone"></div>
        <br />

        <div class="action-row">
            <button class="reset" id="resetBtn" type="button">Réinitialiser</button>
            <button class="validate" id="validerBtn" type="button">Valider</button>
        </div>

        @if(auth()->user()?->isAdmin())
            <div class="coord-corrector">
                <div class="gps-row">
                    <button class="btnStyle" id="toggleCorrectionBtn" type="button">GPS Ajusté</button>
                    <button class="btnStyle" id="saveCorrectionBtn" type="button">GPS Update</button>
                </div>
                <button class="btnStyle hidden" id="addCityBtn" type="button">new city</button>
                <div id="correctionStatus"></div>
            </div>
        @else
            <div class="coord-corrector">
                <div id="correctionStatus"></div>
            </div>
        @endif

        <div class="nav-buttons">
            <button class="btnStyle" id="zoneBtn" type="button">Zone</button>
            <button class="btnStyle" id="websiteBtn" type="button">Website</button>
            <button class="btnStyle" id="itineraireBtn" type="button">Itinéraire</button>
        </div>
        </div>
    </aside>

    <script>
        window.__MAP_BOOTSTRAP = {
            isAdmin: @json((bool) auth()->user()?->isAdmin()),
            hasActiveAgency: @json((bool) $activeAgency),
            mapStyle: @json($mapStyle ?? 'light'),
            routes: {
                citiesSearch: @json(url('/api/v1/cities')),
                cityCoordinatesTemplate: @json(url('/admin/cities/__CITY__/coordinates')),
                cityStore: @json(route('admin.cities.store')),
            }
        };
    </script>
</x-layouts.map>
