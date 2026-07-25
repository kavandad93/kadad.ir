const $ = (id) => document.getElementById(id);
let deferredInstall, selectedPoint = null, markers = [], currentRoom = 'global';
const api = (data = {}) => fetch('api/index.php', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(data)}).then(r=>r.json());

window.addEventListener('beforeinstallprompt', (event) => { event.preventDefault(); deferredInstall = event; });
$('installBtn')?.addEventListener('click', async () => { if (deferredInstall) await deferredInstall.prompt(); document.cookie = 'mmap_installed=1;path=/;max-age=31536000'; location.href='?app=1'; });
$('openAppBtn')?.addEventListener('click', () => { document.cookie = 'mmap_installed=1;path=/;max-age=31536000'; location.href='?app=1'; });

if ($('map')) {
  const map = L.map('map').setView([35.7219, 51.3347], 12);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom: 19, attribution:'© OpenStreetMap'}).addTo(map);
  map.on('click', (e) => { selectedPoint = e.latlng; $('eventComposer').classList.remove('hidden'); L.popup().setLatLng(e.latlng).setContent('اینجا را برای قرار/نقطه انتخاب کردید').openOn(map); });

  const name = () => $('displayName').value.trim() || localStorage.mmapName || 'مهمان';
  const refresh = async () => {
    const state = await api({action:'state'}); markers.forEach(m => m.remove()); markers = [];
    state.users?.forEach(u => markers.push(L.marker([u.lat,u.lng]).addTo(map).bindPopup(`👤 ${u.name}<br>آخرین حضور: ${new Date(u.last_seen*1000).toLocaleTimeString('fa-IR')}`)));
    state.events?.forEach(ev => markers.push(L.circleMarker([ev.lat,ev.lng], {radius:10,color: ev.group_only == 1 ? '#f59e0b' : '#22c55e'}).addTo(map).bindPopup(`<b>${ev.title}</b><br>${ev.note || ''}<br>${ev.allowed_names ? 'مجاز: '+ev.allowed_names : 'عمومی'}`)));
    $('favorites').innerHTML = (state.favorites||[]).map(f=>`<div>⭐ ${f.title} <small>${(+f.lat).toFixed(3)}, ${(+f.lng).toFixed(3)}</small></div>`).join('') || '<small>هنوز علاقه‌مندی ندارید.</small>';
    $('friends').innerHTML = (state.friends||[]).map(f=>`<div>🤝 ${f.requester} → ${f.target} (${f.status})</div>`).join('') || '<small>درخواستی ثبت نشده.</small>';
    const msgs = (state.messages||[]).filter(m => m.room === currentRoom || currentRoom === 'global');
    $('messages').innerHTML = msgs.reverse().map(m=>`<div class="msg"><b>${m.sender_name}</b><p>${m.body}</p><small>${(JSON.parse(m.seen_by||'[]')).length ? 'سین شده توسط: '+JSON.parse(m.seen_by||'[]').join('، ') : 'ارسال شد'}</small></div>`).join('');
    if (document.hasFocus()) api({action:'seen', name:name()});
  };

  $('loginBtn').onclick = async () => { localStorage.mmapName = name(); await api({action:'login', name:name(), email:$('email').value}); refresh(); };
  $('shareLocationBtn').onclick = () => navigator.geolocation?.watchPosition(p => api({action:'location', lat:p.coords.latitude, lng:p.coords.longitude}), alert, {enableHighAccuracy:true});
  $('saveEventBtn').onclick = async () => { if(!selectedPoint) return alert('اول روی نقشه کلیک کنید'); await api({action:'event', title:$('eventTitle').value, note:$('eventNote').value, allowed:$('eventAllowed').value, group_only:$('eventGroupOnly').checked, lat:selectedPoint.lat, lng:selectedPoint.lng}); $('eventComposer').classList.add('hidden'); refresh(); };
  $('saveFavBtn').onclick = async () => { if(!selectedPoint) return alert('اول روی نقشه کلیک کنید'); await api({action:'favorite', title:$('eventTitle').value || 'نقطه محبوب', lat:selectedPoint.lat, lng:selectedPoint.lng}); refresh(); };
  $('sendBtn').onclick = async () => { if(!$('messageText').value.trim()) return; await api({action:'message', room:currentRoom, name:name(), body:$('messageText').value}); $('messageText').value=''; refresh(); };
  $('friendBtn').onclick = async () => { await api({action:'friend', name:name(), target:$('friendName').value}); refresh(); };
  $('createGroupBtn').onclick = async () => { currentRoom = 'group:'+($('roomName').value || 'گروه'); await api({action:'group', name:currentRoom, owner:name()}); refresh(); };
  $('blockBtn').onclick = async () => { await api({action:'block', name:name(), target:$('roomName').value}); alert('مخاطب فعلی بلاک شد'); };
  $('chatFab').onclick = () => $('chatPanel').classList.toggle('open'); $('closeChat').onclick = () => $('chatPanel').classList.remove('open');
  document.querySelectorAll('.tabs button').forEach(b => b.onclick = () => { document.querySelector('.tabs .active').classList.remove('active'); b.classList.add('active'); currentRoom = b.dataset.room === 'private' ? 'private:'+($('roomName').value||'دوست') : b.dataset.room; refresh(); });
  if ('serviceWorker' in navigator) navigator.serviceWorker.register('sw.js');
  refresh(); setInterval(refresh, 2500);
}
