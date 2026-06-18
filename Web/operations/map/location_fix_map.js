// operations/map/location_fix_map.js
document.addEventListener('DOMContentLoaded', function () {
  const admissions = window.admissionsData || [];

  // ---- Map ----
  const map = L.map('admissionsMap', { scrollWheelZoom: false }).setView([54, -2], 6);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 18 }).addTo(map);

  const markerCluster = L.markerClusterGroup({
    maxClusterRadius: 40,
    showCoverageOnHover: false,
    spiderfyOnMaxZoom: true,
    disableClusteringAtZoom: 17
  });
  map.addLayer(markerCluster);

  // ---- UI refs ----
  const speciesSelect = document.getElementById('filterSpecies');
  const selectedSummary = document.getElementById('selectedSummary');
  const selectedMeta = document.getElementById('selectedMeta');
  

  const fixPatientId = document.getElementById('fix_patient_id');
  const fixAnimal = document.getElementById('fix_animal');
  const fixAdmissionDate = document.getElementById('fix_admission_date');
  const fixLat = document.getElementById('fix_lat');
  const fixLng = document.getElementById('fix_lng');
  const fixAddress = document.getElementById('fix_address');
  const fixStoredLocation = document.getElementById('fix_stored_location');


  const clickMode = document.getElementById('fix_click_mode');
  const btnSave = document.getElementById('btnSaveCoords');
  const btnLookup = document.getElementById('btnRerunLookup');
  const msgEl = document.getElementById('fix_msg');

  // ---- Build species filter ----
  const speciesSet = new Set();
  admissions.forEach(a => { if (a.animal_species) speciesSet.add(a.animal_species); });
  Array.from(speciesSet).sort().forEach(sp => {
    speciesSelect.innerHTML += `<option value="${escapeHtml(sp)}">${escapeHtml(sp)}</option>`;
  });


  function debounce(fn, ms) {
  let t = null;
  return function (...args) {
    clearTimeout(t);
    t = setTimeout(() => fn.apply(this, args), ms);
  };
}

function hideSuggestions(box) {
  if (!box) return;
  box.style.display = 'none';
  box.innerHTML = '';
}

function renderSuggestions(box, items, onPick) {
  if (!box) return;
  if (!items || items.length === 0) {
    hideSuggestions(box);
    return;
  }

  box.innerHTML = items.map((it, idx) => `
    <div data-idx="${idx}"
         style="padding:10px 12px; cursor:pointer; border-bottom:1px solid #f0f0f0;">
      ${escapeHtml(it.display_name || '')}
    </div>
  `).join('');

  box.querySelectorAll('[data-idx]').forEach(el => {
    el.addEventListener('mousedown', (e) => {
      // mousedown so blur doesn't hide before click registers
      e.preventDefault();
      const idx = parseInt(el.getAttribute('data-idx'), 10);
      const it = items[idx];
      if (it) onPick(it);
    });
  });

  box.style.display = 'block';
}

