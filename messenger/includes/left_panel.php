<?php
// دریافت کاربر از GET
$current_user = $_GET['user'] ?? $_GET['current_user'] ?? '';

if(empty($current_user)) {
    // تلاش برای دریافت از URL parent
    $current_user = $_REQUEST['user'] ?? '';
}

$usersFile = __DIR__ . '/../data/users.json';
$channelsFile = __DIR__ . '/../data/channels.json';
$groupsFile = __DIR__ . '/../data/groups.json';

$users = json_decode(file_get_contents($usersFile), true);
$channels = file_exists($channelsFile) ? json_decode(file_get_contents($channelsFile), true) : [];
$groups = file_exists($groupsFile) ? json_decode(file_get_contents($groupsFile), true) : [];

if(!isset($users[$current_user])) {
    echo "کاربر یافت نشد: " . $current_user;
    exit();
}

$user_data = $users[$current_user];
?>

<div class="profile-card">
    <img src="../uploads/profiles/<?php echo $user_data['profile_pic']; ?>" class="profile-avatar" onerror="this.src='../uploads/profiles/default.png'">
    <h3><?php echo htmlspecialchars($user_data['username']); ?></h3>
    <p>@<?php echo $current_user; ?></p>
    <button onclick="parent.openProfile('<?php echo $current_user; ?>')">پروفایل من</button>
    <button onclick="location.href='index.php'">خروج</button>
</div>

<div class="search-card">
    <input type="text" id="searchInput" placeholder="جستجوی کاربران">
    <div id="searchResults"></div>
</div>

<div class="chats-list">
    <h3>چت ها</h3>
    <?php
    if(!empty($user_data['friends'])) {
        foreach($user_data['friends'] as $friend_id) {
            if(isset($users[$friend_id])) {
                echo '<div class="chat-item" onclick="parent.openChat(\'user\', \'' . $friend_id . '\', \'' . addslashes($users[$friend_id]['username']) . '\')">';
                echo '<img src="../uploads/profiles/' . $users[$friend_id]['profile_pic'] . '" class="chat-avatar" onerror="this.src=\'../uploads/profiles/default.png\'">';
                echo '<div>' . htmlspecialchars($users[$friend_id]['username']) . '</div>';
                echo '</div>';
            }
        }
    }
    
    foreach($channels as $id => $channel) {
        if(in_array($current_user, $channel['members'])) {
            echo '<div class="chat-item" onclick="parent.openChat(\'channel\', \'' . $id . '\', \'' . addslashes($channel['name']) . '\')">';
            echo '<div class="chat-icon">📢</div>';
            echo '<div>' . htmlspecialchars($channel['name']) . '</div>';
            echo '</div>';
        }
    }
    
    foreach($groups as $id => $group) {
        if(in_array($current_user, $group['members'])) {
            echo '<div class="chat-item" onclick="parent.openChat(\'group\', \'' . $id . '\', \'' . addslashes($group['name']) . '\')">';
            echo '<div class="chat-icon">👥</div>';
            echo '<div>' . htmlspecialchars($group['name']) . '</div>';
            echo '</div>';
        }
    }
    
    if(empty($user_data['friends']) && empty($channels) && empty($groups)) {
        echo '<div style="text-align:center;padding:20px;color:#999;">✨ هیچ چتی وجود ندارد</div>';
    }
    ?>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
var currentUser = '<?php echo $current_user; ?>';

$('#searchInput').on('input', function() {
    var query = $(this).val();
    if(query.length < 2) {
        $('#searchResults').hide();
        return;
    }
    
    $.ajax({
        url: '../api/search.php',
        method: 'POST',
        data: {query: query, current: currentUser},
        success: function(data) {
            if(data && data.trim()) {
                $('#searchResults').html(data).show();
            } else {
                $('#searchResults').hide();
            }
        }
    });
});

$(document).click(function(e) {
    if(!$(e.target).closest('.search-card').length) {
        $('#searchResults').hide();
    }
});
</script>