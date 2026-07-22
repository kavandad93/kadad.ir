<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $channelsFile = '../data/channels.json';
    $channels = file_exists($channelsFile) ? json_decode(file_get_contents($channelsFile), true) : [];
    
    $name = $_POST['name'] ?? '';
    $id = $_POST['id'] ?? '';
    $owner = $_SESSION['user_id'];
    
    if(isset($channels[$id])) {
        $error = 'این آیدی قبلاً استفاده شده است!';
    } else {
        $channels[$id] = [
            'name' => $name,
            'id' => $id,
            'owner' => $owner,
            'admins' => [$owner],
            'members' => [$owner],
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        file_put_contents($channelsFile, json_encode($channels, JSON_PRETTY_PRINT));
        header("Location: ../dashboard.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="UTF-8">
    <title>ساخت کانال</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="create-page">
        <div class="card">
            <h2>ساخت کانال جدید</h2>
            <?php if($error): ?>
                <div class="error-msg"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="text" name="name" placeholder="نام کانال" required>
                <input type="text" name="id" placeholder="آیدی کانال" required>
                <button type="submit">ساخت کانال</button>
                <button type="button" onclick="parent.location.href='../dashboard.php'">انصراف</button>
            </form>
        </div>
    </div>
</body>
</html>