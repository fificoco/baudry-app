const bootstrap = window.__MAP_BOOTSTRAP || { isAdmin: false, hasActiveAgency: false, routes: {} };

const agencySelect = document.getElementById('agency');
const villeInput = document.getElementById('villeInput');
const cpInput = document.getElementById('cpInput');
const adresseInput = document.getElementById('adresseInput');
const resultZone = document.getElementById('result-zone');
const correctionStatus = document.getElementById('correctionStatus');

const validerBtn = document.getElementById('validerBtn');
const resetBtn = document.getElementById('resetBtn');
const zoneBtn = document.getElementById('zoneBtn');
const websiteBtn = document.getElementById('websiteBtn');
const itineraireBtn = document.getElementById('itineraireBtn');
const burgerBtn = document.getElementById('burgerBtn');
const sidebar = document.getElementById('sidebar');
const appHeader = document.querySelector('.app-header');

const toggleCorrectionBtn = document.getElementById('toggleCorrectionBtn');
const saveCorrectionBtn = document.getElementById('saveCorrectionBtn');
const addCityBtn = document.getElementById('addCityBtn');

let userMarker = null;
let agenceMarker = null;
let correctionPreviewMarker = null;
let rings = [];
let zonesActives = false;
let zoneButtonAllVisible = false;
let correctionMode = false;
let addGpsMode = false;
let addCityMode = false;
let currentCity = null;
let rayonAdresse = null;
let statusClearTimer = null;

const map = L.map('map', {
  zoomControl: false,
  attributionControl: false,
});

L.control.zoom({ position: 'bottomright' }).addTo(map);
L.control.attribution({ position: 'bottomleft' }).addTo(map).setPrefix('© Appli BAUDRY');

const tileStyles = {
  light: {
    url: 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png',
    options: {
      attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
      subdomains: 'abcd',
      maxZoom: 20,
    },
  },
  dark: {
    url: 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',
    options: {
      attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
      subdomains: 'abcd',
      maxZoom: 20,
    },
  },
  osm: {
    url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
    options: {
      attribution: '&copy; OpenStreetMap contributors',
      maxZoom: 20,
    },
  },
  voyager: {
    url: 'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png',
    options: {
      attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
      subdomains: 'abcd',
      maxZoom: 20,
    },
  },
};

const selectedStyle = tileStyles[bootstrap.mapStyle] ? bootstrap.mapStyle : 'light';
L.tileLayer(tileStyles[selectedStyle].url, tileStyles[selectedStyle].options).addTo(map);

function selectedAgency() {
  if (!agencySelect || !agencySelect.options || agencySelect.options.length === 0) {
    return null;
  }

  const option = agencySelect.options[agencySelect.selectedIndex];
  const [lat, lng] = option.value.split(',').map(Number);
  let zones = [];
  try {
    zones = JSON.parse(option.dataset.zones || '[]');
  } catch {
    zones = [];
  }

  return {
    id: Number(option.dataset.id),
    name: option.dataset.name,
    center: [lat, lng],
    zones,
  };
}

