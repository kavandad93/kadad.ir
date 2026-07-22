<?php
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
