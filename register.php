<?php
// register.php — Create an account in the existing PostgreSQL users table.

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/config/database.php';

initSession();

if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$fieldError = [];
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedCsrf = $_POST['csrf_token'] ?? '';
    if (!is_string($submittedCsrf) || !verifyCsrf($submittedCsrf)) {
        $error = 'Invalid request. Please refresh and try again.';
    } else {
        $usernameInput = $_POST['username'] ?? '';
        $passwordInput = $_POST['password'] ?? '';
        $confirmPasswordInput = $_POST['confirm_password'] ?? '';
        $username = is_string($usernameInput) ? trim($usernameInput) : '';
        $password = is_string($passwordInput) ? $passwordInput : '';
        $confirmPassword = is_string($confirmPasswordInput) ? $confirmPasswordInput : '';

        if ($username === '') {
            $fieldError['username'] = 'Username is required.';
        } elseif (!preg_match('/\A[a-zA-Z0-9_-]{3,32}\z/', $username)) {
            $fieldError['username'] = 'Use 3–32 letters, numbers, underscores, or hyphens.';
        }

        if ($password === '') {
            $fieldError['password'] = 'Password is required.';
        } elseif (strlen($password) < 8) {
            $fieldError['password'] = 'Password must be at least 8 characters.';
        } elseif (strlen($password) > 72) {
            // PASSWORD_DEFAULT currently uses bcrypt in this deployment, which accepts at most 72 bytes.
            $fieldError['password'] = 'Password must be 72 bytes or fewer.';
        }

        if ($confirmPassword === '') {
            $fieldError['confirm_password'] = 'Please confirm your password.';
        } elseif ($password !== $confirmPassword) {
            $fieldError['confirm_password'] = 'Passwords do not match.';
        }

        if ($error === '' && $fieldError === []) {
            try {
                $pdo = getDatabaseConnection();
                $check = $pdo->prepare('SELECT 1 FROM users WHERE username = :username LIMIT 1');
                $check->execute([':username' => $username]);

                if ($check->fetchColumn()) {
                    $fieldError['username'] = 'That username is already taken.';
                } else {
                    $insert = $pdo->prepare(
                        'INSERT INTO users (username, password_hash) VALUES (:username, :password_hash)'
                    );
                    $insert->execute([
                        ':username' => $username,
                        ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    ]);

                    $_SESSION['registration_success'] = 'Account created successfully. Please sign in.';
                    header('Location: login.php');
                    exit;
                }
            } catch (PDOException $e) {
                // A concurrent registration may win after the duplicate check; the unique DB constraint is authoritative.
                if ($e->getCode() === '23505') {
                    $fieldError['username'] = 'That username is already taken.';
                } else {
                    error_log('Account registration failed: ' . $e->getMessage());
                    $error = 'A server error occurred. Please try again later.';
                }
            } catch (Throwable $e) {
                error_log('Account registration failed: ' . $e->getMessage());
                $error = 'A server error occurred. Please try again later.';
            }
        }
    }
}

$pageTitle = 'Create Account — ACCESS';
$bodyClass = 'page-login';
$csrfToken = csrfToken();
include __DIR__ . '/includes/header.php';
?>

<div class="split-layout">
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
                <?php for ($i = 0; $i < 48; $i++): ?><span class="grid-dot"></span><?php endfor; ?>
            </div>
        </div>
    </aside>

    <main class="login-panel" id="main-content">
        <div class="login-panel__inner">
            <header class="login-panel__header">
                <h2 class="login-panel__heading">Create account</h2>
                <p class="login-panel__subtext">Choose a username and password</p>
            </header>

            <?php if ($error !== ''): ?>
                <div class="alert alert--error" role="alert" aria-live="polite">
                    <span class="alert__icon" aria-hidden="true">&#9888;</span>
                    <?= htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form class="login-form" method="post" action="register.php" novalidate id="registerForm" aria-label="Create account form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

                <div class="form-group <?= isset($fieldError['username']) ? 'form-group--error' : '' ?>">
                    <label class="form-label" for="username">Username</label>
                    <div class="input-wrapper">
                        <span class="input-icon" aria-hidden="true">&#64;</span>
                        <input class="form-input" type="text" id="username" name="username" value="<?= htmlspecialchars($username, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" placeholder="Choose a username" autocomplete="username" minlength="3" maxlength="32" required aria-describedby="<?= isset($fieldError['username']) ? 'username-error' : 'username-hint' ?>">
                    </div>
                    <span class="form-hint" id="username-hint">3–32 letters, numbers, underscores, or hyphens.</span>
                    <?php if (isset($fieldError['username'])): ?><span class="form-error" id="username-error" role="alert"><?= htmlspecialchars($fieldError['username'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span><?php endif; ?>
                </div>

                <div class="form-group <?= isset($fieldError['password']) ? 'form-group--error' : '' ?>">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-wrapper">
                        <span class="input-icon" aria-hidden="true">&#9679;</span>
                        <input class="form-input" type="password" id="password" name="password" placeholder="Create a password" autocomplete="new-password" minlength="8" maxlength="72" required aria-describedby="<?= isset($fieldError['password']) ? 'password-error' : 'password-hint' ?>">
                    </div>
                    <span class="form-hint" id="password-hint">Use at least 8 characters.</span>
                    <?php if (isset($fieldError['password'])): ?><span class="form-error" id="password-error" role="alert"><?= htmlspecialchars($fieldError['password'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span><?php endif; ?>
                </div>

                <div class="form-group <?= isset($fieldError['confirm_password']) ? 'form-group--error' : '' ?>">
                    <label class="form-label" for="confirm_password">Confirm Password</label>
                    <div class="input-wrapper">
                        <span class="input-icon" aria-hidden="true">&#9679;</span>
                        <input class="form-input" type="password" id="confirm_password" name="confirm_password" placeholder="Enter your password again" autocomplete="new-password" maxlength="72" required aria-describedby="<?= isset($fieldError['confirm_password']) ? 'confirm-password-error' : '' ?>">
                    </div>
                    <?php if (isset($fieldError['confirm_password'])): ?><span class="form-error" id="confirm-password-error" role="alert"><?= htmlspecialchars($fieldError['confirm_password'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span><?php endif; ?>
                </div>

                <button type="submit" class="btn btn--primary btn--full" id="submitBtn">
                    <span class="btn__text">Create Account</span>
                    <span class="btn__spinner" aria-hidden="true"></span>
                </button>
            </form>

            <p class="auth-switch">Already have an account? <a href="login.php">Sign in</a></p>
        </div>
    </main>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
