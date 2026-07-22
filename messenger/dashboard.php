<?php
// دیگه سشن چک نمیکنه - مستقیم میاد داخل صفحه
// ولی برای امنیت، پارامترهای GET رو چک میکنه

$user_id = $_GET['user'] ?? '';
$password = $_GET['Pass'] ?? '';

$usersFile = 'data/users.json';
$users = file_exists($usersFile) ? json_decode(file_get_contents($usersFile), true) : [];

$is_logged_in = false;
$current_user = '';

if($user_id && $password && isset($users[$user_id])) {
    if(password_verify($password, $users[$user_id]['password'])) {
        $is_logged_in = true;
        $current_user = $user_id;
    }
}

// اگر لاگین نکرده بود، برگرد به صفحه ورود
if(!$is_logged_in) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>داشبورد</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body style="background-image: url('bg.png'); background-size: cover;">
    <div class="dashboard">
        <div class="left-panel">
            <?php include 'includes/left_panel.php'; ?>
        </div>
        
        <div class="center-panel">
            <iframe name="mainFrame" id="mainFrame" src="welcome.php" class="content-iframe"></iframe>
        </div>
    </div>
    
    <div class="fab-button" onclick="toggleMenu()">
        <span class="fab-icon">+</span>
        <div class="fab-menu" id="fabMenu">
            <div onclick="createChannel()">📢 کانال جدید</div>
            <div onclick="createGroup()">👥 گروه جدید</div>
        </div>
    </div>
    
    <script>
        var currentUser = '<?php echo $current_user; ?>';
        
        function toggleMenu() {
            var menu = document.getElementById('fabMenu');
            menu.style.display = menu.style.display == 'flex' ? 'none' : 'flex';
        }
        
        function createChannel() {
            document.getElementById('mainFrame').src = 'api/create_channel.php?user=' + currentUser;
            document.getElementById('fabMenu').style.display = 'none';
        }
        
        function createGroup() {
            document.getElementById('mainFrame').src = 'api/create_group.php?user=' + currentUser;
            document.getElementById('fabMenu').style.display = 'none';
        }
        
        function openChat(type, id, name) {
            document.getElementById('mainFrame').src = type + '.php?id=' + id + '&name=' + encodeURIComponent(name) + '&user=' + currentUser;
        }
        
        function openProfile(userId) {
            document.getElementById('mainFrame').src = 'users/' + userId + '.php?user=' + currentUser;
        }
        
        $(document).click(function(e) {
            if(!$(e.target).closest('.fab-button').length) {
                $('#fabMenu').hide();
            }
        });
    </script>
</body>
</html>