<?php
include("includes/header.php");
include("includes/navbar.php");
include("config/database.php");
include("includes/password_helpers.php");

$isClient = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Client';
$userID   = $isClient ? (int) $_SESSION['user_id'] : null;
$success  = '';
$error    = '';
$user     = null;

if ($isClient) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM Client WHERE UserID = ?");
    mysqli_stmt_bind_param($stmt, "i", $userID);
    mysqli_stmt_execute($stmt);
    $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isClient) {

    if (isset($_POST['update_profile'])) {
        $firstName   = trim($_POST['first_name']);
        $mi          = trim($_POST['mi']);
        $lastName    = trim($_POST['last_name']);
        $email       = trim($_POST['email']);
        $contact     = trim($_POST['contact']);
        $newUsername = trim($_POST['username']);

        $chk = mysqli_prepare($conn, "SELECT UserID FROM Client WHERE Client_Username = ? AND UserID != ?");
        mysqli_stmt_bind_param($chk, "si", $newUsername, $userID);
        mysqli_stmt_execute($chk);
        mysqli_stmt_store_result($chk);

        if (mysqli_stmt_num_rows($chk) > 0) {
            $error = "Username is already taken.";
        } else {
            $picturePath = $user['ProfilePicture'] ?? null;
            if (!empty($_FILES['profile_picture']['name'])) {
                $uploadDir = "assets/uploads/profiles/";
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $ext = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg','jpeg','png','webp'])) {
                    $filename = "client_{$userID}_" . time() . ".{$ext}";
                    move_uploaded_file($_FILES['profile_picture']['tmp_name'], $uploadDir . $filename);
                    $picturePath = $uploadDir . $filename;
                } else {
                    $error = "Invalid image format. Use JPG, PNG, or WEBP.";
                }
            }

            if (!$error) {
                $up = mysqli_prepare($conn, "UPDATE Client SET Client_FirstName=?, Client_MI=?, Client_LastName=?, Client_Email=?, Client_ContactNumber=?, Client_Username=?, ProfilePicture=? WHERE UserID=?");
                mysqli_stmt_bind_param($up, "sssssssi", $firstName, $mi, $lastName, $email, $contact, $newUsername, $picturePath, $userID);
                if (mysqli_stmt_execute($up)) {
                    $_SESSION['user_name'] = $firstName;
                    $success = "Profile updated successfully.";
                    $stmt2 = mysqli_prepare($conn, "SELECT * FROM Client WHERE UserID = ?");
                    mysqli_stmt_bind_param($stmt2, "i", $userID);
                    mysqli_stmt_execute($stmt2);
                    $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt2));
                } else {
                    $error = "Failed to update profile.";
                }
            }
        }
    }

    if (isset($_POST['change_password'])) {
        $current = $_POST['current_password'];
        $new     = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];
        $hash    = $user['Client_Password'];

        if (!password_verify($current, $hash) && $hash !== $current) {
            $error = "Current password is incorrect.";
        } elseif ($new !== $confirm) {
            $error = "New passwords do not match.";
        } elseif ($pwErr = passwordStrengthError($new)) {
            $error = $pwErr;
        } else {
            $newHash = password_hash($new, PASSWORD_DEFAULT);
            $pw = mysqli_prepare($conn, "UPDATE Client SET Client_Password=? WHERE UserID=?");
            mysqli_stmt_bind_param($pw, "si", $newHash, $userID);
            mysqli_stmt_execute($pw);
            $success = "Password changed successfully.";
        }
    }
}

$displayName = $isClient ? trim(($user['Client_FirstName'] ?? '') . ' ' . ($user['Client_LastName'] ?? '')) : '';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/settings.css">

