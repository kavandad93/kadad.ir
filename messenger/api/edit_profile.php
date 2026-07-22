<?php
session_start();
$usersFile = '../data/users.json';
$users = json_decode(file_get_contents($usersFile), true);

$user_id = $_POST['user_id'] ?? $_SESSION['user_id'] ?? '';
$username = $_POST['username'] ?? '';

if($username && isset($users[$user_id])) {
    $users[$user_id]['username'] = $username;
    $_SESSION['username'] = $username;
    file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
    echo "success";
} else {
    echo "error";
}
?>