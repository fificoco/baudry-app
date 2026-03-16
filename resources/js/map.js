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
const mobileZoneBar = document.getElementById('mobile-zone-bar');
const mobileZoneText = document.getElementById('mobile-zone-text');
const mobileResetBtn = document.getElementById('mobileResetBtn');
const cityCpRow = document.querySelector('.city-cp-row');
const cpField = document.getElementById('cpField');

const toggleCorrectionBtn = document.getElementById('toggleCorrectionBtn');
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
let validateButtonMode = 'validate';
let currentCity = null;
let rayonAdresse = null;
let statusClearTimer = null;
let userPopupAutoCloseTimer = null;

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

function formatCityDisplayName(value) {
  return (value || '')
    .replace(/-/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();
}

function getViewportType() {
  const width = window.innerWidth || document.documentElement.clientWidth || 0;
  if (width <= 500) return 'mobile';
  if (width <= 1024) return 'tablet';
  return 'desktop';
}

function getResponsiveZoom(kind = 'city') {
  const viewport = getViewportType();

  const configured = bootstrap.mapZooms || {};
  const readZoom = (key, fallback) => {
    const raw = Number(configured[key]);
    if (!Number.isFinite(raw)) return fallback;
    return Math.min(20, Math.max(3, raw));
  };

  const zoomByKind = {
    city: {
      mobile: readZoom('map_zoom_city_mobile', 11),
      tablet: readZoom('map_zoom_city_tablet', 12),
      desktop: readZoom('map_zoom_city_desktop', 13),
    },
    agency: {
      mobile: readZoom('map_zoom_agency_mobile', 10),
      tablet: readZoom('map_zoom_agency_tablet', 11),
      desktop: readZoom('map_zoom_agency_desktop', 12),
    },
  };

  const selectedKind = zoomByKind[kind] || zoomByKind.city;
  return selectedKind[viewport] || selectedKind.desktop;
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

function setStatus(message, isError = false, durationMs = 3000) {
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
  }, durationMs);
}

function setValidateButtonMode(mode) {
  validateButtonMode = mode;
  if (!validerBtn) return;

  if (mode === 'save-gps') {
    validerBtn.textContent = 'Save-GPS';
    validerBtn.classList.add('validate-save-mode');
    return;
  }

  if (mode === 'save-city') {
    validerBtn.textContent = 'Save-City';
    validerBtn.classList.add('validate-save-mode');
    return;
  }

  validerBtn.textContent = 'Valider';
  validerBtn.classList.remove('validate-save-mode');
}

function setAddCityInputMode(enabled) {
  if (cityCpRow) {
    cityCpRow.classList.toggle('add-city-split', enabled);
  }

  if (cpField) {
    cpField.classList.toggle('hidden', !enabled);
  }

  if (villeInput) {
    villeInput.placeholder = enabled ? 'Ville' : 'Ville ou code postal';
  }

  if (cpInput) {
    cpInput.value = enabled ? cpInput.value : '';
  }
}

