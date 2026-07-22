<?php
session_start();
$usersFile = 'data/users.json';
$users = file_exists($usersFile) ? json_decode(file_get_contents($usersFile), true) : [];

if(isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$login_error = '';
$register_error = '';

// پردازش ورود
if(isset($_POST['login'])) {
    $user_id = trim($_POST['user_id']);
    $password = $_POST['password'];
    
    if(isset($users[$user_id]) && password_verify($password, $users[$user_id]['password'])) {
        $_SESSION['user_id'] = $user_id;
        header("Location: dashboard.php");
        exit();
    } else {
        $login_error = "آیدی یا رمز عبور اشتباه است!";
    }
}

// پردازش ثبت نام
if(isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $user_id = trim($_POST['user_id']);
    $password = $_POST['password'];
    
    if(isset($users[$user_id])) {
        $register_error = "این آیدی قبلاً ثبت شده است!";
    } elseif(empty($username) || empty($user_id) || empty($password)) {
        $register_error = "تمامی فیلدها را پر کنید!";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $profile_pic = 'default.png';
        
        if(isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
            $ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
            if(in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                $profile_pic = $user_id . '_' . time() . '.' . $ext;
                move_uploaded_file($_FILES['profile_pic']['tmp_name'], 'uploads/profiles/' . $profile_pic);
            }
        }
        
        $users[$user_id] = [
            'username' => $username,
            'password' => $hashed_password,
            'profile_pic' => $profile_pic,
            'friends' => [],
            'blocked' => [],
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
        $_SESSION['user_id'] = $user_id;
        header("Location: dashboard.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پیام رسان</title>
    <link rel="stylesheet" href="style.css">
</head>
<body style="background-image: url('bg.png'); background-size: cover; background-attachment: fixed;">
    <div class="auth-container">
        <div class="auth-cards">
            <!-- فرم ورود -->
            <div class="card login-card">
                <h2>✨ ورود</h2>
                <?php if($login_error): ?>
                    <div class="error-msg"><?php echo $login_error; ?></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="input-group">
                        <input type="text" name="user_id" placeholder="آیدی" required>
                    </div>
                    <div class="input-group">
                        <input type="password" name="password" placeholder="رمز عبور" required>
                    </div>
                    <button type="submit" name="login" class="btn-primary">ورود</button>
                </form>
            </div>
            
            <!-- فرم ثبت نام -->
            <div class="card register-card">
                <h2>📝 ثبت نام</h2>
                <?php if($register_error): ?>
                    <div class="error-msg"><?php echo $register_error; ?></div>
                <?php endif; ?>
                <form method="POST" enctype="multipart/form-data">
                    <div class="input-group">
                        <input type="text" name="username" placeholder="نام کامل" required>
                    </div>
                    <div class="input-group">
                        <input type="text" name="user_id" placeholder="آیدی (یکتا)" required>
                    </div>
                    <div class="input-group">
                        <input type="password" name="password" placeholder="رمز عبور" required>
                    </div>
                    <div class="input-group">
                        <label class="file-label">
                            📸 انتخاب عکس پروفایل
                            <input type="file" name="profile_pic" accept="image/*" style="display:none">
                        </label>
                    </div>
                    <button type="submit" name="register" class="btn-primary">ثبت نام</button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // برای نمایش نام فایل انتخاب شده
        document.querySelectorAll('.file-label').forEach(function(label) {
            var input = label.querySelector('input');
            input.addEventListener('change', function() {
                if(this.files && this.files[0]) {
                    label.innerHTML = '📸 ' + this.files[0].name;
                } else {
                    label.innerHTML = '📸 انتخاب عکس پروفایل';
                }
            });
        });
    </script>
</body>
</html>