<?php
$usersFile = '../data/users.json';
$users = json_decode(file_get_contents($usersFile), true);

$from = $_POST['from'] ?? '';
$to = $_POST['to'] ?? '';

if(!isset($users[$from]) || !isset($users[$to])) {
    echo "کاربر یافت نشد";
    exit();
}

if(in_array($to, $users[$from]['friends'])) {
    echo "قبلاً دوست هستید";
    exit();
}

$users[$from]['friends'][] = $to;
$users[$to]['friends'][] = $from;

file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));

echo "درخواست دوستی ارسال شد";
?>