function syncAddGpsButtonState() {
  if (toggleCorrectionBtn) {
    toggleCorrectionBtn.classList.remove('gps-waiting', 'gps-ready');
    if (addGpsMode || addCityMode) {
      toggleCorrectionBtn.classList.add(correctionPreviewMarker ? 'gps-ready' : 'gps-waiting');
    }
  }

  if (addCityBtn) {
    addCityBtn.classList.remove('gps-waiting', 'gps-ready');
    if (addCityMode) {
      addCityBtn.classList.add(correctionPreviewMarker ? 'gps-ready' : 'gps-waiting');
    }
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

  if (!/^\d{5}$/.test(code)) {
    setStatus('⚠️ Le code postal doit contenir 5 chiffres.', true);
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

  setCombinedCityInput(currentCity.name, currentCity.postal_code);
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
  setAddCityInputMode(false);
  setValidateButtonMode('validate');
  setStatus(`✅ Nouvelle ville créée : ${currentCity.name} (${currentCity.postal_code}).`);
  closeSidebarAfterValidationOnMobile();
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
  map.setView(agency.center, getResponsiveZoom('agency'));
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

  map.setView(agency.center, getResponsiveZoom('agency'));
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
    syncMobileZoneBarFromResult();

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

  syncMobileZoneBarFromResult();

  drawZones({ all: false, distance });
}

function isMobileViewport() {
  return window.matchMedia('(max-width: 500px)').matches;
}

function dismissMobileKeyboard() {
  if (!isMobileViewport()) return;

  const activeElement = document.activeElement;
  if (activeElement && typeof activeElement.blur === 'function') {
    activeElement.blur();
  }

  [villeInput, cpInput, adresseInput]
    .filter(Boolean)
    .forEach((inputEl) => {
      if (typeof inputEl.blur === 'function') {
        inputEl.blur();
      }
    });
}

function clearMobileZoneBar() {
  if (!mobileZoneBar || !mobileZoneText) return;

  mobileZoneText.textContent = '';
  mobileZoneText.classList.remove('resultZone-default', 'resultZone-hors-zone');
  mobileZoneBar.classList.add('hidden');
}

function syncMobileZoneBarFromResult() {
  if (!mobileZoneBar || !mobileZoneText) return;

  if (!isMobileViewport()) {
    clearMobileZoneBar();
    return;
  }

  const text = (resultZone?.textContent || '').trim();
  if (!text) {
    clearMobileZoneBar();
    return;
  }

  mobileZoneText.textContent = text;
  mobileZoneText.classList.remove('resultZone-default', 'resultZone-hors-zone');
  if (resultZone.classList.contains('resultZone-hors-zone')) {
    mobileZoneText.classList.add('resultZone-hors-zone');
  } else {
    mobileZoneText.classList.add('resultZone-default');
  }

  mobileZoneBar.classList.remove('hidden');
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

function scheduleUserMarkerPopupAutoClose(delayMs = 3500) {
  if (userPopupAutoCloseTimer) {
    clearTimeout(userPopupAutoCloseTimer);
    userPopupAutoCloseTimer = null;
  }

  if (!userMarker) return;

  userPopupAutoCloseTimer = window.setTimeout(() => {
    if (userMarker && map.hasLayer(userMarker)) {
      userMarker.closePopup();
    }
    userPopupAutoCloseTimer = null;
  }, delayMs);
}

function setCombinedCityInput(name, postalCode) {
  const cityName = formatCityDisplayName(name);
  const cityPostal = (postalCode || '').trim();

  if (villeInput) {
    villeInput.value = addCityMode ? cityName : (cityPostal ? `${cityName} (${cityPostal})` : cityName);
  }

  if (cpInput) {
    cpInput.value = cityPostal;
  }
}

function parseVilleCodeInputs() {
  if (addCityMode) {
    return {
      ville: (villeInput.value || '').trim(),
      code: (cpInput.value || '').trim(),
    };
  }

  const raw = (villeInput.value || '').trim();
  let ville = raw;
  let code = (cpInput.value || '').trim();

  const postalOnlyPattern = raw.match(/^(\d{4,5})$/);
  if (postalOnlyPattern) {
    return { ville: '', code: postalOnlyPattern[1].trim() };
  }

  const cityPostalInlinePattern = raw.match(/^(\d{4,5})\s*-\s*(.+)$/);
  if (cityPostalInlinePattern) {
    return {
      ville: cityPostalInlinePattern[2].trim(),
      code: cityPostalInlinePattern[1].trim(),
    };
  }

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

  if (!ville && code) {
    return cities.find((city) => String(city.postal_code) === String(code)) || null;
  }

  return cities.find(
    (city) => normalizeStr(city.name) === normalizeStr(ville) && String(city.postal_code) === String(code),
  ) || null;
}

function setUserMarker(lat, lng, popup) {
  if (userPopupAutoCloseTimer) {
    clearTimeout(userPopupAutoCloseTimer);
    userPopupAutoCloseTimer = null;
  }

  if (userMarker) map.removeLayer(userMarker);
  userMarker = L.marker([lat, lng], {
    icon: L.icon({
      iconUrl: 'https://maps.gstatic.com/mapfiles/ms2/micons/red.png',
      iconSize: [32, 32],
    }),
  }).addTo(map).bindPopup(popup, { autoPan: false }).openPopup();

  const targetZoom = getResponsiveZoom('city');
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

  await ensureFirstSuggestionSelectionIfMissingPostal();

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
      scheduleUserMarkerPopupAutoClose();
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
    setCombinedCityInput(city.name, city.postal_code);

    setUserMarker(city.lat, city.lng, city.name);
    rayonAdresse = getDistance(agency.center[0], agency.center[1], city.lat, city.lng);
    updateZoneText(rayonAdresse);
    syncUserMarkerPopupWithZone(city.name);
    scheduleUserMarkerPopupAutoClose();
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

    const enabling = !addGpsMode;
    addCityMode = false;
    addGpsMode = enabling;
    if (enabling && correctionPreviewMarker) {
      map.removeLayer(correctionPreviewMarker);
      correctionPreviewMarker = null;
    }
    setAddCityInputMode(false);
    syncCorrectionModeState();
    setValidateButtonMode(addGpsMode ? 'save-gps' : 'validate');
    setStatus(addGpsMode
      ? (currentCity
        ? `✏️ Ville existante détectée (${currentCity.name}) : place le repère puis Save-GPS.`
        : '➕ Nouvelle ville : place le repère puis Save-GPS.')
      : 'Mode Add-GPS désactivé.');
  });
}

if (addCityBtn) {
  addCityBtn.addEventListener('click', () => {
    const previousParsed = parseVilleCodeInputs();
    addCityMode = !addCityMode;

    if (addCityMode) {
      currentCity = null;
      addGpsMode = true;
      if (correctionPreviewMarker) {
        map.removeLayer(correctionPreviewMarker);
        correctionPreviewMarker = null;
      }
      setAddCityInputMode(true);
      villeInput.value = previousParsed.ville || villeInput.value;
      cpInput.value = previousParsed.code || cpInput.value;
      syncCorrectionModeState();
      setValidateButtonMode('save-city');
      setStatus('➕ Mode Add-City activé : saisis Ville + Postal, place un repère, puis Save-City.');
      return;
    }

    addGpsMode = false;
    setAddCityInputMode(false);
    syncCorrectionModeState();
    setValidateButtonMode('validate');
    setStatus('Mode Add-City désactivé.');
  });
}

async function saveGpsFromMarker() {
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
    setCombinedCityInput(existingCityFromInputs.name, existingCityFromInputs.postal_code);
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
    setCombinedCityInput(currentCity.name, currentCity.postal_code);
    setUserMarker(currentCity.lat, currentCity.lng, `${currentCity.name} (saved)`);

    const agency = selectedAgency();
    rayonAdresse = getDistance(agency.center[0], agency.center[1], currentCity.lat, currentCity.lng);
    updateZoneText(rayonAdresse);

    if (correctionPreviewMarker) {
      map.removeLayer(correctionPreviewMarker);
      correctionPreviewMarker = null;
    }

    addGpsMode = false;
    addCityMode = false;
    syncCorrectionModeState();
    setAddCityInputMode(false);
    setValidateButtonMode('validate');
    setStatus(`✅ Ville mise à jour : ${currentCity.name}.`);
    closeSidebarAfterValidationOnMobile();
    return;
  }

  setStatus('⚠️ Ville introuvable: utilise Add-City pour créer une nouvelle ville.', true);
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

  syncAddGpsButtonState();

});

