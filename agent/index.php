<?php
// index.php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/Security.php';
require_once __DIR__ . '/classes/Auth.php';

Security::initSession();

// Auto-seed default administration account matrix mapping definitions credentials parameters
$auth = new Auth($pdo);
$auth->ensureAdminExists('admin', 'admin123');

$route = $_GET['route'] ?? 'dashboard';

if ($route === 'login') {
    if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
        header("Location: index.php?route=dashboard");
        exit;
    }
    require_once __DIR__ . '/templates/login.php';
    exit;
}

if ($route === 'dashboard') {
    Auth::check();
    require_once __DIR__ . '/templates/dashboard.php';
    exit;
}

// Fallback protection redirection boundary loop maps
header("Location: index.php?route=login");
exit;
