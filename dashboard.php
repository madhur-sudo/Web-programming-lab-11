<?php
// dashboard.php — Protected welcome page shown after successful login.

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/config/database.php';

initSession();
requireAuth(); // Redirects to login.php if not authenticated
header('Cache-Control: no-store, private');

try {
    $pdo = getDatabaseConnection();
    $stmt = $pdo->prepare('SELECT id, username, created_at FROM users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $_SESSION['user_id']]);
    $user = $stmt->fetch();
} catch (Throwable $error) {
    error_log('Dashboard account lookup failed: ' . $error->getMessage());
    http_response_code(503);
    exit('Service temporarily unavailable. Please try again later.');
}
if (!$user) {
    $_SESSION = [];
    session_destroy();
    header('Location: login.php');
    exit;
}
$displayUsername = $user['username'];
$username = htmlspecialchars($displayUsername, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$pageTitle = 'Welcome — ACCESS';
$bodyClass = 'page-dashboard';
include __DIR__ . '/includes/header.php';
?>

<div class="dashboard-layout">

    <!-- Top navigation bar -->
    <nav class="dashboard-nav" aria-label="Dashboard navigation">
        <div class="dashboard-nav__brand">
            <div class="nav-logo" aria-hidden="true">
                <span class="nav-logo__dot"></span>
            </div>
            <span class="dashboard-nav__title">ACCESS</span>
        </div>
        <div class="dashboard-nav__right">
            <span class="nav-user" aria-label="Logged in as <?= $username ?>">
                <span class="nav-user__avatar" aria-hidden="true"><?= htmlspecialchars(strtoupper(substr($displayUsername, 0, 1)), ENT_QUOTES, 'UTF-8') ?></span>
                <span class="nav-user__name"><?= $username ?></span>
            </span>
            <form method="post" action="logout.php" id="logoutForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="btn btn--outline btn--sm" id="logoutBtn" aria-label="Logout">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
                Logout
                </button>
            </form>
        </div>
    </nav>

    <!-- Main dashboard content -->
    <main class="dashboard-main" id="main-content">

        <!-- Success banner -->
        <section class="success-banner" aria-label="Authentication status">
            <div class="success-banner__icon" aria-hidden="true">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
            </div>
            <div class="success-banner__text">
                <span class="success-banner__status">Signed in successfully</span>
                <span class="success-banner__detail">Welcome back.</span>
            </div>
            <div class="success-banner__pill" aria-label="Status: Authenticated">
                <span class="status-dot" aria-hidden="true"></span>
                Active
            </div>
        </section>

        <!-- Welcome card -->
        <section class="welcome-section">
            <div class="welcome-card">
                <div class="welcome-card__header">
                    <div class="welcome-avatar" aria-hidden="true">
                        <?= htmlspecialchars(strtoupper(substr($displayUsername, 0, 2)), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <div class="welcome-card__meta">
                        <h1 class="welcome-card__heading">
                            Welcome, <span class="highlight"><?= $username ?></span>
                        </h1>
                        <p class="welcome-card__sub">Your account is ready.</p>
                    </div>
                </div>

            </div>
        </section>

    </main>

</div><!-- /.dashboard-layout -->

<?php include __DIR__ . '/includes/footer.php'; ?>
