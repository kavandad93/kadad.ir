<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$current_user = $_SESSION['user_id'];
$channel_id = $_GET['id'] ?? '';
$channel_name = $_GET['name'] ?? $channel_id;

$channelsFile = 'data/channels.json';
$channels = file_exists($channelsFile) ? json_decode(file_get_contents($channelsFile), true) : [];

if(!isset($channels[$channel_id])) {
    die("کانال یافت نشد");
}

$channel = $channels[$channel_id];
$is_admin = in_array($current_user, $channel['admins']) || $channel['owner'] == $current_user;
?>
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="UTF-8">
    <title>کانال <?php echo htmlspecialchars($channel_name); ?></title>
    <link rel="stylesheet" href="style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="chat-container">
        <div class="chat-header">
            <h3>📢 <?php echo htmlspecialchars($channel['name']); ?></h3>
            <button onclick="parent.location.href='dashboard.php'">بازگشت</button>
        </div>
        <div class="chat-messages" id="messages"></div>
        <?php if($is_admin): ?>
        <div class="chat-input">
            <textarea id="messageInput" placeholder="پیام..."></textarea>
            <input type="file" id="fileInput" style="display:none">
            <button onclick="$('#fileInput').click()">📎</button>
            <button onclick="sendMessage()">ارسال</button>
        </div>
        <?php else: ?>
        <div class="readonly-msg">🔒 فقط ادمین ها می توانند پیام بفرستند</div>
        <?php endif; ?>
    </div>
    
    <script>
        var channelId = '<?php echo $channel_id; ?>';
        var isAdmin = <?php echo $is_admin ? 'true' : 'false'; ?>;
        
        function loadMessages() {
            $.ajax({
                url: 'api/get_messages.php',
                method: 'GET',
                data: {channel_id: channelId, type: 'channel'},
                dataType: 'json',
                success: function(data) {
                    var html = '';
                    for(var i = 0; i < data.length; i++) {
                        var msg = data[i];
                        html += '<div class="message other">';
                        html += '<div class="message-sender">' + msg.from + '</div>';
                        html += '<div class="message-text">' + msg.message + '</div>';
                        if(msg.media) {
                            html += '<img src="uploads/media/' + msg.media + '" class="message-media">';
                        }
                        html += '<div class="message-time">' + new Date(msg.timestamp * 1000).toLocaleTimeString('fa-IR') + '</div>';
                        html += '</div>';
                    }
                    if(html == '') html = '<div class="empty-msg">📢 پیامی وجود ندارد</div>';
                    $('#messages').html(html);
                    $('#messages').scrollTop($('#messages')[0].scrollHeight);
                }
            });
        }
        
        function sendMessage() {
            if(!isAdmin) {
                alert("شما اجازه ارسال پیام ندارید!");
                return;
            }
            
            var message = $('#messageInput').val();
            if(!message.trim() && !$('#fileInput')[0].files[0]) return;
            
            var formData = new FormData();
            formData.append('from', '<?php echo $current_user; ?>');
            formData.append('to', channelId);
            formData.append('type', 'channel');
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
            if(e.which == 13 && !e.shiftKey && isAdmin) {
                e.preventDefault();
                sendMessage();
            }
        });
        
        loadMessages();
        setInterval(loadMessages, 3000);
    </script>
</body>
</html>