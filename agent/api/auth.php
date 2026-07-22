<?php
// api/auth.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Security.php';
require_once __DIR__ . '/../classes/Auth.php';

Security::initSession();
Security::enforceRateLimit();

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$auth = new Auth($pdo);

if ($action === 'login') {
    $input = json_decode(file_get_contents('php://input'), true);
    $username = $input['username'] ?? '';
    $password = $input['password'] ?? '';

    if ($auth->login($username, $password)) {
        Security::writeLog($pdo, 'LOGIN', $username, 'SUCCESS');
        echo json_encode(['success' => true]);
    } else {
        Security::writeLog($pdo, 'LOGIN', $username, 'FAILED_BAD_CREDENTIALS');
        http_response_code(401);
        echo json_encode(['error' => 'Invalid username or password configuration settings.']);
    }
    exit;
}

if ($action === 'logout') {
    Security::writeLog($pdo, 'LOGOUT', $_SESSION['username'] ?? 'UNKNOWN', 'SUCCESS');
    session_unset();
    session_destroy();
    echo json_encode(['success' => true]);
    exit;
}
