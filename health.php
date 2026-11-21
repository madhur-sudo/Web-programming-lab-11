<?php
// health.php — Health check endpoint for Render deployment.
// Returns HTTP 200 with JSON payload if the app is running.
// Returns HTTP 503 if the database is unreachable.

require_once __DIR__ . '/config/database.php';

header('Content-Type: application/json');

try {
    $pdo  = getDatabaseConnection();
    $stmt = $pdo->query("SELECT 1 AS ping");
    $row  = $stmt->fetch();

    if ($row && (int)$row['ping'] === 1) {
        http_response_code(200);
        echo json_encode([
            'status'   => 'ok',
            'database' => 'connected',
            'app'      => 'ACCESS',
        ]);
    } else {
        throw new RuntimeException('Database ping failed.');
    }
} catch (Throwable $e) {
    http_response_code(503);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Database unavailable',
    ]);
}
