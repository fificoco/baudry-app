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

                <form method="POST" action="{{ route('admin.users.store') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4" autocomplete="off">
                    @csrf
                    <input type="text" name="name" placeholder="Nom" class="border border-black rounded px-3 py-2" autocomplete="off" required>
                    <input type="email" name="email" placeholder="Email" class="border border-black rounded px-3 py-2" autocomplete="off" required>
                    <input type="password" name="password" placeholder="Mot de passe" class="border border-black rounded px-3 py-2" autocomplete="new-password" required>
                    <select name="role" class="border border-black rounded px-3 py-2" style="padding-right:2.25rem; min-width:110px;" required>
                        @foreach ($roles as $role)
                            <option value="{{ $role }}">{{ $roleShortLabels[$role] ?? $role }}</option>
                        @endforeach
                    </select>
                    <div class="md:col-span-4 flex justify-center md:justify-start">
                        <button class="w-52 whitespace-nowrap bg-yellow-400 text-black border border-black rounded px-4 py-2 font-semibold" type="submit">Ajouter utilisateur</button>
                    </div>
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
                                            <select name="role" class="border border-black rounded px-2 py-1" style="padding-right:2.25rem; min-width:96px;">
                                                @foreach ($roles as $role)
                                                    <option value="{{ $role }}" @selected($user->role === $role)>{{ $roleShortLabels[$role] ?? $role }}</option>
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

                <form method="POST" action="{{ route('admin.map-style.update') }}" class="flex flex-col gap-3">
                    @csrf
                    @method('PATCH')

                    <select name="map_style" class="border border-black rounded px-3 py-2 md:min-w-80" required>
                        @foreach($mapStyleOptions as $styleKey => $styleLabel)
                            <option value="{{ $styleKey }}" @selected($currentMapStyle === $styleKey)>{{ $styleLabel }}</option>
                        @endforeach
                    </select>

                    <div class="flex justify-center md:justify-start">
                        <button type="submit" class="w-52 whitespace-nowrap bg-yellow-400 text-black border border-black rounded px-4 py-2 font-semibold">Sauvegarder style</button>
                    </div>
                </form>

                <form method="POST" action="{{ route('admin.map-zoom.update') }}" class="mt-4">
                    @csrf
                    @method('PATCH')

                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2 md:gap-6 md:justify-items-center lg:grid-cols-3 lg:gap-6">
                        <div class="w-full flex justify-center md:order-1 lg:order-1">
                            <label class="w-full md:w-[280px] md:h-[50px] border border-black rounded px-3 py-2 text-sm font-semibold text-black flex items-center justify-between gap-3">
                                <span>Zoom Agence Mobile</span>
                                <input type="number" name="map_zoom_agency_mobile" min="3" max="20" step="0.01" inputmode="decimal" class="w-20 border border-black rounded px-2 py-1 text-center" value="{{ $currentMapZooms['map_zoom_agency_mobile'] }}" required>
                            </label>
                        </div>

                        <div class="w-full flex justify-center md:order-2 lg:order-4">
                            <label class="w-full md:w-[280px] md:h-[50px] border border-black rounded px-3 py-2 text-sm font-semibold text-black flex items-center justify-between gap-3">
                                <span>Zoom Ville Mobile</span>
                                <input type="number" name="map_zoom_city_mobile" min="3" max="20" step="0.01" inputmode="decimal" class="w-20 border border-black rounded px-2 py-1 text-center" value="{{ $currentMapZooms['map_zoom_city_mobile'] }}" required>
                            </label>
                        </div>

                        <div class="w-full flex justify-center md:order-3 lg:order-2">
                            <label class="w-full md:w-[280px] md:h-[50px] border border-black rounded px-3 py-2 text-sm font-semibold text-black flex items-center justify-between gap-3">
                                <span>Zoom Agence Tablette</span>
                                <input type="number" name="map_zoom_agency_tablet" min="3" max="20" step="0.01" inputmode="decimal" class="w-20 border border-black rounded px-2 py-1 text-center" value="{{ $currentMapZooms['map_zoom_agency_tablet'] }}" required>
                            </label>
                        </div>

                        <div class="w-full flex justify-center md:order-4 lg:order-5">
                            <label class="w-full md:w-[280px] md:h-[50px] border border-black rounded px-3 py-2 text-sm font-semibold text-black flex items-center justify-between gap-3">
                                <span>Zoom Ville Tablette</span>
                                <input type="number" name="map_zoom_city_tablet" min="3" max="20" step="0.01" inputmode="decimal" class="w-20 border border-black rounded px-2 py-1 text-center" value="{{ $currentMapZooms['map_zoom_city_tablet'] }}" required>
                            </label>
                        </div>

                        <div class="w-full flex justify-center md:order-5 lg:order-3">
                            <label class="w-full md:w-[280px] md:h-[50px] border border-black rounded px-3 py-2 text-sm font-semibold text-black flex items-center justify-between gap-3">
                                <span>Zoom Agence Desktop</span>
                                <input type="number" name="map_zoom_agency_desktop" min="3" max="20" step="0.01" inputmode="decimal" class="w-20 border border-black rounded px-2 py-1 text-center" value="{{ $currentMapZooms['map_zoom_agency_desktop'] }}" required>
                            </label>
                        </div>

                        <div class="w-full flex justify-center md:order-6 lg:order-6">
                            <label class="w-full md:w-[280px] md:h-[50px] border border-black rounded px-3 py-2 text-sm font-semibold text-black flex items-center justify-between gap-3">
                                <span>Zoom Ville Desktop</span>
                                <input type="number" name="map_zoom_city_desktop" min="3" max="20" step="0.01" inputmode="decimal" class="w-20 border border-black rounded px-2 py-1 text-center" value="{{ $currentMapZooms['map_zoom_city_desktop'] }}" required>
                            </label>
                        </div>
                    </div>

                    <div class="flex justify-center md:justify-start mt-3">
                        <button type="submit" class="w-52 whitespace-nowrap bg-yellow-400 text-black border border-black rounded px-4 py-2 font-semibold">Sauvegarder zooms</button>
                    </div>
                </form>
            </div>

            <div class="bg-white border border-black overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex flex-col items-start gap-2 md:flex-row md:items-center md:justify-between mb-4">
                    <h3 class="text-lg font-semibold text-black">Villes</h3>
                    <div class="flex flex-wrap gap-2">
                        <button id="cityListToggle" type="button" class="border border-black rounded px-3 py-2 text-sm font-semibold bg-yellow-400 text-black">Masquer liste</button>
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
                    <input id="cityName" type="text" name="name" placeholder="Nom" class="border border-black rounded px-3 py-2" list="cityNameSuggestions" autocomplete="off" required>
                    <input id="cityPostal" type="text" name="postal_code" placeholder="Code postal" class="border border-black rounded px-3 py-2" list="cityPostalSuggestions" autocomplete="off" required>
                    <input id="cityLat" type="number" step="0.000001" name="lat" placeholder="Latitude" class="border border-black rounded px-3 py-2" required>
                    <input id="cityLng" type="number" step="0.000001" name="lng" placeholder="Longitude" class="border border-black rounded px-3 py-2" required>
                    <datalist id="cityNameSuggestions"></datalist>
                    <datalist id="cityPostalSuggestions"></datalist>

                    <div class="md:col-span-4 flex flex-col gap-3">
                        <select id="cityActive" name="is_active" class="border border-black rounded px-3 py-2 md:w-44">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>

                        <div class="flex justify-center md:justify-start">
                            <button id="citySubmit" class="w-52 whitespace-nowrap bg-yellow-400 text-black border border-black rounded px-4 py-2 font-semibold" type="submit">Ajouter ville</button>
                        </div>

                        <div class="flex justify-center md:justify-start gap-2">
                            <button id="cityDelete" class="hidden bg-red-500 text-black border border-black rounded px-4 py-2 font-semibold" type="button">Delete</button>
                            <button id="cityCancel" class="hidden border border-black rounded px-4 py-2" type="button">Annuler</button>
                        </div>
                    </div>

                    <input id="cityMethod" type="hidden" name="_method" value="">
                </form>

                <div id="cityListContainer" class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-black text-left">
                                <th class="py-2">
                                    <div class="inline-flex items-center gap-2">
                                        <span>Active</span>
                                        <button id="cityActiveBatchSave" type="button" class="hidden border border-black rounded px-2 py-1 text-xs font-semibold bg-yellow-400 text-black">Sauvegarder</button>
                                    </div>
                                </th>
                                <th class="py-2">Ville</th>
                                <th class="py-2">CP</th>
                                <th class="py-2">Lat</th>
                                <th class="py-2">Lng</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($cities as $city)
                                <tr class="border-b border-gray-200 align-top">
                                    <td class="py-2 pr-2">
                                        <form method="POST" action="{{ route('admin.cities.update', $city) }}" class="inline-flex items-center js-active-switch-form" data-scope="city" data-entity-id="{{ $city->id }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="name" value="{{ $city->name }}">
                                            <input type="hidden" name="postal_code" value="{{ $city->postal_code }}">
                                            <input type="hidden" name="lat" value="{{ $city->lat }}">
                                            <input type="hidden" name="lng" value="{{ $city->lng }}">
                                            <input type="hidden" name="is_active" value="{{ $city->is_active ? 1 : 0 }}" class="js-switch-active-value">

                                            <label class="relative inline-flex items-center cursor-pointer" title="{{ $city->is_active ? 'Actif' : 'Inactif' }}">
                                                <input type="checkbox" class="sr-only peer js-active-switch" @checked($city->is_active) aria-label="Activer ou désactiver {{ $city->name }}">
                                                <span class="w-11 h-6 rounded-full bg-red-500 transition-colors duration-200 peer-checked:bg-green-500 after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:w-5 after:h-5 after:bg-white after:rounded-full after:transition-transform after:duration-200 peer-checked:after:translate-x-5"></span>
                                            </label>
                                        </form>
                                    </td>
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
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white border border-black overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex flex-col items-start gap-2 md:flex-row md:items-center md:justify-between mb-4">
                    <h3 class="text-lg font-semibold text-black">Départements</h3>
                    <div class="flex flex-wrap gap-2">
                        <button id="departmentListToggle" type="button" class="border border-black rounded px-3 py-2 text-sm font-semibold bg-yellow-400 text-black">Masquer liste</button>
                        <span class="inline-flex items-center text-sm font-semibold text-black px-2">Page-{{ $departments->currentPage() }}</span>

                        @if ($departments->previousPageUrl())
                            <a href="{{ $departments->previousPageUrl() }}" class="w-11 h-11 border border-black rounded bg-yellow-400 text-black inline-flex items-center justify-center text-2xl font-bold" aria-label="Page précédente départements">←</a>
                        @else
                            <span class="w-11 h-11 border border-gray-300 rounded bg-yellow-100 text-gray-400 inline-flex items-center justify-center text-2xl">←</span>
                        @endif

                        @if ($departments->nextPageUrl())
                            <a href="{{ $departments->nextPageUrl() }}" class="w-11 h-11 border border-black rounded bg-yellow-400 text-black inline-flex items-center justify-center text-2xl font-bold" aria-label="Page suivante départements">→</a>
                        @else
                            <span class="w-11 h-11 border border-gray-300 rounded bg-yellow-100 text-gray-400 inline-flex items-center justify-center text-2xl">→</span>
                        @endif
                    </div>
                </div>

                <div id="departmentListContainer" class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-black text-left">
                                <th class="py-2">Code</th>
                                <th class="py-2">Nom</th>
                                <th class="py-2">
                                    <div class="inline-flex items-center gap-2">
                                        <span>Active</span>
                                        <button id="departmentActiveBatchSave" type="button" class="hidden border border-black rounded px-2 py-1 text-xs font-semibold bg-yellow-400 text-black">Sauvegarder</button>
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($departments as $department)
                                <tr class="border-b border-gray-200">
                                    <td class="py-2 pr-2 font-semibold">{{ $department->code }}</td>
                                    <td class="py-2 pr-2">{{ $department->name }}</td>
                                    <td class="py-2">
                                        <form method="POST" action="{{ route('admin.departments.update', $department) }}" class="inline-flex items-center js-active-switch-form" data-scope="department" data-entity-id="{{ $department->id }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="is_active" value="{{ $department->is_active ? 1 : 0 }}" class="js-switch-active-value">

                                            <label class="relative inline-flex items-center cursor-pointer" title="{{ $department->is_active ? 'Actif' : 'Inactif' }}">
                                                <input type="checkbox" class="sr-only peer js-active-switch" @checked($department->is_active) aria-label="Activer ou désactiver {{ $department->name }}">
                                                <span class="w-11 h-6 rounded-full bg-red-500 transition-colors duration-200 peer-checked:bg-green-500 after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:w-5 after:h-5 after:bg-white after:rounded-full after:transition-transform after:duration-200 peer-checked:after:translate-x-5"></span>
                                            </label>
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
                        <select id="agencyActive" name="is_active" class="border border-black rounded px-3 py-2">
                            <option value="0" selected>Inactive</option>
                            <option value="1">Active</option>
                        </select>
                        <input id="agencyLat" type="number" step="0.000001" name="lat" placeholder="Lat centre" class="border border-black rounded px-3 py-2" required>
                        <input id="agencyLng" type="number" step="0.000001" name="lng" placeholder="Lng centre" class="border border-black rounded px-3 py-2" required>
                        <div class="md:col-span-2 flex flex-col gap-2">
                            <div class="flex justify-center md:justify-start">
                                <button id="agencySubmit" class="w-52 whitespace-nowrap bg-yellow-400 text-black border border-black rounded px-4 py-2 font-semibold" type="submit">Ajouter agence</button>
                            </div>
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

                    <form id="zoneForm" method="POST" action="{{ route('admin.zones.store') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4">
                        @csrf
                        <input id="zoneRmin" type="number" name="min_radius_km" min="0" step="0.001" placeholder="rmin (km)" class="border border-black rounded px-3 py-2" required>
                        <input id="zoneRmax" type="number" name="max_radius_km" min="0.001" step="0.001" placeholder="rmax (km)" class="border border-black rounded px-3 py-2" required>
                        <div class="flex items-center gap-3 md:col-span-2">
                            <label id="zoneColorSwatch" class="relative w-10 h-10 border border-black rounded bg-yellow-400 flex items-center justify-center cursor-pointer" aria-label="Choisir couleur">
                                <span id="zoneColorIcon" aria-hidden="true" class="inline-flex">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M12 3C7.03 3 3 6.58 3 11C3 13.39 4.18 15.54 6.06 16.95C6.64 17.39 7 18.07 7 18.8V19C7 20.66 8.34 22 10 22H11.2C12.75 22 14 20.75 14 19.2C14 17.98 14.98 17 16.2 17H17C20.31 17 23 14.31 23 11C23 6.58 18.97 3 14 3H12Z" stroke="#111111" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                                        <circle cx="7.5" cy="11" r="1" fill="#111111"/>
                                        <circle cx="10.5" cy="8" r="1" fill="#111111"/>
                                        <circle cx="14" cy="8" r="1" fill="#111111"/>
                                        <circle cx="16.5" cy="11" r="1" fill="#111111"/>
                                    </svg>
                                </span>
                                <input id="zoneColor" type="color" name="color_hex" value="#f7c600" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" aria-label="Choisir couleur">
                            </label>
                            <button id="zoneColorValidate" type="button" class="hidden h-10 px-3 border border-black rounded bg-black text-white text-sm font-semibold" aria-label="Valider couleur">✓</button>
                        </div>
                        <div class="md:col-span-4 flex flex-col items-center md:items-start gap-2">
                            <button id="zoneSubmit" class="w-52 whitespace-nowrap bg-yellow-400 text-black border border-black rounded px-4 py-2 font-semibold text-center" type="submit">Ajouter zone</button>
                            <button id="zoneCancel" class="hidden border border-black rounded w-10 h-10 p-0 text-center shrink-0 inline-flex items-center justify-center" type="button" aria-label="Back" title="Back">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path d="M15 6L9 12L15 18" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
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
                                            <button
                                                type="button"
                                                class="js-color-view w-7 h-7 border border-black rounded"
                                                style="background-color: {{ $zone->color_hex }}"
                                                data-id="{{ $zone->id }}"
                                                data-rmin="{{ number_format($zone->min_radius_m / 1000, 3, '.', '') }}"
                                                data-rmax="{{ number_format($zone->max_radius_m / 1000, 3, '.', '') }}"
                                                data-color="{{ $zone->color_hex }}"
                                                aria-label="Choisir couleur"
                                            ></button>
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
            const cityDelete = document.getElementById('cityDelete');
            const cityName = document.getElementById('cityName');
            const cityPostal = document.getElementById('cityPostal');
            const cityLat = document.getElementById('cityLat');
            const cityLng = document.getElementById('cityLng');
            const cityActive = document.getElementById('cityActive');
            const cityNameSuggestions = document.getElementById('cityNameSuggestions');
            const cityPostalSuggestions = document.getElementById('cityPostalSuggestions');
            const cityListContainer = document.getElementById('cityListContainer');
            const cityListToggle = document.getElementById('cityListToggle');
            const departmentListContainer = document.getElementById('departmentListContainer');
            const departmentListToggle = document.getElementById('departmentListToggle');
            const cityActiveBatchSave = document.getElementById('cityActiveBatchSave');
            const departmentActiveBatchSave = document.getElementById('departmentActiveBatchSave');
            const cityStoreUrl = @json(route('admin.cities.store'));
            const cityUpdateTpl = @json(route('admin.cities.update', '__CITY__'));
            const cityDestroyTpl = @json(route('admin.cities.destroy', '__CITY__'));
            const citySearchUrl = @json(route('admin.cities.search'));
            const cityEditId = @json($cityEditId ?? null);
            const cityListStorageKey = 'admin.cityListVisible';
            const departmentListStorageKey = 'admin.departmentListVisible';
            const nameSuggestionToUrl = new Map();
            const postalSuggestionToUrl = new Map();
            let citySearchTimer = null;

            function postDeleteTo(url) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = url;

                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = @json(csrf_token());

                const methodInput = document.createElement('input');
                methodInput.type = 'hidden';
                methodInput.name = '_method';
                methodInput.value = 'DELETE';

                form.appendChild(csrfInput);
                form.appendChild(methodInput);
                document.body.appendChild(form);
                form.submit();
            }

            function applyCitySuggestionSelection(inputValue, sourceMap) {
                const targetUrl = sourceMap.get(inputValue);
                if (!targetUrl) return;
                window.location.href = targetUrl;
            }

            async function loadCitySuggestions(rawTerm) {
                const term = (rawTerm || '').trim();
                if (term.length < 1) {
                    cityNameSuggestions.innerHTML = '';
                    cityPostalSuggestions.innerHTML = '';
                    nameSuggestionToUrl.clear();
                    postalSuggestionToUrl.clear();
                    return;
                }

                const response = await fetch(`${citySearchUrl}?q=${encodeURIComponent(term)}`, {
                    headers: { Accept: 'application/json' },
                });

                if (!response.ok) {
                    return;
                }

                const payload = await response.json();
                const items = payload?.data || [];

                cityNameSuggestions.innerHTML = '';
                cityPostalSuggestions.innerHTML = '';
                nameSuggestionToUrl.clear();
                postalSuggestionToUrl.clear();

                items.forEach((city) => {
                    const nameLabel = `${city.name} (${city.postal_code})`;
                    const postalLabel = `${city.postal_code} - ${city.name}`;

                    const nameOption = document.createElement('option');
                    nameOption.value = nameLabel;
                    cityNameSuggestions.appendChild(nameOption);
                    nameSuggestionToUrl.set(nameLabel, city.edit_url);

                    const postalOption = document.createElement('option');
                    postalOption.value = postalLabel;
                    cityPostalSuggestions.appendChild(postalOption);
                    postalSuggestionToUrl.set(postalLabel, city.edit_url);
                });
            }

            function setCityListVisible(visible) {
                cityListContainer.classList.toggle('hidden', !visible);
                cityListToggle.textContent = visible ? 'Masquer liste' : 'Afficher liste';
                cityListToggle.setAttribute('aria-expanded', visible ? 'true' : 'false');
                localStorage.setItem(cityListStorageKey, visible ? '1' : '0');
            }

            function setDepartmentListVisible(visible) {
                departmentListContainer.classList.toggle('hidden', !visible);
                departmentListToggle.textContent = visible ? 'Masquer liste' : 'Afficher liste';
                departmentListToggle.setAttribute('aria-expanded', visible ? 'true' : 'false');
                localStorage.setItem(departmentListStorageKey, visible ? '1' : '0');
            }

            function resetCityForm() {
                cityForm.action = cityStoreUrl;
                cityMethod.value = '';
                citySubmit.textContent = 'Ajouter ville';
                cityDelete.classList.add('hidden');
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
                    cityDelete.classList.remove('hidden');
                    cityDelete.dataset.deleteUrl = cityDestroyTpl.replace('__CITY__', id);
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

            cityDelete.addEventListener('click', () => {
                const deleteUrl = cityDelete.dataset.deleteUrl;
                if (!deleteUrl) return;
                if (!window.confirm('Supprimer cette ville ?')) return;
                postDeleteTo(deleteUrl);
            });

            cityName.addEventListener('input', () => {
                clearTimeout(citySearchTimer);
                citySearchTimer = setTimeout(() => loadCitySuggestions(cityName.value), 180);
            });

            cityPostal.addEventListener('input', () => {
                clearTimeout(citySearchTimer);
                citySearchTimer = setTimeout(() => loadCitySuggestions(cityPostal.value), 180);
            });

            cityName.addEventListener('change', () => {
                applyCitySuggestionSelection(cityName.value, nameSuggestionToUrl);
            });

            cityPostal.addEventListener('change', () => {
                applyCitySuggestionSelection(cityPostal.value, postalSuggestionToUrl);
            });

            const savedCityListVisible = localStorage.getItem(cityListStorageKey);
            setCityListVisible(savedCityListVisible === null ? true : savedCityListVisible === '1');
            cityListToggle.addEventListener('click', () => {
                const isHidden = cityListContainer.classList.contains('hidden');
                setCityListVisible(isHidden);
            });

            const savedDepartmentListVisible = localStorage.getItem(departmentListStorageKey);
            setDepartmentListVisible(savedDepartmentListVisible === null ? true : savedDepartmentListVisible === '1');
            departmentListToggle.addEventListener('click', () => {
                const isHidden = departmentListContainer.classList.contains('hidden');
                setDepartmentListVisible(isHidden);
            });

            if (cityEditId) {
                const editButton = document.querySelector(`.js-edit-city[data-id="${cityEditId}"]`);
                if (editButton) {
                    editButton.click();
                }
            }

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
                agencySubmit.textContent = 'Ajouter agence';
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
            const zoneColorValidate = document.getElementById('zoneColorValidate');
            const zoneStoreUrl = @json(route('admin.zones.store'));
            const zoneUpdateTpl = @json(route('admin.zones.update', '__ZONE__'));
            const defaultZoneColor = '#f7c600';

            function syncZoneSwatch() {
                zoneColorSwatch.style.backgroundColor = zoneColor.value;
                const isDefault = (zoneColor.value || '').toLowerCase() === defaultZoneColor;
                zoneColorIcon.classList.toggle('hidden', !isDefault);
            }

            function clearZoneColorPending() {
                zoneColorValidate.classList.add('hidden');
                zoneColorSwatch.classList.remove('ring-2', 'ring-black');
            }

            function resetZoneForm() {
                zoneForm.action = zoneStoreUrl;
                zoneMethod.value = '';
                zoneSubmit.textContent = 'Ajouter zone';
                zoneCancel.classList.add('hidden');
                zoneForm.reset();
                zoneColor.value = defaultZoneColor;
                syncZoneSwatch();
                clearZoneColorPending();
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
                    clearZoneColorPending();
                    window.scrollTo({ top: zoneForm.offsetTop - 80, behavior: 'smooth' });
                });
            });

            document.querySelectorAll('.js-color-view').forEach((button) => {
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
                    clearZoneColorPending();
                    window.scrollTo({ top: zoneForm.offsetTop - 80, behavior: 'smooth' });

                    if (typeof zoneColor.showPicker === 'function') {
                        zoneColor.showPicker();
                    } else {
                        zoneColor.click();
                    }
                });
            });

            zoneColor.addEventListener('input', () => {
                syncZoneSwatch();
                zoneColorValidate.classList.remove('hidden');
                zoneColorSwatch.classList.add('ring-2', 'ring-black');
            });

            zoneColorValidate.addEventListener('click', () => {
                if (zoneMethod.value === 'PATCH') {
                    zoneForm.requestSubmit();
                    return;
                }

                clearZoneColorPending();
            });
            zoneCancel.addEventListener('click', resetZoneForm);

            function updateBatchSaveVisibility(scope) {
                const hasPending = Array.from(document.querySelectorAll(`.js-active-switch-form[data-scope="${scope}"]`))
                    .some((form) => form.dataset.dirty === '1');

                if (scope === 'city' && cityActiveBatchSave) {
                    cityActiveBatchSave.classList.toggle('hidden', !hasPending);
                }

                if (scope === 'department' && departmentActiveBatchSave) {
                    departmentActiveBatchSave.classList.toggle('hidden', !hasPending);
                }
            }

            async function submitPendingScope(scope, triggerButton) {
                const pendingForms = Array.from(document.querySelectorAll(`.js-active-switch-form[data-scope="${scope}"][data-dirty="1"]`));
                if (!pendingForms.length) return;

                if (triggerButton) {
                    triggerButton.disabled = true;
                    triggerButton.textContent = '...';
                }

                let hasError = false;

                for (const form of pendingForms) {
                    const payload = new FormData(form);

                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: payload,
                        });

                        if (!response.ok) {
                            hasError = true;
                            continue;
                        }

                        form.dataset.dirty = '0';
                        const currentValue = form.querySelector('.js-switch-active-value')?.value;
                        const editBtn = form.closest('tr')?.querySelector('.js-edit-city');
                        if (editBtn && currentValue !== undefined) {
                            editBtn.dataset.active = currentValue;
                        }
                    } catch {
                        hasError = true;
                    }
                }

                updateBatchSaveVisibility(scope);

                if (triggerButton) {
                    triggerButton.disabled = false;
                    triggerButton.textContent = 'Sauvegarder';
                }

                if (hasError) {
                    window.alert('Certaines mises à jour ont échoué.');
                }
            }

            document.querySelectorAll('.js-active-switch').forEach((toggle) => {
                toggle.addEventListener('change', () => {
                    const form = toggle.closest('.js-active-switch-form');
                    if (!form) return;

                    const hiddenActive = form.querySelector('.js-switch-active-value');
                    if (!hiddenActive) return;

                    hiddenActive.value = toggle.checked ? '1' : '0';
                    form.dataset.dirty = '1';

                    const scope = form.dataset.scope;
                    if (scope) {
                        updateBatchSaveVisibility(scope);
                    }
                });
            });

            if (cityActiveBatchSave) {
                cityActiveBatchSave.addEventListener('click', () => submitPendingScope('city', cityActiveBatchSave));
            }

            if (departmentActiveBatchSave) {
                departmentActiveBatchSave.addEventListener('click', () => submitPendingScope('department', departmentActiveBatchSave));
            }

            syncZoneSwatch();
            clearZoneColorPending();
        })();
    </script>
</x-app-layout>
