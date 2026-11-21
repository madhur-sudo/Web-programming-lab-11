<?php
// login.php — Login page and POST handler.

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/config/database.php';

initSession();

// If already logged in, go to dashboard
if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error      = '';
$fieldError = [];
$username   = '';
$registrationSuccess = '';

if (isset($_SESSION['registration_success'])) {
    $registrationSuccess = (string) $_SESSION['registration_success'];
    unset($_SESSION['registration_success']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- CSRF verification ---
    $submittedCsrf = $_POST['csrf_token'] ?? '';
    if (!is_string($submittedCsrf) || !verifyCsrf($submittedCsrf)) {
        $error = 'Invalid request. Please refresh and try again.';
    } else {

        // --- Sanitise inputs ---
        $usernameInput = $_POST['username'] ?? '';
        $passwordInput = $_POST['password'] ?? '';
        $username = is_string($usernameInput) ? trim($usernameInput) : '';
        $password = is_string($passwordInput) ? $passwordInput : '';

        // --- Server-side validation ---
        if ($username === '') {
            $fieldError['username'] = 'Username is required.';
        }
        if ($password === '') {
            $fieldError['password'] = 'Password is required.';
        }

        if (empty($fieldError)) {

            // --- Enforce reasonable length limits ---
            if (strlen($username) > 100 || strlen($password) > 255) {
                $error = 'Invalid username or password.';
            } else {
                try {
                    $pdo  = getDatabaseConnection();
                    $stmt = $pdo->prepare(
                        "SELECT id, username, password_hash FROM users WHERE username = :username LIMIT 1"
                    );
                    $stmt->execute([':username' => $username]);
                    $user = $stmt->fetch();

                    if ($user && password_verify($password, $user['password_hash'])) {
                        // --- Successful login ---
                        session_regenerate_id(true); // prevent session fixation

                        $_SESSION['user_id']  = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['login_at'] = date('Y-m-d H:i:s');

                        // Rotate CSRF token after login
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                            $rehash = $pdo->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
                            $rehash->execute([':hash' => password_hash($password, PASSWORD_DEFAULT), ':id' => $user['id']]);
                        }

                        header('Location: dashboard.php');
                        exit;
                    } else {
                        // Generic failure — do not reveal which field is wrong
                        $error = 'Invalid username or password.';
                        // Intentional slight delay to slow brute-force attempts
                        usleep(300000);
                    }

                } catch (Throwable $e) {
                    $error = 'A server error occurred. Please try again later.';
                    error_log($e->getMessage());
                }
            }
        }
    }
}

$pageTitle  = 'Sign In — ACCESS';
$bodyClass  = 'page-login';
$csrfToken  = csrfToken();
include __DIR__ . '/includes/header.php';
?>

<div class="split-layout">

    <!-- ===== LEFT: BRANDING PANEL ===== -->
    <aside class="brand-panel" aria-label="Application branding">
        <div class="brand-panel__inner">

            <div class="brand-panel__logo" aria-hidden="true">
                <div class="logo-icon">
                    <span class="logo-icon__ring"></span>
                    <span class="logo-icon__ring logo-icon__ring--mid"></span>
                    <span class="logo-icon__dot"></span>
                </div>
            </div>

            <h1 class="brand-panel__title">ACCESS</h1>
            <p class="brand-panel__tagline">A simple, clean authentication experience.</p>

            <div class="brand-panel__grid" aria-hidden="true">
                <?php for ($i = 0; $i < 48; $i++): ?>
                    <span class="grid-dot"></span>
                <?php endfor; ?>
            </div>

        </div>
    </aside>

    <!-- ===== RIGHT: LOGIN FORM ===== -->
    <main class="login-panel" id="main-content">
        <div class="login-panel__inner">

            <header class="login-panel__header">
                <h2 class="login-panel__heading">Sign in</h2>
                <p class="login-panel__subtext">Enter your credentials to continue</p>
            </header>

            <?php if ($error): ?>
                <div class="alert alert--error" role="alert" aria-live="polite">
                    <span class="alert__icon" aria-hidden="true">&#9888;</span>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($registrationSuccess !== ''): ?>
                <div class="alert alert--success" role="status" aria-live="polite">
                    <span class="alert__icon" aria-hidden="true">&#10003;</span>
                    <?= htmlspecialchars($registrationSuccess, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form
                class="login-form"
                method="POST"
                action="login.php"
                novalidate
                id="loginForm"
                aria-label="Login form"
            >
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                <!-- Username -->
                <div class="form-group <?= isset($fieldError['username']) ? 'form-group--error' : '' ?>">
                    <label class="form-label" for="username">Username</label>
                    <div class="input-wrapper">
                        <span class="input-icon" aria-hidden="true">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                        </span>
                        <input
                            type="text"
                            id="username"
                            name="username"
                            class="form-input"
                            value="<?= htmlspecialchars($username) ?>"
                            placeholder="Enter your username"
                            autocomplete="username"
                            maxlength="100"
                            required
                            aria-required="true"
                            aria-describedby="<?= isset($fieldError['username']) ? 'username-error' : '' ?>"
                        >
                    </div>
                    <?php if (isset($fieldError['username'])): ?>
                        <span class="form-error" id="username-error" role="alert">
                            <?= htmlspecialchars($fieldError['username']) ?>
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Password -->
                <div class="form-group <?= isset($fieldError['password']) ? 'form-group--error' : '' ?>">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-wrapper">
                        <span class="input-icon" aria-hidden="true">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                        </span>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-input form-input--password"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            maxlength="255"
                            required
                            aria-required="true"
                            aria-describedby="<?= isset($fieldError['password']) ? 'password-error' : '' ?>"
                        >
                        <button
                            type="button"
                            class="toggle-password"
                            id="togglePassword"
                            aria-label="Show password"
                            aria-pressed="false"
                        >
                            <svg class="eye-icon eye-icon--show" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                            <svg class="eye-icon eye-icon--hide" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="display:none">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                                <line x1="1" y1="1" x2="23" y2="23"/>
                            </svg>
                        </button>
                    </div>
                    <?php if (isset($fieldError['password'])): ?>
                        <span class="form-error" id="password-error" role="alert">
                            <?= htmlspecialchars($fieldError['password']) ?>
                        </span>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn--primary btn--full" id="submitBtn">
                    <span class="btn__text">Sign In</span>
                    <span class="btn__spinner" aria-hidden="true"></span>
                </button>

            </form>

            <p class="auth-switch">Don't have an account? <a href="register.php">Create one</a></p>

        </div><!-- /.login-panel__inner -->
    </main>

</div><!-- /.split-layout -->

<?php include __DIR__ . '/includes/footer.php'; ?>
