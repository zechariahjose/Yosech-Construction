<?php
include("config/database.php");
include("includes/password_helpers.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('BASE_URL')) {
    $scriptPath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    $appBase    = preg_replace('#/(admin|manager)$#', '', $scriptPath);
    $appBase    = rtrim($appBase, '/');
    if ($appBase === '') $appBase = '/';
    define('BASE_URL', $appBase);
}

// Redirect if already logged in
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Client') {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$step    = 'form';   // form → sent → reset
$error   = '';
$success = '';
$token   = trim($_GET['token'] ?? '');

// ── STEP 3: Handle new password submission ──────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_reset'], $_POST['token'])) {
    $tok     = trim($_POST['token']);
    $newPw   = $_POST['new_password']  ?? '';
    $confPw  = $_POST['confirm_password'] ?? '';

    // Validate token
    $stmt = mysqli_prepare($conn,
        "SELECT * FROM password_reset_tokens
         WHERE token = ? AND user_type = 'Client' AND used = 0 AND expires_at > NOW()
         LIMIT 1"
    );
    mysqli_stmt_bind_param($stmt, "s", $tok);
    mysqli_stmt_execute($stmt);
    $tokenRow = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$tokenRow) {
        $error = "This reset link is invalid or has expired. Please request a new one.";
        $step  = 'expired';
    } elseif ($newPw !== $confPw) {
        $error = "Passwords do not match.";
        $step  = 'reset';
        $token = $tok;
    } elseif ($pwErr = passwordStrengthError($newPw)) {
        $error = $pwErr;
        $step  = 'reset';
        $token = $tok;
    } else {
        // Update password and mark token as used
        $hash = password_hash($newPw, PASSWORD_DEFAULT);
        $uid  = (int) $tokenRow['user_id'];

        $upd = mysqli_prepare($conn, "UPDATE Client SET Client_Password = ? WHERE UserID = ?");
        mysqli_stmt_bind_param($upd, "si", $hash, $uid);
        mysqli_stmt_execute($upd);

        $mark = mysqli_prepare($conn, "UPDATE password_reset_tokens SET used = 1 WHERE token = ?");
        mysqli_stmt_bind_param($mark, "s", $tok);
        mysqli_stmt_execute($mark);

        $step    = 'done';
        $success = "Your password has been reset successfully. You can now log in.";
    }
}

// ── STEP 2: Handle email submission (generate token) ───────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_forgot'])) {
    $email = trim($_POST['email'] ?? '');

    if ($email === '') {
        $error = "Please enter your email address.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $escEmail = mysqli_real_escape_string($conn, $email);
        $client   = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT UserID, Client_FirstName FROM Client WHERE Client_Email = '{$escEmail}' LIMIT 1"
        ));

        if (!$client) {
            // Show the same "sent" screen to avoid email enumeration
            $step = 'sent';
        } else {
            // Clean old unused tokens for this user
            $uid = (int) $client['UserID'];
            mysqli_query($conn,
                "DELETE FROM password_reset_tokens WHERE user_id = {$uid} AND used = 0"
            );

            // Generate secure token
            // NOTE: Use MySQL's NOW()+INTERVAL to avoid PHP↔MySQL timezone mismatch
            $rawToken = bin2hex(random_bytes(32)); // 64 hex chars

            $ins = mysqli_prepare($conn,
                "INSERT INTO password_reset_tokens (user_type, user_id, token, expires_at)
                 VALUES ('Client', ?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))"
            );
            mysqli_stmt_bind_param($ins, "is", $uid, $rawToken);
            mysqli_stmt_execute($ins);

            // Build reset URL to display on screen
            $resetUrl = rtrim(BASE_URL, '/') . '/forgot_password.php?token=' . urlencode($rawToken);

            $step    = 'sent';
            $success = $resetUrl; // We pass the URL so it can be displayed
        }
    }
}

