<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$current_user = $_SESSION['user_id'];
$group_id = $_GET['id'] ?? '';
$group_name = $_GET['name'] ?? $group_id;

$groupsFile = 'data/groups.json';
$groups = file_exists($groupsFile) ? json_decode(file_get_contents($groupsFile), true) : [];

if(!isset($groups[$group_id])) {
    die("گروه یافت نشد");
}

$group = $groups[$group_id];
$is_member = in_array($current_user, $group['members']);
?>
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="UTF-8">
    <title>گروه <?php echo htmlspecialchars($group_name); ?></title>
    <link rel="stylesheet" href="style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="chat-container">
        <div class="chat-header">
            <h3>👥 <?php echo htmlspecialchars($group['name']); ?></h3>
            <button onclick="parent.location.href='dashboard.php'">بازگشت</button>
        </div>
        <div class="chat-messages" id="messages"></div>
        <?php if($is_member): ?>
        <div class="chat-input">
            <textarea id="messageInput" placeholder="پیام..."></textarea>
            <input type="file" id="fileInput" style="display:none">
            <button onclick="$('#fileInput').click()">📎</button>
            <button onclick="sendMessage()">ارسال</button>
        </div>
        <?php else: ?>
        <div class="readonly-msg">🔒 برای ارسال پیام باید عضو گروه شوید</div>
        <?php endif; ?>
    </div>
    
    <script>
        var groupId = '<?php echo $group_id; ?>';
        var isMember = <?php echo $is_member ? 'true' : 'false'; ?>;
        
        function loadMessages() {
            $.ajax({
                url: 'api/get_messages.php',
                method: 'GET',
                data: {group_id: groupId, type: 'group'},
                dataType: 'json',
                success: function(data) {
                    var html = '';
                    for(var i = 0; i < data.length; i++) {
                        var msg = data[i];
                        var cls = (msg.from == '<?php echo $current_user; ?>') ? 'own' : 'other';
                        html += '<div class="message ' + cls + '">';
                        html += '<div class="message-sender">' + msg.from + '</div>';
                        html += '<div class="message-text">' + msg.message + '</div>';
                        if(msg.media) {
                            html += '<img src="uploads/media/' + msg.media + '" class="message-media">';
                        }
                        html += '<div class="message-time">' + new Date(msg.timestamp * 1000).toLocaleTimeString('fa-IR') + '</div>';
                        html += '</div>';
                    }
                    if(html == '') html = '<div class="empty-msg">💬 پیامی وجود ندارد</div>';
                    $('#messages').html(html);
                    $('#messages').scrollTop($('#messages')[0].scrollHeight);
                }
            });
        }
        
        function sendMessage() {
            if(!isMember) {
                alert("شما عضو این گروه نیستید!");
                return;
            }
            
            var message = $('#messageInput').val();
            if(!message.trim() && !$('#fileInput')[0].files[0]) return;
            
            var formData = new FormData();
            formData.append('from', '<?php echo $current_user; ?>');
            formData.append('to', groupId);
            formData.append('type', 'group');
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
            if(e.which == 13 && !e.shiftKey && isMember) {
                e.preventDefault();
                sendMessage();
            }
        });
        
        loadMessages();
        setInterval(loadMessages, 3000);
    </script>
</body>
</html>