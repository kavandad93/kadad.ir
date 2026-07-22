<?php ?>
<!DOCTYPE html>
<html dir="rtl">
<head>
<meta charset="utf-8">
<title>اتاق</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="box">

<h2>🎮 اتاق بازی</h2>

<button onclick="createRoom()">ساخت اتاق</button>

<input id="roomId" placeholder="کد اتاق">

<button onclick="joinRoom()">ورود</button>

<hr>

<div id="panel" style="display:none">

<h3>🎭 تنظیم نقش‌ها (میزبان)</h3>

مافیا <input id="mafia" type="number" value="1"><br>
دکتر <input id="doctor" type="number" value="1"><br>
کارآگاه <input id="detective" type="number" value="1"><br>
جوکر <input id="joker" type="number" value="0"><br>
اسکورت <input id="escort" type="number" value="0"><br>
ورولو <input id="werewolf" type="number" value="0"><br>
ومپایر <input id="vampire" type="number" value="0"><br>

<button onclick="saveConfig()">ذخیره</button>

<br><br>

<button onclick="startGame()">🚀 شروع بازی</button>

</div>

</div>

<script>
let roomId = null;

function createRoom()
{
    fetch("api/create_room.php")
    .then(r=>r.text())
    .then(id=>{
        roomId = id;
        document.getElementById("panel").style.display="block";
        alert("Room: "+id);
    });
}

function joinRoom()
{
    roomId = document.getElementById("roomId").value;
    location.href="game.php?id="+roomId;
}

function saveConfig()
{
    fetch("api/save_roles_config.php?id="+roomId,{
        method:"POST",
        body:JSON.stringify({
            mafia:+mafia.value,
            doctor:+doctor.value,
            detective:+detective.value,
            joker:+joker.value,
            escort:+escort.value,
            werewolf:+werewolf.value,
            vampire:+vampire.value
        })
    })
    .then(r=>r.text())
    .then(alert);
}

function startGame()
{
    location.href="api/start_game.php?id="+roomId;
}
</script>

</body>
</html>