async function runValidateAction() {
  if (validateButtonMode === 'save-city') {
    await createCityFromMarker();
    return;
  }

  if (validateButtonMode === 'save-gps') {
    await saveGpsFromMarker();
    return;
  }

  await validateSearch();
}

validerBtn.addEventListener('click', runValidateAction);

if (villeInput) {
  villeInput.addEventListener('keydown', async (event) => {
    if (event.key === 'ArrowDown') {
      if (!currentVilleSuggestionItems.length) return;
      event.preventDefault();
      moveVilleSuggestionSelection(1);
      return;
    }

    if (event.key === 'ArrowUp') {
      if (!currentVilleSuggestionItems.length) return;
      event.preventDefault();
      moveVilleSuggestionSelection(-1);
      return;
    }

    if (event.key !== 'Enter') return;

    event.preventDefault();

    if (!villeSuggestions.classList.contains('hidden')) {
      applyCurrentVilleSuggestion();
    }

    await runValidateAction();
  });
}

[cpInput, adresseInput]
  .filter(Boolean)
  .forEach((inputEl) => {
    inputEl.addEventListener('keydown', async (event) => {
      if (event.key !== 'Enter') return;
      event.preventDefault();
      await runValidateAction();
    });
  });

resetBtn.addEventListener('click', () => {
  resetSearchState();
});

if (mobileResetBtn) {
  mobileResetBtn.addEventListener('click', () => {
    resetSearchState();
    clearMobileZoneBar();
  });
}

