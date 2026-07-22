<?php
$user = $_GET['user'] ?? '';
if(empty($user)) {
    header("Location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="UTF-8">
    <title>ساخت گروه</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="create-page">
        <div class="card">
            <h2>ساخت گروه جدید</h2>
            <form method="POST" action="create_group_submit.php?user=<?php echo $user; ?>">
                <div class="input-group">
                    <input type="text" name="name" placeholder="نام گروه" required>
                </div>
                <div class="input-group">
                    <input type="text" name="id" placeholder="آیدی گروه" required>
                </div>
                <button type="submit" class="btn-primary">ساخت گروه</button>
                <button type="button" onclick="parent.location.href='../dashboard.php?user=<?php echo $user; ?>'" class="btn-secondary">انصراف</button>
            </form>
        </div>
    </div>
</body>
</html>