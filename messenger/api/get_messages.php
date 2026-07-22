<?php
$messagesFile = '../data/messages.json';
$messages = file_exists($messagesFile) ? json_decode(file_get_contents($messagesFile), true) : [];

$type = $_GET['type'] ?? '';
$result = [];

if($type == 'pv') {
    $user1 = $_GET['user1'] ?? '';
    $user2 = $_GET['user2'] ?? '';
    
    foreach($messages as $msg) {
        if($msg['type'] == 'pv') {
            if(($msg['from'] == $user1 && $msg['to'] == $user2) ||
               ($msg['from'] == $user2 && $msg['to'] == $user1)) {
                $result[] = $msg;
            }
        }
    }
} elseif($type == 'channel') {
    $channel_id = $_GET['channel_id'] ?? '';
    
    foreach($messages as $msg) {
        if($msg['type'] == 'channel' && $msg['to'] == $channel_id) {
            $result[] = $msg;
        }
    }
} elseif($type == 'group') {
    $group_id = $_GET['group_id'] ?? '';
    
    foreach($messages as $msg) {
        if($msg['type'] == 'group' && $msg['to'] == $group_id) {
            $result[] = $msg;
        }
    }
}

$result = array_slice($result, -50);
echo json_encode($result);
?>