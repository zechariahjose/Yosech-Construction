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
$activeTab   = isset($_GET['tab']) && $_GET['tab'] === 'security' ? 'security' : 'profile';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/settings.css">

<div class="st-page">
<div class="st-wrap">

    <?php if ($success): ?>
        <div class="st-toast st-toast-success">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="st-toast st-toast-error">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if (!$isClient): ?>
    <!-- GUEST STATE -->
    <div class="st-guest-card">
        <div class="st-guest-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        </div>
        <h2 class="st-guest-title">Sign in to manage your account</h2>
        <p class="st-guest-sub">You need to be logged in as a registered client to view and update your account settings.</p>
        <a href="<?= BASE_URL ?>/login.php" class="st-btn st-btn-primary">Log In</a>
    </div>

    <?php else: ?>

    <!-- TWO-COLUMN LAYOUT -->
    <div class="st-layout">

        <!-- ── SIDEBAR ── -->
        <aside class="st-sidebar">
            <!-- User identity -->
            <div class="st-sidebar-identity">
                <div class="st-sidebar-avatar">
                    <?php if (!empty($user['ProfilePicture'])): ?>
                        <img src="<?= BASE_URL . '/' . htmlspecialchars($user['ProfilePicture']) ?>" alt="Profile">
                    <?php else: ?>
                        <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <?php endif; ?>
                </div>
                <div class="st-sidebar-name"><?= htmlspecialchars($displayName ?: 'Client') ?></div>
                <div class="st-sidebar-username">@<?= htmlspecialchars($user['Client_Username'] ?? '') ?></div>
            </div>

            <!-- Nav -->
            <nav class="st-sidebar-nav">
                <a href="?tab=profile" class="st-nav-item <?= $activeTab === 'profile' ? 'active' : '' ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    Profile
                </a>
                <a href="?tab=security" class="st-nav-item <?= $activeTab === 'security' ? 'active' : '' ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    Security
                </a>
            </nav>

            <div class="st-sidebar-divider"></div>

            <a href="logout.php" class="st-nav-item st-nav-signout">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Sign Out
            </a>
        </aside>

        <!-- ── MAIN CONTENT ── -->
        <main class="st-main">

            <?php if ($activeTab === 'profile'): ?>
            <!-- ===== PROFILE TAB ===== -->

            <!-- Profile header card -->
            <div class="st-card st-profile-header-card">
                <div class="st-profile-hero">
                    <div class="st-profile-hero-avatar">
                        <?php if (!empty($user['ProfilePicture'])): ?>
                            <img src="<?= BASE_URL . '/' . htmlspecialchars($user['ProfilePicture']) ?>" alt="Profile" id="avatarHeroImg">
                        <?php else: ?>
                            <svg id="avatarHeroSvg" xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            <img id="avatarHeroImg" style="display:none;width:100%;height:100%;object-fit:cover;border-radius:50%;">
                        <?php endif; ?>
                    </div>
                    <div class="st-profile-hero-info">
                        <div class="st-profile-hero-name"><?= htmlspecialchars($displayName ?: 'Client') ?></div>
                        <div class="st-profile-hero-meta">@<?= htmlspecialchars($user['Client_Username'] ?? '') ?></div>
                    </div>
                </div>
            </div>

            <!-- Profile form card -->
            <div class="st-card">
                <div class="st-card-header">
                    <div class="st-card-header-left">
                        <div class="st-card-title">Personal Information</div>
                        <div class="st-card-sub">Manage your name, username, email and contact details.</div>
                    </div>
                </div>

                <div class="st-card-body">
                    <form method="POST" enctype="multipart/form-data" id="profileForm">

                        <!-- Photo upload -->
                        <div class="st-photo-row">
                            <div class="st-photo-avatar">
                                <?php if (!empty($user['ProfilePicture'])): ?>
                                    <img src="<?= BASE_URL . '/' . htmlspecialchars($user['ProfilePicture']) ?>" alt="Profile" id="avatarPreview">
                                <?php else: ?>
                                    <svg id="avatarSvg" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                    <img id="avatarPreview" style="display:none;width:100%;height:100%;object-fit:cover;border-radius:50%;">
                                <?php endif; ?>
                            </div>
                            <div>
                                <div class="st-photo-label">Profile Photo</div>
                                <div class="st-photo-hint">JPG, PNG or WEBP · Max 2MB</div>
                                <label class="st-photo-btn" for="profile_picture">Change photo</label>
                                <input type="file" id="profile_picture" name="profile_picture" accept="image/*" style="display:none;" onchange="previewAvatar(this)">
                            </div>
                        </div>

                        <div class="st-divider"></div>

                        <!-- Name -->
                        <div class="st-row-label">Full Name</div>
                        <div class="st-grid-3">
                            <div class="st-field">
                                <label>First Name</label>
                                <input type="text" name="first_name" value="<?= htmlspecialchars($user['Client_FirstName'] ?? '') ?>" required>
                            </div>
                            <div class="st-field">
                                <label>M.I.</label>
                                <input type="text" name="mi" maxlength="1" value="<?= htmlspecialchars($user['Client_MI'] ?? '') ?>">
                            </div>
                            <div class="st-field">
                                <label>Last Name</label>
                                <input type="text" name="last_name" value="<?= htmlspecialchars($user['Client_LastName'] ?? '') ?>" required>
                            </div>
                        </div>

                        <!-- Username + Email -->
                        <div class="st-grid-2">
                            <div class="st-field">
                                <label>Username</label>
                                <div class="st-input-prefix-wrap">
                                    <span class="st-input-prefix">@</span>
                                    <input type="text" name="username" class="st-has-prefix" value="<?= htmlspecialchars($user['Client_Username'] ?? '') ?>" required>
                                </div>
                            </div>
                            <div class="st-field">
                                <label>Email Address</label>
                                <input type="email" name="email" value="<?= htmlspecialchars($user['Client_Email'] ?? '') ?>" required>
                            </div>
                        </div>

                        <!-- Contact -->
                        <div class="st-field">
                            <label>Contact Number</label>
                            <input type="text" name="contact" value="<?= htmlspecialchars($user['Client_ContactNumber'] ?? '') ?>">
                        </div>

                        <div class="st-form-footer">
                            <button type="submit" name="update_profile" id="saveProfileBtn" class="st-btn st-btn-primary" disabled>Save Changes</button>
                        </div>

                    </form>
                </div>
            </div>

            <?php else: ?>
            <!-- ===== SECURITY TAB ===== -->

            <div class="st-card">
                <div class="st-card-header">
                    <div class="st-card-header-left">
                        <div class="st-card-title">Security Settings</div>
                        <div class="st-card-sub">Manage your account password and keep your account secure.</div>
                    </div>
                </div>

                <div class="st-card-body">

                    <!-- Password row -->
                    <div class="st-security-item">
                        <div class="st-security-item-left">
                            <div class="st-security-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            </div>
                            <div>
                                <div class="st-security-label">Password</div>
                                <div class="st-security-sub">Keep your account secure with a strong password.</div>
                            </div>
                        </div>
                        <div class="st-security-item-right">
                            <span class="st-badge-active">
                                <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path d="M20 6L9 17l-5-5"/></svg>
                                Active
                            </span>
                            <button class="st-btn st-btn-outline st-btn-sm" onclick="togglePasswordForm()">Change Password</button>
                        </div>
                    </div>

                    <!-- Inline password form -->
                    <div id="passwordForm" style="display:none;">
                        <div class="st-pw-form">
                            <form method="POST">
                                <div class="st-grid-2">
                                    <div class="st-field">
                                        <label>Current Password</label>
                                        <div class="st-pw-wrap">
                                            <input type="password" name="current_password" id="cur_password" required>
                                            <button type="button" class="st-pw-eye" onclick="togglePwVisibility('cur_password', this)" tabindex="-1" aria-label="Toggle visibility">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                    <div></div>
                                </div>
                                <div class="st-grid-2">
                                    <div class="st-field">
                                        <label>New Password</label>
                                        <div class="st-pw-wrap">
                                            <input type="password" name="new_password" id="new_password" required minlength="8">
                                            <button type="button" class="st-pw-eye" onclick="togglePwVisibility('new_password', this)" tabindex="-1" aria-label="Toggle visibility">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="st-field">
                                        <label>Confirm New Password</label>
                                        <div class="st-pw-wrap">
                                            <input type="password" name="confirm_password" id="confirm_password" required minlength="8">
                                            <button type="button" class="st-pw-eye" onclick="togglePwVisibility('confirm_password', this)" tabindex="-1" aria-label="Toggle visibility">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <ul class="st-pw-rules" id="passwordRequirements">
                                    <li data-rule="length">At least 8 characters</li>
                                    <li data-rule="upper">One uppercase letter</li>
                                    <li data-rule="lower">One lowercase letter</li>
                                    <li data-rule="number">One number</li>
                                    <li data-rule="special">One special character (!@#$%…)</li>
                                </ul>

                                <div class="st-form-footer" style="gap:8px;">
                                    <button type="button" class="st-btn st-btn-outline st-btn-sm" onclick="togglePasswordForm()">Cancel</button>
                                    <button type="submit" name="change_password" class="st-btn st-btn-primary st-btn-sm">Update Password</button>
                                </div>
                            </form>
                        </div>
                    </div>

                </div>
            </div>

            <?php endif; ?>

        </main>
    </div><!-- /.st-layout -->

    <?php endif; ?>

</div><!-- /.st-wrap -->
</div><!-- /.st-page -->

<script>
function previewAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            // small form avatar
            const preview = document.getElementById('avatarPreview');
            const svg     = document.getElementById('avatarSvg');
            if (preview) { preview.src = e.target.result; preview.style.display = 'block'; }
            if (svg) svg.style.display = 'none';
            // hero avatar
            const heroImg = document.getElementById('avatarHeroImg');
            const heroSvg = document.getElementById('avatarHeroSvg');
            if (heroImg) { heroImg.src = e.target.result; heroImg.style.display = 'block'; }
            if (heroSvg) heroSvg.style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
        checkProfileChanges();
    }
}

