<?php include $_SERVER['DOCUMENT_ROOT']."/head.php"; ?>
<h2>افزودن قهوه</h2>
<form method="POST" action="save.php">
نام: <input name="name"><br>
نوع: <select name="type"><option>hot</option><option>ice</option></select><br>
JSON مواد:<br>
<textarea name="ingredients"></textarea><br>
JSON مراحل:<br>
<textarea name="steps"></textarea><br>
<button>ذخیره</button>
</form>
