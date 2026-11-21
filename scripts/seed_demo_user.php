<?php
// Optional local/lab helper. Set DEMO_USERNAME and DEMO_PASSWORD explicitly.
require_once __DIR__ . '/../config/database.php';

$username = getenv('DEMO_USERNAME');
$password = getenv('DEMO_PASSWORD');
if (!$username || !$password || strlen($username) > 100 || strlen($password) > 255) {
    fwrite(STDERR, "Set DEMO_USERNAME and DEMO_PASSWORD (max 100 and 255 characters).\n");
    exit(2);
}

try {
    $pdo = getDatabaseConnection();
    $stmt = $pdo->prepare('INSERT INTO users (username, password_hash) VALUES (:username, :hash) ON CONFLICT (username) DO NOTHING');
    $stmt->execute([':username' => $username, ':hash' => password_hash($password, PASSWORD_DEFAULT)]);
    fwrite(STDOUT, $stmt->rowCount() === 1 ? "Demo user created.\n" : "That username already exists.\n");
} catch (Throwable $error) {
    error_log('Demo user setup failed: ' . $error->getMessage());
    fwrite(STDERR, "Could not create demo user. Check database connectivity and configuration.\n");
    exit(1);
}
