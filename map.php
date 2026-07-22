<!DOCTYPE html>
<html lang="fa">
<head>
<meta charset="UTF-8">
<title>Kadad Maps</title>

<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>

<style>
html, body {
  margin: 0;
  height: 100%;
  font-family: sans-serif;
}

/* 🗺️ نقشه کامل صفحه */
#map {
  height: 100%;
  width: 100%;
}

/* 📱 پنل موبایل */
#panel {
  position: absolute;
  top: 10px;
  left: 10px;
  right: 10px;
  max-width: 420px;
  background: white;
  z-index: 999;
  padding: 10px;
  border-radius: 12px;
  box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

input, button {
  width: 100%;
  padding: 10px;
  margin-top: 6px;
  font-size: 14px;
}

.result {
  padding: 8px;
  border-bottom: 1px solid #eee;
  cursor: pointer;
}

.result:hover {
  background: #f2f2f2;
}

#info {
  margin-top: 8px;
  padding: 8px;
  background: #f6f6f6;
  border-radius: 8px;
  font-size: 13px;
}
</style>
</head>

<body>

<div id="panel">

<h3>🗺️ Kadad Maps</h3>

<input id="searchBox" placeholder="🔎 جستجو مکان..." />
<button onclick="searchPlace()">نمایش مکان‌ها</button>

<div id="results"></div>

<hr>

<button onclick="setMode('start')">📍 مبدا</button>
<button onclick="setMode('end')">🎯 مقصد</button>
<button onclick="route()">🧭 مسیریابی</button>

<div id="info">📏 فاصله: - | ⏱ زمان: -</div>

</div>

<div id="map"></div>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<script>
/* 🗺️ نقشه */
let map = L.map('map').setView([35.6892, 51.3890], 12);

/* ✔️ Tile پایدار (حل مشکل لود نشدن) */
L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
  maxZoom: 19,
  attribution: '&copy; OpenStreetMap & CartoDB'
}).addTo(map);

let mode = null;
let start = null;
let end = null;
let routeLine = null;
let preview = null;

/* 🎯 انتخاب حالت */
function setMode(m){
  mode = m;
}

/* 🖱️ کلیک نقشه */
map.on('click', e => {
  if(!mode) return;

  const marker = L.marker([e.latlng.lat, e.latlng.lng]).addTo(map);

  if(mode === 'start'){
    if(start) start.remove();
    start = marker;
    marker.bindPopup("📍 مبدا").openPopup();
  }

  if(mode === 'end'){
    if(end) end.remove();
    end = marker;
    marker.bindPopup("🎯 مقصد").openPopup();
  }

  mode = null;
});

/* 🔎 سرچ (فقط نمایش + preview) */
function searchPlace(){
  const q = document.getElementById("searchBox").value;

  fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${q}`)
    .then(r => r.json())
    .then(data => {

      let html = "";

      data.forEach(item => {
        html += `
          <div class="result" onclick="preview(${item.lat},${item.lon})">
            📍 ${item.display_name}
          </div>
        `;
      });

      document.getElementById("results").innerHTML = html;
    });
}

/* 👀 preview روی نقشه */
function preview(lat, lon){
  if(preview) preview.remove();

  preview = L.marker([lat, lon], {opacity:0.7})
    .addTo(map)
    .bindPopup("📍 پیش‌نمایش")
    .openPopup();

  map.setView([lat, lon], 15);
}

/* 🧭 مسیر + فاصله + زمان */
function route(){
  if(!start || !end){
    alert("مبدا و مقصد را انتخاب کن");
    return;
  }

  const a = start.getLatLng();
  const b = end.getLatLng();

  const url =
    `https://router.project-osrm.org/route/v1/driving/` +
    `${a.lng},${a.lat};${b.lng},${b.lat}?overview=full&geometries=geojson`;

  fetch(url)
    .then(r => r.json())
    .then(data => {

      const route = data.routes[0];

      const path = route.geometry.coordinates.map(c => [c[1], c[0]]);

      if(routeLine) map.removeLayer(routeLine);

      routeLine = L.polyline(path, {
        color: "blue",
        weight: 5
      }).addTo(map);

      map.fitBounds(routeLine.getBounds());

      const km = (route.distance / 1000).toFixed(2);
      const min = Math.round(route.duration / 60);

      document.getElementById("info").innerText =
        `📏 فاصله: ${km} km | ⏱ زمان: ${min} دقیقه`;
    });
}
</script>

</body>
</html>