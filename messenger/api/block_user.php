<?php
$usersFile = '../data/users.json';
$users = json_decode(file_get_contents($usersFile), true);

$blocker = $_POST['blocker'] ?? '';
$blocked = $_POST['blocked'] ?? '';

if(!isset($users[$blocker]) || !isset($users[$blocked])) {
    echo "کاربر یافت نشد";
    exit();
}

if(!in_array($blocked, $users[$blocker]['blocked'])) {
    $users[$blocker]['blocked'][] = $blocked;
    
    $key = array_search($blocked, $users[$blocker]['friends']);
    if($key !== false) unset($users[$blocker]['friends'][$key]);
    
    $key = array_search($blocker, $users[$blocked]['friends']);
    if($key !== false) unset($users[$blocked]['friends'][$key]);
    
    file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
    echo "کاربر بلاک شد";
} else {
    echo "قبلاً بلاک شده است";
}
?>