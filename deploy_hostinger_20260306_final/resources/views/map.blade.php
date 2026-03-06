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

    <header class="app-header {{ auth()->user()?->isAdmin() ? 'is-admin' : 'is-viewer' }}">
        <div class="logo-wrap">
            <img src="{{ Vite::asset('resources/images/logoBaudry.png') }}" alt="Logo Baudry" class="logo" />
        </div>

        <div class="header-actions">
            <button id="burgerBtn" class="burger-btn" type="button" aria-label="Ouvrir le menu">
                <span class="burger-lines" aria-hidden="true">
                    <span></span>
                    <span></span>
                    <span></span>
                </span>
            </button>

            <button class="account-btn header-shortcut-btn" id="zoneBtn" type="button" title="Zone" aria-label="Zone">
                <svg width="34" height="34" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <circle cx="12" cy="12" r="8" stroke="#111111" stroke-width="2"/>
                    <circle cx="12" cy="12" r="4" stroke="#111111" stroke-width="2"/>
                    <circle cx="12" cy="12" r="1.5" fill="#111111"/>
                </svg>
            </button>

            <button class="account-btn header-shortcut-btn" id="websiteBtn" type="button" title="Website" aria-label="Website">
                <svg width="29" height="29" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <circle cx="12" cy="12" r="9" stroke="#111111" stroke-width="2"/>
                    <path d="M3 12H21" stroke="#111111" stroke-width="2"/>
                    <path d="M12 3C14.8 5.4 16.4 8.5 16.4 12C16.4 15.5 14.8 18.6 12 21" stroke="#111111" stroke-width="2"/>
                    <path d="M12 3C9.2 5.4 7.6 8.5 7.6 12C7.6 15.5 9.2 18.6 12 21" stroke="#111111" stroke-width="2"/>
                </svg>
            </button>

            <button class="account-btn header-shortcut-btn" id="itineraireBtn" type="button" title="Itinéraire" aria-label="Itinéraire">
                <svg width="33" height="33" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M5 13V10.5C5 8.57 6.57 7 8.5 7H15.5C17.43 7 19 8.57 19 10.5V13" stroke="#111111" stroke-width="2" stroke-linecap="round"/>
                    <rect x="4" y="10" width="16" height="6" rx="2" stroke="#111111" stroke-width="2"/>
                    <circle cx="7" cy="17" r="1.5" fill="#111111"/>
                    <circle cx="17" cy="17" r="1.5" fill="#111111"/>
                </svg>
            </button>

            @if(auth()->user()?->isAdmin())
                <a href="{{ route('admin.index') }}" class="account-btn admin-a-btn" title="Admin" aria-label="Admin">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M10.325 4.317C10.751 2.561 13.249 2.561 13.675 4.317C13.95 5.451 15.24 5.988 16.262 5.425C17.845 4.552 19.612 6.319 18.739 7.902C18.176 8.924 18.713 10.214 19.847 10.489C21.603 10.915 21.603 13.413 19.847 13.839C18.713 14.114 18.176 15.404 18.739 16.426C19.612 18.009 17.845 19.776 16.262 18.903C15.24 18.34 13.95 18.877 13.675 20.011C13.249 21.767 10.751 21.767 10.325 20.011C10.05 18.877 8.76 18.34 7.738 18.903C6.155 19.776 4.388 18.009 5.261 16.426C5.824 15.404 5.287 14.114 4.153 13.839C2.397 13.413 2.397 10.915 4.153 10.489C5.287 10.214 5.824 8.924 5.261 7.902C4.388 6.319 6.155 4.552 7.738 5.425C8.76 5.988 10.05 5.451 10.325 4.317Z" stroke="#111111" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="12" cy="12" r="3" stroke="#111111" stroke-width="2.6"/>
                    </svg>
                </a>
            @endif

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
            <div class="suggestion-field" id="villeField">
                <input id="villeInput" placeholder="Ville ou code postal" type="text" autocomplete="off" />
                <div id="suggestions-ville" class="suggestions-dropdown hidden"></div>
            </div>

            <div class="suggestion-field hidden" id="cpField">
                <input id="cpInput" placeholder="Code postal" type="text" inputmode="numeric" maxlength="5" autocomplete="off" />
            </div>
        </div>

        <input id="adresseInput" placeholder="Adresse" type="text" autocomplete="off" />
        <div id="result-zone"></div>
        <br />

        <div class="action-row">
            <button class="validate" id="validerBtn" type="button">Valider</button>
            <button class="reset" id="resetBtn" type="button">Reset</button>
        </div>

        @if(auth()->user()?->isAdmin())
            <div class="coord-corrector">
                <div class="gps-row">
                    <button class="btnStyle" id="toggleCorrectionBtn" type="button">Add-GPS</button>
                    <button class="btnStyle" id="addCityBtn" type="button">Add-City</button>
                </div>
                <div id="correctionStatus"></div>
            </div>
        @else
            <div class="coord-corrector">
                <div id="correctionStatus"></div>
            </div>
        @endif
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
