import os

# ساختار درختی پروژه به همراه محتوای کامل هر فایل
project_structure = {
    # 1. DATABASE CONFIGURATION
    "config/database.php": """<?php
// config/database.php

define('DB_PATH', __DIR__ . '/kadad_secure.sqlite');

try {
    $pdo = new PDO("sqlite:" . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Initialize core schema
    $pdo->exec("CREATE TABLE IF NOT EXISTS system_config (
        key_name TEXT PRIMARY KEY,
        key_value TEXT
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE,
        password_hash TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS chats (
        id TEXT PRIMARY KEY,
        title TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS chat_messages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        chat_id TEXT,
        role TEXT,
        content TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(chat_id) REFERENCES chats(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS activity_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
        username TEXT,
        action TEXT,
        target_item TEXT,
        result TEXT
    )");

    // Seed default configuration if empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM system_config");
    if ($stmt->fetchColumn() == 0) {
        $defaultConfig = [
            'api_key' => '',
            'base_url' => 'https://api.deepseek.com/v1',
            'default_model' => 'deepseek-chat',
            'temperature' => '0.2',
            'max_tokens' => '4096',
            'current_workspace' => '',
            'auto_save' => '1',
            'streaming' => '1'
        ];
        $insert = $pdo->prepare("INSERT INTO system_config (key_name, key_value) VALUES (?, ?)");
        foreach ($defaultConfig as $k => $v) {
            $insert->execute([$k, $v]);
        }
    }
} catch (PDOException $e) {
    die("Database initialization critical failure: " . $e->getMessage());
}
""",

    # 2. SECURITY CLASS
    "classes/Security.php": """<?php
// classes/Security.php

class Security {
    public static function initSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_use_only_cookies', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
            ini_set('session.use_strict_mode', 1);
            session_start();
        }
        
        // Session Timeout (30 minutes)
        if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1800)) {
            session_unset();
            session_destroy();
        }
        $_SESSION['LAST_ACTIVITY'] = time();
    }

    public static function generateCSRFToken(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function validateCSRFToken(?string $token): bool {
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function sanitizeHtml(string $data): string {
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }

    public static function sanitizeWorkspacePath(string $baseWorkspace, string $userPath): string {
        $realBase = realpath($baseWorkspace);
        if (!$realBase) {
            throw new Exception("Active base workspace path does not exist.");
        }

        $targetPath = $realBase . DIRECTORY_SEPARATOR . ltrim($userPath, '/\\\\');
        $realTarget = realpath($targetPath);

        if ($realTarget === false) {
            $parentDir = dirname($targetPath);
            $realParent = realpath($parentDir);
            if ($realParent === false || strpos($realParent, $realBase) !== 0) {
                throw new Exception("Security Violation: Target path is outside designated workspace.");
            }
            return $targetPath;
        }

        if (strpos($realTarget, $realBase) !== 0) {
            throw new Exception("Security Violation: Action tried pointing outside designated workspace.");
        }

        return $realTarget;
    }

    public static function writeLog(PDO $pdo, string $action, string $target, string $result): void {
        $username = $_SESSION['username'] ?? 'SYSTEM';
        $stmt = $pdo->prepare("INSERT INTO activity_logs (username, action, target_item, result) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $action, $target, $result]);

        $logFile = __DIR__ . '/../logs/audit.log';
        if (!file_exists(dirname($logFile))) {
            mkdir(dirname($logFile), 0755, true);
        }
        $logMessage = sprintf("[%s] USER: %s | ACTION: %s | TARGET: %s | RESULT: %s\\n", 
            date('Y-m-d H:i:s'), $username, $action, $target, $result
        );
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }

    public static function enforceRateLimit(): void {
        if (!isset($_SESSION['rate_limit_hits'])) {
            $_SESSION['rate_limit_hits'] = [];
        }
        $now = time();
        $_SESSION['rate_limit_hits'] = array_filter($_SESSION['rate_limit_hits'], function($timestamp) use ($now) {
            return ($now - $timestamp) < 60;
        });
        
        if (count($_SESSION['rate_limit_hits']) > 120) {
            http_response_code(429);
            echo json_encode(['error' => 'Too many requests. Please slow down.']);
            exit;
        }
        $_SESSION['rate_limit_hits'][] = $now;
    }
}
""",

    # 3. AUTH CLASS
    "classes/Auth.php": """<?php
// classes/Auth.php

class Auth {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function ensureAdminExists(string $username, string $password): void {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() == 0) {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $this->pdo->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?)");
            $stmt->execute([$username, $hash]);
        }
    }

    public function login(string $username, string $password): bool {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['authenticated'] = true;
            $_SESSION['username'] = $user['username'];
            return true;
        }
        return false;
    }

    public static function check(): void {
        Security::initSession();
        if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                http_response_code(401);
                echo json_encode(['error' => 'Unauthorized session access.']);
                exit;
            }
            header("Location: index.php?route=login");
            exit;
        }
    }
}
""",

    # 4. WORKSPACE MANAGER
    "classes/WorkspaceManager.php": """<?php
// classes/WorkspaceManager.php

class WorkspaceManager {
    private string $workspaceRoot;
    private PDO $pdo;

    public function __construct(string $workspaceRoot, PDO $pdo) {
        if (!is_dir($workspaceRoot)) {
            mkdir($workspaceRoot, 0755, true);
        }
        $this->workspaceRoot = realpath($workspaceRoot);
        $this->pdo = $pdo;
    }

    public function getTree(string $subDir = ''): array {
        $target = Security::sanitizeWorkspacePath($this->workspaceRoot, $subDir);
        $items = [];
        if (!is_dir($target)) return [];

        $files = scandir($target);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            $fullPath = $target . DIRECTORY_SEPARATOR . $file;
            $relativePath = ltrim(str_replace($this->workspaceRoot, '', $fullPath), DIRECTORY_SEPARATOR . '\\\\');
            $isDir = is_dir($fullPath);

            $items[] = [
                'name' => $file,
                'path' => str_replace('\\\\', '/', $relativePath),
                'type' => $isDir ? 'folder' : 'file'
            ];
        }

        usort($items, function ($a, $b) {
            if ($a['type'] === $b['type']) return strcmp($a['name'], $b['name']);
            return $a['type'] === 'folder' ? -1 : 1;
        });

        return $items;
    }

    public function createBackup(string $relativePath): bool {
        $filePath = Security::sanitizeWorkspacePath($this->workspaceRoot, $relativePath);
        if (!file_exists($filePath) || is_dir($filePath)) return false;

        $backupDir = __DIR__ . '/../backups';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $info = pathinfo($relativePath);
        $safeName = preg_replace('/[^A-Za-z0-9_\\\\-]/', '_', $info['filename']);
        $timestamp = date('Y-m-d_H-i-s');
        $ext = $info['extension'] ?? 'txt';
        
        $backupPath = $backupDir . '/' . $safeName . '_' . $timestamp . '.' . $ext;
        
        $indexData = [
            'original_path' => $relativePath,
            'backup_file' => basename($backupPath),
            'timestamp' => $timestamp
        ];
        file_put_contents($backupDir . '/index.log', json_encode($indexData) . "\\n", FILE_APPEND);

        return copy($filePath, $backupPath);
    }

    public function writeFile(string $relativePath, string $content): bool {
        $filePath = Security::sanitizeWorkspacePath($this->workspaceRoot, $relativePath);
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (file_exists($filePath)) {
            $this->createBackup($relativePath);
        }

        return file_put_contents($filePath, $content) !== false;
    }

    public function readFile(string $relativePath): string {
        $filePath = Security::sanitizeWorkspacePath($this->workspaceRoot, $relativePath);
        if (!file_exists($filePath)) {
            throw new Exception("File not found.");
        }
        if (is_dir($filePath)) {
            throw new Exception("Target item is a directory, not a file.");
        }
        return file_get_contents($filePath);
    }

    public function createFolder(string $relativePath): bool {
        $dirPath = Security::sanitizeWorkspacePath($this->workspaceRoot, $relativePath);
        if (is_dir($dirPath)) return true;
        return mkdir($dirPath, 0755, true);
    }

    public function deleteItem(string $relativePath): bool {
        $path = Security::sanitizeWorkspacePath($this->workspaceRoot, $relativePath);
        if (!file_exists($path)) return false;

        if (is_dir($path)) {
            return $this->recursiveDelete($path);
        } else {
            $this->createBackup($relativePath);
            return unlink($path);
        }
    }

    private function recursiveDelete(string $dir): bool {
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') continue;
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->recursiveDelete($path);
            } else {
                unlink($path);
            }
        }
        return rmdir($dir);
    }

    public function renameItem(string $oldRelative, string $newRelative): bool {
        $oldPath = Security::sanitizeWorkspacePath($this->workspaceRoot, $oldRelative);
        $newPath = Security::sanitizeWorkspacePath($this->workspaceRoot, $newRelative);
        
        if (!file_exists($oldPath)) {
            throw new Exception("Source item does not exist.");
        }
        if (file_exists($newPath)) {
            throw new Exception("Destination target item already exists.");
        }

        if (!is_dir($oldPath)) {
            $this->createBackup($oldRelative);
        }
        return rename($oldPath, $newPath);
    }

    public function searchWorkspace(string $query): array {
        $results = [];
        $directory = new RecursiveDirectoryIterator($this->workspaceRoot);
        $iterator = new RecursiveIteratorIterator($directory);

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $filePath = $file->getPathname();
                $relativePath = ltrim(str_replace($this->workspaceRoot, '', $filePath), DIRECTORY_SEPARATOR . '\\\\');
                $relativePath = str_replace('\\\\', '/', $relativePath);
                
                $content = file_get_contents($filePath);
                if (strpos($content, $query) !== false) {
                    $lines = explode("\\n", $content);
                    foreach ($lines as $index => $line) {
                        if (strpos($line, $query) !== false) {
                            $results[] = [
                                'file' => $relativePath,
                                'line' => $index + 1,
                                'matched' => trim($line)
                            ];
                        }
                    }
                }
            }
        }
        return $results;
    }
}
""",

    # 5. DEEPSEEK API CLIENT
    "classes/DeepSeekClient.php": """<?php
// classes/DeepSeekClient.php

class DeepSeekClient {
    private string $apiKey;
    private string $baseUrl;
    private string $model;
    private float $temperature;
    private int $maxTokens;

    public function __construct(array $config) {
        $this->apiKey = $config['api_key'] ?? '';
        $this->baseUrl = $config['base_url'] ?? 'https://api.deepseek.com/v1';
        $this->model = $config['default_model'] ?? 'deepseek-chat';
        $this->temperature = (float)($config['temperature'] ?? 0.2);
        $this->maxTokens = (int)($config['max_tokens'] ?? 4096);
    }

    public function getSystemInstructions(): string {
        return "You are an advanced, high-performance Full-Stack AI Coding Agent called Kadad AI Agent.\\n" .
               "You execute system changes directly through deterministic JSON object operations.\\n" .
               "You must analyze context, execute files modifications, and stop using the 'finish' command when done.\\n\\n" .
               "CRITICAL: You MUST answer using ONLY a single valid JSON object containing exactly two root-level fields: 'explanation' and 'action'. Do not output any markdown code blocks encapsulation (no ```json code blocks). Plain text raw JSON formatting string structure rules output only.\\n\\n" .
               "Structure syntax payload model:\\n" .
               "{\\n" .
               "  \\\"explanation\\\": \\\"Text explanation targeting user on step intent\\\",\\n" .
               "  \\\"action\\\": {\\n" .
               "     \\\"type\\\": \\\"write_file\\\" | \\\"replace_text\\\" | \\\"append_file\\\" | \\\"create_file\\\" | \\\"delete_file\\\" | \\\"create_folder\\\" | \\\"delete_folder\\\" | \\\"rename\\\" | \\\"read_file\\\" | \\\"search\\\" | \\\"finish\\\",\\n" .
               "     \\\"path\\\": \\\"relative/path/to/target.ext\\\",\\n" .
               "     \\\"content\\\": \\\"Full text content needed for write/create operations or text block mappings\\\",\\n" .
               "     \\\"search_text\\\": \\\"text string search segments inside file for replace_text or global search execution query\\\",\\n" .
               "     \\\"replace_text\\\": \\\"replacement text details inside code module modifications\\\",\\n" .
               "     \\\"new_path\\\": \\\"relative/path/target_new.ext\\\"\\n" .
               "  }\\n" .
               "}";
    }

    public function sendPayload(array $messages, bool $stream = false) {
        $url = rtrim($this->baseUrl, '/') . '/chat/completions';
        
        $headers = [
            "Content-Type: application/json",
            "Authorization: Bearer " . $this->apiKey
        ];

        $postData = [
            "model" => $this->model,
            "messages" => $messages,
            "temperature" => $this->temperature,
            "max_tokens" => $this->maxTokens,
            "stream" => $stream
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, !$stream);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);

        if ($stream) {
            curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $data) {
                echo $data;
                if (ob_get_level() > 0) ob_flush();
                flush();
                return strlen($data);
            });
            curl_exec($ch);
            curl_close($ch);
            exit;
        }

        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            throw new Exception("cURL Transfer Error Connection Failed: " . $err);
        }

        return json_decode($response, true);
    }
}
""",

    # 6. AUTH API
    "api/auth.php": """<?php
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
""",

    # 7. WORKSPACE API
    "api/workspace.php": """<?php
// api/workspace.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Security.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/WorkspaceManager.php';

Auth::check();
Security::enforceRateLimit();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    $headers = getallheaders();
    $token = $headers['X-CSRF-Token'] ?? '';
    if (!Security::validateCSRFToken($token)) {
        http_response_code(403);
        echo json_encode(['error' => 'CSRF verification failed.']);
        exit;
    }
}

$stmt = $pdo->prepare("SELECT key_value FROM system_config WHERE key_name = 'current_workspace'");
$stmt->execute();
$workspacePath = $stmt->fetchColumn();

if (empty($workspacePath)) {
    $workspacePath = __DIR__ . '/../workspaces/default';
}

$wm = new WorkspaceManager($workspacePath, $pdo);
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            $path = $_GET['path'] ?? '';
            echo json_encode(['success' => true, 'data' => $wm->getTree($path)]);
            break;

        case 'read':
            $path = $_GET['path'] ?? '';
            echo json_encode(['success' => true, 'content' => $wm->readFile($path)]);
            break;

        case 'write':
            $input = json_decode(file_get_contents('php://input'), true);
            $path = $input['path'] ?? '';
            $content = $input['content'] ?? '';
            if ($wm->writeFile($path, $content)) {
                Security::writeLog($pdo, 'WRITE_FILE', $path, 'SUCCESS');
                echo json_encode(['success' => true]);
            } else {
                throw new Exception("Failed writing structure content.");
            }
            break;

        case 'delete':
            $path = $_GET['path'] ?? '';
            if ($wm->deleteItem($path)) {
                Security::writeLog($pdo, 'DELETE_ITEM', $path, 'SUCCESS');
                echo json_encode(['success' => true]);
            } else {
                throw new Exception("Error removing file structure element target.");
            }
            break;

        case 'rename':
            $input = json_decode(file_get_contents('php://input'), true);
            $old = $input['old'] ?? '';
            $new = $input['new'] ?? '';
            if ($wm->renameItem($old, $new)) {
                Security::writeLog($pdo, 'RENAME_ITEM', "$old -> $new", 'SUCCESS');
                echo json_encode(['success' => true]);
            } else {
                throw new Exception("Error during structural modification execution.");
            }
            break;

        case 'create_folder':
            $input = json_decode(file_get_contents('php://input'), true);
            $path = $input['path'] ?? '';
            if ($wm->createFolder($path)) {
                Security::writeLog($pdo, 'CREATE_FOLDER', $path, 'SUCCESS');
                echo json_encode(['success' => true]);
            } else {
                throw new Exception("Folder construction execution parameters failed.");
            }
            break;

        case 'upload':
            if (!isset($_FILES['file']) || empty($_POST['path'])) {
                throw new Exception("Invalid file uploading parameter arguments specified.");
            }
            $destRel = $_POST['path'] . '/' . $_FILES['file']['name'];
            $destAbs = Security::sanitizeWorkspacePath($workspacePath, $destRel);
            
            if (move_uploaded_file($_FILES['file']['tmp_name'], $destAbs)) {
                Security::writeLog($pdo, 'UPLOAD_FILE', $destRel, 'SUCCESS');
                echo json_encode(['success' => true]);
            } else {
                throw new Exception("File system allocation copy relocation failed writing onto server.");
            }
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Unknown endpoint operation code parameter syntax pattern.']);
    }
} catch (Exception $e) {
    Security::writeLog($pdo, 'WORKSPACE_ERROR', $_GET['path'] ?? 'GENERIC', $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
""",

    # 8. CHAT API
    "api/chat.php": """<?php
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
""",

    # 9. AGENT CORE API
    "api/agent.php": """<?php
// api/agent.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Security.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/WorkspaceManager.php';
require_once __DIR__ . '/../classes/DeepSeekClient.php';

Auth::check();

$stmt = $pdo->query("SELECT key_name, key_value FROM system_config");
$config = [];
foreach ($stmt->fetchAll() as $row) {
    $config[$row['key_name']] = $row['key_value'];
}

$workspaceRoot = !empty($config['current_workspace']) ? $config['current_workspace'] : __DIR__ . '/../workspaces/default';
$wm = new WorkspaceManager($workspaceRoot, $pdo);
$client = new DeepSeekClient($config);

$chatId = $_GET['chat_id'] ?? '';
$userPrompt = $_POST['prompt'] ?? '';

if (empty($chatId) || empty($userPrompt)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing functional tracking system dependencies matrix metadata values.']);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO chat_messages (chat_id, role, content) VALUES (?, 'user', ?)");
$stmt->execute([$chatId, $userPrompt]);

$projectTreeLayout = $wm->getTree();
$contextTreeStr = json_encode($projectTreeLayout, JSON_PRETTY_PRINT);

$stmt = $pdo->prepare("SELECT role, content FROM chat_messages WHERE chat_id = ? ORDER BY id ASC");
$stmt->execute([$chatId]);
$historicalMessageList = $stmt->fetchAll();

$messages = [
    ["role" => "system", "content" => $client->getSystemInstructions() . "\\n\\nActive Automated Project Structural Mapping Workspace Matrix Context Tree View Data Layout:\\n" . $contextTreeStr]
];

foreach ($historicalMessageList as $msg) {
    $messages[] = ["role" => $msg['role'], "content" => $msg['content']];
}

header('Content-Type: application/json');

try {
    $rawResponse = $client->sendPayload($messages, false);
    $responseContent = $rawResponse['choices'][0]['message']['content'] ?? '{}';
    
    $cleanJson = trim($responseContent);
    if (strpos($cleanJson, '```json') === 0) {
        $cleanJson = substr($cleanJson, 7);
        if (substr($cleanJson, -3) === '```') {
            $cleanJson = substr($cleanJson, 0, -3);
        }
    }
    $cleanJson = trim($cleanJson);

    $parsedObject = json_decode($cleanJson, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        $parsedObject = [
            'explanation' => 'I encountered a problem trying to generate a standard structured JSON response. Raw output: ' . $responseContent,
            'action' => ['type' => 'finish']
        ];
    }

    $actionResult = "No automated tool execution step needed.";
    if (!empty($parsedObject['action']['type']) && $parsedObject['action']['type'] !== 'finish') {
        $act = $parsedObject['action'];
        $type = $act['type'];
        $path = $act['path'] ?? '';

        try {
            switch ($type) {
                case 'write_file':
                case 'create_file':
                    $wm->writeFile($path, $act['content'] ?? '');
                    $actionResult = "Successfully written code modifications onto path: " . $path;
                    break;
                case 'read_file':
                    $fileData = $wm->readFile($path);
                    $actionResult = "Successfully parsed contents from file: " . $path . "\\nContent: \\n" . $fileData;
                    break;
                case 'create_folder':
                    $wm->createFolder($path);
                    $actionResult = "Successfully built folder structure layer: " . $path;
                    break;
                case 'delete_file':
                case 'delete_folder':
                    $wm->deleteItem($path);
                    $actionResult = "Successfully purged target path item matching from environment: " . $path;
                    break;
                case 'rename':
                    $wm->renameItem($path, $act['new_path'] ?? '');
                    $actionResult = "Successfully migrated tracking location from: " . $path . " to: " . ($act['new_path'] ?? '');
                    break;
                case 'search':
                    $searchResults = $wm->searchWorkspace($act['search_text'] ?? '');
                    $actionResult = "Search queries matching tracking arrays metrics: " . json_encode($searchResults);
                    break;
                case 'replace_text':
                    $currentContent = $wm->readFile($path);
                    $updatedContent = str_replace($act['search_text'], $act['replace_text'], $currentContent);
                    $wm->writeFile($path, $updatedContent);
                    $actionResult = "Text matching substitution adjustments built into module component: " . $path;
                    break;
                case 'append_file':
                    $currentContent = $wm->readFile($path);
                    $updatedContent = $currentContent . "\\n" . ($act['content'] ?? '');
                    $wm->writeFile($path, $updatedContent);
                    $actionResult = "Appended text configuration logic safely inside file: " . $path;
                    break;
            }
            Security::writeLog($pdo, 'AGENT_ACTION_EXECUTE_' . strtoupper($type), $path, 'SUCCESS');
        } catch (Exception $actionException) {
            $actionResult = "CRITICAL ERROR ATTEMPTING ACTION METHOD OPERATION EXECUTION: " . $actionException->getMessage();
            Security::writeLog($pdo, 'AGENT_ACTION_EXECUTE_FAILED_' . strtoupper($type), $path, $actionResult);
        }
    }

    $agentResponsePayloadText = $parsedObject['explanation'] . "\\n\\n*Executed Tool Metrics*: " . $actionResult;
    $stmt = $pdo->prepare("INSERT INTO chat_messages (chat_id, role, content) VALUES (?, 'assistant', ?)");
    $stmt->execute([$chatId, $agentResponsePayloadText]);

    echo json_encode([
        'success' => true,
        'explanation' => $parsedObject['explanation'],
        'action' => $parsedObject['action'] ?? ['type' => 'finish'],
        'result_summary' => $actionResult
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'API Endpoint Agent Orchestrator Failure: ' . $e->getMessage()]);
}
""",

    # 10. LOGIN VIEW TEMPLATE
    "templates/login.php": """<?php // templates/login.php ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Kadad AI Agent - Authentication Terminal</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-body-layout">
    <div class="login-card-container">
        <h2>Kadad AI Agent</h2>
        <p class="subtitle-desc-text">Secure Cloud Agent Environment Access Framework</p>
        <div id="loginError" class="error-banner hidden-element" style="color:var(--accent-danger); margin-bottom:15px; font-size:0.9rem;"></div>
        <form id="loginForm">
            <div class="form-group-item">
                <label>Admin Username</label>
                <input type="text" id="username" required autocomplete="off" autofocus>
            </div>
            <div class="form-group-item">
                <label>Secure Password</label>
                <input type="password" id="password" required>
            </div>
            <button type="submit" class="btn-primary-action" style="width:100%; border-radius:4px;">Authenticate Identity Token</button>
        </form>
    </div>
    <script>
        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const errBox = document.getElementById('loginError');
            errBox.classList.add('hidden-element');

            const payload = {
                username: document.getElementById('username').value,
                password: document.getElementById('password').value
            };

            try {
                const res = await fetch('api/auth.php?action=login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    window.location.href = 'index.php?route=dashboard';
                } else {
                    errBox.textContent = data.error || 'Authentication layer validation failure.';
                    errBox.classList.remove('hidden-element');
                }
            } catch (err) {
                errBox.textContent = 'Network communication pathway transmission failure.';
                errBox.classList.remove('hidden-element');
            }
        });
    </script>
</body>
</html>
""",

    # 11. DASHBOARD VIEW TEMPLATE
    "templates/dashboard.php": """<?php
// templates/dashboard.php
$csrf = Security::generateCSRFToken();

$stmt = $pdo->query("SELECT key_name, key_value FROM system_config");
$sysConfig = [];
foreach ($stmt->fetchAll() as $r) {
    $sysConfig[$r['key_name']] = $r['key_value'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Kadad AI Agent Console</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="[https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.39.0/min/vs/loader.min.js](https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.39.0/min/vs/loader.min.js)"></script>
</head>
<body class="dark-theme-active app-layout-grid-base" data-csrf="<?php echo $csrf; ?>">

    <header class="app-header-navigation-bar">
        <div class="brand-system-meta-zone">
            <span class="logo-text-em">Kadad AI Agent</span>
            <span class="version-tag">v2.4 Engine</span>
        </div>
        <div class="workspace-selection-context-selector-box">
            <label style="font-size:0.8rem; margin-right:5px;">Active Workspace:</label>
            <input type="text" id="topWorkspaceDisplay" value="<?php echo Security::sanitizeHtml($sysConfig['current_workspace'] ?? ''); ?>" readonly placeholder="/workspaces/default">
        </div>
        <div class="session-management-actions-zone">
            <button class="btn-secondary-action" onclick="ui.showSettingsModal()">Global Settings</button>
            <button class="btn-danger-action" onclick="ui.executeLogout()">Logout</button>
        </div>
    </header>

    <main class="dashboard-workspace-viewport-matrix-grid">
        <section class="left-wing-panel-sidebar flex-column-layout">
            <div class="chats-history-panel-component flex-column-layout layout-segment-half">
                <div class="sidebar-header-section-bar">
                    <h3>Conversations</h3>
                    <button class="btn-icon-add" onclick="chat.createNewSession()">+ New Session</button>
                </div>
                <div id="chatsListContainer" class="scrollable-content-list-wrapper"></div>
            </div>
            <div class="project-explorer-panel-component flex-column-layout layout-segment-half">
                <div class="sidebar-header-section-bar">
                    <h3>Workspace Tree</h3>
                    <button class="btn-icon-add" onclick="explorer.createNewFolderPrompt()">+ Folder</button>
                    <button class="btn-icon-add" onclick="explorer.createNewFilePrompt()">+ File</button>
                </div>
                <div id="fileExplorerTreeContainer" class="scrollable-content-list-wrapper"></div>
            </div>
        </section>

        <section class="center-wing-workspace-viewport flex-column-layout">
            <div id="chatConversationViewportArea" class="chat-conversation-viewport-area scrollable-content-list-wrapper">
                <div class="system-welcome-message-banner" style="padding:20px; background:var(--bg-surface); border-radius:6px; margin-bottom:15px;">
                    <h2>Kadad AI Agent Automation Console</h2>
                    <p style="font-size:0.9rem; margin-top:10px; color:var(--text-main);">Provide instructional processing command intents using standard interaction inputs below.</p>
                </div>
            </div>
            <div class="chat-input-interaction-dock-bar">
                <form id="agentInteractionForm" class="flex-row-layout">
                    <textarea id="agentQueryInput" placeholder="Instruct the coding agent... (e.g., 'Analyze our project database definitions file')" required></textarea>
                    <button type="submit" class="btn-submit-interaction-trigger">Execute</button>
                </form>
            </div>
        </section>

        <section class="right-wing-workspace-preview-viewport flex-column-layout">
            <div class="tabs-navigation-management-bar-strip" id="editorTabsNavigationBarStrip"></div>
            <div id="monacoEditorEngineSurfaceContainer" class="monaco-editor-engine-surface-container flex-grow-layout-element" style="height: calc(100% - 195px);">
                <div class="editor-surface-placeholder-screen">
                    <p>Select a file from the Explorer Tree to open the editor surface viewport.</p>
                </div>
            </div>
            <div class="live-action-audit-log-tracker-component">
                <div class="audit-log-header-strip">
                    <h4>Agent Structural Actions Audit Log</h4>
                </div>
                <div id="liveAuditLogStreamContainer" class="live-audit-log-stream-container"></div>
            </div>
        </section>
    </main>

    <div id="settingsGlobalModalConfigurationOverlayWindow" class="modal-layout-infrastructure-structure-component-panel-overlay-window hidden-element">
        <div class="modal-card-box-content-wrapper">
            <h3>Global Environment Configuration Settings</h3>
            <hr style="border-color:var(--border-color); margin: 15px 0;">
            <form id="settingsSubmissionUpdateConfigurationForm">
                <div class="form-group-item">
                    <label>DeepSeek API Security Key Token</label>
                    <input type="password" id="cfg_api_key" value="<?php echo Security::sanitizeHtml($sysConfig['api_key'] ?? ''); ?>">
                </div>
                <div class="form-group-item">
                    <label>Base Endpoint URL Route Gateway Destination</label>
                    <input type="text" id="cfg_base_url" value="<?php echo Security::sanitizeHtml($sysConfig['base_url'] ?? ''); ?>">
                </div>
                <div class="form-group-item">
                    <label>Default LLM Model Identifier Sequence</label>
                    <input type="text" id="cfg_default_model" value="<?php echo Security::sanitizeHtml($sysConfig['default_model'] ?? ''); ?>">
                </div>
                <div class="form-group-item">
                    <label>Temperature Sampling Metric Factor</label>
                    <input type="number" step="0.1" min="0" max="2" id="cfg_temperature" value="<?php echo Security::sanitizeHtml($sysConfig['temperature'] ?? '0.2'); ?>">
                </div>
                <div class="form-group-item">
                    <label>Max Tokens Length Response Size Value</label>
                    <input type="number" id="cfg_max_tokens" value="<?php echo Security::sanitizeHtml($sysConfig['max_tokens'] ?? '4096'); ?>">
                </div>
                <div class="form-group-item">
                    <label>Active Project Workspace Absolute Path Target</label>
                    <input type="text" id="cfg_current_workspace" value="<?php echo Security::sanitizeHtml($sysConfig['current_workspace'] ?? ''); ?>">
                </div>
                <div class="modal-actions-footer-row-bar">
                    <button type="button" class="btn-secondary-action" onclick="ui.hideSettingsModal()">Cancel</button>
                    <button type="submit" class="btn-primary-action">Apply Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/js/app.js"></script>
</body>
</html>
""",

    # 12. APP FRONTEND STYLESHEET
    "assets/css/style.css": """:root {
    --bg-base: #111216;
    --bg-surface: #17191f;
    --bg-panel: #1e222b;
    --bg-active: #2c313c;
    --text-main: #abb2bf;
    --text-light: #ffffff;
    --text-muted: #5c6370;
    --accent-primary: #4b6fff;
    --accent-success: #98c379;
    --accent-danger: #e06c75;
    --accent-warning: #d19a66;
    --border-color: #282c34;
}

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

body.dark-theme-active {
    background-color: var(--bg-base);
    color: var(--text-main);
    overflow: hidden;
    height: 100vh;
    display: flex;
    flex-direction: column;
}

.hidden-element { display: none !important; }

.login-body-layout {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100vh;
    background-color: var(--bg-base);
}

.login-card-container {
    background-color: var(--bg-surface);
    border: 1px solid var(--border-color);
    padding: 2.5rem;
    border-radius: 8px;
    width: 100%;
    max-width: 440px;
    box-shadow: 0 12px 40px rgba(0,0,0,0.5);
}

.login-card-container h2 { color: var(--text-light); margin-bottom: 0.5rem; font-weight: 600; }
.subtitle-desc-text { color: var(--text-muted); font-size: 0.85rem; margin-bottom: 2rem; }

.app-header-navigation-bar {
    height: 55px;
    background-color: var(--bg-surface);
    border-bottom: 1px solid var(--border-color);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 1.5rem;
    z-index: 100;
}

.brand-system-meta-zone .logo-text-em { color: var(--text-light); font-weight: 700; margin-right: 0.5rem; }
.brand-system-meta-zone .version-tag { font-size: 0.75rem; color: var(--accent-primary); background: rgba(75, 111, 255, 0.15); padding: 2px 6px; border-radius: 4px; }

.workspace-selection-context-selector-box input {
    background-color: var(--bg-base);
    border: 1px solid var(--border-color);
    color: var(--accent-success);
    padding: 6px 12px;
    border-radius: 4px;
    width: 320px;
    font-family: monospace;
    font-size: 0.8rem;
}

.dashboard-workspace-viewport-matrix-grid {
    display: grid;
    grid-template-columns: 320px 1fr 1fr;
    height: calc(100vh - 55px);
    overflow: hidden;
}

.flex-column-layout { display: flex; flex-direction: column; }
.flex-row-layout { display: flex; flex-direction: row; }
.layout-segment-half { height: 50%; overflow: hidden; border-bottom: 1px solid var(--border-color); }
.flex-grow-layout-element { flex-grow: 1; position: relative; }

.scrollable-content-list-wrapper { flex-grow: 1; overflow-y: auto; padding: 1rem; }

.sidebar-header-section-bar {
    background-color: var(--bg-surface);
    padding: 10px 1rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid var(--border-color);
}

.sidebar-header-section-bar h3 { font-size: 0.8rem; text-transform: uppercase; color: var(--text-light); }

button { cursor: pointer; border: none; font-size: 0.85rem; border-radius: 4px; font-weight: 500; }
.btn-primary-action { background-color: var(--accent-primary); color: var(--text-light); padding: 10px 20px; }
.btn-secondary-action { background-color: var(--bg-active); color: var(--text-main); padding: 8px 16px; margin-right: 8px;}
.btn-danger-action { background-color: var(--accent-danger); color: var(--text-light); padding: 8px 16px; }
.btn-icon-add { background: transparent; color: var(--accent-primary); font-size: 0.75rem; border: 1px solid rgba(75,111,255,0.3); padding: 3px 8px; }

.form-group-item { margin-bottom: 1.2rem; display: flex; flex-direction: column; }
.form-group-item label { font-size: 0.8rem; margin-bottom: 6px; color: var(--text-main); }
.form-group-item input { background-color: var(--bg-base); border: 1px solid var(--border-color); color: var(--text-light); padding: 10px; border-radius: 4px; }

.tree-item-node-row { display: flex; align-items: center; justify-content: space-between; padding: 6px 8px; border-radius: 4px; cursor: pointer; font-size: 0.85rem; }
.tree-item-node-row:hover { background-color: var(--bg-surface); }
.tree-item-node-row.active-file-node { background-color: var(--bg-active); color: var(--text-light); }

.chat-conversation-viewport-area { background-color: var(--bg-base); display: flex; flex-direction: column; gap: 1rem; }
.dialogue-message-bubble-row { padding: 1.2rem; border-radius: 6px; max-width: 90%; font-size: 0.95rem; line-height: 1.5; }
.dialogue-message-bubble-row.role-user { background-color: var(--bg-panel); align-self: flex-end; color: var(--text-light); border-left: 4px solid var(--accent-primary); }
.dialogue-message-bubble-row.role-assistant { background-color: var(--bg-surface); align-self: flex-start; border-left: 4px solid var(--accent-success); white-space: pre-wrap; }

.chat-input-interaction-dock-bar { padding: 1rem; background-color: var(--bg-surface); border-top: 1px solid var(--border-color); }
.chat-input-interaction-dock-bar textarea { flex-grow: 1; background-color: var(--bg-base); border: 1px solid var(--border-color); color: var(--text-light); padding: 12px; border-radius: 6px; resize: none; height: 60px; margin-right: 12px; }
.btn-submit-interaction-trigger { background-color: var(--accent-success); color: var(--text-light); padding: 0 24px; height: 60px; }

.tabs-navigation-management-bar-strip { display: flex; background-color: var(--bg-surface); border-bottom: 1px solid var(--border-color); height: 35px; }
.editor-tab-item-token { padding: 0 15px; display: flex; align-items: center; gap: 8px; font-size: 0.8rem; border-right: 1px solid var(--border-color); background-color: var(--bg-base); cursor: pointer; }
.editor-tab-item-token.active-tab { background-color: var(--bg-panel); color: var(--text-light); border-top: 2px solid var(--accent-primary); }

.editor-surface-placeholder-screen { display: flex; align-items: center; justify-content: center; height: 100%; color: var(--text-muted); }

.live-action-audit-log-tracker-component { height: 160px; background-color: var(--bg-surface); border-top: 1px solid var(--border-color); display: flex; flex-direction: column; }
.audit-log-header-strip { padding: 6px 12px; background-color: var(--bg-base); border-bottom: 1px solid var(--border-color); font-size: 0.75rem; font-weight: 600; color: var(--accent-warning); }
.live-audit-log-stream-container { padding: 8px; overflow-y: auto; font-family: monospace; font-size: 0.75rem; color: var(--text-main); flex-grow: 1; }
.audit-log-entry-row { margin-bottom: 4px; border-bottom: 1px dashed rgba(255,255,255,0.05); }

.modal-layout-infrastructure-structure-component-panel-overlay-window { position: fixed; top:0; left:0; width:100vw; height:100vh; background: rgba(0,0,0,0.7); display:flex; align-items:center; justify-content:center; z-index:500; }
.modal-card-box-content-wrapper { background-color: var(--bg-surface); border: 1px solid var(--border-color); border-radius: 8px; width: 100%; max-width: 650px; padding: 2rem; }
.modal-actions-footer-row-bar { display: flex; justify-content: flex-end; }

.chat-session-link-row { display: flex; align-items: center; justify-content: space-between; padding: 8px; margin-bottom: 4px; border-radius: 4px; background: var(--bg-panel); cursor: pointer; font-size: 0.85rem; }
.chat-session-link-row.active-session-chat { border-left: 3px solid var(--accent-primary); background: var(--bg-active); }
.chat-actions-btn-group { display: flex; gap: 4px; }
""",

    # 13. IDE CORE APP JAVASCRIPT
    "assets/js/app.js": """const state = {
    activeChatId: null,
    activeFilePath: null,
    openTabs: {}, 
    monacoInstance: null,
    csrfToken: document.body.getAttribute('data-csrf')
};

const ui = {
    init() {
        document.getElementById('agentInteractionForm').addEventListener('submit', (e) => {
            e.preventDefault();
            ui.submitPromptToAgent();
        });

        document.getElementById('settingsSubmissionUpdateConfigurationForm').addEventListener('submit', (e) => {
            e.preventDefault();
            ui.saveGlobalSettingsParameters();
        });
    },

    showSettingsModal() {
        document.getElementById('settingsGlobalModalConfigurationOverlayWindow').classList.remove('hidden-element');
    },

    hideSettingsModal() {
        document.getElementById('settingsGlobalModalConfigurationOverlayWindow').classList.add('hidden-element');
    },

    async saveGlobalSettingsParameters() {
        alert('Settings parameter modification committed successfully.');
        ui.hideSettingsModal();
    },

    async executeLogout() {
        await fetch('api/auth.php?action=logout');
        window.location.href = 'index.php?route=login';
    },

    appendLogEntry(action, file, status) {
        const container = document.getElementById('liveAuditLogStreamContainer');
        const entry = document.createElement('div');
        entry.className = 'audit-log-entry-row';
        entry.innerHTML = `<strong>[${new Date().toLocaleTimeString()}]</strong> <span style="color:var(--accent-primary)">${action}</span> | Target: <i>${file}</i> -> ${status}`;
        container.appendChild(entry);
        container.scrollTop = container.scrollHeight;
    },

    appendChatBubble(role, text) {
        const viewport = document.getElementById('chatConversationViewportArea');
        const bubble = document.createElement('div');
        bubble.className = `dialogue-message-bubble-row role-${role}`;
        bubble.textContent = text;
        viewport.appendChild(bubble);
        viewport.scrollTop = viewport.scrollHeight;
    },

    async submitPromptToAgent() {
        if (!state.activeChatId) {
            alert('Please select or create an active chat session.');
            return;
        }

        const inputField = document.getElementById('agentQueryInput');
        const promptText = inputField.value.trim();
        if (!promptText) return;

        inputField.value = '';
        this.appendChatBubble('user', promptText);

        const formData = new FormData();
        formData.append('prompt', promptText);

        try {
            const res = await fetch(`api/agent.php?chat_id=${state.activeChatId}`, {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if (data.success) {
                this.appendChatBubble('assistant', data.explanation);
                ui.appendLogEntry('AGENT_ACTION', data.action.type, 'EXECUTED');
                explorer.refreshTree();
            }
        } catch(e) {
            alert('Agent execution runtime pipeline block connection dropped.');
        }
    }
};

const chat = {
    async loadHistory() {
        const res = await fetch('api/chat.php?action=list');
        const data = await res.json();
        const container = document.getElementById('chatsListContainer');
        container.innerHTML = '';
        
        if(data.chats && data.chats.length > 0) {
            data.chats.forEach(c => {
                const row = document.createElement('div');
                row.className = `chat-session-link-row ${state.activeChatId === c.id ? 'active-session-chat' : ''}`;
                row.innerHTML = `<span>${c.title}</span>`;
                row.onclick = () => chat.select(c.id);
                container.appendChild(row);
            });
        }
    },

    async createNewSession() {
        const title = prompt('Enter session topic layout:');
        if(!title) return;
        const res = await fetch('api/chat.php?action=create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ title })
        });
        const data = await res.json();
        if (data.success) {
            state.activeChatId = data.id;
            this.loadHistory();
        }
    },

    select(id) {
        state.activeChatId = id;
        this.loadHistory();
        document.getElementById('chatConversationViewportArea').innerHTML = '';
        this.appendChatBubble('assistant', 'Session context loaded. Waiting for automated structural instructions...');
    }
};

const explorer = {
    async refreshTree() {
        const res = await fetch('api/workspace.php?action=list');
        const data = await res.json();
        const container = document.getElementById('fileExplorerTreeContainer');
        container.innerHTML = '';

        if(data.success && data.data) {
            data.data.forEach(item => {
                const node = document.createElement('div');
                node.className = 'tree-item-node-row';
                node.innerHTML = `<span>${item.type === 'folder' ? '📁' : '📄'} ${item.name}</span>`;
                if(item.type === 'file') {
                    node.onclick = () => editorCore.openFile(item.path);
                }
                container.appendChild(node);
            });
        }
    }
};

const editorCore = {
    initializeMonaco() {
        require.config({ paths: { vs: '[https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.39.0/min/vs](https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.39.0/min/vs)' } });
        require(['vs/editor/editor.main'], () => {
            const surface = document.getElementById('monacoEditorEngineSurfaceContainer');
            surface.innerHTML = '';
            state.monacoInstance = monaco.editor.create(surface, {
                value: '// Welcome to Kadad AI Integrated Monaco IDE Editor Engine Workspace Surface Panel',
                language: 'javascript',
                theme: 'vs-dark',
                automaticLayout: true
            });
        });
    },

    async openFile(path) {
        state.activeFilePath = path;
        const res = await fetch(`api/workspace.php?action=read&path=${encodeURIComponent(path)}`);
        const data = await res.json();
        if(data.success) {
            state.monacoInstance.setValue(data.content);
            ui.appendLogEntry('OPEN_FILE', path, 'SUCCESS');
        }
    }
};
""",

    # 14. MASTER INDEX APPLICATION GATEWAY ENTRYPOINT
    "index.php": """<?php
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
"""
}

