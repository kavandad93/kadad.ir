<?php
// api/chat.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Security.php';
require_once __DIR__ . '/../classes/Auth.php';

Auth::check();
Security::enforceRateLimit();

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'list') {
    $stmt = $pdo->query("SELECT * FROM chats ORDER BY created_at DESC");
    echo json_encode(['success' => true, 'chats' => $stmt->fetchAll()]);
    exit;
}

if ($action === 'create') {
    $id = bin2hex(random_bytes(16));
    $input = json_decode(file_get_contents('php://input'), true);
    $title = $input['title'] ?? 'New Coding Session';
    
    $stmt = $pdo->prepare("INSERT INTO chats (id, title) VALUES (?, ?)");
    $stmt->execute([$id, $title]);
    echo json_encode(['success' => true, 'id' => $id, 'title' => $title]);
    exit;
}

if ($action === 'messages') {
    $chatId = $_GET['chat_id'] ?? '';
    $stmt = $pdo->prepare("SELECT * FROM chat_messages WHERE chat_id = ? ORDER BY id ASC");
    $stmt->execute([$chatId]);
    echo json_encode(['success' => true, 'messages' => $stmt->fetchAll()]);
    exit;
}

if ($action === 'rename') {
    $input = json_decode(file_get_contents('php://input'), true);
    $chatId = $input['id'] ?? '';
    $title = $input['title'] ?? '';

    $stmt = $pdo->prepare("UPDATE chats SET title = ? WHERE id = ?");
    $stmt->execute([$title, $chatId]);
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'delete') {
    $chatId = $_GET['chat_id'] ?? '';
    $stmt = $pdo->prepare("DELETE FROM chats WHERE id = ?");
    $stmt->execute([$chatId]);
    echo json_encode(['success' => true]);
    exit;
}
