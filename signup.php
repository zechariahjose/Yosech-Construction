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

$error = '';
$success = '';
$redirect = $_GET['redirect'] ?? $_POST['redirect'] ?? 'index.php';

if (isset($_SESSION['user_type'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$form = [
    'first_name' => '',
    'mi' => '',
    'last_name' => '',
    'username' => '',
    'email' => '',
    'contact' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['first_name'] = trim($_POST['first_name'] ?? '');
    $form['mi'] = trim($_POST['mi'] ?? '');
    $form['last_name'] = trim($_POST['last_name'] ?? '');
    $form['username'] = trim($_POST['username'] ?? '');
    $form['email'] = trim($_POST['email'] ?? '');
    $form['contact'] = trim($_POST['contact'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($form['first_name'] === '' || $form['last_name'] === '' || $form['username'] === '' || $form['email'] === '') {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $escUsername = mysqli_real_escape_string($conn, $form['username']);
        $escEmail = mysqli_real_escape_string($conn, $form['email']);

        $check = mysqli_query($conn, "SELECT UserID FROM Client WHERE Client_Username = '{$escUsername}' OR Client_Email = '{$escEmail}' LIMIT 1");
        if (mysqli_num_rows($check) > 0) {
            $error = 'Username or email is already registered.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $escHash = mysqli_real_escape_string($conn, $hash);
            $escFirst = mysqli_real_escape_string($conn, $form['first_name']);
            $escMI = mysqli_real_escape_string($conn, $form['mi']);
            $escLast = mysqli_real_escape_string($conn, $form['last_name']);
            $escContact = mysqli_real_escape_string($conn, $form['contact']);

            $miVal = $escMI !== '' ? "'{$escMI}'" : 'NULL';
            $contactVal = $escContact !== '' ? "'{$escContact}'" : 'NULL';

            $sql = "INSERT INTO Client (Client_FirstName, Client_MI, Client_LastName, Client_Username, Client_Password, Client_Email, Client_ContactNumber)
                    VALUES ('{$escFirst}', {$miVal}, '{$escLast}', '{$escUsername}', '{$escHash}', '{$escEmail}', {$contactVal})";

            if (mysqli_query($conn, $sql)) {
                $loginUrl = BASE_URL . '/login.php?registered=1';
                if ($redirect !== 'index.php') {
                    $loginUrl .= '&redirect=' . urlencode($redirect);
                }
                header('Location: ' . $loginUrl);
                exit;
            }
            $error = 'Registration failed. Please try again.';
        }
    }
}

include("includes/header.php");
include("includes/navbar.php");
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/auth.css">

<div class="auth-page">
    <div class="auth-layout">
        <div class="auth-card auth-card-wide">
            <div class="auth-logo">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <h1 class="auth-title">Sign Up</h1>
            <p class="auth-subtitle">Create your client account to submit applications and track project updates.</p>

            <?php if ($error): ?>
                <div class="auth-alert auth-alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= BASE_URL ?>/signup.php">
                <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">

                <div class="auth-row">
                    <div class="auth-field">
                        <label for="first_name">First Name</label>
                        <div class="auth-input-wrap">
                            <input type="text" id="first_name" name="first_name" required value="<?= htmlspecialchars($form['first_name']) ?>">
                        </div>
                    </div>
                    <div class="auth-field">
                        <label for="mi">M.I.</label>
                        <div class="auth-input-wrap">
                            <input type="text" id="mi" name="mi" maxlength="1" value="<?= htmlspecialchars($form['mi']) ?>">
                        </div>
                    </div>
                </div>

                <div class="auth-field">
                    <label for="last_name">Last Name</label>
                    <div class="auth-input-wrap">
                        <input type="text" id="last_name" name="last_name" required value="<?= htmlspecialchars($form['last_name']) ?>">
                    </div>
                </div>

                <div class="auth-field">
                    <label for="username">Username</label>
                    <div class="auth-input-wrap">
                        <input type="text" id="username" name="username" required autocomplete="username" value="<?= htmlspecialchars($form['username']) ?>">
                    </div>
                </div>

                <div class="auth-field">
                    <label for="email">Email</label>
                    <div class="auth-input-wrap">
                        <input type="email" id="email" name="email" required autocomplete="email" value="<?= htmlspecialchars($form['email']) ?>">
                    </div>
                </div>

                <div class="auth-field">
                    <label for="contact">Contact Number <span style="font-weight:400;color:var(--ysc-muted-light)">(optional)</span></label>
                    <div class="auth-input-wrap">
                        <input type="tel" id="contact" name="contact" value="<?= htmlspecialchars($form['contact']) ?>">
                    </div>
                </div>

                <div class="auth-row-2">
                    <div class="auth-field">
                        <label for="password">Password</label>
                        <div class="auth-input-wrap">
                            <input type="password" id="password" name="password" required autocomplete="new-password" minlength="8">
                        </div>
                    </div>
                    <div class="auth-field">
                        <label for="confirm_password">Confirm Password</label>
                        <div class="auth-input-wrap">
                            <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password" minlength="8">
                        </div>
                    </div>
                </div>

                <button type="submit" class="auth-submit">
                    Create Account
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </button>
            </form>

            <p class="auth-switch">Already have an account? <a href="<?= BASE_URL ?>/login.php<?= $redirect !== 'index.php' ? '?redirect=' . urlencode($redirect) : '' ?>">Sign in</a></p>
        </div>

        <div class="auth-deco" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="0.8"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 3l18 18M3 21L21 3"/></svg>
        </div>
    </div>
</div>

<?php include("includes/footer.php"); ?>
