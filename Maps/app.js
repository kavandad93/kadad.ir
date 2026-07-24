const $=(id)=>document.getElementById(id);
const installed=()=>matchMedia('(display-mode: standalone)').matches||navigator.standalone===true||new URLSearchParams(location.search).get('app')==='1';
const state=JSON.parse(localStorage.getItem('maps-aaa-state')||'{}');
state.people??=[{id:'sara',name:'سارا',lat:35.7219,lng:51.3347},{id:'reza',name:'رضا',lat:35.697,lng:51.389},{id:'nima',name:'نیما',lat:35.744,lng:51.421}];
state.points??=[{id:'p1',title:'قرار کافه',lat:35.715,lng:51.404,owner:'سارا'},{id:'p2',title:'Fav پارک آب‌و‌آتش',lat:35.754,lng:51.409,owner:'رضا'}];
state.events??=[{id:'e1',title:'رویداد گروه دوچرخه',lat:35.735,lng:51.365,access:'سارا، رضا'}];
state.groups??=[{id:'g1',name:'گروه دوستان',members:'سارا، رضا، من'}];
state.messages??={sara:[{from:'سارا',text:'سلام، لوکیشنت رو روی نقشه می‌بینم.'}],g1:[{from:'سیستم',text:'قرار گروهی روی نقشه فعال شد.'}]};
let map,deferredInstall,activeChat='sara';
function save(){localStorage.setItem('maps-aaa-state',JSON.stringify(state))}
function icon(kind,text){return L.divIcon({className:'',html:`<span class="pin ${kind}">${text}</span>`})}
function showApp(){ $('landing').hidden=true; $('app').hidden=false; initMap(); renderChats(); renderEvents() }
function initMap(){ if(map||!window.L)return; map=L.map('map',{zoomControl:false}).setView([35.721,51.39],12); L.control.zoom({position:'bottomleft'}).addTo(map); L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19,attribution:'&copy; OpenStreetMap'}).addTo(map); renderMap() }
function renderMap(){ if(!map)return; map.eachLayer(l=>{if(l.options?.mapsLayer)map.removeLayer(l)}); state.people.forEach(p=>L.marker([p.lat,p.lng],{icon:icon(p.me?'me':'person',p.me?`من: ${p.name}`:`👤 ${p.name}`),mapsLayer:true}).addTo(map).bindPopup(`<b>${p.name}</b><br><button onclick="openChat('${p.id}')">چت خصوصی</button>`)); state.points.forEach(p=>L.marker([p.lat,p.lng],{icon:icon('point',`📍 ${p.title}`),mapsLayer:true}).addTo(map).bindPopup(`<b>${p.title}</b><br>سازنده: ${p.owner||'مهمان'}`)); state.events.forEach(e=>L.marker([e.lat,e.lng],{icon:icon('event',`🎟 ${e.title}`),mapsLayer:true}).addTo(map).bindPopup(`<b>${e.title}</b><br>${e.access?`فقط برای: ${e.access}`:'رویداد عمومی'}<br><button onclick="attend('${e.id}')">حضور/تغییر</button>`)); const me=state.people.find(p=>p.me); $('sharingState').textContent=me?`لوکیشن ${me.name} فعال است و رسیدن به نقاط بررسی می‌شود.`:'لوکیشن هنوز شیر نشده است.' }
async function notify(msg){ if(!('Notification'in window))return alert(msg); if(Notification.permission==='granted')new Notification('Maps',{body:msg,icon:'icon.svg'}); else alert(msg) }
function distance(a,b){return Math.hypot((a.lat-b.lat)*111,(a.lng-b.lng)*91)}
function checkArrivals(me){[...state.points,...state.events].filter(x=>distance(me,x)<1.2).forEach(x=>notify(`${me.name} به محدوده «${x.title}» رسید.`))}
function renderEvents(){ $('eventList').innerHTML=state.events.map(e=>`<article class="card"><b>${e.title}</b><p>${e.access?`خصوصی برای: ${e.access}`:'عمومی برای همه'}</p><button onclick="attend('${e.id}')">حضور دارم / تغییر می‌دهم</button></article>`).join('') }
window.attend=(id)=>{const e=state.events.find(x=>x.id===id); if(e)notify(`وضعیت حضور برای ${e.title} ثبت/تغییر شد.`)};
function renderChats(){ const targets=[...state.people.filter(p=>!p.me).map(p=>({id:p.id,label:`👤 ${p.name}`})),...state.groups.map(g=>({id:g.id,label:`👥 ${g.name}`}))]; $('chatTargets').innerHTML=targets.map(t=>`<button class="target ${t.id===activeChat?'active':''}" data-chat="${t.id}">${t.label}<span>›</span></button>`).join(''); document.querySelectorAll('[data-chat]').forEach(b=>b.onclick=()=>openChat(b.dataset.chat)); const t=targets.find(x=>x.id===activeChat); $('chatTitle').textContent=t?t.label:'یک گفتگو انتخاب کنید'; $('chatMessages').innerHTML=(state.messages[activeChat]||[]).map(m=>`<div class="bubble ${m.from==='من'?'me':''}"><b>${m.from}</b><br>${m.text}</div>`).join('')||'<p>هنوز پیامی نیست.</p>' }
window.openChat=(id)=>{activeChat=id;$('chatDrawer').hidden=false;renderChats()};
window.addEventListener('beforeinstallprompt',e=>{e.preventDefault();deferredInstall=e;$('installBtn').hidden=false});
$('installBtn').onclick=async()=>{if(deferredInstall){deferredInstall.prompt();deferredInstall=null;$('installBtn').hidden=true}};
$('shareLocation').onclick=()=>{const name=$('displayName').value.trim();if(!name)return alert('قبل از Share Location اسم خود را وارد کنید.');const c=map.getCenter();const me={id:'me',name,lat:c.lat+(Math.random()-.5)/80,lng:c.lng+(Math.random()-.5)/80,me:true};state.people=state.people.filter(p=>!p.me).concat(me);save();renderMap();checkArrivals(me)};
$('addPoint').onclick=()=>{const title=prompt('نام نقطه/قرار؟','قرار جدید');if(!title)return;const c=map.getCenter();state.points.push({id:crypto.randomUUID(),title,lat:c.lat,lng:c.lng,owner:$('displayName').value.trim()||'مهمان'});save();renderMap();notify(`نقطه ${title} ساخته شد؛ رسیدن افراد اعلان می‌دهد.`)};
$('notifyBtn').onclick=async()=>{if('Notification'in window){const p=await Notification.requestPermission();notify(p==='granted'?'اعلان‌ها فعال شد.':'برای اعلان باید اجازه بدهید.')}};
$('loginBtn').onclick=()=>alert('در نسخه بک‌اند، ثبت‌نام رایگان برای ذخیره Favها، همگام‌سازی، تاریخچه و مدیریت پیشرفته فعال می‌شود.');
$('eventForm').onsubmit=e=>{e.preventDefault();const c=map.getCenter();state.events.push({id:crypto.randomUUID(),title:$('eventTitle').value.trim(),access:$('eventAccess').value.trim(),lat:c.lat,lng:c.lng});e.target.reset();save();renderEvents();renderMap()};
$('chatFab').onclick=()=>{$('chatDrawer').hidden=false;renderChats()};$('closeChat').onclick=()=>{$('chatDrawer').hidden=true};
$('messageForm').onsubmit=e=>{e.preventDefault();const text=$('messageText').value.trim();if(!text)return;state.messages[activeChat]??=[];state.messages[activeChat].push({from:'من',text});$('messageText').value='';save();renderChats()};
$('groupForm').onsubmit=e=>{e.preventDefault();const group={id:crypto.randomUUID(),name:$('groupName').value.trim(),members:$('groupMembers').value.trim()||'عمومی'};state.groups.push(group);state.messages[group.id]=[{from:'سیستم',text:`گروه ${group.name} با دسترسی ${group.members} ساخته شد.`}];activeChat=group.id;e.target.reset();save();renderChats()};
if('serviceWorker'in navigator)navigator.serviceWorker.register('sw.js');
installed()?showApp():($('landing').hidden=false,$('app').hidden=true);
