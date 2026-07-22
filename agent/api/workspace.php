<?php
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
