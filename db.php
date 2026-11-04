<?php
declare(strict_types=1);
function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $path = __DIR__ . '/config.php';
    if (!is_file($path)) {
        http_response_code(503);
        exit('Configuration is missing. Copy config.example.php to config.php and update the database settings.');
    }
    $cfg = require $path;
    try {
        $pdo = new PDO(
            sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $cfg['host'], $cfg['port'], $cfg['database']),
            $cfg['username'], $cfg['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
             PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
             PDO::ATTR_EMULATE_PREPARES => false]
        );
    } catch (PDOException $e) {
        http_response_code(503);
        exit('Could not connect to MySQL. Check config.php and import sql/schema.sql.');
    }
    return $pdo;
}
