<?php
include("../config/database.php");
include("../includes/admin/helpers.php");
include("../includes/password_helpers.php");

adminRequireLogin('admin/amin_settings.php');

$adminEmployee = adminCurrentEmployee($conn);
$adminPendingCount = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM Application WHERE Status = 'Pending'"))['total'];

$successMsg = '';
$errorMsg   = '';

// ── 1. Update admin profile ───────────────────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $newEmail    = trim($_POST['email'] ?? '');
    $newContact  = trim($_POST['contact'] ?? '');
    $newUsername = trim($_POST['username'] ?? '');

    if ($newEmail === '' || $newUsername === '') {
        $errorMsg = 'Username and email are required.';
    } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        $errorMsg = 'Please enter a valid email address.';
    } else {
        $adminId = (int) ($adminEmployee['EmployeeID'] ?? 0);

        $stmt = mysqli_prepare($conn,
            "UPDATE Employee SET Username = ?, Email = ?, ContactNumber = ? WHERE EmployeeID = ?");
        mysqli_stmt_bind_param($stmt, 'sssi', $newUsername, $newEmail, $newContact, $adminId);

        if (mysqli_stmt_execute($stmt)) {
            $successMsg    = 'Profile updated successfully.';
            $adminEmployee = adminCurrentEmployee($conn); // refresh
        } else {
            $errorMsg = 'Could not update profile. The username or email may already be in use.';
        }
        mysqli_stmt_close($stmt);
    }
}

// ── 2. Change admin password ──────────────────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $currentPw  = $_POST['current_password'] ?? '';
    $newPw      = $_POST['new_password'] ?? '';
    $confirmPw  = $_POST['confirm_password'] ?? '';
    $adminId    = (int) ($adminEmployee['EmployeeID'] ?? 0);

    $row = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT Password FROM Employee WHERE EmployeeID = {$adminId} LIMIT 1"));

    if (!$row || !password_verify($currentPw, $row['Password'])) {
        $errorMsg = 'Current password is incorrect.';
    } elseif ($newPw !== $confirmPw) {
        $errorMsg = 'New passwords do not match.';
    } elseif ($strengthErr = passwordStrengthError($newPw)) {
        $errorMsg = $strengthErr;
    } else {
        $hashed = password_hash($newPw, PASSWORD_DEFAULT);
        $stmt   = mysqli_prepare($conn,
            "UPDATE Employee SET Password = ? WHERE EmployeeID = ?");
        mysqli_stmt_bind_param($stmt, 'si', $hashed, $adminId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $successMsg = 'Password changed successfully.';
    }
}

// ── 3. Reset employee password ────────────────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'reset_employee_password') {
    $targetId = (int) ($_POST['target_id'] ?? 0);
    $newPw    = $_POST['new_password'] ?? '';
    $confirmPw = $_POST['confirm_password'] ?? '';

    if ($targetId <= 0) {
        $errorMsg = 'Invalid employee selected.';
    } elseif ($newPw !== $confirmPw) {
        $errorMsg = 'Passwords do not match.';
    } elseif ($strengthErr = passwordStrengthError($newPw)) {
        $errorMsg = $strengthErr;
    } else {
        $hashed = password_hash($newPw, PASSWORD_DEFAULT);
        $stmt   = mysqli_prepare($conn,
            "UPDATE Employee SET Password = ? WHERE EmployeeID = ?");
        mysqli_stmt_bind_param($stmt, 'si', $hashed, $targetId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $successMsg = 'Employee password reset successfully.';
    }
}

// ── 4. Reset client password ──────────────────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'reset_client_password') {
    $targetId  = (int) ($_POST['target_id'] ?? 0);
    $newPw     = $_POST['new_password'] ?? '';
    $confirmPw = $_POST['confirm_password'] ?? '';

    if ($targetId <= 0) {
        $errorMsg = 'Invalid client selected.';
    } elseif ($newPw !== $confirmPw) {
        $errorMsg = 'Passwords do not match.';
    } elseif ($strengthErr = passwordStrengthError($newPw)) {
        $errorMsg = $strengthErr;
    } else {
        $hashed = password_hash($newPw, PASSWORD_DEFAULT);
        $stmt   = mysqli_prepare($conn,
            "UPDATE Client SET Client_Password = ? WHERE UserID = ?");
        mysqli_stmt_bind_param($stmt, 'si', $hashed, $targetId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $successMsg = 'Client password reset successfully.';
    }
}

