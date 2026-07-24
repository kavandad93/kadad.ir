const $ = (id) => document.getElementById(id);
const isStandalone = () => window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true || new URLSearchParams(location.search).get('app') === '1';
const state = JSON.parse(localStorage.getItem('locmy-state') || '{}');
state.people ||= [
  { id: 'sara', name: 'سارا', lat: 35.7219, lng: 51.3347 },
  { id: 'reza', name: 'رضا', lat: 35.697, lng: 51.389 },
  { id: 'nima', name: 'نیما', lat: 35.744, lng: 51.421 },
];
state.meetups ||= [
  { id: 'm1', title: 'قرار کافه', lat: 35.715, lng: 51.404 },
  { id: 'm2', title: 'رویداد گروه دوچرخه', lat: 35.735, lng: 51.365, access: 'سارا، رضا' },
];
state.groups ||= [{ id: 'g1', name: 'گروه دوستان', members: 'سارا، رضا، من' }];
state.messages ||= { sara: [{ from: 'سارا', text: 'سلام، مسیرت مشخصه؟' }], g1: [{ from: 'گروه', text: 'قرار ساعت ۶ روی نقشه ثبت شد.' }] };
let map;
let activeChat = 'sara';
let deferredInstall;

function save() { localStorage.setItem('locmy-state', JSON.stringify(state)); }
function showInstalledApp() { $('landing').hidden = true; $('app').hidden = false; initMap(); renderChats(); }
function showLanding() { $('landing').hidden = false; $('app').hidden = true; }
function icon(className, text) { return L.divIcon({ className, html: `<span class="pin ${className.includes('meetup') ? 'meetup' : className.includes('me') ? 'me' : ''}">${text}</span>` }); }
function initMap() {
  if (map || !window.L) return;
  map = L.map('map', { zoomControl: false }).setView([35.715, 51.39], 12);
  L.control.zoom({ position: 'bottomright' }).addTo(map);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(map);
  renderMap();
}
function renderMap() {
  if (!map) return;
  map.eachLayer((layer) => { if (layer.options?.locmyLayer) map.removeLayer(layer); });
  state.people.forEach((p) => L.marker([p.lat, p.lng], { icon: icon(p.me ? 'locmy-me' : 'locmy-person', p.me ? `من: ${p.name}` : `👤 ${p.name}`), locmyLayer: true }).addTo(map).bindPopup(`<b>${p.name}</b><br><button onclick="openChat('${p.id}')">چت خصوصی</button>`));
  state.meetups.forEach((m) => L.marker([m.lat, m.lng], { icon: icon('locmy-meetup', `📍 ${m.title}`), locmyLayer: true }).addTo(map).bindPopup(`<b>${m.title}</b><br>${m.access ? `خصوصی برای: ${m.access}` : 'قرار عمومی'}`));
  const me = state.people.find((p) => p.me);
  $('sharingState').textContent = me ? `لوکیشن ${me.name} روی نقشه فعال است` : 'نام خود را بزنید و لوکیشن را شیر کنید';
}
async function notify(message) {
  if (!('Notification' in window)) return alert(message);
  if (Notification.permission === 'granted') new Notification('LocMy', { body: message, icon: 'icon.svg' });
  else alert(message);
}
function distance(a, b) { return Math.hypot((a.lat - b.lat) * 111, (a.lng - b.lng) * 91); }
function checkArrivals(me) { const meetup = state.meetups.find((m) => distance(me, m) < 1.6); if (meetup) notify(`${me.name} به محدوده ${meetup.title} رسید.`); }
function renderChats() {
  const targets = [...state.people.filter((p) => !p.me).map((p) => ({ id: p.id, label: `👤 ${p.name}` })), ...state.groups.map((g) => ({ id: g.id, label: `👥 ${g.name}` }))];
  $('chatTargets').innerHTML = targets.map((t) => `<button class="target ${t.id === activeChat ? 'active' : ''}" data-chat="${t.id}">${t.label}<span>›</span></button>`).join('');
  document.querySelectorAll('[data-chat]').forEach((button) => button.onclick = () => openChat(button.dataset.chat));
  const target = targets.find((t) => t.id === activeChat);
  $('chatTitle').textContent = target ? target.label : 'یک گفتگو انتخاب کنید';
  $('chatMessages').innerHTML = (state.messages[activeChat] || []).map((m) => `<div class="bubble ${m.from === 'من' ? 'me' : ''}"><strong>${m.from}</strong><br>${m.text}</div>`).join('') || '<p class="hint">هنوز پیامی نیست.</p>';
}
window.openChat = (id) => { activeChat = id; $('chatDrawer').hidden = false; renderChats(); };

window.addEventListener('beforeinstallprompt', (event) => { event.preventDefault(); deferredInstall = event; $('installBtn').hidden = false; });
$('installBtn').onclick = async () => { if (deferredInstall) { deferredInstall.prompt(); deferredInstall = null; $('installBtn').hidden = true; } };
$('shareLocation').onclick = () => {
  const name = $('displayName').value.trim();
  if (!name) return alert('اول نام خود را وارد کنید.');
  const center = map.getCenter();
  const me = { id: 'me', name, lat: center.lat + (Math.random() - .5) / 120, lng: center.lng + (Math.random() - .5) / 120, me: true };
  state.people = state.people.filter((p) => !p.me).concat(me);
  save(); renderMap(); checkArrivals(me);
};
$('addMeetup').onclick = () => { const title = prompt('نام قرار یا رویداد؟', 'قرار جدید'); if (!title) return; const center = map.getCenter(); state.meetups.push({ id: crypto.randomUUID(), title, lat: center.lat, lng: center.lng }); save(); renderMap(); };
$('notifyBtn').onclick = async () => { if ('Notification' in window) { const permission = await Notification.requestPermission(); notify(permission === 'granted' ? 'نوتیفیکیشن فعال شد.' : 'برای اعلان باید اجازه بدهید.'); } };
$('chatFab').onclick = () => { $('chatDrawer').hidden = false; renderChats(); };
$('closeChat').onclick = () => { $('chatDrawer').hidden = true; };
$('messageForm').onsubmit = (event) => { event.preventDefault(); const text = $('messageText').value.trim(); if (!text || !activeChat) return; state.messages[activeChat] ||= []; state.messages[activeChat].push({ from: 'من', text }); $('messageText').value = ''; save(); renderChats(); };
$('groupForm').onsubmit = (event) => { event.preventDefault(); const group = { id: crypto.randomUUID(), name: $('groupName').value.trim(), members: $('groupMembers').value.trim() || 'عمومی' }; state.groups.push(group); state.messages[group.id] = [{ from: 'سیستم', text: `گروه ${group.name} ساخته شد.` }]; activeChat = group.id; event.target.reset(); save(); renderChats(); };
if ('serviceWorker' in navigator) navigator.serviceWorker.register('sw.js');
isStandalone() ? showInstalledApp() : showLanding();