function resetSearchState() {
  setCombinedCityInput('', '');
  adresseInput.value = '';
  resultZone.textContent = '';
  resultZone.className = 'resultZone-default';
  clearMobileZoneBar();

  if (userMarker) {
    map.removeLayer(userMarker);
    userMarker = null;
  }
  if (userPopupAutoCloseTimer) {
    clearTimeout(userPopupAutoCloseTimer);
    userPopupAutoCloseTimer = null;
  }
  if (correctionPreviewMarker) {
    map.removeLayer(correctionPreviewMarker);
    correctionPreviewMarker = null;
  }

  currentCity = null;
  rayonAdresse = null;

  addCityMode = false;
  addGpsMode = false;
  setAddCityInputMode(false);
  syncCorrectionModeState();
  setValidateButtonMode('validate');

  zoneButtonAllVisible = false;
  zonesActives = false;
  clearZones();
  updateAgenceMarker();
  setStatus('Reset ok.', false, 1500);
}

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
  if (!sidebar) return;
  if (!window.matchMedia('(max-width: 500px)').matches) return;

  dismissMobileKeyboard();

  if (!sidebar.classList.contains('open')) return;

  sidebar.classList.remove('open');
  syncMobileZoneBarFromResult();

  window.setTimeout(() => {
    map.invalidateSize();
    if (!userMarker) return;
    map.setView(userMarker.getLatLng(), getResponsiveZoom('city'), { animate: true });
  }, 160);
}

burgerBtn.addEventListener('click', () => {
  syncMobileSidebarOffset();
  sidebar.classList.toggle('open');

  if (!isMobileViewport()) return;

  if (sidebar.classList.contains('open')) {
    clearMobileZoneBar();
    return;
  }

  syncMobileZoneBarFromResult();
});

let villeTimer = null;
const villeSuggestions = document.getElementById('suggestions-ville');
let villeSuggestionRequestId = 0;
let currentVilleSuggestionItems = [];
let currentVilleSuggestionIndex = -1;

function clearSuggestions(container) {
  if (!container) return;
  container.innerHTML = '';
  container.classList.add('hidden');
  currentVilleSuggestionItems = [];
  currentVilleSuggestionIndex = -1;
}

function updateVilleSuggestionHighlight(container) {
  if (!container) return;

  const buttons = Array.from(container.querySelectorAll('.suggestion-item'));
  buttons.forEach((button, index) => {
    const isActive = index === currentVilleSuggestionIndex;
    button.classList.toggle('is-preselected', isActive);
    button.setAttribute('aria-selected', isActive ? 'true' : 'false');
  });
}

function moveVilleSuggestionSelection(delta) {
  if (!villeSuggestions) return;
  if (!currentVilleSuggestionItems.length) return;

  const lastIndex = currentVilleSuggestionItems.length - 1;

  if (currentVilleSuggestionIndex < 0) {
    currentVilleSuggestionIndex = 0;
  } else {
    currentVilleSuggestionIndex += delta;
    if (currentVilleSuggestionIndex < 0) currentVilleSuggestionIndex = lastIndex;
    if (currentVilleSuggestionIndex > lastIndex) currentVilleSuggestionIndex = 0;
  }

  updateVilleSuggestionHighlight(villeSuggestions);

  const activeButton = villeSuggestions.querySelectorAll('.suggestion-item')[currentVilleSuggestionIndex];
  if (activeButton && typeof activeButton.scrollIntoView === 'function') {
    activeButton.scrollIntoView({ block: 'nearest' });
  }
}

function applyCurrentVilleSuggestion() {
  if (!currentVilleSuggestionItems.length) return false;

  const safeIndex = currentVilleSuggestionIndex >= 0 ? currentVilleSuggestionIndex : 0;
  const item = currentVilleSuggestionItems[safeIndex];
  if (!item) return false;

  setCombinedCityInput(item.cityName, item.postalCode);
  clearSuggestions(villeSuggestions);

  return true;
}

function renderSuggestions(container, items, onSelect) {
  if (!container) return;
  container.innerHTML = '';
  currentVilleSuggestionItems = items;
  currentVilleSuggestionIndex = items.length ? 0 : -1;

  if (!items.length) {
    container.classList.add('hidden');
    return;
  }

  items.forEach((item, index) => {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'suggestion-item';
    if (index === currentVilleSuggestionIndex) {
      btn.classList.add('is-preselected');
      btn.setAttribute('aria-selected', 'true');
    } else {
      btn.setAttribute('aria-selected', 'false');
    }
    btn.textContent = item.label;
    btn.addEventListener('click', () => {
      currentVilleSuggestionIndex = index;
      updateVilleSuggestionHighlight(container);
      onSelect(item);
    });
    container.appendChild(btn);
  });

  container.classList.remove('hidden');
}

