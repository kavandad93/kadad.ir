<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$current_user = $_SESSION['user_id'];
$other_user = $_GET['id'] ?? '';
$other_name = $_GET['name'] ?? $other_user;

$usersFile = 'data/users.json';
$users = json_decode(file_get_contents($usersFile), true);

if(!isset($users[$other_user])) {
    die("کاربر یافت نشد");
}

if(in_array($current_user, $users[$other_user]['blocked'] ?? [])) {
    die("شما توسط این کاربر بلاک شده اید");
}
?>
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="UTF-8">
    <title>چت با <?php echo htmlspecialchars($other_name); ?></title>
    <link rel="stylesheet" href="style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="chat-container">
        <div class="chat-header">
            <h3>💬 <?php echo htmlspecialchars($other_name); ?></h3>
            <button onclick="parent.location.href='dashboard.php'">بازگشت</button>
        </div>
        <div class="chat-messages" id="messages"></div>
        <div class="chat-input">
            <textarea id="messageInput" placeholder="پیام... **پررنگ** *ایتالیک*"></textarea>
            <input type="file" id="fileInput" style="display:none">
            <button onclick="$('#fileInput').click()">📎</button>
            <button onclick="sendMessage()">ارسال</button>
        </div>
    </div>
    
    <script>
        var currentUser = '<?php echo $current_user; ?>';
        var otherUser = '<?php echo $other_user; ?>';
        
        function loadMessages() {
            $.ajax({
                url: 'api/get_messages.php',
                method: 'GET',
                data: {user1: currentUser, user2: otherUser, type: 'pv'},
                dataType: 'json',
                success: function(data) {
                    var html = '';
                    for(var i = 0; i < data.length; i++) {
                        var msg = data[i];
                        var cls = (msg.from == currentUser) ? 'own' : 'other';
                        html += '<div class="message ' + cls + '">';
                        html += '<div class="message-text">' + msg.message + '</div>';
                        if(msg.media) {
                            html += '<img src="uploads/media/' + msg.media + '" class="message-media">';
                        }
                        html += '<div class="message-time">' + new Date(msg.timestamp * 1000).toLocaleTimeString('fa-IR') + '</div>';
                        html += '</div>';
                    }
                    if(html == '') html = '<div class="empty-msg">✨ پیامی وجود ندارد</div>';
                    $('#messages').html(html);
                    $('#messages').scrollTop($('#messages')[0].scrollHeight);
                }
            });
        }
        
        function sendMessage() {
            var message = $('#messageInput').val();
            if(!message.trim() && !$('#fileInput')[0].files[0]) return;
            
            var formData = new FormData();
            formData.append('from', currentUser);
            formData.append('to', otherUser);
            formData.append('type', 'pv');
            formData.append('message', message);
            if($('#fileInput')[0].files[0]) {
                formData.append('media', $('#fileInput')[0].files[0]);
            }
            
            $.ajax({
                url: 'api/send_message.php',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function() {
                    $('#messageInput').val('');
                    $('#fileInput').val('');
                    loadMessages();
                }
            });
        }
        
        $('#messageInput').on('keypress', function(e) {
            if(e.which == 13 && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
        
        loadMessages();
        setInterval(loadMessages, 3000);
    </script>
</body>
</html>