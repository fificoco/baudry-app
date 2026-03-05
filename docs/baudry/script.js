// ================================
// Variables globales et données
// ================================
let citiesData = [];
let userMarker = null;
let rings = [];
let agenceMarker = null;
let zonesActives = false;
let centerAgence = [48.18025, 3.29187];
let rayonAdresse = null;
const CORRECTIONS_KEY = 'cityCorrectionsV1';
let cityCorrections = {};
let correctionMode = false;
let correctionCandidate = null;
let correctionPreviewMarker = null;
const fallbackZoneLimit = 80000;
const agencySelect = document.getElementById('agency');
const villeInput = document.querySelector('input[placeholder="Ville"]');
const cpInput = document.querySelector('input[placeholder="Code Postal"]');
const correctionStatus = document.getElementById('correctionStatus');
const toggleCorrectionBtn = document.getElementById('toggleCorrectionBtn');
const saveCorrectionBtn = document.getElementById('saveCorrectionBtn');

// Fix iOS WebKit : text-align et placeholder ignorés sur input[list] via CSS
villeInput.style.setProperty('text-align', 'center', 'important');
cpInput.style.setProperty('text-align', 'center', 'important');

// Injection d'un style pour les placeholders (pseudo-éléments non accessibles en JS inline)
const iosPlaceholderFix = document.createElement('style');
iosPlaceholderFix.textContent = `
  #villeInput::placeholder, #cpInput::placeholder {
    text-align: center !important;
    opacity: 1;
  }
  #villeInput::-webkit-input-placeholder, #cpInput::-webkit-input-placeholder {
    text-align: center !important;
  }
`;
document.head.appendChild(iosPlaceholderFix);


const map = L.map('map', {
  zoomControl: false,          // Supprime les boutons + / –
  attributionControl: false    // Supprime le texte "© OpenStreetMap contributors"
}).setView(centerAgence, 13);

const agences = [
  { name: "Baudry Sens 89", coords: [48.18025, 3.29187] },
  { name: "Centre Sens", coords: [48.199847, 3.276939] },
  { name: "Congy Marc 89", coords: [47.81970,3.58274] },
];

agences.forEach(a => updateAgenceMarker(a.name, a.coords));

// ================================================================================
// mise en place de la carte Leaflet
// ================================================================================
L.control.attribution({
  position: 'bottomright' // ou 'topright', etc.
}).addTo(map).setPrefix('© Appli BAUDRY');

L.control.zoom({ position: 'topright' }).addTo(map);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: '&copy; OpenStreetMap contributors',
  tileSize: 512,
  zoomOffset: -1
}).addTo(map);

// Corrige la grille au chargement initial
window.addEventListener('load', () => {
  setTimeout(() => { map.invalidateSize(); }, 200);
});
window.addEventListener('resize', () => { map.invalidateSize(); });

// ================================================================================
// charge des données de villes/codes postaux/geocodage
// ================================================================================
fetch('geoData.json')
  .then(res => res.json())
  .then(data => {
    citiesData = data;
    cityCorrections = loadCorrections();
    applyLocalCorrectionsToCities();
    const count = Object.keys(cityCorrections).length;
    if (count > 0) {
      setCorrectionStatus(`📌 ${count} correction(s) locale(s) appliquée(s).`);
    }
});