function normalizeStr(value) {
  return (value || '')
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[-'\u2019]/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();
}

function getDistance(lat1, lon1, lat2, lon2) {
  const R = 6371000;
  const dLat = (lat2 - lat1) * Math.PI / 180;
  const dLon = (lon2 - lon1) * Math.PI / 180;
  const a = Math.sin(dLat / 2) ** 2 +
    Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
    Math.sin(dLon / 2) ** 2;
  return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

function setStatus(message, isError = false) {
  if (!correctionStatus) return;
  if (statusClearTimer) {
    clearTimeout(statusClearTimer);
    statusClearTimer = null;
  }

  correctionStatus.textContent = message;
  correctionStatus.classList.remove('status-error', 'status-success', 'status-info');

  if (isError || /^⚠️|^❌/.test(message || '')) {
    correctionStatus.classList.add('status-error');
  } else if (/^✅/.test(message || '')) {
    correctionStatus.classList.add('status-success');
  } else if (/^✏️/.test(message || '')) {
    correctionStatus.classList.add('status-info');
  } else {
    correctionStatus.classList.add('status-info');
  }

  statusClearTimer = window.setTimeout(() => {
    correctionStatus.textContent = '';
    correctionStatus.classList.remove('status-error', 'status-success', 'status-info');
    statusClearTimer = null;
  }, 3000);
}

function syncAddGpsButtonState() {
  if (toggleCorrectionBtn) {
    toggleCorrectionBtn.classList.toggle('gps-active', addGpsMode || addCityMode);
  }

  if (addCityBtn) {
    addCityBtn.classList.toggle('gps-active', addCityMode);
  }
}

function syncCorrectionModeState() {
  correctionMode = addGpsMode || addCityMode;
  map.getContainer().style.cursor = correctionMode ? 'crosshair' : '';
  syncAddGpsButtonState();
}

async function createCityFromMarker() {
  const { ville, code } = parseVilleCodeInputs();
  if (!ville || !code) {
    setStatus('⚠️ Ville + Postal obligatoires avant création.', true);
    return;
  }

  const coords = correctionPreviewMarker
    ? correctionPreviewMarker.getLatLng()
    : userMarker
      ? userMarker.getLatLng()
      : null;

  if (!coords) {
    setStatus('⚠️ Place un repère sur la carte avant de valider.', true);
    return;
  }

  let existingCity = null;
  try {
    existingCity = await findCityByInputs(ville, code);
  } catch {
    setStatus('❌ Erreur de vérification des doublons.', true);
    return;
  }

  if (existingCity) {
    setStatus('⚠️ Cette ville existe déjà avec ce code postal. Aucun doublon créé.', true);
    return;
  }

  const response = await fetch(bootstrap.routes.cityStore, {
    method: 'POST',
    headers: csrfHeaders(),
    body: JSON.stringify({
      name: ville,
      postal_code: code,
      lat: coords.lat,
      lng: coords.lng,
      is_active: true,
    }),
  });

  if (!response.ok) {
    const payload = await response.json().catch(() => null);
    setStatus(`❌ ${payload?.message || 'Échec de création de la ville.'}`, true);
    return;
  }

  const payload = await response.json();
  currentCity = payload.data;

  villeInput.value = currentCity.name;
  cpInput.value = currentCity.postal_code;
  setUserMarker(currentCity.lat, currentCity.lng, `${currentCity.name} (new)`);

  const agency = selectedAgency();
  rayonAdresse = getDistance(agency.center[0], agency.center[1], currentCity.lat, currentCity.lng);
  updateZoneText(rayonAdresse);

  if (correctionPreviewMarker) {
    map.removeLayer(correctionPreviewMarker);
    correctionPreviewMarker = null;
  }

  addCityMode = false;
  addGpsMode = false;
  syncCorrectionModeState();
  setStatus(`✅ Nouvelle ville créée : ${currentCity.name} (${currentCity.postal_code}).`);
}

function updateAgenceMarker() {
  const agency = selectedAgency();
  if (!agency) return;
  if (agenceMarker) map.removeLayer(agenceMarker);
  agenceMarker = L.marker(agency.center, {
    icon: L.icon({
      iconUrl: 'https://maps.gstatic.com/mapfiles/ms2/micons/blue-dot.png',
      iconSize: [32, 32],
    }),
  }).addTo(map).bindPopup(`<b>${agency.name}</b>`);
  map.setView(agency.center, 12);
}

function clearZones() {
  rings.forEach((layer) => map.removeLayer(layer));
  rings = [];
}

function drawZoneEvidee(center, rMin, rMax, color) {
  const steps = 64;
  const latlngsOuter = [];
  const latlngsInner = [];

  const toLatLng = (angle, radius) => {
    const dx = radius * Math.cos(angle);
    const dy = radius * Math.sin(angle);
    const lat = center[0] + (180 / Math.PI) * (dy / 6378137);
    const lng = center[1] + (180 / Math.PI) * (dx / 6378137) / Math.cos(center[0] * Math.PI / 180);
    return [lat, lng];
  };

  for (let i = 0; i < steps; i += 1) {
    const angle = 2 * Math.PI * i / steps;
    latlngsOuter.push(toLatLng(angle, rMax));
    latlngsInner.unshift(toLatLng(angle, rMin));
  }

  const poly = L.polygon([latlngsOuter, latlngsInner], {
    color: '#222',
    fillColor: color || '#cccccc',
    fillOpacity: 0.22,
    weight: 0.7,
    opacity: 0.35,
  }).addTo(map);

  rings.push(poly);
}

function drawZones({ all = false, distance = null } = {}) {
  const agency = selectedAgency();
  if (!agency) return;
  clearZones();
  if (!zonesActives) return;

  const zones = [...agency.zones].sort((a, b) => Number(a.rmin) - Number(b.rmin));

  zones.forEach((zone) => {
    if (all || (distance !== null && distance >= Number(zone.rmin))) {
      drawZoneEvidee(agency.center, Number(zone.rmin), Number(zone.rmax), zone.color);
    }
  });
}

function focusAgencyAndAllZones() {
  const agency = selectedAgency();
  if (!agency) return;

  if (rings.length > 0) {
    const bounds = L.featureGroup(rings).getBounds();
    if (bounds.isValid()) {
      map.fitBounds(bounds, { padding: [32, 32] });
      return;
    }
  }

  map.setView(agency.center, 12);
}

function updateZoneText(distance) {
  const agency = selectedAgency();
  if (!agency) return;
  const zones = [...agency.zones].sort((a, b) => Number(a.rmin) - Number(b.rmin));
  const maxRmax = Math.max(...zones.map((z) => Number(z.rmax)), 0);
  const emojis = ['🟢', '🟡', '🟠', '🔴', '🟣', '🟤', '⚫'];

  zoneButtonAllVisible = false;
  zonesActives = true;

  if (distance > maxRmax) {
    const km = Math.round(distance / 1000);
    resultZone.className = 'resultZone-hors-zone';
    resultZone.textContent = `Hors zone de livraison (${km} km)`;

    drawZones({ all: true, distance: null });
    return;
  }

  resultZone.className = 'resultZone-default';
  for (let i = 0; i < zones.length; i += 1) {
    if (distance <= Number(zones[i].rmax)) {
      const minKm = Math.round(Number(zones[i].rmin) / 1000);
      const maxKm = Math.round(Number(zones[i].rmax) / 1000);
      resultZone.textContent = `${emojis[i] || '🔵'} Zone ${i + 1} (${minKm}–${maxKm} km)`;
      break;
    }
  }

  drawZones({ all: false, distance });
}

function getZoneShortLabel(distance) {
  const agency = selectedAgency();
  if (!agency || distance === null || distance === undefined) return '';

  const zones = [...agency.zones].sort((a, b) => Number(a.rmin) - Number(b.rmin));
  const maxRmax = Math.max(...zones.map((z) => Number(z.rmax)), 0);

  if (distance > maxRmax) {
    return 'Hors zone';
  }

  for (let i = 0; i < zones.length; i += 1) {
    if (distance <= Number(zones[i].rmax)) {
      return `Zone (${i + 1})`;
    }
  }

  return '';
}

function syncUserMarkerPopupWithZone(baseLabel) {
  if (!userMarker) return;

  const zoneLabel = getZoneShortLabel(rayonAdresse);
  if (!zoneLabel) {
    userMarker.setPopupContent(baseLabel).openPopup();
    return;
  }

  userMarker.setPopupContent(`${baseLabel}<br>${zoneLabel}`).openPopup();
}

function parseVilleCodeInputs() {
  let ville = (villeInput.value || '').trim();
  let code = (cpInput.value || '').trim();

  const fromVillePattern = ville.match(/^(.+?)\s*\((\d{4,5})\)$/);
  if (fromVillePattern) {
    ville = fromVillePattern[1].trim();
    if (!code) code = fromVillePattern[2].trim();
  }

  const fromCpPattern = code.match(/^(\d{4,5})\s*-\s*(.+)$/);
  if (fromCpPattern) {
    code = fromCpPattern[1].trim();
    if (!ville) ville = fromCpPattern[2].trim();
  }

  return { ville, code };
}

async function searchCities(search = '', postalCode = '') {
  const params = new URLSearchParams();
  if (search) params.set('search', search);
  if (postalCode) params.set('postal_code', postalCode);
  params.set('per_page', '20');

  const response = await fetch(`${bootstrap.routes.citiesSearch}?${params.toString()}`);
  if (!response.ok) throw new Error('Erreur chargement villes');
  const payload = await response.json();
  return payload.data || [];
}

async function findCityByInputs(ville, code) {
  const cities = await searchCities(ville, code);
  return cities.find(
    (city) => normalizeStr(city.name) === normalizeStr(ville) && String(city.postal_code) === String(code),
  ) || null;
}

function setUserMarker(lat, lng, popup) {
  if (userMarker) map.removeLayer(userMarker);
  userMarker = L.marker([lat, lng], {
    icon: L.icon({
      iconUrl: 'https://maps.gstatic.com/mapfiles/ms2/micons/red.png',
      iconSize: [32, 32],
    }),
  }).addTo(map).bindPopup(popup).openPopup();

  const targetZoom = 12;
  map.setView([lat, lng], targetZoom, { animate: true });
}

function csrfHeaders() {
  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  return {
    'Content-Type': 'application/json',
    Accept: 'application/json',
    'X-CSRF-TOKEN': token || '',
  };
}

async function validateSearch() {
  if (!bootstrap.hasActiveAgency) {
    resultZone.className = 'resultZone-hors-zone';
    resultZone.textContent = '❌ Aucune agence active configurée.';
    return;
  }

  const { ville, code } = parseVilleCodeInputs();
  const adresse = (adresseInput.value || '').trim();
  const agency = selectedAgency();

  currentCity = null;

  try {
    if (adresse) {
      const fullAddress = `${adresse}, ${ville} ${code}`.trim();
      const res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(fullAddress)}`);
      const data = await res.json();
      if (!data || data.length === 0) {
        resultZone.className = 'resultZone-hors-zone';
        resultZone.textContent = '❌ Adresse introuvable.';
        return;
      }

      const lat = parseFloat(data[0].lat);
      const lng = parseFloat(data[0].lon);
      setUserMarker(lat, lng, `Destination : ${fullAddress}`);
      rayonAdresse = getDistance(agency.center[0], agency.center[1], lat, lng);
      updateZoneText(rayonAdresse);
      syncUserMarkerPopupWithZone(`Destination : ${fullAddress}`);
      closeSidebarAfterValidationOnMobile();
      return;
    }

    const city = await findCityByInputs(ville, code);
    if (!city) {
      resultZone.className = 'resultZone-hors-zone';
      resultZone.textContent = '❌ Ville + code postal non trouvé.';
      return;
    }

    currentCity = city;
    villeInput.value = city.name;
    cpInput.value = city.postal_code;

    setUserMarker(city.lat, city.lng, city.name);
    rayonAdresse = getDistance(agency.center[0], agency.center[1], city.lat, city.lng);
    updateZoneText(rayonAdresse);
    syncUserMarkerPopupWithZone(city.name);
    closeSidebarAfterValidationOnMobile();
  } catch {
    resultZone.className = 'resultZone-hors-zone';
    resultZone.textContent = '❌ Erreur de recherche.';
  }
}

if (toggleCorrectionBtn) {
  toggleCorrectionBtn.addEventListener('click', async () => {
    const { ville, code } = parseVilleCodeInputs();
    if (!ville || !code) {
      setStatus('⚠️ Renseigne d\'abord Ville + Postal.', true);
      return;
    }

    try {
      const city = await findCityByInputs(ville, code);
      currentCity = city || null;
    } catch {
      setStatus('⚠️ Impossible de vérifier la ville pour le moment.', true);
      return;
    }

    addCityMode = false;
    addGpsMode = !addGpsMode;
    syncCorrectionModeState();
    setStatus(addGpsMode
      ? (currentCity
        ? `✏️ Ville existante détectée (${currentCity.name}) : place le repère puis Save-GPS.`
        : '➕ Nouvelle ville : place le repère puis Save-GPS.')
      : 'Mode Add-GPS désactivé.');
  });
}

if (addCityBtn) {
  addCityBtn.addEventListener('click', () => {
    addCityMode = !addCityMode;

    if (addCityMode) {
      currentCity = null;
      addGpsMode = true;
      syncCorrectionModeState();
      setStatus('➕ Mode Add-City activé : saisis Ville + Postal, place un repère, puis Valider.');
      return;
    }

    addGpsMode = false;
    syncCorrectionModeState();
    setStatus('Mode Add-City désactivé.');
  });
}

if (saveCorrectionBtn) {
  saveCorrectionBtn.addEventListener('click', async () => {
    const { ville, code } = parseVilleCodeInputs();
    if (!ville || !code) {
      setStatus('⚠️ Ville + Postal obligatoires avant Save-GPS.', true);
      return;
    }

    const coords = correctionPreviewMarker
      ? correctionPreviewMarker.getLatLng()
      : userMarker
        ? userMarker.getLatLng()
        : null;

    if (!coords) {
      setStatus('⚠️ Place un repère sur la carte avant Save-GPS.', true);
      return;
    }

    let existingCityFromInputs = null;
    try {
      existingCityFromInputs = await findCityByInputs(ville, code);
    } catch {
      setStatus('❌ Erreur de vérification des doublons.', true);
      return;
    }

    const shouldUpdateExisting = Boolean(
      currentCity
      && existingCityFromInputs
      && Number(existingCityFromInputs.id) === Number(currentCity.id),
    );

    if (!shouldUpdateExisting && existingCityFromInputs) {
      currentCity = existingCityFromInputs;
      villeInput.value = existingCityFromInputs.name;
      cpInput.value = existingCityFromInputs.postal_code;
      setUserMarker(existingCityFromInputs.lat, existingCityFromInputs.lng, `${existingCityFromInputs.name} (existe déjà)`);
      setStatus('⚠️ Cette ville existe déjà avec ce code postal. Aucun doublon créé.', true);
      return;
    }

    if (shouldUpdateExisting) {
      const url = bootstrap.routes.cityCoordinatesTemplate.replace('__CITY__', currentCity.id);

      const response = await fetch(url, {
        method: 'PATCH',
        headers: csrfHeaders(),
        body: JSON.stringify({ lat: coords.lat, lng: coords.lng, reason: 'Save map admin' }),
      });

      if (!response.ok) {
        setStatus('❌ Échec de la sauvegarde GPS.', true);
        return;
      }

      const payload = await response.json();
      currentCity = payload.data;

      villeInput.value = currentCity.name;
      cpInput.value = currentCity.postal_code;
      setUserMarker(currentCity.lat, currentCity.lng, `${currentCity.name} (saved)`);

      const agency = selectedAgency();
      rayonAdresse = getDistance(agency.center[0], agency.center[1], currentCity.lat, currentCity.lng);
      updateZoneText(rayonAdresse);

      addCityMode = false;
      addGpsMode = false;
      syncCorrectionModeState();
      setStatus(`✅ Ville mise à jour : ${currentCity.name}.`);
      return;
    }

    const response = await fetch(bootstrap.routes.cityStore, {
      method: 'POST',
      headers: csrfHeaders(),
      body: JSON.stringify({
        name: ville,
        postal_code: code,
        lat: coords.lat,
        lng: coords.lng,
        is_active: true,
      }),
    });

    if (!response.ok) {
      const payload = await response.json().catch(() => null);
      setStatus(`❌ ${payload?.message || 'Échec de création de la ville.'}`, true);
      return;
    }

    const payload = await response.json();
    currentCity = payload.data;

    villeInput.value = currentCity.name;
    cpInput.value = currentCity.postal_code;
    setUserMarker(currentCity.lat, currentCity.lng, `${currentCity.name} (new)`);

    const agency = selectedAgency();
    rayonAdresse = getDistance(agency.center[0], agency.center[1], currentCity.lat, currentCity.lng);
    updateZoneText(rayonAdresse);

    addCityMode = false;
    addGpsMode = false;
    syncCorrectionModeState();
    setStatus(`✅ Nouvelle ville créée : ${currentCity.name} (${currentCity.postal_code}).`);
  });
}

map.on('click', (event) => {
  if (!correctionMode || !bootstrap.isAdmin) return;

  if (correctionPreviewMarker) map.removeLayer(correctionPreviewMarker);

  correctionPreviewMarker = L.marker([event.latlng.lat, event.latlng.lng], {
    icon: L.icon({
      iconUrl: 'https://maps.gstatic.com/mapfiles/ms2/micons/orange-dot.png',
      iconSize: [32, 32],
    }),
  }).addTo(map).bindPopup(`Nouveau point: ${event.latlng.lat.toFixed(6)}, ${event.latlng.lng.toFixed(6)}`).openPopup();

});

validerBtn.addEventListener('click', async () => {
  if (addCityMode) {
    await createCityFromMarker();
    return;
  }

  await validateSearch();
});
resetBtn.addEventListener('click', () => {
  villeInput.value = '';
  cpInput.value = '';
  adresseInput.value = '';
  resultZone.textContent = '';
  resultZone.className = 'resultZone-default';

  if (userMarker) {
    map.removeLayer(userMarker);
    userMarker = null;
  }
  if (correctionPreviewMarker) {
    map.removeLayer(correctionPreviewMarker);
    correctionPreviewMarker = null;
  }

  currentCity = null;
  rayonAdresse = null;

  addCityMode = false;
  addGpsMode = false;
  syncCorrectionModeState();

  zoneButtonAllVisible = false;
  zonesActives = false;
  clearZones();
  updateAgenceMarker();
  setStatus('Réinitialisé.');
});

zoneBtn.addEventListener('click', () => {
  if (!bootstrap.hasActiveAgency) return;
  zoneButtonAllVisible = !zoneButtonAllVisible;

  if (!zoneButtonAllVisible) {
    zonesActives = false;
    clearZones();
    if (!userMarker) {
      updateAgenceMarker();
    }
    return;
  }

  zonesActives = true;
  drawZones({ all: true, distance: null });
  focusAgencyAndAllZones();
});

websiteBtn.addEventListener('click', () => {
  window.open('https://www.baudry-sa.com', '_blank');
});

itineraireBtn.addEventListener('click', () => {
  if (!userMarker || !agenceMarker) {
    window.alert('Veuillez d’abord valider une adresse client.');
    return;
  }
  const { lat: lat1, lng: lng1 } = agenceMarker.getLatLng();
  const { lat: lat2, lng: lng2 } = userMarker.getLatLng();
  const url = `https://www.google.com/maps/dir/?api=1&origin=${lat1},${lng1}&destination=${lat2},${lng2}&travelmode=driving`;
  window.open(url, '_blank');
});

if (agencySelect) {
  agencySelect.addEventListener('change', () => {
    updateAgenceMarker();
    if (zonesActives && rayonAdresse !== null) {
      updateZoneText(rayonAdresse);
    }
  });
}

function syncMobileSidebarOffset() {
  if (!appHeader) return;

  if (window.innerWidth <= 500) {
    const headerBottom = Math.ceil(appHeader.getBoundingClientRect().bottom);
    const offset = Math.max(headerBottom + 8, 120);
    document.documentElement.style.setProperty('--mobile-header-offset', `${offset}px`);
    return;
  }

  document.documentElement.style.removeProperty('--mobile-header-offset');
}

function closeSidebarAfterValidationOnMobile() {
  if (window.innerWidth > 500) return;
  if (!sidebar) return;
  sidebar.classList.remove('open');

  window.setTimeout(() => {
    map.invalidateSize();
    if (!userMarker) return;
    map.setView(userMarker.getLatLng(), map.getZoom(), { animate: true });
  }, 160);
}

burgerBtn.addEventListener('click', () => {
  syncMobileSidebarOffset();
  sidebar.classList.toggle('open');
});

let villeTimer = null;
const villeDatalist = document.getElementById('suggestions-ville');
const cpDatalist = document.getElementById('suggestions-cp');
let villeSuggestionRequestId = 0;
let cpSuggestionRequestId = 0;

function showInputSuggestions(input, listId) {
  input.setAttribute('list', listId);
}

function hideInputSuggestions(input, datalist) {
  input.removeAttribute('list');
  if (datalist) datalist.innerHTML = '';
}

async function loadVilleSuggestions() {
  if (!villeDatalist) return;
  const requestId = ++villeSuggestionRequestId;
  villeDatalist.innerHTML = '';
  if (!villeInput.value.trim()) return;

  try {
    const cities = await searchCities(villeInput.value.trim(), '');
    if (requestId !== villeSuggestionRequestId) return;

    const seen = new Set();
    cities.slice(0, 15).forEach((city) => {
      const label = `${city.name} (${city.postal_code})`;
      if (seen.has(label)) return;
      seen.add(label);
      const option = document.createElement('option');
      option.value = label;
      villeDatalist.appendChild(option);
    });
  } catch {
    if (requestId === villeSuggestionRequestId) {
      villeDatalist.innerHTML = '';
    }
  }
}

async function loadCpSuggestions() {
  if (!cpDatalist) return;
  const requestId = ++cpSuggestionRequestId;
  cpDatalist.innerHTML = '';
  if (!cpInput.value.trim()) return;

  try {
    const cities = await searchCities('', cpInput.value.trim());
    if (requestId !== cpSuggestionRequestId) return;

    const seen = new Set();
    cities.slice(0, 15).forEach((city) => {
      const label = `${city.postal_code} - ${city.name}`;
      if (seen.has(label)) return;
      seen.add(label);
      const option = document.createElement('option');
      option.value = label;
      cpDatalist.appendChild(option);
    });
  } catch {
    if (requestId === cpSuggestionRequestId) {
      cpDatalist.innerHTML = '';
    }
  }
}

villeInput.addEventListener('input', () => {
  showInputSuggestions(villeInput, 'suggestions-ville');
  clearTimeout(villeTimer);
  villeTimer = setTimeout(loadVilleSuggestions, 180);
});

let cpTimer = null;
cpInput.addEventListener('input', () => {
  showInputSuggestions(cpInput, 'suggestions-cp');
  clearTimeout(cpTimer);
  cpTimer = setTimeout(loadCpSuggestions, 180);
});

villeInput.addEventListener('focus', () => {
  showInputSuggestions(villeInput, 'suggestions-ville');
  loadVilleSuggestions();
});

villeInput.addEventListener('click', () => {
  showInputSuggestions(villeInput, 'suggestions-ville');
  loadVilleSuggestions();
});

cpInput.addEventListener('focus', () => {
  showInputSuggestions(cpInput, 'suggestions-cp');
  loadCpSuggestions();
});

cpInput.addEventListener('click', () => {
  showInputSuggestions(cpInput, 'suggestions-cp');
  loadCpSuggestions();
});

villeInput.addEventListener('change', () => {
  const match = villeInput.value.match(/^(.+?)\s*\((\d{4,5})\)$/);
  if (match) {
    villeInput.value = match[1].trim();
    cpInput.value = match[2].trim();
  }
  hideInputSuggestions(villeInput, villeDatalist);
});

cpInput.addEventListener('change', () => {
  const match = cpInput.value.match(/^(\d{4,5})\s*-\s*(.+)$/);
  if (match) {
    cpInput.value = match[1].trim();
    villeInput.value = match[2].trim();
  }
  hideInputSuggestions(cpInput, cpDatalist);
});

villeInput.addEventListener('blur', () => {
  hideInputSuggestions(villeInput, villeDatalist);
});

cpInput.addEventListener('blur', () => {
  hideInputSuggestions(cpInput, cpDatalist);
});

window.addEventListener('load', () => {
  syncMobileSidebarOffset();
  syncCorrectionModeState();

  if (bootstrap.hasActiveAgency) {
    updateAgenceMarker();
  } else {
    map.setView([46.603354, 1.888334], 6);
    resultZone.className = 'resultZone-hors-zone';
    resultZone.textContent = '❌ Aucune agence active configurée.';
  }
  setTimeout(() => map.invalidateSize(), 180);
});

window.addEventListener('resize', () => {
  syncMobileSidebarOffset();
  map.invalidateSize();
});
