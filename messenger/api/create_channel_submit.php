<?php
$user = $_GET['user'] ?? '';
if(empty($user)) {
    header("Location: ../index.php");
    exit();
}

$channelsFile = '../data/channels.json';
$channels = file_exists($channelsFile) ? json_decode(file_get_contents($channelsFile), true) : [];

$name = $_POST['name'] ?? '';
$id = $_POST['id'] ?? '';

if(!isset($channels[$id])) {
    $channels[$id] = [
        'name' => $name,
        'id' => $id,
        'owner' => $user,
        'admins' => [$user],
        'members' => [$user],
        'created_at' => date('Y-m-d H:i:s')
    ];
    file_put_contents($channelsFile, json_encode($channels, JSON_PRETTY_PRINT));
}

header("Location: ../dashboard.php?user=" . urlencode($user));
exit();
?>