// Normalise une chaîne : minuscules, sans accents, sans tirets
function normalizeStr(str) {
  return str
    .toLowerCase()
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "") // supprimer accents
    .replace(/[-'\u2019]/g, " ")     // tirets et apostrophes → espace
    .replace(/\s+/g, " ")            // doubles espaces
    .trim();
}

function parseVilleCodeInputs() {
  let ville = (villeInput?.value || '').trim();
  let code = (cpInput?.value || '').trim();

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

  const cpOnlyFromVille = ville.match(/\b(\d{4,5})\b$/);
  if (!code && cpOnlyFromVille) {
    code = cpOnlyFromVille[1];
  }

  return { ville, code };
}

function cityKey(ville, code) {
  return `${normalizeStr(ville || '')}|${(code || '').trim()}`;
}

function loadCorrections() {
  try {
    return JSON.parse(localStorage.getItem(CORRECTIONS_KEY) || '{}');
  } catch {
    return {};
  }
}

function persistCorrections() {
  localStorage.setItem(CORRECTIONS_KEY, JSON.stringify(cityCorrections));
}

function getCorrection(ville, code) {
  return cityCorrections[cityKey(ville, code)] || null;
}

function applyLocalCorrectionsToCities() {
  if (!Array.isArray(citiesData)) return;
  citiesData.forEach(city => {
    const correction = getCorrection(city.ville, city.code);
    if (correction) {
      city.lat = correction.lat;
      city.lng = correction.lng;
    }
  });
}

function exportUpdatedCitiesJson() {
  if (!Array.isArray(citiesData) || citiesData.length === 0) {
    throw new Error('Aucune donnée ville à exporter.');
  }

  const payload = JSON.stringify(citiesData, null, 2);
  const blob = new Blob([payload], { type: 'application/json;charset=utf-8' });
  const url = URL.createObjectURL(blob);
  const anchor = document.createElement('a');
  anchor.href = url;
  anchor.download = 'geoData.json';
  document.body.appendChild(anchor);
  anchor.click();
  anchor.remove();
  URL.revokeObjectURL(url);
}

async function saveUpdatedCitiesJsonDirect() {
  if (!Array.isArray(citiesData) || citiesData.length === 0) {
    throw new Error('Aucune donnée ville à enregistrer.');
  }

  if (!window.showSaveFilePicker) {
    throw new Error('API de sauvegarde directe indisponible.');
  }

  const payload = JSON.stringify(citiesData, null, 2);
  const handle = await window.showSaveFilePicker({
    suggestedName: 'geoData.json',
    types: [
      {
        description: 'Fichier JSON',
        accept: { 'application/json': ['.json'] }
      }
    ]
  });

  const writable = await handle.createWritable();
  await writable.write(payload);
  await writable.close();
}

function setCorrectionStatus(message, isError = false) {
  if (!correctionStatus) return;
  correctionStatus.textContent = message;
  correctionStatus.style.color = isError ? '#c0392b' : '#222';
}

function updateZoneDisplay(distance, resultZone) {
  resultZone.className = '';
  const maxZoneLimit = getMaxZoneLimit();
  if (distance > maxZoneLimit) {
    zonesActives = false;
    clearZones();
    const distKm = Math.round(distance / 1000);
    resultZone.textContent = `🚫 Adresse hors zone de livraison (${distKm} km)`;
    resultZone.classList.add("resultZone-hors-zone");
  } else {
    zonesActives = true;
    resultZone.textContent = getZoneResultText(distance);
    resultZone.classList.add("resultZone-style-temp");
    drawZones(centerAgence);
  }
}

// ================================================================================
//
// ================================================================================
function getDefaultZones() {
    return [
      { rmin: 0, rmax: 12000, color: '#008000' },
      { rmin: 12000, rmax: 24000, color: '#ffff00' },
      { rmin: 24000, rmax: 36000, color: '#ffa500' },
      { rmin: 36000, rmax: 47000, color: '#ff0000' },
      { rmin: 47000, rmax: 76000, color: '#800080' },
    ];
  }

function getZonesConfig() {
  const stored = localStorage.getItem('zonesConfig');
  const parsed = stored ? JSON.parse(stored) : getDefaultZones();
  if (!Array.isArray(parsed) || parsed.length === 0) {
    return getDefaultZones();
  }

  const validZones = parsed
    .map(zone => ({
      rmin: parseInt(zone.rmin),
      rmax: parseInt(zone.rmax),
      color: zone.color || '#cccccc'
    }))
    .filter(zone => !isNaN(zone.rmin) && !isNaN(zone.rmax) && zone.rmin < zone.rmax)
    .sort((a, b) => a.rmin - b.rmin);

  return validZones.length > 0 ? validZones : getDefaultZones();
}

function getMaxZoneLimit() {
  const zones = getZonesConfig();
  const maxRmax = Math.max(...zones.map(zone => zone.rmax));
  return Number.isFinite(maxRmax) ? maxRmax : fallbackZoneLimit;
}

function getZoneResultText(distance) {
  const zones = getZonesConfig();
  const emojis = ['🟢', '🟡', '🟠', '🔴', '🟣', '🟤', '⚫', '⚪'];

  for (let index = 0; index < zones.length; index++) {
    const zone = zones[index];
    if (distance <= zone.rmax) {
      const minKm = Math.round(zone.rmin / 1000);
      const maxKm = Math.round(zone.rmax / 1000);
      const emoji = emojis[index] || '🔵';
      return `${emoji} Zone ${index + 1} (${minKm}–${maxKm} km)`;
    }
  }

  return '';
}
// ================================================================================
// mise a jour des marqueurs des agences
// ================================================================================
function updateAgenceMarker(name, coords) {
  if (agenceMarker) map.removeLayer(agenceMarker);
  agenceMarker = L.marker(coords, {
    icon: L.icon({
      iconUrl: 'https://maps.gstatic.com/mapfiles/ms2/micons/blue-dot.png',
      iconSize: [32, 32]
    })
  }).addTo(map).bindPopup(`<b>${name}</b>`);
}
// ================================================================================
// affiche/masque la sidebar
// ================================================================================
function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('open');
}

