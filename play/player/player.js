const video = document.getElementById("video");
const quality = document.getElementById("quality");

// 🎬 set default (AUTO)
video.src = sources.auto;

// 🔥 populate quality menu
Object.keys(sources).forEach(q => {
  const opt = document.createElement("option");
  opt.value = q;
  opt.textContent = q.toUpperCase();
  quality.appendChild(opt);
});

// 🎯 change quality manually
quality.addEventListener("change", () => {
  const currentTime = video.currentTime;
  const isPlaying = !video.paused;

  video.src = sources[quality.value];
  
  video.addEventListener("loadedmetadata", () => {
    video.currentTime = currentTime;
    if (isPlaying) video.play();
  });
});

// ▶ play/pause
function togglePlay() {
  video.paused ? video.play() : video.pause();
}

// ⏩ skip
function skip(sec) {
  video.currentTime += sec;
}

// 🔊 volume
document.getElementById("volume").addEventListener("input", (e) => {
  video.volume = e.target.value;
});

// 🖥 fullscreen
function fullscreen() {
  video.requestFullscreen();
}

function exitFS() {
  document.exitFullscreen();
}

// 🔥 AUTO QUALITY (basic smart switch)
video.addEventListener("progress", () => {
  if (video.buffered.length) {
    const buffered = video.buffered.end(0);
    const current = video.currentTime;

    // اگر اینترنت ضعیف بود → downgrade
    if (buffered - current < 2 && quality.value === "1080") {
      quality.value = "720";
      quality.dispatchEvent(new Event("change"));
    }
  }
});