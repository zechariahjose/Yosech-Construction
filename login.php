<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("config/database.php");

if (!defined('BASE_URL')) {
    $scriptPath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    $appBase = preg_replace('#/(admin|manager)$#', '', $scriptPath);
    $appBase = rtrim($appBase, '/');
    if ($appBase === '') $appBase = '/';
    define('BASE_URL', $appBase);
}

function loginRedirectTarget($path) {
    $path = trim($path);
    if ($path === '' || strpos($path, 'http') === 0 || strpos($path, '//') === 0)
        return BASE_URL . '/index.php';
    if ($path[0] === '/') return $path;
    return BASE_URL . '/' . ltrim($path, '/');
}

function loginStaffRedirectTarget($path, ?string $userType = null) {
    $userType = $userType ?? ($_SESSION['user_type'] ?? 'Admin');
    $path = ltrim(trim($path), '/');
    if ($userType === 'Manager') {
        if ($path !== '' && strpos($path, 'manager/') === 0)
            return loginRedirectTarget($path);
        return loginRedirectTarget('manager/mgr_dashboard.php');
    }
    if ($path !== '' && strpos($path, 'admin/') === 0)
        return loginRedirectTarget($path);
    return loginRedirectTarget('admin/amin_dashboard.php');
}

$error   = '';
$success = isset($_GET['registered']) ? 'Account created successfully. Please sign in.' : '';
$redirect = $_GET['redirect'] ?? $_POST['redirect'] ?? 'index.php';

if (isset($_SESSION['user_type'])) {
    if ($_SESSION['user_type'] === 'Client') { header('Location: ' . loginRedirectTarget($redirect)); exit; }
    if ($_SESSION['user_type'] === 'Admin')   { header('Location: ' . loginStaffRedirectTarget($redirect, 'Admin')); exit; }
    if ($_SESSION['user_type'] === 'Manager') { header('Location: ' . loginStaffRedirectTarget($redirect, 'Manager')); exit; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim(mysqli_real_escape_string($conn, $_POST['username'] ?? ''));
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter your username and password.';
    } else {
        $result = mysqli_query($conn, "SELECT * FROM Employee WHERE Username = '{$username}' LIMIT 1");
        if (mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);
            $hash = $user['Password'];
            if (password_verify($password, $hash) || $hash === $password) {
                $_SESSION['user_id']   = $user['EmployeeID'];
                $_SESSION['user_type'] = $user['UserType'];
                $_SESSION['username']  = $user['Username'];
                header('Location: ' . loginStaffRedirectTarget($redirect, $user['UserType']));
                exit;
            }
        }

        $result = mysqli_query($conn, "SELECT * FROM Client WHERE Client_Username = '{$username}' LIMIT 1");
        if (mysqli_num_rows($result) === 0)
            $result = mysqli_query($conn, "SELECT * FROM Client WHERE Username = '{$username}' LIMIT 1");

        if (mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);
            $hash = $user['Client_Password'] ?? $user['Password'] ?? '';
            if (password_verify($password, $hash) || $hash === $password) {
                $_SESSION['user_id']   = $user['UserID'];
                $_SESSION['user_type'] = 'Client';
                $_SESSION['username']  = $user['Client_Username'] ?? $user['Username'] ?? $username;
                $_SESSION['user_name'] = trim(($user['Client_FirstName'] ?? '') . ' ' . ($user['Client_LastName'] ?? ''));
                header('Location: ' . loginRedirectTarget($redirect !== '' ? $redirect : 'index.php'));
                exit;
            }
        }
        $error = 'Invalid username or password.';
    }
}

if (isset($_GET['error'])) $error = 'Invalid username or password.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In · Yosech Construction</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/theme.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/login.css">
</head>
<body class="login-body">

<div class="login-split">

    <!-- LEFT PANEL — image + branding -->
    <div class="login-panel-left">
        <div class="login-panel-brand">
            <span class="login-brand-mark">Y</span>
            <span class="login-brand-name">Yosech Construction</span>
        </div>
        <div class="login-panel-overlay"></div>
        <div class="login-panel-quote">
            <p>"Building the foundation for progress through structural integrity and architectural precision."</p>
            <div class="login-panel-quote-line"></div>
        </div>
    </div>

    <!-- RIGHT PANEL — form -->
    <div class="login-panel-right">

        <div class="login-form-wrap">
            <h1 class="login-title">Log In</h1>
            <p class="login-subtitle">Enter your credentials to access project management, safety reports, and invoicing.</p>

            <?php if ($success): ?>
                <div class="login-alert login-alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="login-alert login-alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= BASE_URL ?>/login.php">
                <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">

                <div class="login-field">
                    <label for="username">EMAIL ADDRESS / USERNAME</label>
                    <input type="text" id="username" name="username" placeholder="client@project.com" required autocomplete="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>

                <div class="login-field">
                    <div class="login-field-row">
                        <label for="password">PASSWORD</label>
                        <a href="#" class="login-forgot">Forgot Password?</a>
                    </div>
                    <div class="login-password-wrap">
                        <input type="password" id="password" name="password" required autocomplete="current-password">
                        <button type="button" class="login-eye" onclick="togglePassword()" aria-label="Toggle password visibility">
                            <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>

                <label class="login-remember">
                    <input type="checkbox" name="remember" value="1">
                    Remember me on this device
                </label>

                <button type="submit" name="submit" class="login-submit">
                    SIGN IN TO PORTAL
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </button>
            </form>

            <div class="login-divider"></div>

            <p class="login-signup-text">Don't have an account? <a href="<?= BASE_URL ?>/signup.php<?= $redirect !== 'index.php' ? '?redirect=' . urlencode($redirect) : '' ?>">Sign up</a></p>

            <div class="login-footer-note">
                © 2024 Yosech Construction &amp; Civil Engineering. Licensed in Zamboanga del Norte.
                Unauthorized access attempts are logged and monitored for structural security compliance.
            </div>
        </div>

    </div>
</div>

<script>
function togglePassword() {
    const input = document.getElementById('password');
    const icon  = document.getElementById('eyeIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>';
    } else {
        input.type = 'password';
        icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
    }
}
</script>
</body>
</html>
