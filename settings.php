<?php
include("includes/header.php");
include("config/database.php");

$isClient = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Client';
$userID = $isClient ? $_SESSION['user_id'] : null;

$success = '';
$error = '';
$user = null;

if ($isClient) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM Client WHERE UserID = ?");
    mysqli_stmt_bind_param($stmt, "i", $userID);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isClient) {

    // Update profile info
    if (isset($_POST['update_profile'])) {
        $firstName       = trim($_POST['first_name']);
        $mi              = trim($_POST['mi']);
        $lastName        = trim($_POST['last_name']);
        $email           = trim($_POST['email']);
        $contact         = trim($_POST['contact']);
        $newUsername     = trim($_POST['username']);

        // Check username uniqueness
        $chkStmt = mysqli_prepare($conn, "SELECT UserID FROM Client WHERE Client_Username = ? AND UserID != ?");
        mysqli_stmt_bind_param($chkStmt, "si", $newUsername, $userID);
        mysqli_stmt_execute($chkStmt);
        mysqli_stmt_store_result($chkStmt);

        if (mysqli_stmt_num_rows($chkStmt) > 0) {
            $error = "Username is already taken.";
        } else {
            // Handle profile picture upload
            $picturePath = $user['ProfilePicture'] ?? null;
            if (!empty($_FILES['profile_picture']['name'])) {
                $uploadDir = "assets/uploads/profiles/";
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                if (in_array(strtolower($ext), $allowed)) {
                    $filename = "client_" . $userID . "_" . time() . "." . $ext;
                    move_uploaded_file($_FILES['profile_picture']['tmp_name'], $uploadDir . $filename);
                    $picturePath = $uploadDir . $filename;
                } else {
                    $error = "Invalid image format. Use JPG, PNG, or WEBP.";
                }
            }

            if (!$error) {
                $upStmt = mysqli_prepare($conn, "UPDATE Client SET Client_FirstName=?, Client_MI=?, Client_LastName=?, Client_Email=?, Client_ContactNumber=?, Client_Username=?, ProfilePicture=? WHERE UserID=?");
                mysqli_stmt_bind_param($upStmt, "sssssssi", $firstName, $mi, $lastName, $email, $contact, $newUsername, $picturePath, $userID);
                if (mysqli_stmt_execute($upStmt)) {
                    $_SESSION['user_name'] = $firstName;
                    $success = "Profile updated successfully.";
                    // Refresh user data
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

    // Change password
    if (isset($_POST['change_password'])) {
        $currentPassword = $_POST['current_password'];
        $newPassword     = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];

        $hash = $user['Client_Password'];
        if (!password_verify($currentPassword, $hash) && $hash !== $currentPassword) {
            $error = "Current password is incorrect.";
        } elseif ($newPassword !== $confirmPassword) {
            $error = "New passwords do not match.";
        } elseif (strlen($newPassword) < 6) {
            $error = "Password must be at least 6 characters.";
        } else {
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $pwStmt = mysqli_prepare($conn, "UPDATE Client SET Client_Password=? WHERE UserID=?");
            mysqli_stmt_bind_param($pwStmt, "si", $newHash, $userID);
            mysqli_stmt_execute($pwStmt);
            $success = "Password changed successfully.";
        }
    }
}
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/settings.css">

<div class="settings-topbar">
    <a href="<?= BASE_URL ?>/index.php" class="settings-back">← Return to Homepage</a>
    <span class="settings-brand">Yosech</span>
    <div class="settings-avatar-sm">
        <?php if ($isClient && !empty($user['ProfilePicture'])): ?>
            <img src="<?= BASE_URL . '/' . htmlspecialchars($user['ProfilePicture']) ?>" alt="avatar">
        <?php else: ?>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="#aaa" stroke-width="1.8"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        <?php endif; ?>
    </div>
</div>

<div class="settings-wrapper">
    <h2 class="settings-title">Account Settings</h2>
    <p class="settings-subtitle">Manage your profile, security preferences, and additional construction details.</p>

    <?php if ($success): ?>
        <div class="alert alert-success py-2 small mb-4"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger py-2 small mb-4"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!$isClient): ?>
        <div class="settings-guest">
            <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" fill="none" viewBox="0 0 24 24" stroke="#ccc" stroke-width="1.5"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            <p>You need to <a href="<?= BASE_URL ?>/login.php">login</a> to manage your account settings.</p>
        </div>
    <?php else: ?>

    <div class="row g-4">
        <!-- Profile Information -->
        <div class="col-md-7">
            <div class="settings-card">
                <div class="settings-card-header">
                    <div class="settings-card-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="#6b7280" stroke-width="1.8"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </div>
                    <div>
                        <div class="settings-card-title">Profile Information</div>
                        <div class="settings-card-sub">Update your personal identification details.</div>
                    </div>
                </div>

                <form method="POST" enctype="multipart/form-data">
                    <!-- Profile Picture -->
                    <div class="settings-avatar-wrap">
                        <div class="settings-avatar">
                            <?php if (!empty($user['ProfilePicture'])): ?>
                                <img src="<?= BASE_URL . '/' . htmlspecialchars($user['ProfilePicture']) ?>" alt="Profile Picture" id="avatarPreview">
                            <?php else: ?>
                                <svg id="avatarSvg" xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="none" viewBox="0 0 24 24" stroke="#aaa" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                <img id="avatarPreview" style="display:none; width:100%; height:100%; object-fit:cover; border-radius:50%;">
                            <?php endif; ?>
                        </div>
                        <label class="settings-avatar-btn" for="profile_picture">Change Photo</label>
                        <input type="file" id="profile_picture" name="profile_picture" accept="image/*" style="display:none;" onchange="previewAvatar(this)">
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-5">
                            <label class="settings-label">First Name</label>
                            <input type="text" name="first_name" class="form-control settings-input" value="<?= htmlspecialchars($user['Client_FirstName']) ?>" required>
                        </div>
                        <div class="col-md-2">
                            <label class="settings-label">M.I.</label>
                            <input type="text" name="mi" class="form-control settings-input" maxlength="1" value="<?= htmlspecialchars($user['Client_MI'] ?? '') ?>">
                        </div>
                        <div class="col-md-5">
                            <label class="settings-label">Last Name</label>
                            <input type="text" name="last_name" class="form-control settings-input" value="<?= htmlspecialchars($user['Client_LastName']) ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="settings-label">Username</label>
                        <input type="text" name="username" class="form-control settings-input" value="<?= htmlspecialchars($user['Client_Username']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="settings-label">Email Address</label>
                        <input type="email" name="email" class="form-control settings-input" value="<?= htmlspecialchars($user['Client_Email']) ?>" required>
                    </div>

                    <div class="mb-4">
                        <label class="settings-label">Contact Number</label>
                        <input type="text" name="contact" class="form-control settings-input" value="<?= htmlspecialchars($user['Client_ContactNumber'] ?? '') ?>">
                    </div>

                    <div class="text-end">
                        <button type="submit" name="update_profile" class="btn btn-primary settings-btn">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Security -->
        <div class="col-md-5">
            <div class="settings-card">
                <div class="settings-card-header">
                    <div class="settings-card-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="#6b7280" stroke-width="1.8"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    </div>
                    <div>
                        <div class="settings-card-title">Security</div>
                        <div class="settings-card-sub">Manage your authentication methods.</div>
                    </div>
                </div>

                <div class="settings-security-item">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <span class="settings-security-label">Password</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#22c55e" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
                    </div>
                    <p class="settings-security-sub">Keep your account secure with a strong password.</p>
                    <button class="btn btn-outline-secondary btn-sm settings-change-btn" onclick="togglePasswordForm()">Change Password</button>

                    <div id="passwordForm" style="display:none; margin-top:16px;">
                        <form method="POST">
                            <div class="mb-2">
                                <label class="settings-label">Current Password</label>
                                <input type="password" name="current_password" class="form-control settings-input" required>
                            </div>
                            <div class="mb-2">
                                <label class="settings-label">New Password</label>
                                <input type="password" name="new_password" class="form-control settings-input" required>
                            </div>
                            <div class="mb-3">
                                <label class="settings-label">Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-control settings-input" required>
                            </div>
                            <button type="submit" name="change_password" class="btn btn-primary btn-sm w-100 settings-btn">Update Password</button>
                        </form>
                    </div>
                </div>

                <div class="settings-security-item">
                    <div class="settings-security-label">2FA Status</div>
                    <p class="settings-security-sub">Two-factor authentication is not yet available.</p>
                    <button class="btn btn-outline-secondary btn-sm settings-change-btn" disabled>Coming Soon</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<footer class="settings-footer">
    <a href="#">Privacy Policy</a>
    <a href="#">Terms of Service</a>
    <a href="#">Safety Guidelines</a>
    <a href="#">Contact Support</a>
    <p class="mt-2 mb-0">© 2024 Yosech Construction Inc. All rights reserved.</p>
</footer>

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
    }
}

function togglePasswordForm() {
    const form = document.getElementById('passwordForm');
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
}
</script>

<?php include("includes/footer.php"); ?>
