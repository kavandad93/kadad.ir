<?php
session_start();
$messagesFile = '../data/messages.json';
$messages = file_exists($messagesFile) ? json_decode(file_get_contents($messagesFile), true) : [];

$from = $_POST['from'] ?? '';
$to = $_POST['to'] ?? '';
$type = $_POST['type'] ?? '';
$message = $_POST['message'] ?? '';
$timestamp = time();

$message = htmlspecialchars($message);
$message = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $message);
$message = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $message);
$message = nl2br($message);

$msgData = [
    'from' => $from,
    'to' => $to,
    'type' => $type,
    'message' => $message,
    'timestamp' => $timestamp,
    'media' => null
];

if(isset($_FILES['media']) && $_FILES['media']['error'] == 0) {
    $ext = strtolower(pathinfo($_FILES['media']['name'], PATHINFO_EXTENSION));
    $forbidden = ['apk', 'exe', 'php', 'html', 'js'];
    
    if(!in_array($ext, $forbidden) && $_FILES['media']['size'] <= 5 * 1024 * 1024) {
        $filename = time() . '_' . $from . '.' . $ext;
        move_uploaded_file($_FILES['media']['tmp_name'], '../uploads/media/' . $filename);
        $msgData['media'] = $filename;
    }
}

$messages[] = $msgData;
file_put_contents($messagesFile, json_encode($messages, JSON_PRETTY_PRINT));

echo json_encode(['success' => true]);
?>