// ── STEP 1: Show reset form when token is in URL ────────────
if ($step === 'form' && $token !== '') {
    // Validate token exists and is not expired
    $stmt = mysqli_prepare($conn,
        "SELECT id FROM password_reset_tokens
         WHERE token = ? AND user_type = 'Client' AND used = 0 AND expires_at > NOW()
         LIMIT 1"
    );
    mysqli_stmt_bind_param($stmt, "s", $token);
    mysqli_stmt_execute($stmt);
    $valid = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    $step  = $valid ? 'reset' : 'expired';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password · Yosech Construction</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/theme.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/login.css">
    <style>
        .fp-card {
            background: #fff;
            border-radius: 16px;
            padding: 40px 44px;
            max-width: 480px;
            width: 100%;
            box-shadow: 0 4px 40px rgba(0,0,0,.09);
        }
        .fp-icon {
            width: 56px; height: 56px;
            background: #fff7ed; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 0 20px;
            color: #f97316;
        }
        .fp-title { font-size: 1.35rem; font-weight: 800; color: #111; margin: 0 0 6px; }
        .fp-sub   { font-size: 0.88rem; color: #6b7280; margin: 0 0 28px; line-height: 1.6; }
        .fp-field { margin-bottom: 16px; }
        .fp-field label { display: block; font-size: 0.72rem; font-weight: 700; letter-spacing: .07em; text-transform: uppercase; color: #6b7280; margin-bottom: 6px; }
        .fp-field input { width: 100%; padding: 12px 14px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 0.9rem; font-family: inherit; outline: none; transition: border-color .15s; }
        .fp-field input:focus { border-color: #f97316; box-shadow: 0 0 0 3px rgba(249,115,22,.12); }
        .fp-btn { width: 100%; padding: 13px; background: #f97316; color: #fff; border: none; border-radius: 8px; font-size: 0.9rem; font-weight: 700; cursor: pointer; transition: background .15s; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .fp-btn:hover { background: #ea6c0a; }
        .fp-link { text-align: center; margin-top: 20px; font-size: 0.84rem; color: #6b7280; }
        .fp-link a { color: #f97316; font-weight: 600; text-decoration: none; }
        .fp-link a:hover { text-decoration: underline; }
        .fp-alert { padding: 12px 16px; border-radius: 8px; font-size: 0.85rem; margin-bottom: 20px; display: flex; align-items: flex-start; gap: 10px; }
        .fp-alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .fp-alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .fp-reset-link-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 14px 16px; margin: 16px 0; word-break: break-all; font-size: 0.82rem; font-family: monospace; color: #374151; }
        .fp-reset-link-box a { color: #f97316; font-weight: 600; text-decoration: underline; }
        .fp-pw-wrap { position: relative; }
        .fp-pw-wrap input { padding-right: 44px; }
        .fp-pw-eye { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; padding: 4px; cursor: pointer; color: #9ca3af; display: flex; align-items: center; }
        .fp-pw-eye:hover { color: #f97316; }
        .fp-pw-rules { list-style: none; margin: 10px 0 16px; padding: 10px 14px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 0.76rem; color: #9ca3af; display: grid; grid-template-columns: 1fr 1fr; gap: 4px 12px; }
        .fp-pw-rules li { padding-left: 16px; position: relative; }
        .fp-pw-rules li::before { content: '○'; position: absolute; left: 0; color: #cbd5e1; }
        .fp-pw-rules li.met { color: #059669; }
        .fp-pw-rules li.met::before { content: '●'; color: #059669; }
        .login-panel-right { display: flex; align-items: center; justify-content: center; }
    </style>
</head>
<body class="login-body">

<div class="login-split">

    <!-- LEFT — branding -->
    <div class="login-panel-left">
        <div class="login-panel-brand">
            <span class="login-brand-mark">Y</span>
            <span class="login-brand-name">Yosech Construction</span>
        </div>
        <div class="login-panel-overlay"></div>
        <div class="login-panel-quote">
            <p>"Account recovery is quick and secure — your project data stays safe."</p>
            <div class="login-panel-quote-line"></div>
        </div>
    </div>

    <!-- RIGHT — form -->
    <div class="login-panel-right">
        <div class="fp-card">

            <?php if ($step === 'form'): ?>
            <!-- STEP 1: Enter email -->
            <div class="fp-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            </div>
            <h1 class="fp-title">Forgot Password?</h1>
            <p class="fp-sub">Enter the email address linked to your client account. We'll generate a secure reset link for you.</p>

            <?php if ($error): ?>
            <div class="fp-alert fp-alert-error">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="flex-shrink:0;margin-top:1px;"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg>
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="do_forgot" value="1">
                <div class="fp-field">
                    <label for="fp_email">Email Address</label>
                    <input type="email" id="fp_email" name="email"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           placeholder="you@example.com" required autofocus>
                </div>
                <button type="submit" class="fp-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    Generate Reset Link
                </button>
            </form>

            <div class="fp-link">
                Remembered your password? <a href="<?= BASE_URL ?>/login.php">Sign In</a>
            </div>

            <?php elseif ($step === 'sent'): ?>
            <!-- STEP 2: Show reset link or confirmation -->
            <div class="fp-icon" style="background:#f0fdf4;color:#059669;">
                <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M20 6L9 17l-5-5"/></svg>
            </div>
            <h1 class="fp-title">Reset Link Ready</h1>

            <?php if ($success): ?>
            <!-- Account found — show the link -->
            <p class="fp-sub">Your password reset link has been generated. Click the link below to set a new password. It expires in <strong>1 hour</strong>.</p>
            <div class="fp-reset-link-box">
                <a href="<?= htmlspecialchars($success) ?>" id="resetLink"><?= htmlspecialchars($success) ?></a>
            </div>
            <button type="button" class="fp-btn" style="margin-bottom:16px;"
                    onclick="navigator.clipboard.writeText(this.dataset.url).then(function(){var b=document.getElementById('copyBtn');b.textContent='\u2713 Copied!';setTimeout(function(){b.textContent='Copy Link';},2000);})" id="copyBtn" data-url="<?= htmlspecialchars($success) ?>">
                Copy Link
            </button>
            <a href="<?= htmlspecialchars($success) ?>" class="fp-btn" style="text-decoration:none;margin-bottom:0;">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                Go to Reset Page
            </a>
            <?php else: ?>
            <!-- Account not found — show generic message (no enumeration) -->
            <p class="fp-sub">If that email is registered in our system, a reset link will appear here. No account was found matching that address.</p>
            <?php endif; ?>

            <div class="fp-link" style="margin-top:24px;">
                <a href="<?= BASE_URL ?>/forgot_password.php">Try a different email</a> ·
                <a href="<?= BASE_URL ?>/login.php">Back to Login</a>
            </div>

            <?php elseif ($step === 'reset'): ?>
            <!-- STEP 3: Enter new password -->
            <div class="fp-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>
            </div>
            <h1 class="fp-title">Set New Password</h1>
            <p class="fp-sub">Choose a strong password for your account. It must meet all the requirements below.</p>

            <?php if ($error): ?>
            <div class="fp-alert fp-alert-error">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="flex-shrink:0;margin-top:1px;"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg>
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <form method="POST" id="resetForm">
                <input type="hidden" name="do_reset" value="1">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                <div class="fp-field">
                    <label for="new_password">New Password</label>
                    <div class="fp-pw-wrap">
                        <input type="password" id="new_password" name="new_password"
                               required minlength="8" autofocus>
                        <button type="button" class="fp-pw-eye" onclick="toggleFpPw('new_password',this)" tabindex="-1">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>

                <ul class="fp-pw-rules" id="fpPwRules">
                    <li data-rule="length">At least 8 characters</li>
                    <li data-rule="upper">One uppercase letter</li>
                    <li data-rule="lower">One lowercase letter</li>
                    <li data-rule="number">One number</li>
                    <li data-rule="special">One special character</li>
                </ul>

                <div class="fp-field">
                    <label for="confirm_password">Confirm New Password</label>
                    <div class="fp-pw-wrap">
                        <input type="password" id="confirm_password" name="confirm_password"
                               required minlength="8">
                        <button type="button" class="fp-pw-eye" onclick="toggleFpPw('confirm_password',this)" tabindex="-1">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="fp-btn" id="resetSubmitBtn" disabled>
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
                    Reset Password
                </button>
            </form>

            <?php elseif ($step === 'done'): ?>
            <!-- STEP 4: Success -->
            <div class="fp-icon" style="background:#f0fdf4;color:#059669;">
                <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M20 6L9 17l-5-5"/></svg>
            </div>
            <h1 class="fp-title">Password Reset!</h1>
            <p class="fp-sub">Your password has been updated successfully. You can now sign in with your new password.</p>
            <a href="<?= BASE_URL ?>/login.php" class="fp-btn" style="text-decoration:none;">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                Go to Login
            </a>

            <?php elseif ($step === 'expired'): ?>
            <!-- Expired token -->
            <div class="fp-icon" style="background:#fef2f2;color:#dc2626;">
                <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg>
            </div>
            <h1 class="fp-title">Link Expired</h1>
            <p class="fp-sub">This password reset link is invalid or has already been used. Please request a new one.</p>
            <a href="<?= BASE_URL ?>/forgot_password.php" class="fp-btn" style="text-decoration:none;">
                Request New Link
            </a>
            <div class="fp-link">
                <a href="<?= BASE_URL ?>/login.php">Back to Login</a>
            </div>

            <?php endif; ?>

        </div>
    </div>

</div>

<script>
function toggleFpPw(id, btn) {
    const inp = document.getElementById(id);
    if (!inp) return;
    const hidden = inp.type === 'password';
    inp.type = hidden ? 'text' : 'password';
    btn.style.color = hidden ? '#f97316' : '';
}

// Password requirements
(function () {
    const pw   = document.getElementById('new_password');
    const conf = document.getElementById('confirm_password');
    const btn  = document.getElementById('resetSubmitBtn');
    const list = document.getElementById('fpPwRules');
    if (!pw) return;

    const rules = {
        length:  v => v.length >= 8,
        upper:   v => /[A-Z]/.test(v),
        lower:   v => /[a-z]/.test(v),
        number:  v => /[0-9]/.test(v),
        special: v => /[^A-Za-z0-9]/.test(v)
    };

    function check() {
        const v = pw.value;
        const allMet = Object.values(rules).every(fn => fn(v));
        list.querySelectorAll('li').forEach(li => {
            li.classList.toggle('met', rules[li.dataset.rule]?.(v));
        });
        if (btn && conf) {
            btn.disabled = !(allMet && conf.value.trim() !== '');
        }
    }

    pw.addEventListener('input', check);
    if (conf) conf.addEventListener('input', check);
    check();
})();
</script>
</body>
</html>

