<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("config/database.php");

if (!defined('BASE_URL')) {
    $scriptPath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    $appBase = preg_replace('#/admin$#', '', $scriptPath);
    $appBase = rtrim($appBase, '/');
    if ($appBase === '') {
        $appBase = '/';
    }
    define('BASE_URL', $appBase);
}

function loginRedirectTarget($path)
{
    $path = trim($path);
    if ($path === '' || strpos($path, 'http') === 0 || strpos($path, '//') === 0) {
        return BASE_URL . '/index.php';
    }
    if ($path[0] === '/') {
        return $path;
    }
    return BASE_URL . '/' . ltrim($path, '/');
}

$error = '';
$success = isset($_GET['registered']) ? 'Account created successfully. Please sign in.' : '';
$redirect = $_GET['redirect'] ?? $_POST['redirect'] ?? 'index.php';

if (isset($_SESSION['user_type'])) {
    if ($_SESSION['user_type'] === 'Client') {
        header('Location: ' . loginRedirectTarget($redirect));
        exit;
    }
    if (in_array($_SESSION['user_type'], ['Admin', 'Manager'])) {
        header('Location: ' . loginRedirectTarget($redirect !== 'index.php' ? $redirect : 'admin/amin_dashboard.php'));
        exit;
    }
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
                $_SESSION['user_id'] = $user['EmployeeID'];
                $_SESSION['user_type'] = $user['UserType'];
                $_SESSION['username'] = $user['Username'];

                header('Location: ' . loginRedirectTarget($redirect !== '' ? $redirect : 'admin/amin_dashboard.php'));
                exit;
            }
        }

        $result = mysqli_query($conn, "SELECT * FROM Client WHERE Client_Username = '{$username}' LIMIT 1");
        if (mysqli_num_rows($result) === 0) {
            $result = mysqli_query($conn, "SELECT * FROM Client WHERE Username = '{$username}' LIMIT 1");
        }

        if (mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);
            $hash = $user['Client_Password'] ?? $user['Password'] ?? '';

            if (password_verify($password, $hash) || $hash === $password) {
                $_SESSION['user_id'] = $user['UserID'];
                $_SESSION['user_type'] = 'Client';
                $_SESSION['username'] = $user['Client_Username'] ?? $user['Username'] ?? $username;
                $_SESSION['user_name'] = trim(($user['Client_FirstName'] ?? $user['FirstName'] ?? '') . ' ' . ($user['Client_LastName'] ?? $user['LastName'] ?? ''));

                header('Location: ' . loginRedirectTarget($redirect !== '' ? $redirect : 'index.php'));
                exit;
            }
        }

        $error = 'Invalid username or password.';
    }
}

if (isset($_GET['error'])) {
    $error = 'Invalid username or password.';
}

include("includes/header.php");
include("includes/navbar.php");
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/auth.css">

<div class="auth-page">
    <div class="auth-layout">
        <div class="auth-card">
            <div class="auth-logo">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4"/><path d="M9 9v.01M9 12v.01M9 15v.01"/></svg>
            </div>
            <h1 class="auth-title">Client Portal</h1>
            <p class="auth-subtitle">Sign in to submit applications and view project updates.</p>

            <?php if ($success): ?>
                <div class="auth-alert auth-alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="auth-alert auth-alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= BASE_URL ?>/login.php">
                <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">

                <div class="auth-field">
                    <label for="username">Username or Email</label>
                    <div class="auth-input-wrap">
                        <input type="text" id="username" name="username" required autocomplete="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                        <span class="auth-input-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </span>
                    </div>
                </div>

                <div class="auth-field">
                    <label for="password">Password</label>
                    <div class="auth-input-wrap">
                        <input type="password" id="password" name="password" required autocomplete="current-password">
                        <span class="auth-input-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        </span>
                    </div>
                </div>

                <label class="auth-remember">
                    <input type="checkbox" name="remember" value="1">
                    Remember this device for 30 days
                </label>

                <button type="submit" name="submit" class="auth-submit">
                    Sign In
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </button>
            </form>

            <p class="auth-switch">Don't have an account? <a href="<?= BASE_URL ?>/signup.php<?= $redirect !== 'index.php' ? '?redirect=' . urlencode($redirect) : '' ?>">Sign up</a></p>
        </div>

        <div class="auth-deco" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="0.8"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 3l18 18M3 21L21 3"/></svg>
        </div>
    </div>
</div>

<?php include("includes/footer.php"); ?>