// ── Data for tables ───────────────────────────────────────────────────────
$staffResult  = mysqli_query($conn, "SELECT EmployeeID, UserType, Username, Email, ContactNumber FROM Employee ORDER BY UserType, Username");
$clientResult = mysqli_query($conn, "SELECT UserID, Client_FirstName, Client_MI, Client_LastName, Client_Username, Client_Email FROM Client ORDER BY Client_LastName, Client_FirstName");
$staffCount   = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM Employee"))['total'];
$clientCount  = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM Client"))['total'];

$adminActiveNav   = 'settings';
$adminPageTitle   = 'Admin Settings';
$adminPageSubtitle = 'Profile, password management, user roles, and platform branding.';
$adminPageActions = '
    <a href="' . BASE_URL . '/logout.php" class="admin-btn admin-btn-outline">Sign Out</a>
';

include("../includes/admin/layout_start.php");
?>

<?php if ($successMsg): ?>
    <div class="alert alert-success mb-4"><?= htmlspecialchars($successMsg) ?></div>
<?php endif; ?>
<?php if ($errorMsg): ?>
    <div class="alert alert-danger mb-4"><?= htmlspecialchars($errorMsg) ?></div>
<?php endif; ?>

<div class="row g-4">

    <!-- ── Edit Profile ─────────────────────────────────────────────────── -->
    <div class="col-lg-6">
        <div class="admin-card">
            <h3 class="admin-card-title">Edit Your Profile</h3>
            <?php if ($adminEmployee): ?>
            <form method="post" novalidate>
                <input type="hidden" name="action" value="update_profile">
                <div class="mb-3">
                    <label class="form-label small">Username</label>
                    <input type="text" name="username" class="form-control"
                           value="<?= htmlspecialchars($adminEmployee['Username']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small">Email</label>
                    <input type="email" name="email" class="form-control"
                           value="<?= htmlspecialchars($adminEmployee['Email']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small">Contact Number</label>
                    <input type="text" name="contact" class="form-control"
                           value="<?= htmlspecialchars($adminEmployee['ContactNumber'] ?? '') ?>">
                </div>
                <div class="admin-meta-item mb-3">
                    <span>Role</span><?= htmlspecialchars($adminEmployee['UserType']) ?>
                </div>
                <button type="submit" class="admin-btn admin-btn-primary">Save Changes</button>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── Change Own Password ──────────────────────────────────────────── -->
    <div class="col-lg-6">
        <div class="admin-card">
            <h3 class="admin-card-title">Change Your Password</h3>
            <form method="post" novalidate>
                <input type="hidden" name="action" value="change_password">
                <div class="mb-3">
                    <label class="form-label small">Current Password</label>
                    <input type="password" name="current_password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small">New Password</label>
                    <input type="password" name="new_password" class="form-control" required>
                    <div class="form-text">Min 8 chars, uppercase, lowercase, number, special character.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label small">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                <button type="submit" class="admin-btn admin-btn-primary">Update Password</button>
            </form>
        </div>
    </div>

    <!-- ── Platform Branding ────────────────────────────────────────────── -->
    <div class="col-12">
        <div class="admin-card">
            <h3 class="admin-card-title">Platform Branding</h3>
            <div class="admin-meta-grid">
                <div class="admin-meta-item"><span>Company Name</span>Yosech Construction &amp; Civil Engineering</div>
                <div class="admin-meta-item"><span>Public Site Title</span>Yosech Construction</div>
                <div class="admin-meta-item"><span>Admin Console</span>Yosech Admin · Operations Console</div>
                <div class="admin-meta-item"><span>PM Console</span>Yosech Field Ops · Project Manager</div>
            </div>
            <p class="small text-muted mb-0">Branding values are configured in the application layout files. Contact your developer to update logos and company identity.</p>
        </div>
    </div>

    <!-- ── Reset Employee Password ──────────────────────────────────────── -->
    <div class="col-lg-6">
        <div class="admin-card">
            <h3 class="admin-card-title">Reset Employee / Manager Password</h3>
            <form method="post" novalidate>
                <input type="hidden" name="action" value="reset_employee_password">
                <div class="mb-3">
                    <label class="form-label small">Select Employee</label>
                    <select name="target_id" class="form-select" required>
                        <option value="">— choose employee —</option>
                        <?php
                        $empList = mysqli_query($conn, "SELECT EmployeeID, Username, UserType FROM Employee ORDER BY UserType, Username");
                        while ($emp = mysqli_fetch_assoc($empList)):
                            // Admin should not reset their own password here — use the form above
                            if ((int)$emp['EmployeeID'] === (int)($adminEmployee['EmployeeID'] ?? 0)) continue;
                        ?>
                        <option value="<?= (int)$emp['EmployeeID'] ?>">
                            <?= htmlspecialchars($emp['Username']) ?> (<?= htmlspecialchars($emp['UserType']) ?>)
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small">New Password</label>
                    <input type="password" name="new_password" class="form-control" required>
                    <div class="form-text">Min 8 chars, uppercase, lowercase, number, special character.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label small">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                <button type="submit" class="admin-btn admin-btn-primary">Reset Password</button>
            </form>
        </div>
    </div>

    <!-- ── Reset Client Password ────────────────────────────────────────── -->
    <div class="col-lg-6">
        <div class="admin-card">
            <h3 class="admin-card-title">Reset Client Password</h3>
            <form method="post" novalidate>
                <input type="hidden" name="action" value="reset_client_password">
                <div class="mb-3">
                    <label class="form-label small">Select Client</label>
                    <select name="target_id" class="form-select" required>
                        <option value="">— choose client —</option>
                        <?php while ($cli = mysqli_fetch_assoc($clientResult)): ?>
                        <option value="<?= (int)$cli['UserID'] ?>">
                            <?= htmlspecialchars(trim($cli['Client_FirstName'] . ' ' . ($cli['Client_MI'] ? $cli['Client_MI'].'. ' : '') . $cli['Client_LastName'])) ?>
                            (<?= htmlspecialchars($cli['Client_Username']) ?>)
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small">New Password</label>
                    <input type="password" name="new_password" class="form-control" required>
                    <div class="form-text">Min 8 chars, uppercase, lowercase, number, special character.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label small">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                <button type="submit" class="admin-btn admin-btn-primary">Reset Password</button>
            </form>
        </div>
    </div>

    <!-- ── User Management Table ────────────────────────────────────────── -->
    <div class="col-12">
        <div class="admin-panel">
            <div class="admin-panel-head">
                <h2 class="admin-panel-title">User Management</h2>
                <span class="admin-btn admin-btn-outline admin-btn-sm" style="cursor:default;"><?= $staffCount ?> staff · <?= $clientCount ?> clients</span>
            </div>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Email</th>
                        <th>Contact</th>
                        <th>Console Access</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($staff = mysqli_fetch_assoc($staffResult)):
                        $console = match ($staff['UserType']) {
                            'Admin'   => 'Admin Panel',
                            'Manager' => 'Project Manager',
                            default   => 'Staff (no console)',
                        };
                    ?>
                    <tr>
                        <td>#<?= (int) $staff['EmployeeID'] ?></td>
                        <td><span class="admin-table-project"><?= htmlspecialchars($staff['Username']) ?></span></td>
                        <td><?= htmlspecialchars($staff['UserType']) ?></td>
                        <td><?= htmlspecialchars($staff['Email']) ?></td>
                        <td><?= htmlspecialchars($staff['ContactNumber'] ?: '—') ?></td>
                        <td><?= htmlspecialchars($console) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ── System Integrations ──────────────────────────────────────────── -->
    <div class="col-12">
        <div class="admin-card">
            <h3 class="admin-card-title">System Integrations</h3>
            <p class="small text-muted mb-3">Third-party service connections are not yet configured for this deployment.</p>
            <div class="admin-meta-grid">
                <div class="admin-meta-item"><span>Email Notifications</span>Not connected</div>
                <div class="admin-meta-item"><span>Cloud Storage</span>Not connected</div>
                <div class="admin-meta-item"><span>Accounting Export</span>Not connected</div>
                <div class="admin-meta-item"><span>Public Website</span><a href="<?= BASE_URL ?>/index.php" target="_blank" rel="noopener">Open site →</a></div>
            </div>
        </div>
    </div>

</div>

<?php include("../includes/admin/layout_end.php"); ?>