const burgerGroup = document.getElementById('burgerGroup');
const sidebar = document.getElementById('sidebar');

burgerGroup.addEventListener('click', () => {
  const isOpen = sidebar.classList.toggle('open');

  // Ternaire : tu peux loguer, changer un attribut, etc.
  console.log(isOpen ? 'Menu ouvert' : 'Menu fermé');

  // Bonus : si tu veux changer l’icône image (exemple)
  // menuBurger.setAttribute('xlink:href', isOpen ? 'iconeFermer.png' : 'iconeMenu.png');
});

// ================================================================================
// suppression des zones de livraison
// ================================================================================
function clearZones() {
  rings.forEach(r => map.removeLayer(r));
  rings = [];
}
// ================================================================================
// dessine une zone de livraison avec trou
// ================================================================================
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
  for (let i = 0; i < steps; i++) {
    const angle = 2 * Math.PI * i / steps;
    latlngsOuter.push(toLatLng(angle, rMax));
    latlngsInner.unshift(toLatLng(angle, rMin));
  }
  const polygon = L.polygon([latlngsOuter, latlngsInner], {
    color: 'black',
    fillColor: color,
    fillOpacity: 0.2,
    weight: 0.7,
    opacity: 0.3
  }).addTo(map);
  rings.push(polygon);
}
// ================================================================================
// dessine les zones de livraison sans recherche
// ================================================================================
function drawZones(center, all = false) {
  clearZones();
  if (!zonesActives) return;
  const zonesConfig = getZonesConfig();
  // 📏 Distance cible
  let distance = all ? getMaxZoneLimit() : rayonAdresse;
  zonesConfig.forEach(zone => {
    if (all || (distance !== null && distance >= zone.rmin)) {
      drawZoneEvidee(center, zone.rmin, zone.rmax, zone.color);
    }
  });
  map.setView(center, 9);
}
// ================================================================================
// mise en ecoute du select des agences
// ================================================================================

// ================================================================================
// mise en ecoute du select des agences
// ================================================================================
agencySelect.addEventListener('change', e => {
  const [lat, lng] = e.target.value.split(',').map(Number);
  centerAgence = [lat, lng];
  const name = e.target.options[e.target.selectedIndex].dataset.name;
  map.setView(centerAgence, 13);
  updateAgenceMarker(name, centerAgence);
  if (zonesActives) drawZones(centerAgence);
});
// ================================================================================
// calcul de la distance entre deux points géographiques
// ================================================================================
function getDistance(lat1, lon1, lat2, lon2) {
  const R = 6371000;
  const dLat = (lat2 - lat1) * Math.PI / 180;
  const dLon = (lon2 - lon1) * Math.PI / 180;
  const a = Math.sin(dLat / 2) ** 2 +
            Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
            Math.sin(dLon / 2) ** 2;
  return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}
