<?php
$src = $_GET['src'] ?? null;
$id  = $_GET['id'] ?? null;

$url = $src ?: "/videos/$id.mp4";
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Universal Player FIX</title>

<style>
body{
  margin:0;
  background:black;
  font-family:sans-serif;
}

/* PLAYER ROOT */
.player{
  width:100vw;
  height:100vh;
  position:relative;
  background:black;
  overflow:hidden;
}

/* VIDEO ALWAYS EXISTS */
video{
  width:100%;
  height:100%;
  background:black;
  display:block;
}

/* IFRAME WRAPPER */
iframe{
  width:100%;
  height:100%;
  border:0;
}

/* CONTROLS */
.controls{
  position:absolute;
  bottom:0;
  width:100%;
  display:flex;
  justify-content:space-between;
  align-items:center;
  padding:12px;
  background:linear-gradient(to top, rgba(0,0,0,0.85), transparent);
  color:white;
  z-index:10;
}

.left, .right{
  display:flex;
  gap:10px;
  align-items:center;
}

button{
  background:#222;
  color:white;
  border:none;
  padding:8px 10px;
  border-radius:6px;
  cursor:pointer;
}

button:hover{
  background:#444;
}

input[type=range]{
  width:80px;
}

/* LOADER */
#loading{
  position:absolute;
  top:50%;
  left:50%;
  transform:translate(-50%,-50%);
  color:white;
}
</style>

</head>

<body>

<div class="player">

<div id="loading">Loading...</div>

<!-- VIDEO LAYER (ALWAYS PRESENT) -->
<video id="video"></video>

<!-- IFRAME LAYER (hidden by default) -->
<div id="iframeBox" style="display:none;width:100%;height:100%;position:absolute;top:0;left:0;"></div>

<!-- CONTROLS -->
<div class="controls">

  <div class="left">
    <button onclick="toggle()">▶/⏸</button>
    <button onclick="seek(-10)">⏪</button>
    <button onclick="seek(10)">⏩</button>

    <input type="range" id="vol" min="0" max="1" step="0.1">
  </div>

  <div class="right">
    <button onclick="fullscreen()">⛶</button>
    <button onclick="openKadad()">Kadad</button>
  </div>

</div>

</div>

<script>
const url = <?= json_encode($url) ?>;

const video = document.getElementById("video");
const iframeBox = document.getElementById("iframeBox");
const loading = document.getElementById("loading");

let mode = "";

/* ---------- DETECT ---------- */
function isEmbed(u){
  return u.includes("aparat.com") || u.includes("youtube.com");
}

/* ---------- INIT ---------- */
if(isEmbed(url)){

  mode = "iframe";

  video.style.display = "none";

  const iframe = document.createElement("iframe");
  iframe.src = url;
  iframe.allowFullscreen = true;

  iframeBox.style.display = "block";
  iframeBox.appendChild(iframe);

  loading.style.display = "none";

}else{

  mode = "video";

  video.src = url;

  video.addEventListener("loadeddata", ()=>{
    loading.style.display = "none";
  });

  video.addEventListener("error", ()=>{
    loading.innerText = "Video load error";
  });

}

/* ---------- CONTROLS ---------- */

function isVideoMode(){
  return mode === "video";
}

function toggle(){
  if(!isVideoMode()) return;
  video.paused ? video.play() : video.pause();
}

function seek(s){
  if(!isVideoMode()) return;
  video.currentTime += s;
}

document.getElementById("vol").addEventListener("input",(e)=>{
  if(!isVideoMode()) return;
  video.volume = e.target.value;
});

function fullscreen(){
  document.querySelector(".player").requestFullscreen();
}

function openKadad(){
  window.open("https://kadad.ir/play","_blank");
}
</script>

</body>
</html>