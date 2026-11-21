<?php
// config/database.php
// Database configuration loaded from environment variables only.
// Never hardcode credentials here.
require_once __DIR__ . '/environment.php';

function getDatabaseConnection(): PDO
{
    $databaseUrl = getenv('DATABASE_URL');
    if ($databaseUrl) {
        $parts = parse_url($databaseUrl);
        if ($parts === false || empty($parts['host']) || empty($parts['path'])) {
            throw new RuntimeException('DATABASE_URL is invalid.');
        }
        $host = $parts['host'];
        $port = $parts['port'] ?? 5432;
        $dbname = rawurldecode(ltrim($parts['path'], '/'));
        $user = rawurldecode($parts['user'] ?? '');
        $password = rawurldecode($parts['pass'] ?? '');
        $sslmode = getenv('DB_SSLMODE') ?: 'require';
    } else {
        $host     = getenv('DB_HOST') ?: 'localhost';
        $port     = getenv('DB_PORT') ?: '5432';
        $dbname   = getenv('DB_NAME') ?: 'lab11_db';
        $user     = getenv('DB_USER') ?: 'postgres';
        $password = getenv('DB_PASSWORD') ?: '';
        $sslmode  = getenv('DB_SSLMODE') ?: 'prefer';
    }

    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};sslmode={$sslmode}";

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, $user, $password, $options);
        return $pdo;
    } catch (PDOException $e) {
        // Log error without exposing credentials
        error_log('Database connection failed: ' . $e->getMessage());
        throw new RuntimeException('Database connection failed. Please try again later.');
    }
}