// ================================================================================
// mise en ecoute du bouton de validation de l'adresse
// ================================================================================
document.querySelector('.validate').addEventListener('click', () => {
  const { ville, code } = parseVilleCodeInputs();
  const adresse = document.querySelector('input[placeholder="Adresse"]')?.value.trim();
  const resultZone = document.getElementById('result-zone');

  if (adresse) {
    correctionCandidate = null;
    const fullAddress = `${adresse}, ${ville} ${code}`;
    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(fullAddress)}`)
      .then(res => res.json())
      .then(data => {
        if (data && data.length > 0) {
          const lat = parseFloat(data[0].lat);
          const lng = parseFloat(data[0].lon);
          if (userMarker) map.removeLayer(userMarker);
          userMarker = L.marker([lat, lng], {
            icon: L.icon({
              iconUrl: 'https://maps.gstatic.com/mapfiles/ms2/micons/red.png',
              iconSize: [32, 32]
            })
          }).addTo(map).bindPopup("Destination : " + fullAddress).openPopup();
          rayonAdresse = getDistance(centerAgence[0], centerAgence[1], lat, lng);
          updateZoneDisplay(rayonAdresse, resultZone);
        } else {
          resultZone.className = '';
          resultZone.textContent = "❌ Adresse introuvable.";
          resultZone.classList.add("resultZone-hors-zone");
        }
      })
      .catch(() => {
        resultZone.className = '';
        resultZone.textContent = "❌ Erreur de géocodage.";
        resultZone.classList.add("resultZone-hors-zone");
      });
  } else {
    const match = citiesData.find(c =>
      ville && code &&
      normalizeStr(c.ville) === normalizeStr(ville) &&
      c.code === code
    );
    if (match) {
      correctionCandidate = { ville: match.ville, code: match.code };
      const correction = getCorrection(match.ville, match.code);
      const targetLat = correction ? correction.lat : match.lat;
      const targetLng = correction ? correction.lng : match.lng;
      if (userMarker) map.removeLayer(userMarker);
      userMarker = L.marker([targetLat, targetLng], {
        icon: L.icon({
          iconUrl: 'https://maps.gstatic.com/mapfiles/ms2/micons/red.png',
          iconSize: [32, 32]
        })
      }).addTo(map).bindPopup(match.ville).openPopup();
      rayonAdresse = getDistance(centerAgence[0], centerAgence[1], targetLat, targetLng);
      updateZoneDisplay(rayonAdresse, resultZone);
      if (correction) {
        setCorrectionStatus(`📌 Coordonnée locale utilisée pour ${match.ville} (${match.code}).`);
      }
    } else {
      resultZone.className = '';
      resultZone.textContent = "❌ Ville + code postal non trouvé.";
      resultZone.classList.add("resultZone-hors-zone");
    }
  }
});

map.on('click', (event) => {
  if (!correctionMode) return;

  const parsed = parseVilleCodeInputs();
  const ville = parsed.ville;
  const code = parsed.code;
  const candidate = correctionCandidate || (ville && code ? { ville, code } : null);

  if (!candidate) {
    setCorrectionStatus('⚠️ Sélectionne d’abord une ville + code postal puis clique Valider.', true);
    return;
  }

  correctionCandidate = candidate;

  if (correctionPreviewMarker) {
    map.removeLayer(correctionPreviewMarker);
  }

  correctionPreviewMarker = L.marker([event.latlng.lat, event.latlng.lng], {
    icon: L.icon({
      iconUrl: 'https://maps.gstatic.com/mapfiles/ms2/micons/orange-dot.png',
      iconSize: [32, 32]
    })
  }).addTo(map).bindPopup(`Nouveau point: ${candidate.ville} (${candidate.code})`).openPopup();

  setCorrectionStatus(`📍 Point choisi: ${event.latlng.lat.toFixed(6)}, ${event.latlng.lng.toFixed(6)}.`);
});

toggleCorrectionBtn.addEventListener('click', () => {
  correctionMode = !correctionMode;
  map.getContainer().style.cursor = correctionMode ? 'crosshair' : '';
  if (correctionMode) {
    setCorrectionStatus('🛠️ Mode correction activé: clique sur la carte puis "Valider correction".');
  } else {
    setCorrectionStatus('Mode correction désactivé.');
  }
});

saveCorrectionBtn.addEventListener('click', async () => {
  if (!correctionMode) {
    setCorrectionStatus('⚠️ Active d’abord le mode correction.', true);
    return;
  }

  if (!correctionCandidate || !correctionPreviewMarker) {
    setCorrectionStatus('⚠️ Choisis un point sur la carte avant d’enregistrer.', true);
    return;
  }

  const { lat, lng } = correctionPreviewMarker.getLatLng();
  const key = cityKey(correctionCandidate.ville, correctionCandidate.code);

  const targetCity = citiesData.find(c =>
    normalizeStr(c.ville) === normalizeStr(correctionCandidate.ville) &&
    c.code === correctionCandidate.code
  );

  if (!targetCity) {
    setCorrectionStatus('❌ Ville sélectionnée introuvable dans la base.', true);
    return;
  }

  targetCity.lat = lat;
  targetCity.lng = lng;

  cityCorrections[key] = {
    ville: correctionCandidate.ville,
    code: correctionCandidate.code,
    lat,
    lng,
    updatedAt: new Date().toISOString()
  };

  persistCorrections();
  applyLocalCorrectionsToCities();

  let jsonSavedDirectly = false;
  try {
    await saveUpdatedCitiesJsonDirect();
    jsonSavedDirectly = true;
  } catch {
    setCorrectionStatus('✅ Correction locale enregistrée. Export JSON manuel disponible (pas de téléchargement automatique).');
  }

  if (userMarker) {
    userMarker.setLatLng([lat, lng]).bindPopup(correctionCandidate.ville).openPopup();
    const resultZone = document.getElementById('result-zone');
    rayonAdresse = getDistance(centerAgence[0], centerAgence[1], lat, lng);
    if (resultZone) {
      updateZoneDisplay(rayonAdresse, resultZone);
    }
  }

  if (jsonSavedDirectly) {
    setCorrectionStatus(`✅ Coordonnées remplacées pour ${correctionCandidate.ville} (${correctionCandidate.code}) + fichier JSON mis à jour.`);
  }

  correctionMode = false;
  map.getContainer().style.cursor = '';
});
// ================================================================================
// mise en ecoute du champ ville et mise a jour des suggestions de villes
// ================================================================================
document.querySelector('input[placeholder="Ville"]').addEventListener('input', function () {
  const input = normalizeStr(this.value);
  const datalist = document.getElementById('suggestions-ville');
  datalist.innerHTML = '';
  citiesData
    .filter(c => normalizeStr(c.ville).startsWith(input))
    .slice(0, 15)
    .forEach(c => {
      const option = document.createElement('option');
      option.value = `${c.ville} (${c.code})`;
      datalist.appendChild(option);
    });
});
// ================================================================================
// mise en ecoute du champ code postal et mise a jour des suggestions de villes
// ================================================================================
document.querySelector('input[placeholder="Code Postal"]').addEventListener('input', function () {
  const input = this.value;
  const datalist = document.getElementById('suggestions-cp');
  datalist.innerHTML = '';
  citiesData
    .filter(c => c.code.startsWith(input))
    .slice(0, 15)
    .forEach(c => {
      const option = document.createElement('option');
      option.value = `${c.code} - ${c.ville}`;
      datalist.appendChild(option);
    });
});
// ================================================================================
// mise en ecoute du champ ville et mise a jour du champ code postal
// ================================================================================
villeInput.addEventListener('change', () => {
  const match = villeInput.value.match(/^(.+?)\s*\((\d{4,5})\)$/);
  if (match) {
    villeInput.value = match[1];
    cpInput.value = match[2];
  }
});
// ================================================================================
// mise en ecoute du champ code postal et mise a jour du champ ville
// ================================================================================
cpInput.addEventListener('change', () => {
  const match = cpInput.value.match(/^(\d{4,5})\s*-\s*(.+)$/);
  if (match) {
    cpInput.value = match[1];
    villeInput.value = match[2];
  }
});
// ================================================================================
// chargement du DOMContentLoaded et initialisation de la carte
// ================================================================================
window.addEventListener("DOMContentLoaded", () => {
  const agencySelect = document.getElementById('agency');
  const selectedOption = agencySelect.options[agencySelect.selectedIndex];
  const [lat, lng] = selectedOption.value.split(',').map(Number);
  centerAgence = [lat, lng];
  const selectedName = selectedOption.dataset.name;
  map.setView(centerAgence, 13);
  updateAgenceMarker(selectedName, centerAgence);
  zonesActives = false;
});
// ================================================================================
// mise en ecoute du bouton de réinitialisation
// ================================================================================
document.getElementById('resetBtn').addEventListener('click', () => {
  document.getElementById('villeInput').value = '';
  document.getElementById('cpInput').value = '';
  document.getElementById('adresseInput').value = '';
  document.getElementById('result-zone').innerHTML = '';
  document.getElementById('suggestions-ville').innerHTML = '';
  document.getElementById('suggestions-cp').innerHTML = '';
  // Supprimer le marqueur client s’il existe
  if (userMarker) {
    map.removeLayer(userMarker);
    userMarker = null;
  }
  if (correctionPreviewMarker) {
    map.removeLayer(correctionPreviewMarker);
    correctionPreviewMarker = null;
  }
  correctionCandidate = null;
  correctionMode = false;
  map.getContainer().style.cursor = '';
  setCorrectionStatus('Brouillon GPS annulé.');
  // Supprimer toutes les zones de livraison affichées
  clearZones();
  // Réinitialiser les flags
  zonesActives = false;
  rayonAdresse = null;
  // Recentrer sur l’agence sélectionnée
  const agencySelect = document.getElementById('agency');
  const [lat, lng] = agencySelect.value.split(',').map(Number);
  map.setView([lat, lng], 11);
});
// ================================================================================
// mise en ecoute du bouton zones de livraison
// ================================================================================
document.getElementById('zoneBtn').addEventListener('click', () => {
  zonesActives = !zonesActives;
  if (zonesActives) {
    // 🔍 Recherche en cours → afficher uniquement la zone concernée
    if (rayonAdresse !== null) {
      drawZones(centerAgence, false);
    } else {
      // 🚫 Pas de recherche → affiche toutes les zones
      drawZones(centerAgence, true);
    }
  } else {
    clearZones();
  }
});
// ================================================================================
// mise en ecoute du bouton site web et ouverture du site
// ================================================================================
document.getElementById('websiteBtn').addEventListener('click', () => {
  window.open('https://www.baudry-sa.com', '_blank');
});
// ================================================================================
// mise en ecoute du bouton itinéraire et ouverture de Google Maps
// ================================================================================
document.getElementById('itineraireBtn').addEventListener('click', () => {
  if (userMarker && agenceMarker) {
    const { lat: lat1, lng: lng1 } = agenceMarker.getLatLng();
    const { lat: lat2, lng: lng2 } = userMarker.getLatLng();
    const url = `https://www.google.com/maps/dir/?api=1&origin=${lat1},${lng1}&destination=${lat2},${lng2}&travelmode=driving`;
    window.open(url, '_blank');
  } else {
    alert("Veuillez d’abord valider une adresse client.");
  }
});
// ================================================================================
// Gestion du panneau de zones
// Création de la modale pour configurer les zones de livraison
// ================================================================================
window.addEventListener('DOMContentLoaded', () => {
  const btnMenuPerso = document.getElementById('btnMenuPerso');
  const zoneModal = document.getElementById('zoneModal');
  const closeModal = document.getElementById('closeModal');
  const addZoneBtn = document.getElementById('addZone');
  const saveZonesBtn = document.getElementById('saveZones');
  const resetZonesBtn = document.getElementById('resetZones');
  const zoneInputsContainer = document.getElementById('zoneInputs');
  // Configuration zones
  let zonesConfig = [];
  function updateZoneCount() {
    const count = document.querySelectorAll('.zone-row').length;
    document.getElementById('zoneCount').textContent = count;
  }
  function addSingleZone(rmin = 0, rmax = '', color = '#cccccc') {
    const row = document.createElement('div');
    row.className = 'zone-row';
    row.innerHTML = `
      <label>Min <input type="number" class="rmin" value="${rmin}" min="0" step="1000"></label>
      <label>Max <input type="number" class="rmax" value="${rmax}" min="1000" step="1000"></label>
      <input type="color" class="color" value="${color}">
      <button class="removeZone">✖</button>
    `;
    row.querySelector('.removeZone').addEventListener('click', () => {
      row.remove();
      updateZoneCount();
    });
    zoneInputsContainer.appendChild(row);
    updateZoneCount();
  }
  function generateZoneInputs(zones = getDefaultZones()) {
    zoneInputsContainer.innerHTML = '';
    zones.forEach(z => addSingleZone(z.rmin, z.rmax, z.color));
  }
  btnMenuPerso.addEventListener('click', () => {
    const config = localStorage.getItem('zonesConfig');
    zonesConfig = config ? JSON.parse(config) : getDefaultZones();
    generateZoneInputs(zonesConfig);
    zoneModal.classList.remove('hidden');
  });
  closeModal.addEventListener('click', () => {
    zoneModal.classList.add('hidden');
  });
  addZoneBtn.addEventListener('click', () => {
    const rows = document.querySelectorAll('.zone-row');
    let nextRmin = 0;
    if (rows.length > 0) {
      const lastRmax = rows[rows.length - 1].querySelector('.rmax').value;
      nextRmin = parseInt(lastRmax) || 0;
    }
    addSingleZone(nextRmin, '');
  });
  saveZonesBtn.addEventListener('click', () => {
    const rows = document.querySelectorAll('.zone-row');
    zonesConfig = [];
    rows.forEach(row => {
      const rmin = parseInt(row.querySelector('.rmin').value);
      const rmax = parseInt(row.querySelector('.rmax').value);
      const color = row.querySelector('.color').value;
      if (!isNaN(rmin) && !isNaN(rmax) && rmin < rmax) {
        zonesConfig.push({ rmin, rmax, color });
      }
    });
    localStorage.setItem('zonesConfig', JSON.stringify(zonesConfig));
    zoneModal.classList.add('hidden');
  });
  resetZonesBtn.addEventListener('click', () => {
    localStorage.removeItem('zonesConfig');
    zonesConfig = getDefaultZones();
    generateZoneInputs(zonesConfig);
  });
});