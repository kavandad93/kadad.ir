<?php
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

        $targetPath = $realBase . DIRECTORY_SEPARATOR . ltrim($userPath, '/\\');
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
        $logMessage = sprintf("[%s] USER: %s | ACTION: %s | TARGET: %s | RESULT: %s\n", 
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
