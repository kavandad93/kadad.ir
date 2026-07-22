<?php
// اصلاح مسیر
$usersFile = __DIR__ . '/../data/users.json';

if(!file_exists($usersFile)) {
    echo "";
    exit();
}

$users = json_decode(file_get_contents($usersFile), true);

$query = $_POST['query'] ?? '';
$current = $_POST['current'] ?? '';
$html = '';

if(empty($query) || empty($current)) {
    echo "";
    exit();
}

foreach($users as $id => $user) {
    if($id != $current && strpos($id, $query) !== false) {
        $html .= '<div class="search-result" onclick="parent.openChat(\'user\', \'' . $id . '\', \'' . addslashes($user['username']) . '\')">';
        $html .= '<img src="../uploads/profiles/' . $user['profile_pic'] . '" class="search-avatar" onerror="this.src=\'../uploads/profiles/default.png\'">';
        $html .= '<div><strong>' . htmlspecialchars($user['username']) . '</strong><br><small>@' . $id . '</small></div>';
        $html .= '</div>';
    }
}

echo $html;
?>