function togglePasswordForm() {
    const form = document.getElementById('passwordForm');
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
}

function togglePwVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    btn.style.color = isHidden ? 'var(--ysc-primary)' : '';
}

// ── Profile change detection ──────────────────────────────────────────────────
(function () {
    const saveBtn = document.getElementById('saveProfileBtn');
    if (!saveBtn) return;

    const profileForm = saveBtn.closest('form');
    if (!profileForm) return;

    const trackedInputs = profileForm.querySelectorAll('input[type="text"], input[type="email"]');

    const originals = {};
    trackedInputs.forEach(input => { originals[input.name] = input.value; });

    let photoChanged = false;
    const fileInput = profileForm.querySelector('input[type="file"]');
    if (fileInput) {
        fileInput.addEventListener('change', () => {
            photoChanged = fileInput.files && fileInput.files.length > 0;
            checkProfileChanges();
        });
    }

    window.checkProfileChanges = function () {
        const hasTextChange = Array.from(trackedInputs).some(
            input => input.value !== originals[input.name]
        );
        saveBtn.disabled = !(hasTextChange || photoChanged);
    };

    trackedInputs.forEach(input => input.addEventListener('input', checkProfileChanges));
    checkProfileChanges();
})();

// ── Password requirements checker ────────────────────────────────────────────
(function () {
    const pw   = document.getElementById('new_password');
    const list = document.getElementById('passwordRequirements');
    if (!pw || !list) return;
    const rules = {
        length:  v => v.length >= 8,
        upper:   v => /[A-Z]/.test(v),
        lower:   v => /[a-z]/.test(v),
        number:  v => /[0-9]/.test(v),
        special: v => /[^A-Za-z0-9]/.test(v)
    };
    function update() {
        list.querySelectorAll('li').forEach(li => {
            li.classList.toggle('met', rules[li.dataset.rule]?.(pw.value));
        });
    }
    pw.addEventListener('input', update);
    update();
})();
</script>

<?php include("includes/footer.php"); ?>
