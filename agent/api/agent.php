<?php
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
    ["role" => "system", "content" => $client->getSystemInstructions() . "\n\nActive Automated Project Structural Mapping Workspace Matrix Context Tree View Data Layout:\n" . $contextTreeStr]
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
                    $actionResult = "Successfully parsed contents from file: " . $path . "\nContent: \n" . $fileData;
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
                    $updatedContent = $currentContent . "\n" . ($act['content'] ?? '');
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

    $agentResponsePayloadText = $parsedObject['explanation'] . "\n\n*Executed Tool Metrics*: " . $actionResult;
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
