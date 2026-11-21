<?php
// Apply the idempotent baseline schema on a fresh or existing database.
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDatabaseConnection();
    $schema = file_get_contents(__DIR__ . '/../database/schema.sql');
    if ($schema === false) {
        throw new RuntimeException('Could not read database/schema.sql.');
    }
    $pdo->exec($schema);
    fwrite(STDOUT, "Database schema is ready.\n");
} catch (Throwable $error) {
    error_log('Database schema setup failed: ' . $error->getMessage());
    fwrite(STDERR, "Database schema setup failed. Check database connectivity and configuration.\n");
    exit(1);
}
