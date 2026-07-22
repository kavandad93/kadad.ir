<?php
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
            $relativePath = ltrim(str_replace($this->workspaceRoot, '', $fullPath), DIRECTORY_SEPARATOR . '\\');
            $isDir = is_dir($fullPath);

            $items[] = [
                'name' => $file,
                'path' => str_replace('\\', '/', $relativePath),
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
        $safeName = preg_replace('/[^A-Za-z0-9_\\-]/', '_', $info['filename']);
        $timestamp = date('Y-m-d_H-i-s');
        $ext = $info['extension'] ?? 'txt';
        
        $backupPath = $backupDir . '/' . $safeName . '_' . $timestamp . '.' . $ext;
        
        $indexData = [
            'original_path' => $relativePath,
            'backup_file' => basename($backupPath),
            'timestamp' => $timestamp
        ];
        file_put_contents($backupDir . '/index.log', json_encode($indexData) . "\n", FILE_APPEND);

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
                $relativePath = ltrim(str_replace($this->workspaceRoot, '', $filePath), DIRECTORY_SEPARATOR . '\\');
                $relativePath = str_replace('\\', '/', $relativePath);
                
                $content = file_get_contents($filePath);
                if (strpos($content, $query) !== false) {
                    $lines = explode("\n", $content);
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
