<x-app-layout>
    <x-slot name="header">
        <div class="font-bold text-lg">
            Admin Panel
        </div>
    </x-slot>

    <div class="py-6 bg-black min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="bg-yellow-100 border border-black text-black px-4 py-3 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="bg-red-100 border border-red-300 text-red-700 px-4 py-3 rounded-lg">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white border border-black overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-4 text-black">Utilisateurs</h3>

                <form method="POST" action="{{ route('admin.users.store') }}" class="grid grid-cols-1 md:grid-cols-5 gap-3 mb-4">
                    @csrf
                    <input type="text" name="name" placeholder="Nom" class="border border-black rounded px-3 py-2" required>
                    <input type="email" name="email" placeholder="Email" class="border border-black rounded px-3 py-2" required>
                    <input type="password" name="password" placeholder="Mot de passe" class="border border-black rounded px-3 py-2" required>
                    <select name="role" class="border border-black rounded px-3 py-2" required>
                        @foreach ($roles as $role)
                            <option value="{{ $role }}">{{ $role }}</option>
                        @endforeach
                    </select>
                    <button class="bg-yellow-400 text-black border border-black rounded px-4 py-2 font-semibold" type="submit">Créer</button>
                </form>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-black text-left">
                                <th class="py-2">Nom</th>
                                <th class="py-2">Email</th>
                                <th class="py-2">Rôle</th>
                                <th class="py-2">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $user)
                                <tr class="border-b border-gray-200">
                                    <td class="py-2 pr-2">{{ $user->name }}</td>
                                    <td class="py-2 pr-2">{{ $user->email }}</td>
                                    <td class="py-2 pr-2">
                                        <form method="POST" action="{{ route('admin.users.role', $user) }}" class="flex gap-2">
                                            @csrf
                                            @method('PATCH')
                                            <select name="role" class="border border-black rounded px-2 py-1">
                                                @foreach ($roles as $role)
                                                    <option value="{{ $role }}" @selected($user->role === $role)>{{ $role }}</option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="border border-black rounded px-2 py-1">Maj</button>
                                        </form>
                                    </td>
                                    <td class="py-2">
                                        @if (auth()->id() !== $user->id)
                                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Supprimer cet utilisateur ?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="text-red-600" type="submit">Supprimer</button>
                                            </form>
                                        @else
                                            <span class="text-gray-500">Compte courant</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white border border-black overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-4 text-black">Gestion des cartes</h3>

                <form method="POST" action="{{ route('admin.map-style.update') }}" class="flex flex-col md:flex-row md:items-center gap-3">
                    @csrf
                    @method('PATCH')

                    <select name="map_style" class="border border-black rounded px-3 py-2 md:min-w-80" required>
                        @foreach($mapStyleOptions as $styleKey => $styleLabel)
                            <option value="{{ $styleKey }}" @selected($currentMapStyle === $styleKey)>{{ $styleLabel }}</option>
                        @endforeach
                    </select>

                    <button type="submit" class="bg-yellow-400 text-black border border-black rounded px-4 py-2 font-semibold">Sauvegarder style</button>
                </form>
            </div>

            <div class="bg-white border border-black overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-black">Villes</h3>
                    <div class="flex gap-2">
                        <span class="inline-flex items-center text-sm font-semibold text-black px-2">Page-{{ $cities->currentPage() }}</span>

                        @if ($cities->previousPageUrl())
                            <a href="{{ $cities->previousPageUrl() }}" class="w-11 h-11 border border-black rounded bg-yellow-400 text-black inline-flex items-center justify-center text-2xl font-bold" aria-label="Page précédente">←</a>
                        @else
                            <span class="w-11 h-11 border border-gray-300 rounded bg-yellow-100 text-gray-400 inline-flex items-center justify-center text-2xl">←</span>
                        @endif

                        @if ($cities->nextPageUrl())
                            <a href="{{ $cities->nextPageUrl() }}" class="w-11 h-11 border border-black rounded bg-yellow-400 text-black inline-flex items-center justify-center text-2xl font-bold" aria-label="Page suivante">→</a>
                        @else
                            <span class="w-11 h-11 border border-gray-300 rounded bg-yellow-100 text-gray-400 inline-flex items-center justify-center text-2xl">→</span>
                        @endif
                    </div>
                </div>

                <form id="cityForm" method="POST" action="{{ route('admin.cities.store') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4">
                    @csrf
                    <input id="cityName" type="text" name="name" placeholder="Nom" class="border border-black rounded px-3 py-2" required>
                    <input id="cityPostal" type="text" name="postal_code" placeholder="Code postal" class="border border-black rounded px-3 py-2" required>
                    <input id="cityLat" type="number" step="0.000001" name="lat" placeholder="Latitude" class="border border-black rounded px-3 py-2" required>
                    <input id="cityLng" type="number" step="0.000001" name="lng" placeholder="Longitude" class="border border-black rounded px-3 py-2" required>

                    <div class="md:col-span-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                        <select id="cityActive" name="is_active" class="border border-black rounded px-3 py-2 md:w-44">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>

                        <div class="flex gap-2 md:justify-end">
                            <button id="citySubmit" class="bg-yellow-400 text-black border border-black rounded px-4 py-2 font-semibold" type="submit">Sauvegarder</button>
                            <button id="cityCancel" class="hidden border border-black rounded px-4 py-2" type="button">Annuler</button>
                        </div>
                    </div>

                    <input id="cityMethod" type="hidden" name="_method" value="">
                </form>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-black text-left">
                                <th class="py-2">Ville</th>
                                <th class="py-2">CP</th>
                                <th class="py-2">Lat</th>
                                <th class="py-2">Lng</th>
                                <th class="py-2">Actif</th>
                                <th class="py-2">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($cities as $city)
                                <tr class="border-b border-gray-200 align-top">
                                    <td class="py-2 pr-2">
                                        <button
                                            type="button"
                                            class="js-edit-city underline text-black"
                                            data-id="{{ $city->id }}"
                                            data-name="{{ $city->name }}"
                                            data-postal="{{ $city->postal_code }}"
                                            data-lat="{{ $city->lat }}"
                                            data-lng="{{ $city->lng }}"
                                            data-active="{{ $city->is_active ? 1 : 0 }}"
                                        >{{ $city->name }}</button>
                                    </td>
                                    <td class="py-2 pr-2">{{ $city->postal_code }}</td>
                                    <td class="py-2 pr-2">{{ $city->lat }}</td>
                                    <td class="py-2 pr-2">{{ $city->lng }}</td>
                                    <td class="py-2 pr-2">{{ $city->is_active ? 'Oui' : 'Non' }}</td>
                                    <td class="py-2">
                                        <form method="POST" action="{{ route('admin.cities.destroy', $city) }}" onsubmit="return confirm('Supprimer cette ville ?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-red-600" type="submit">Supprimer</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white border border-black overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold mb-4 text-black">Agences</h3>

                    <form id="agencyForm" method="POST" action="{{ route('admin.agencies.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
                        @csrf
                        <input id="agencyName" type="text" name="name" placeholder="Nom agence" class="border border-black rounded px-3 py-2" required>
                        <input id="agencyLat" type="number" step="0.000001" name="lat" placeholder="Lat centre" class="border border-black rounded px-3 py-2" required>
                        <input id="agencyLng" type="number" step="0.000001" name="lng" placeholder="Lng centre" class="border border-black rounded px-3 py-2" required>
                        <select id="agencyActive" name="is_active" class="border border-black rounded px-3 py-2">
                            <option value="0" selected>Inactive</option>
                            <option value="1">Active</option>
                        </select>
                        <div class="flex gap-2 md:col-span-2">
                            <button id="agencySubmit" class="bg-yellow-400 text-black border border-black rounded px-4 py-2 font-semibold w-full" type="submit">Ajouter</button>
                            <button id="agencyCancel" class="hidden border border-black rounded px-3 py-2" type="button">Annuler</button>
                        </div>
                        <input id="agencyMethod" type="hidden" name="_method" value="">
                    </form>

                    <ul class="space-y-2 text-sm">
                        @foreach ($agencies as $agency)
                            <li class="border border-gray-300 rounded p-2 flex items-center justify-between gap-3">
                                <button
                                    type="button"
                                    class="js-edit-agency underline text-left text-black"
                                    data-id="{{ $agency->id }}"
                                    data-name="{{ $agency->name }}"
                                    data-lat="{{ $agency->lat }}"
                                    data-lng="{{ $agency->lng }}"
                                    data-active="{{ $agency->is_active ? 1 : 0 }}"
                                >{{ $agency->name }} ({{ $agency->lat }}, {{ $agency->lng }}) - {{ $agency->is_active ? 'Active' : 'Inactive' }}</button>
                                <form method="POST" action="{{ route('admin.agencies.destroy', $agency) }}" onsubmit="return confirm('Supprimer cette agence ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-red-600" type="submit">Supprimer</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="bg-white border border-black overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold mb-4 text-black">Zones</h3>
                    <p class="text-sm text-gray-700 mb-3">
                        Configuration appliquée à l’agence active: <strong>{{ $activeAgency?->name ?? 'Aucune' }}</strong>
                    </p>

                    <form id="zoneForm" method="POST" action="{{ route('admin.zones.store') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4">
                        @csrf
                        <input id="zoneRmin" type="number" name="min_radius_km" min="0" step="0.001" placeholder="rmin (km)" class="border border-black rounded px-3 py-2" required>
                        <input id="zoneRmax" type="number" name="max_radius_km" min="0.001" step="0.001" placeholder="rmax (km)" class="border border-black rounded px-3 py-2" required>
                        <div class="flex items-center gap-3">
                            <button id="zoneColorSwatch" type="button" class="w-10 h-10 border border-black rounded bg-yellow-400 flex items-center justify-center" aria-label="Choisir couleur">
                                <span id="zoneColorIcon" aria-hidden="true" class="inline-flex">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M12 3C7.03 3 3 6.58 3 11C3 13.39 4.18 15.54 6.06 16.95C6.64 17.39 7 18.07 7 18.8V19C7 20.66 8.34 22 10 22H11.2C12.75 22 14 20.75 14 19.2C14 17.98 14.98 17 16.2 17H17C20.31 17 23 14.31 23 11C23 6.58 18.97 3 14 3H12Z" stroke="#111111" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                                        <circle cx="7.5" cy="11" r="1" fill="#111111"/>
                                        <circle cx="10.5" cy="8" r="1" fill="#111111"/>
                                        <circle cx="14" cy="8" r="1" fill="#111111"/>
                                        <circle cx="16.5" cy="11" r="1" fill="#111111"/>
                                    </svg>
                                </span>
                            </button>
                            <input id="zoneColor" type="color" name="color_hex" value="#f7c600" class="hidden" aria-hidden="true" tabindex="-1">
                        </div>
                        <div class="flex gap-2">
                            <button id="zoneSubmit" class="bg-yellow-400 text-black border border-black rounded px-4 py-2 font-semibold w-full" type="submit">Ajouter</button>
                            <button id="zoneCancel" class="hidden border border-black rounded px-3 py-2" type="button">Annuler</button>
                        </div>
                        <input id="zoneMethod" type="hidden" name="_method" value="">
                    </form>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b border-black text-left">
                                    <th class="py-2">Zone</th>
                                    <th class="py-2">rmin (km)</th>
                                    <th class="py-2">rmax (km)</th>
                                    <th class="py-2">Couleur</th>
                                    <th class="py-2">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($zones as $zone)
                                    <tr class="border-b border-gray-200 js-zone-row">
                                        <td class="py-2 pr-2">
                                            <button
                                                type="button"
                                                class="js-edit-zone underline text-black"
                                                data-id="{{ $zone->id }}"
                                                data-rmin="{{ number_format($zone->min_radius_m / 1000, 3, '.', '') }}"
                                                data-rmax="{{ number_format($zone->max_radius_m / 1000, 3, '.', '') }}"
                                                data-color="{{ $zone->color_hex }}"
                                            >{{ $zone->order_index }}</button>
                                        </td>
                                        <td class="py-2 pr-2">{{ number_format($zone->min_radius_m / 1000, 3, ',', ' ') }}</td>
                                        <td class="py-2 pr-2">{{ number_format($zone->max_radius_m / 1000, 3, ',', ' ') }}</td>
                                        <td class="py-2 pr-2">
                                            <button type="button" class="js-color-view w-7 h-7 border border-black rounded" style="background-color: {{ $zone->color_hex }}" data-color="{{ $zone->color_hex }}" aria-label="Choisir couleur"></button>
                                        </td>
                                        <td class="py-2">
                                            <form method="POST" action="{{ route('admin.zones.destroy', $zone) }}" onsubmit="return confirm('Supprimer cette zone ?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="text-red-600" type="submit">Supprimer</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const cityForm = document.getElementById('cityForm');
            const cityMethod = document.getElementById('cityMethod');
            const citySubmit = document.getElementById('citySubmit');
            const cityCancel = document.getElementById('cityCancel');
            const cityName = document.getElementById('cityName');
            const cityPostal = document.getElementById('cityPostal');
            const cityLat = document.getElementById('cityLat');
            const cityLng = document.getElementById('cityLng');
            const cityActive = document.getElementById('cityActive');
            const cityStoreUrl = @json(route('admin.cities.store'));
            const cityUpdateTpl = @json(route('admin.cities.update', '__CITY__'));

            function resetCityForm() {
                cityForm.action = cityStoreUrl;
                cityMethod.value = '';
                citySubmit.textContent = 'Sauvegarder';
                cityCancel.classList.add('hidden');
                cityForm.reset();
                cityActive.value = '1';
            }

            document.querySelectorAll('.js-edit-city').forEach((button) => {
                button.addEventListener('click', () => {
                    const id = button.dataset.id;
                    cityForm.action = cityUpdateTpl.replace('__CITY__', id);
                    cityMethod.value = 'PATCH';
                    citySubmit.textContent = 'Sauvegarder';
                    cityCancel.classList.remove('hidden');

                    cityName.value = button.dataset.name;
                    cityPostal.value = button.dataset.postal;
                    cityLat.value = button.dataset.lat;
                    cityLng.value = button.dataset.lng;
                    cityActive.value = button.dataset.active;
                    window.scrollTo({ top: cityForm.offsetTop - 80, behavior: 'smooth' });
                });
            });

            cityCancel.addEventListener('click', resetCityForm);

            const agencyForm = document.getElementById('agencyForm');
            const agencyMethod = document.getElementById('agencyMethod');
            const agencySubmit = document.getElementById('agencySubmit');
            const agencyCancel = document.getElementById('agencyCancel');
            const agencyName = document.getElementById('agencyName');
            const agencyLat = document.getElementById('agencyLat');
            const agencyLng = document.getElementById('agencyLng');
            const agencyActive = document.getElementById('agencyActive');
            const agencyStoreUrl = @json(route('admin.agencies.store'));
            const agencyUpdateTpl = @json(route('admin.agencies.update', '__AGENCY__'));

            function resetAgencyForm() {
                agencyForm.action = agencyStoreUrl;
                agencyMethod.value = '';
                agencySubmit.textContent = 'Ajouter';
                agencyCancel.classList.add('hidden');
                agencyForm.reset();
                agencyActive.value = '0';
            }

            document.querySelectorAll('.js-edit-agency').forEach((button) => {
                button.addEventListener('click', () => {
                    const id = button.dataset.id;
                    agencyForm.action = agencyUpdateTpl.replace('__AGENCY__', id);
                    agencyMethod.value = 'PATCH';
                    agencySubmit.textContent = 'Sauvegarder';
                    agencyCancel.classList.remove('hidden');

                    agencyName.value = button.dataset.name;
                    agencyLat.value = button.dataset.lat;
                    agencyLng.value = button.dataset.lng;
                    agencyActive.value = button.dataset.active;
                    window.scrollTo({ top: agencyForm.offsetTop - 80, behavior: 'smooth' });
                });
            });

            agencyCancel.addEventListener('click', resetAgencyForm);

            const zoneForm = document.getElementById('zoneForm');
            const zoneMethod = document.getElementById('zoneMethod');
            const zoneSubmit = document.getElementById('zoneSubmit');
            const zoneCancel = document.getElementById('zoneCancel');
            const zoneRmin = document.getElementById('zoneRmin');
            const zoneRmax = document.getElementById('zoneRmax');
            const zoneColor = document.getElementById('zoneColor');
            const zoneColorSwatch = document.getElementById('zoneColorSwatch');
            const zoneColorIcon = document.getElementById('zoneColorIcon');
            const zoneStoreUrl = @json(route('admin.zones.store'));
            const zoneUpdateTpl = @json(route('admin.zones.update', '__ZONE__'));
            const defaultZoneColor = '#f7c600';

            function syncZoneSwatch() {
                zoneColorSwatch.style.backgroundColor = zoneColor.value;
                const isDefault = (zoneColor.value || '').toLowerCase() === defaultZoneColor;
                zoneColorIcon.classList.toggle('hidden', !isDefault);
            }

            function resetZoneForm() {
                zoneForm.action = zoneStoreUrl;
                zoneMethod.value = '';
                zoneSubmit.textContent = 'Ajouter';
                zoneCancel.classList.add('hidden');
                zoneForm.reset();
                zoneColor.value = defaultZoneColor;
                syncZoneSwatch();
            }

            document.querySelectorAll('.js-edit-zone').forEach((button) => {
                button.addEventListener('click', () => {
                    const id = button.dataset.id;
                    zoneForm.action = zoneUpdateTpl.replace('__ZONE__', id);
                    zoneMethod.value = 'PATCH';
                    zoneSubmit.textContent = 'Sauvegarder';
                    zoneCancel.classList.remove('hidden');

                    zoneRmin.value = button.dataset.rmin;
                    zoneRmax.value = button.dataset.rmax;
                    zoneColor.value = button.dataset.color;
                    syncZoneSwatch();
                    window.scrollTo({ top: zoneForm.offsetTop - 80, behavior: 'smooth' });
                });
            });

            document.querySelectorAll('.js-color-view').forEach((button) => {
                button.addEventListener('click', () => {
                    zoneColor.value = button.dataset.color;
                    syncZoneSwatch();
                    zoneColor.click();
                });
            });

            zoneColor.addEventListener('input', syncZoneSwatch);
            zoneColorSwatch.addEventListener('click', () => zoneColor.click());
            zoneCancel.addEventListener('click', resetZoneForm);

            syncZoneSwatch();
        })();
    </script>
</x-app-layout>
