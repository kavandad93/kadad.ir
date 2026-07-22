<?php
?>
<!DOCTYPE html>
<html dir="rtl">
<head>
<meta charset="utf-8">
<title>مافیا</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="box">

<h1>🎭 مافیا آنلاین</h1>

<input id="name" placeholder="نام شما">

<button onclick="go()">ورود</button>

</div>

<script>
function go()
{
    const name = document.getElementById("name").value;
    if(!name) return alert("نام وارد کن");

    localStorage.setItem("name", name);

    location.href="room.php";
}
</script>

</body>
</html>