<?php
$user = $_GET['user'] ?? '';
if(empty($user)) {
    header("Location: ../index.php");
    exit();
}

$groupsFile = '../data/groups.json';
$groups = file_exists($groupsFile) ? json_decode(file_get_contents($groupsFile), true) : [];

$name = $_POST['name'] ?? '';
$id = $_POST['id'] ?? '';

if(!isset($groups[$id])) {
    $groups[$id] = [
        'name' => $name,
        'id' => $id,
        'owner' => $user,
        'admins' => [$user],
        'members' => [$user],
        'created_at' => date('Y-m-d H:i:s')
    ];
    file_put_contents($groupsFile, json_encode($groups, JSON_PRETTY_PRINT));
}

header("Location: ../dashboard.php?user=" . urlencode($user));
exit();
?>