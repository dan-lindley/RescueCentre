// admissions_map.js

// ---------- Tooltip builder ----------
function makeWeatherTooltip(a) {
    const t = window.admissionsMapLang || {};
    return `
        <strong>${t.weather}</strong><br>
        Temp: ${a.w_temp ?? 'N/A'} °C<br>
        ${t.wind}: ${a.w_wind ?? t.na} mph<br>
        ${t.rain}: ${a.w_rainfall ?? t.na} mm
    `.replace('Temp:', `${t.temp}:`).replaceAll('N/A', t.na);
}

document.addEventListener('DOMContentLoaded', function() {
    const admissions = window.admissionsData || [];
    const t = window.admissionsMapLang || {};

    var map = L.map('admissionsMap', {
        scrollWheelZoom: false
    }).setView([54, -2], 6);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18
    }).addTo(map);


    // ---------- Layers ----------
    var markerCluster = L.markerClusterGroup({
        maxClusterRadius: 40,
        showCoverageOnHover: false,
        spiderfyOnMaxZoom: true,
        disableClusteringAtZoom: 17
    });

    var tempLayer = L.layerGroup();
    var windLayer = L.layerGroup();
    var rainLayer = L.layerGroup();

    map.addLayer(markerCluster);
    map.addLayer(tempLayer);
    map.addLayer(windLayer);
    map.addLayer(rainLayer);

    // Controls
    var yearSelect = document.getElementById('filterYear');
    var speciesSelect = document.getElementById('filterSpecies');
    var chkTemp = document.getElementById('toggleTemp');
    var chkWind = document.getElementById('toggleWind');
    var chkRain = document.getElementById('toggleRain');


    // ---------- Build filter lists ----------
    var years = new Set();
    var speciesSet = new Set();

    admissions.forEach(function(a) {
        if (a.admission_date) {
            var y = new Date(a.admission_date).getFullYear();
            if (!isNaN(y)) years.add(y);
        }
        if (a.animal_species) speciesSet.add(a.animal_species);
    });

    Array.from(years).sort().forEach(function(y) {
        yearSelect.innerHTML += `<option value="${y}">${y}</option>`;
    });

    Array.from(speciesSet).sort().forEach(function(sp) {
        speciesSelect.innerHTML += `<option value="${sp}">${sp}</option>`;
    });


    // ---------- Colour helpers ----------
    function getTempColor(t) {
        t = parseFloat(t); if (isNaN(t)) return null;
        if (t < 10) return '#0000ff';
        if (t < 15) return '#00ffff';
        if (t < 20) return '#00ff00';
        if (t < 25) return '#ffff00';
        if (t < 30) return '#ff9900';
        return '#ff0000';
    }

    function getWindColor(w) {
        w = parseFloat(w); if (isNaN(w)) return null;
        if (w < 10) return '#00ff00';
        if (w < 20) return '#ffff00';
        if (w < 30) return '#ff9900';
        return '#ff0000';
    }

    function getRainColor(r) {
        r = parseFloat(r); if (isNaN(r)) return null;
        if (r < 1) return '#a0c4ff';
        if (r < 5) return '#4361ee';
        if (r < 10) return '#7209b7';
        return '#3a0ca3';
    }


    // ---------- Pin colors ----------
    const currentYear = new Date().getFullYear();
    const lastYear = currentYear - 1;
    const twoYearsAgo = currentYear - 2;

    function getPinColorByYear(dateString) {
        if (!dateString) return 'gray';
        var y = new Date(dateString).getFullYear();
        if (y === currentYear) return "green";
        if (y === lastYear) return "yellow";
        if (y === twoYearsAgo) return "blue";
        return "gray";
    }

    function markerIcon(color) {
        return L.icon({
            iconUrl: `https://maps.google.com/mapfiles/ms/icons/${color}-dot.png`,
            iconSize: [32, 32],
            iconAnchor: [16, 32],
            popupAnchor: [0, -28]
        });
    }

    // ---------- Weather square size ----------
    var RECT_SIZE = 0.02; // ~2km
    var half = RECT_SIZE / 2;


    // ---------- Build layers ----------
    function rebuildLayers() {
        markerCluster.clearLayers();
        tempLayer.clearLayers();
        windLayer.clearLayers();
        rainLayer.clearLayers();

        var bounds = L.latLngBounds();
        var selectedYear = yearSelect.value;
        var selectedSpecies = speciesSelect.value;

        var tempOn = chkTemp.checked;
        var windOn = chkWind.checked;
        var rainOn = chkRain.checked;

        admissions.forEach(function(a) {
            var lat = parseFloat(a.location_lat);
            var lng = parseFloat(a.location_long);
            if (isNaN(lat) || isNaN(lng)) return;

            if (selectedYear !== 'all') {
                var y = new Date(a.admission_date).getFullYear();
                if (String(y) !== selectedYear) return;
            }

            if (selectedSpecies !== 'all' && a.animal_species !== selectedSpecies)
                return;

            // ----- PINS -----
            var pinColor = getPinColorByYear(a.admission_date);
            var icon = markerIcon(pinColor);

            var popupHTML = `
                <strong>${a.name || ''}</strong><br>
                ${a.animal_species || ''}<br>
                <u>${t.complaint}:</u> ${a.presenting_complaint || ''}<br>
                <u>${t.age}:</u> ${a.age_on_admission || ''}<br>
                <u>${t.disposition}:</u> ${a.disposition || ''}<br>
                <a  href="${BASE_URL}viewpatient.php?patient_id=${a.patient_id}"
                   target="_blank" style="color:blue;text-decoration:underline;">
                   ${t.view_patient}</a>
            `;

            var marker = L.marker([lat, lng], { icon }).bindPopup(popupHTML);
            markerCluster.addLayer(marker);

            bounds.extend([lat, lng]);


            // ----- WEATHER SQUARES -----
            var rectBounds = [
                [lat - half, lng - half],
                [lat + half, lng + half]
            ];

            // Temperature
            if (tempOn) {
                var tCol = getTempColor(a.w_temp);
                if (tCol) {
                    L.rectangle(rectBounds, {
                        stroke: false,
                        fillColor: tCol,
                        fillOpacity: 0.20
                    })
                    .bindTooltip(makeWeatherTooltip(a), { sticky: true })
                    .addTo(tempLayer);
                }
            }

            // Wind
            if (windOn) {
                var wCol = getWindColor(a.w_wind);
                if (wCol) {
                    L.rectangle(rectBounds, {
                        stroke: false,
                        fillColor: wCol,
                        fillOpacity: 0.20
                    })
                    .bindTooltip(makeWeatherTooltip(a), { sticky: true })
                    .addTo(windLayer);
                }
            }

            // Rainfall
            if (rainOn) {
                var rCol = getRainColor(a.w_rainfall);
                if (rCol) {
                    L.rectangle(rectBounds, {
                        stroke: false,
                        fillColor: rCol,
                        fillOpacity: 0.20
                    })
                    .bindTooltip(makeWeatherTooltip(a), { sticky: true })
                    .addTo(rainLayer);
                }
            }

        });

        if (bounds.isValid()) {
            map.fitBounds(bounds, { padding: [20, 20] });
        }
    }


    // ---------- Event Listeners ----------
    yearSelect.addEventListener('change', rebuildLayers);
    speciesSelect.addEventListener('change', rebuildLayers);
    chkTemp.addEventListener('change', rebuildLayers);
    chkWind.addEventListener('change', rebuildLayers);
    chkRain.addEventListener('change', rebuildLayers);


    rebuildLayers();


    // ---------- Collapsible Panels ----------
    const controlsBtn = document.getElementById('toggleControls');
    const controlsContent = document.getElementById('controlsContent');

    controlsBtn.addEventListener('click', () => {
        const isCollapsed = controlsContent.classList.toggle('collapsed');
        controlsBtn.textContent = isCollapsed ? '▲' : '▼';
    });

    const legendBtn = document.getElementById('toggleLegend');
    const legendContent = document.getElementById('legendContent');

    legendBtn.addEventListener('click', () => {
        const isCollapsed = legendContent.classList.toggle('collapsed');
        legendBtn.textContent = isCollapsed ? '▲' : '▼';
    });

});
