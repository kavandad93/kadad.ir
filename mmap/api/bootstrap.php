<?php
declare(strict_types=1);
function mmap_db(): PDO {
    static $db = null;
    if ($db instanceof PDO) return $db;
    $dir = __DIR__ . '/../data';
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    $db = new PDO('sqlite:' . $dir . '/mmap.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec('CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, email TEXT UNIQUE, lat REAL, lng REAL, last_seen INTEGER, created_at INTEGER NOT NULL);');
    $db->exec('CREATE TABLE IF NOT EXISTS events (id INTEGER PRIMARY KEY AUTOINCREMENT, owner_id INTEGER, title TEXT NOT NULL, note TEXT, lat REAL NOT NULL, lng REAL NOT NULL, allowed_names TEXT, group_only INTEGER DEFAULT 0, created_at INTEGER NOT NULL, updated_at INTEGER NOT NULL);');
    $db->exec('CREATE TABLE IF NOT EXISTS messages (id INTEGER PRIMARY KEY AUTOINCREMENT, room TEXT NOT NULL, sender_id INTEGER, sender_name TEXT NOT NULL, body TEXT NOT NULL, seen_by TEXT DEFAULT "[]", blocked_for TEXT DEFAULT "[]", created_at INTEGER NOT NULL);');
    $db->exec('CREATE TABLE IF NOT EXISTS favorites (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, title TEXT NOT NULL, lat REAL NOT NULL, lng REAL NOT NULL, created_at INTEGER NOT NULL);');
    $db->exec('CREATE TABLE IF NOT EXISTS friends (id INTEGER PRIMARY KEY AUTOINCREMENT, requester TEXT NOT NULL, target TEXT NOT NULL, status TEXT NOT NULL DEFAULT "pending", created_at INTEGER NOT NULL);');
    $db->exec('CREATE TABLE IF NOT EXISTS groups (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT UNIQUE NOT NULL, members TEXT DEFAULT "[]", created_at INTEGER NOT NULL);');
    $db->exec('CREATE TABLE IF NOT EXISTS blocks (id INTEGER PRIMARY KEY AUTOINCREMENT, blocker TEXT NOT NULL, blocked TEXT NOT NULL, created_at INTEGER NOT NULL, UNIQUE(blocker, blocked));');
    return $db;
}
function json_input(): array { return json_decode(file_get_contents('php://input'), true) ?: $_POST ?: $_GET; }
function respond($data): void { header('Content-Type: application/json; charset=utf-8'); echo json_encode($data, JSON_UNESCAPED_UNICODE); exit; }
function current_user_id(): ?int { return isset($_COOKIE['mmap_uid']) ? (int)$_COOKIE['mmap_uid'] : null; }
