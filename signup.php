<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("config/database.php");
include("includes/password_helpers.php");

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
    } elseif ($passwordError = passwordStrengthError($password)) {
        $error = $passwordError;
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

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up · Yosech Construction</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/theme.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/login.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/auth.css">
    <style>
        /* Signup-specific overrides for split layout */
        .login-body { background: #fff; }

        .signup-panel-right {
            flex: 0 0 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fafafa;
            padding: 40px 32px;
            overflow-y: auto;
        }

        .signup-form-wrap {
            width: 100%;
            max-width: 440px;
            padding: 16px 0;
        }

        .signup-title {
            font-size: 2rem;
            font-weight: 700;
            color: #111;
            letter-spacing: -0.03em;
            margin-bottom: 10px;
        }

        .signup-subtitle {
            font-size: 0.85rem;
            color: #6b7280;
            line-height: 1.6;
            margin-bottom: 28px;
        }

        .signup-field {
            margin-bottom: 18px;
        }

        .signup-field label {
            display: block;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            color: #374151;
            margin-bottom: 7px;
            text-transform: uppercase;
        }

        .signup-field input {
            width: 100%;
            padding: 11px 16px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 0.88rem;
            background: #fff;
            color: #111;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .signup-field input:focus {
            outline: none;
            border-color: #6b7f94;
            box-shadow: 0 0 0 3px rgba(107,127,148,0.12);
        }

        .signup-field input::placeholder { color: #9ca3af; }

        .signup-row {
            display: grid;
            grid-template-columns: 1fr 72px;
            gap: 12px;
        }

        .signup-row-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .signup-password-wrap {
            position: relative;
        }

        .signup-password-wrap input {
            width: 100%;
            padding: 11px 44px 11px 16px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 0.88rem;
            background: #fff;
            color: #111;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .signup-password-wrap input:focus {
            outline: none;
            border-color: #6b7f94;
            box-shadow: 0 0 0 3px rgba(107,127,148,0.12);
        }

        .signup-eye {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #9ca3af;
            padding: 4px;
            display: flex;
            align-items: center;
        }

        .signup-eye:hover { color: #374151; }

        .signup-submit {
            width: 100%;
            padding: 13px 20px;
            background: #c2622a;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: background 0.2s, transform 0.15s;
            margin-top: 4px;
        }

        .signup-submit:hover { background: #a8521f; }
        .signup-submit:active { transform: scale(0.98); }

        .signup-divider {
            height: 1px;
            background: #e5e7eb;
            margin: 22px 0;
        }

        .signup-login-text {
            font-size: 0.84rem;
            color: #6b7280;
            text-align: center;
            margin-bottom: 20px;
        }

        .signup-login-text a {
            color: #c2622a;
            font-weight: 600;
            text-decoration: none;
        }

        .signup-login-text a:hover { text-decoration: underline; }

        .signup-footer-note {
            font-size: 0.72rem;
            color: #9ca3af;
            line-height: 1.6;
            text-align: center;
        }

        .signup-alert {
            padding: 10px 14px;
            border-radius: 6px;
            font-size: 0.84rem;
            margin-bottom: 20px;
        }
        .signup-alert-error   { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .signup-alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }

        .signup-label-opt {
            font-size: 0.68rem;
            font-weight: 400;
            color: #9ca3af;
            text-transform: none;
            letter-spacing: 0;
            margin-left: 4px;
        }

        /* reuse password requirements from auth.css */
        .password-requirements {
            list-style: none;
            margin: 0 0 18px;
            padding: 11px 14px;
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            font-size: 0.78rem;
            color: #64748b;
        }
        .password-requirements li {
            position: relative;
            padding-left: 18px;
            margin-bottom: 4px;
        }
        .password-requirements li:last-child { margin-bottom: 0; }
        .password-requirements li::before { content: '○'; position: absolute; left: 0; color: #cbd5e1; }
        .password-requirements li.met { color: #059669; }
        .password-requirements li.met::before { content: '●'; color: #059669; }

        @media (max-width: 768px) {
            .login-panel-left { display: none; }
            .signup-panel-right { flex: 1; padding: 40px 24px; }
            .signup-row-2 { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body class="login-body">

<div class="login-split">

    <!-- LEFT PANEL — same image + branding as login -->
    <div class="login-panel-left">
        <div class="login-panel-brand">
            <span class="login-brand-mark">Y</span>
            <span class="login-brand-name">Yosech Construction</span>
        </div>
        <div class="login-panel-overlay"></div>
        <div class="login-panel-quote">
            <p>"Join us and be part of building the future — one structure, one project, one milestone at a time."</p>
            <div class="login-panel-quote-line"></div>
        </div>
    </div>

    <!-- RIGHT PANEL — signup form -->
    <div class="signup-panel-right">

        <div class="signup-form-wrap">
            <h1 class="signup-title">Create Account</h1>
            <p class="signup-subtitle">Sign up to submit project applications and track construction updates.</p>

            <?php if ($error): ?>
                <div class="signup-alert signup-alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= BASE_URL ?>/signup.php">
                <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">

                <!-- Name row -->
                <div class="signup-row">
                    <div class="signup-field">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" placeholder="Juan" required value="<?= htmlspecialchars($form['first_name']) ?>">
                    </div>
                    <div class="signup-field">
                        <label for="mi">M.I.</label>
                        <input type="text" id="mi" name="mi" maxlength="1" placeholder="A" value="<?= htmlspecialchars($form['mi']) ?>">
                    </div>
                </div>

                <div class="signup-field">
                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" placeholder="dela Cruz" required value="<?= htmlspecialchars($form['last_name']) ?>">
                </div>

                <div class="signup-field">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="juandelacruz" required autocomplete="username" value="<?= htmlspecialchars($form['username']) ?>">
                </div>

                <div class="signup-field">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="juan@email.com" required autocomplete="email" value="<?= htmlspecialchars($form['email']) ?>">
                </div>

                <div class="signup-field">
                    <label for="contact">Contact Number <span class="signup-label-opt">(optional)</span></label>
                    <input type="tel" id="contact" name="contact" placeholder="+63 9XX XXX XXXX" value="<?= htmlspecialchars($form['contact']) ?>">
                </div>

                <!-- Password row -->
                <div class="signup-row-2">
                    <div class="signup-field">
                        <label for="password">Password</label>
                        <div class="signup-password-wrap">
                            <input type="password" id="password" name="password" required autocomplete="new-password" minlength="8">
                            <button type="button" class="signup-eye" onclick="togglePassword('password','eyeIcon1')" aria-label="Toggle password">
                                <svg id="eyeIcon1" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="signup-field">
                        <label for="confirm_password">Confirm Password</label>
                        <div class="signup-password-wrap">
                            <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password" minlength="8">
                            <button type="button" class="signup-eye" onclick="togglePassword('confirm_password','eyeIcon2')" aria-label="Toggle confirm password">
                                <svg id="eyeIcon2" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <ul class="password-requirements" id="passwordRequirements">
                    <li data-rule="length">At least 8 characters</li>
                    <li data-rule="upper">One uppercase letter</li>
                    <li data-rule="lower">One lowercase letter</li>
                    <li data-rule="number">One number</li>
                    <li data-rule="special">One special character (!@#$%…)</li>
                </ul>

                <button type="submit" name="submit" class="signup-submit">
                    CREATE ACCOUNT
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </button>
            </form>

            <div class="signup-divider"></div>

            <p class="signup-login-text">Already have an account? <a href="<?= BASE_URL ?>/login.php<?= $redirect !== 'index.php' ? '?redirect=' . urlencode($redirect) : '' ?>">Sign in</a></p>

            <div class="signup-footer-note">
                © 2024 Yosech Construction &amp; Civil Engineering. Licensed in Zamboanga del Norte.
                By signing up, you agree to our terms and privacy policy.
            </div>
        </div>

    </div>
</div>

<script>
function togglePassword(inputId, iconId) {
    var input = document.getElementById(inputId);
    var icon  = document.getElementById(iconId);
    if (input.type === 'password') {
        input.type = 'text';
        icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>';
    } else {
        input.type = 'password';
        icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
    }
}

(function () {
    var passwordInput = document.getElementById('password');
    var requirements  = document.getElementById('passwordRequirements');
    if (!passwordInput || !requirements) return;

    var rules = {
        length:  function (v) { return v.length >= 8; },
        upper:   function (v) { return /[A-Z]/.test(v); },
        lower:   function (v) { return /[a-z]/.test(v); },
        number:  function (v) { return /[0-9]/.test(v); },
        special: function (v) { return /[^A-Za-z0-9]/.test(v); }
    };

    function updateRequirements() {
        var value = passwordInput.value;
        requirements.querySelectorAll('li').forEach(function (item) {
            var rule = item.dataset.rule;
            var met  = rules[rule] && rules[rule](value);
            item.classList.toggle('met', met);
        });
    }

    passwordInput.addEventListener('input', updateRequirements);
    updateRequirements();
})();
</script>
</body>
</html>