<div class="settings-page">
<div class="settings-container">

    <h1 class="settings-page-title">Account Settings</h1>
    <p class="settings-page-sub">Manage your profile, security preferences, and personal details.</p>

    <?php if ($success): ?>
        <div class="settings-alert settings-alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="settings-alert settings-alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!$isClient): ?>
    <!-- GUEST STATE -->
    <div class="settings-section">
        <div class="settings-guest">
            <div class="settings-guest-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            </div>
            <div class="settings-guest-title">Sign in to manage your account</div>
            <p class="settings-guest-sub">You need to be logged in as a registered client to view and update your account settings.</p>
            <a href="<?= BASE_URL ?>/login.php" class="settings-btn settings-btn-primary">Log In</a>
        </div>
    </div>

    <?php else: ?>

    <!-- PROFILE SECTION -->
    <div class="settings-section">
        <div class="settings-section-head">
            <div class="settings-section-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </div>
            <div>
                <div class="settings-section-title">Profile Information</div>
                <div class="settings-section-sub">Update your name, username, email, and contact details.</div>
            </div>
        </div>

        <div class="settings-section-body">
            <form method="POST" enctype="multipart/form-data">

                <!-- Avatar row -->
                <div class="settings-avatar-row">
                    <div class="settings-avatar">
                        <?php if (!empty($user['ProfilePicture'])): ?>
                            <img src="<?= BASE_URL . '/' . htmlspecialchars($user['ProfilePicture']) ?>" alt="Profile" id="avatarPreview">
                        <?php else: ?>
                            <svg id="avatarSvg" xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            <img id="avatarPreview" style="display:none;width:100%;height:100%;object-fit:cover;border-radius:50%;">
                        <?php endif; ?>
                    </div>
                    <div class="settings-avatar-info">
                        <div class="settings-avatar-name"><?= htmlspecialchars($displayName ?: 'Your Name') ?></div>
                        <div class="settings-avatar-hint">JPG, PNG or WEBP. Max 2MB recommended.</div>
                        <label class="settings-avatar-upload" for="profile_picture">Change photo</label>
                        <input type="file" id="profile_picture" name="profile_picture" accept="image/*" style="display:none;" onchange="previewAvatar(this)">
                    </div>
                </div>

                <!-- Name row -->
                <div class="settings-grid-3">
                    <div class="settings-field">
                        <label>First Name</label>
                        <input type="text" name="first_name" value="<?= htmlspecialchars($user['Client_FirstName'] ?? '') ?>" required>
                    </div>
                    <div class="settings-field">
                        <label>M.I.</label>
                        <input type="text" name="mi" maxlength="1" value="<?= htmlspecialchars($user['Client_MI'] ?? '') ?>">
                    </div>
                    <div class="settings-field">
                        <label>Last Name</label>
                        <input type="text" name="last_name" value="<?= htmlspecialchars($user['Client_LastName'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="settings-grid">
                    <div class="settings-field">
                        <label>Username</label>
                        <input type="text" name="username" value="<?= htmlspecialchars($user['Client_Username'] ?? '') ?>" required>
                    </div>
                    <div class="settings-field">
                        <label>Email Address</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($user['Client_Email'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="settings-field">
                    <label>Contact Number</label>
                    <input type="text" name="contact" value="<?= htmlspecialchars($user['Client_ContactNumber'] ?? '') ?>">
                </div>

                <div class="settings-field-actions">
                    <button type="submit" name="update_profile" id="saveProfileBtn" class="settings-btn settings-btn-primary" disabled>Save Changes</button>
                </div>

            </form>
        </div>
    </div>

    <!-- SECURITY SECTION -->
    <div class="settings-section">
        <div class="settings-section-head">
            <div class="settings-section-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            </div>
            <div>
                <div class="settings-section-title">Security</div>
                <div class="settings-section-sub">Manage your account password and authentication.</div>
            </div>
        </div>

        <div class="settings-section-body">
            <div class="settings-security-row">
                <div class="settings-security-info">
                    <div class="settings-security-label">Password</div>
                    <div class="settings-security-sub">Keep your account secure with a strong password.</div>
                </div>
                <span class="settings-security-badge">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
                    Active
                </span>
                <button class="settings-btn settings-btn-outline settings-btn-sm" onclick="togglePasswordForm()">Change</button>
            </div>

            <div id="passwordForm" style="display:none;">
                <div class="settings-password-form">
                    <form method="POST">
                        <div class="settings-field">
                            <label>Current Password</label>
                            <input type="password" name="current_password" required>
                        </div>
                        <div class="settings-field">
                            <label>New Password</label>
                            <input type="password" name="new_password" id="new_password" required minlength="8">
                        </div>
                        <ul class="password-requirements" id="passwordRequirements">
                            <li data-rule="length">At least 8 characters</li>
                            <li data-rule="upper">One uppercase letter</li>
                            <li data-rule="lower">One lowercase letter</li>
                            <li data-rule="number">One number</li>
                            <li data-rule="special">One special character (!@#$%…)</li>
                        </ul>
                        <div class="settings-field">
                            <label>Confirm New Password</label>
                            <input type="password" name="confirm_password" required minlength="8">
                        </div>
                        <div class="settings-field-actions" style="gap:8px;display:flex;">
                            <button type="button" class="settings-btn settings-btn-outline settings-btn-sm" onclick="togglePasswordForm()">Cancel</button>
                            <button type="submit" name="change_password" class="settings-btn settings-btn-primary settings-btn-sm">Update Password</button>
                        </div>
                    </form>
                </div>
            </div>


        </div>
    </div>

    <?php endif; ?>

</div>
</div>

<script>
function previewAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            const preview = document.getElementById('avatarPreview');
            const svg = document.getElementById('avatarSvg');
            preview.src = e.target.result;
            preview.style.display = 'block';
            if (svg) svg.style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
        // A new photo counts as a change
        checkProfileChanges();
    }
}

function togglePasswordForm() {
    const form = document.getElementById('passwordForm');
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
}

// ── Profile change detection ──────────────────────────────────────────────────
(function () {
    const saveBtn = document.getElementById('saveProfileBtn');
    if (!saveBtn) return;

    // Collect all text/email inputs inside the profile form (excludes file input)
    const profileForm = saveBtn.closest('form');
    if (!profileForm) return;

    const trackedInputs = profileForm.querySelectorAll('input[type="text"], input[type="email"]');

    // Store original values on page load
    const originals = {};
    trackedInputs.forEach(input => {
        originals[input.name] = input.value;
    });

    // Track whether a new photo was picked
    let photoChanged = false;
    const fileInput = profileForm.querySelector('input[type="file"]');
    if (fileInput) {
        fileInput.addEventListener('change', () => {
            photoChanged = fileInput.files && fileInput.files.length > 0;
            checkProfileChanges();
        });
    }

    function checkProfileChanges() {
        const hasTextChange = Array.from(trackedInputs).some(
            input => input.value !== originals[input.name]
        );
        saveBtn.disabled = !(hasTextChange || photoChanged);
    }

    trackedInputs.forEach(input => {
        input.addEventListener('input', checkProfileChanges);
    });

    // Initialise state
    checkProfileChanges();
})();

// ── Password requirements checker ────────────────────────────────────────────
(function () {
    var passwordInput = document.getElementById('new_password');
    var requirements  = document.getElementById('passwordRequirements');
    if (!passwordInput || !requirements) return;
    var rules = {
        length:  v => v.length >= 8,
        upper:   v => /[A-Z]/.test(v),
        lower:   v => /[a-z]/.test(v),
        number:  v => /[0-9]/.test(v),
        special: v => /[^A-Za-z0-9]/.test(v)
    };
    function update() {
        var val = passwordInput.value;
        requirements.querySelectorAll('li').forEach(li => {
            li.classList.toggle('met', rules[li.dataset.rule]?.(val));
        });
    }
    passwordInput.addEventListener('input', update);
    update();
})();
</script>

<?php include("includes/footer.php"); ?>
