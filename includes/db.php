<?php
function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $c = require __DIR__ . '/../config/config.php';
    $dsn = "mysql:host={$c['host']};port={$c['port']};dbname={$c['db']};charset=utf8mb4";
    $pdo = new PDO($dsn, $c['user'], $c['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    return $pdo;
}
function q(string $sql): array { return db()->query($sql)->fetchAll(); }
function one(string $sql): array { return db()->query($sql)->fetch() ?: []; }
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function cop($v): string { return '$ ' . number_format((float)$v, 0, ',', '.'); }
