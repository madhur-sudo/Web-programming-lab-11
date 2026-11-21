<?php
// logout.php — Destroys the session and redirects to login.

require_once __DIR__ . '/includes/session.php';

initSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrf((string)($_POST['csrf_token'] ?? ''))) {
    http_response_code(405);
    header('Allow: POST');
    exit('Invalid logout request.');
}

// Unset all session variables
$_SESSION = [];

// Destroy the session cookie
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Destroy the session on the server
session_destroy();

// Prevent caching so the browser back button cannot restore the session
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('Location: login.php');
exit;