async function loadVilleSuggestions() {
  if (!villeSuggestions) return;
  const requestId = ++villeSuggestionRequestId;
  const term = villeInput.value.trim();
  if (!term) {
    clearSuggestions(villeSuggestions);
    return;
  }

  try {
    const isPostalSearch = /^\d{1,5}$/.test(term);
    const cities = await searchCities(isPostalSearch ? '' : term, isPostalSearch ? term : '');
    if (requestId !== villeSuggestionRequestId) return;

    const seen = new Set();
    const items = [];
    cities.slice(0, 15).forEach((city) => {
      const displayName = formatCityDisplayName(city.name);
      const label = `${displayName} (${city.postal_code})`;
      if (seen.has(label)) return;
      seen.add(label);
      items.push({
        label,
        cityName: displayName,
        postalCode: city.postal_code,
      });
    });

    renderSuggestions(villeSuggestions, items, (item) => {
      setCombinedCityInput(item.cityName, item.postalCode);
      clearSuggestions(villeSuggestions);
    });
  } catch {
    if (requestId === villeSuggestionRequestId) {
      clearSuggestions(villeSuggestions);
    }
  }
}

async function ensureFirstSuggestionSelectionIfMissingPostal() {
  const parsed = parseVilleCodeInputs();
  if (parsed.code || !parsed.ville) return;

  if (currentVilleSuggestionItems.length > 0) {
    const first = currentVilleSuggestionItems[0];
    setCombinedCityInput(first.cityName, first.postalCode);
    clearSuggestions(villeSuggestions);
    return;
  }

  try {
    const isPostalSearch = /^\d{1,5}$/.test(parsed.ville);
    const cities = await searchCities(isPostalSearch ? '' : parsed.ville, isPostalSearch ? parsed.ville : '');
    if (!cities || cities.length === 0) return;

    const first = cities[0];
    setCombinedCityInput(formatCityDisplayName(first.name), String(first.postal_code || ''));
    clearSuggestions(villeSuggestions);
  } catch {
    // Keep current inputs unchanged on fallback failure.
  }
}

villeInput.addEventListener('input', () => {
  if (!villeInput.value.trim()) {
    clearSuggestions(villeSuggestions);
    if (cpInput) cpInput.value = '';
    return;
  }
  clearTimeout(villeTimer);
  villeTimer = setTimeout(loadVilleSuggestions, 180);
});

villeInput.addEventListener('focus', () => {
  if (!villeInput.value.trim()) {
    clearSuggestions(villeSuggestions);
    return;
  }
  loadVilleSuggestions();
});

villeInput.addEventListener('click', () => {
  if (!villeInput.value.trim()) {
    clearSuggestions(villeSuggestions);
    return;
  }
  loadVilleSuggestions();
});

villeInput.addEventListener('change', () => {
  const match = villeInput.value.match(/^(.+?)\s*\((\d{4,5})\)$/);
  if (match) {
    setCombinedCityInput(match[1].trim(), match[2].trim());
    clearSuggestions(villeSuggestions);
    return;
  }

  const matchPostalOnly = villeInput.value.match(/^(\d{4,5})$/);
  if (matchPostalOnly) {
    if (cpInput) cpInput.value = matchPostalOnly[1].trim();
    return;
  }

  if (!villeInput.value.trim()) {
    clearSuggestions(villeSuggestions);
  }
});

if (cpInput) {
  cpInput.addEventListener('input', () => {
    cpInput.value = (cpInput.value || '').replace(/\D/g, '').slice(0, 5);
  });
}

document.addEventListener('click', (event) => {
  const target = event.target;
  if (!(target instanceof Element)) return;
  if (target.closest('#villeField')) return;
  clearSuggestions(villeSuggestions);
});

window.addEventListener('load', () => {
  syncMobileSidebarOffset();
  setAddCityInputMode(false);
  setValidateButtonMode('validate');
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
  syncMobileZoneBarFromResult();
  map.invalidateSize();
});