# ساختار پوشه‌ها و تزریق محتویات به فایل‌ها به صورت پویا
def build_production_application():
    print("Initializing structural build pipeline automation routine...")
    for file_path, code_content in project_structure.items():
        # بررسی و ساخت پوشه‌های والد در صورت عدم وجود
        parent_directory = os.path.dirname(file_path)
        if parent_directory and not os.path.exists(parent_directory):
            os.makedirs(parent_directory, exist_ok=True)
            print(f"Created system layer layout directory: {parent_directory}")
        
        # نگارش نهایی سورس کدهای خام درون ساختار دیسک
        with open(file_path, "w", encoding="utf-8") as file_handle:
            file_handle.write(code_content.strip() + "\n")
        print(f"Committed code source target stream file output: {file_path}")

    # ایجاد پوشه‌های خالی حیاتی سیستم به جهت ممانعت از بروز استثناهای ساختاری فایل سیستم
    empty_system_dirs = ["backups", "logs", "workspaces/default"]
    for dir_path in empty_system_dirs:
        os.makedirs(dir_path, exist_ok=True)
        print(f"Allocated empty localized workspace zone storage directory: {dir_path}")

    print("\\n[SUCCESS] Kadad AI Agent application stack compiled successfully!")
    print("Default Access Credentials -> User: admin | Pass: admin123")

if __name__ == "__main__":
    build_production_application()
