<?php $id=$_GET["id"]; ?>
<!DOCTYPE html>
<html dir="rtl">
<head>
<meta charset="utf-8">
<title>بازی</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="box">

<h2>🎭 اتاق <?= $id ?></h2>

<div id="players"></div>

</div>

<script>

const id = "<?= $id ?>";

setInterval(()=>{
fetch("api/get_room.php?id="+id)
.then(r=>r.json())
.then(data=>{

let html="";

data.players.forEach(p=>{
html += "👤 "+p.name+"<br>";
});

document.getElementById("players").innerHTML=html;

});

},1000);

</script>

</body>
</html>