async function nominatimSearch(q) {
  const url = `https://nominatim.openstreetmap.org/search?format=json&limit=6&q=${encodeURIComponent(q)}`;
  const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
  if (!res.ok) return [];
  const items = await res.json();
  return Array.isArray(items) ? items : [];
}



  // ---- Marker store ----
  const markerByPatient = new Map();
  let selected = { patient_id: null, marker: null, record: null };

  function markerIcon(color) {
    return L.icon({
      iconUrl: `https://maps.google.com/mapfiles/ms/icons/${color}-dot.png`,
      iconSize: [32, 32],
      iconAnchor: [16, 32],
      popupAnchor: [0, -28]
    });
  }

  function escapeHtml(s) {
    return String(s ?? '')
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  function setMsg(t) {
    msgEl.textContent = t || '';
  }

  function enableForm(on) {
    btnSave.disabled = !on;
    btnLookup.disabled = !on;
  }

  function formatDate(d) {
    if (!d) return '';
    // Keep it simple: show raw string, or try date
    const dt = new Date(d);
    if (!isNaN(dt.getTime())) return dt.toISOString().slice(0, 10);
    return String(d);
  }

  // ---- Autocomplete hook ----
  // Reuse whatever you already have. Two common patterns:
  // 1) window.initAddressAutocomplete(inputEl, onSelectCallback)
  // 2) window.init_address_autocomplete(inputId, callback)
// ---- Autocomplete hook ----
// This *forces* autocomplete attachment (if the function exists on the page),
// and applies chosen coords into the lat/lng inputs.
function initAutocompleteIfPresent() {
  const inputEl = fixAddress;
  const box = document.getElementById('fix_address_suggestions');
  if (!inputEl || !box) return;

  if (inputEl.dataset.autocompleteReady === '1') return;
  inputEl.dataset.autocompleteReady = '1';

  const doSearch = debounce(async () => {
    const q = (inputEl.value || '').trim();
    if (q.length < 4) {
      hideSuggestions(box);
      return;
    }

    try {
      const items = await nominatimSearch(q);
      renderSuggestions(box, items, (it) => {
        // pick
        inputEl.value = it.display_name || inputEl.value;

        const lat = parseFloat(it.lat);
        const lng = parseFloat(it.lon);
        if (!isNaN(lat) && !isNaN(lng)) {
          fixLat.value = lat.toFixed(6);
          fixLng.value = lng.toFixed(6);
          setMsg('Address selected. Save when ready.');
        } else {
          setMsg('Selected address has no coordinates.');
        }

        hideSuggestions(box);
      });
    } catch (e) {
      hideSuggestions(box);
    }
  }, 250);

  inputEl.addEventListener('input', doSearch);

  inputEl.addEventListener('blur', () => {
    // delay so mousedown selection can fire
    setTimeout(() => hideSuggestions(box), 150);
  });

  inputEl.addEventListener('focus', () => {
    const q = (inputEl.value || '').trim();
    if (q.length >= 4) doSearch();
  });
}



  // ---- Selection ----
  function selectRecord(a) {
    selected.patient_id = String(a.patient_id);
    selected.record = a;
    selected.marker = markerByPatient.get(selected.patient_id) || null;

    fixPatientId.value = selected.patient_id;
    fixAnimal.value = `${a.name || ''}${a.animal_species ? ' — ' + a.animal_species : ''}`.trim();
    fixAdmissionDate.value = formatDate(a.admission_date);

    fixLat.value = (a.location_lat ?? '').toString();
    fixLng.value = (a.location_long ?? '').toString();
    // Show the original stored collection location (read-only field)
fixStoredLocation.value = (a.collection_location ?? '').toString();

// Seed the search box ONLY if the user hasn't already typed anything
if (!fixAddress.value) {
  fixAddress.value = (a.collection_location ?? '').toString();
}



    // Don’t overwrite address if user already typed something; clear if switching patient
    fixAddress.value = '';

    selectedSummary.textContent = `Selected: ${a.name || 'Patient'} (#${a.patient_id})`;
    selectedMeta.textContent = `Patient ID ${a.patient_id} • ${a.animal_species || 'Unknown species'} • ${formatDate(a.admission_date)}`;

    enableForm(true);
    setMsg('Selected. You can edit coords below.');

    initAutocompleteIfPresent();
  }

  function rebuildPins() {
    markerCluster.clearLayers();
    markerByPatient.clear();

    const selectedSpecies = speciesSelect.value;
    const bounds = L.latLngBounds();

    admissions.forEach(a => {
      const lat = parseFloat(a.location_lat);
      const lng = parseFloat(a.location_long);
      if (isNaN(lat) || isNaN(lng)) return;

      if (selectedSpecies !== 'all' && a.animal_species !== selectedSpecies) return;

      const marker = L.marker([lat, lng], { icon: markerIcon('red') });

      marker.on('click', () => selectRecord(a));

      markerCluster.addLayer(marker);
      markerByPatient.set(String(a.patient_id), marker);
      bounds.extend([lat, lng]);
    });

    if (bounds.isValid()) map.fitBounds(bounds, { padding: [20, 20] });
  }

  speciesSelect.addEventListener('change', rebuildPins);

  // ---- Map click to set coords (when enabled) ----
  map.on('click', (ev) => {
    if (!clickMode.checked) return;
    if (!selected.patient_id) return;

    fixLat.value = ev.latlng.lat.toFixed(6);
    fixLng.value = ev.latlng.lng.toFixed(6);
    setMsg('Map click applied. Save when ready.');
  });

  // ---- Save coords ----
  btnSave.addEventListener('click', async () => {
    const patient_id = fixPatientId.value;
    if (!patient_id) return;

    const lat = parseFloat(fixLat.value);
    const lng = parseFloat(fixLng.value);
    if (isNaN(lat) || isNaN(lng)) {
      setMsg('Invalid lat/long.');
      return;
    }

    setMsg('Saving...');

    try {
      const res = await fetch(`${BASE_URL}controllers/admissions/location_fix.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'update_coords', patient_id, lat, lng })
      });

      const data = await res.json();
      if (!data || !data.ok) throw new Error(data?.error || 'Save failed');

      // Update local dataset + marker
      admissions.forEach(a => {
        if (String(a.patient_id) === String(patient_id)) {
          a.location_lat = String(lat);
          a.location_long = String(lng);
        }
      });

      const marker = markerByPatient.get(String(patient_id));
      if (marker) marker.setLatLng([lat, lng]);

      setMsg('Saved ✅');
    } catch (err) {
      setMsg(String(err.message || err));
    }
  });

  // ---- Re-run lookup ----
  btnLookup.addEventListener('click', async () => {
    const patient_id = fixPatientId.value;
    if (!patient_id) return;

    const address = (fixAddress.value || '').trim();
    if (!address) {
      setMsg('Enter an address first.');
      return;
    }

    setMsg('Looking up...');

    try {
      const res = await fetch(`${BASE_URL}controllers/admissions/location_fix.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'geocode_and_update', patient_id, address })
      });

      const data = await res.json();
      if (!data || !data.ok) throw new Error(data?.error || 'Lookup failed');

      fixLat.value = Number(data.lat).toFixed(6);
      fixLng.value = Number(data.lng).toFixed(6);

      // Update local dataset + marker
      admissions.forEach(a => {
        if (String(a.patient_id) === String(patient_id)) {
          a.location_lat = String(data.lat);
          a.location_long = String(data.lng);
        }
      });

      const marker = markerByPatient.get(String(patient_id));
      if (marker) marker.setLatLng([parseFloat(data.lat), parseFloat(data.lng)]);

      setMsg('Updated from lookup ✅');
    } catch (err) {
      setMsg(String(err.message || err));
    }
  });

  // ---- Init ----
  enableForm(false);
  setMsg('');
  rebuildPins();
});
