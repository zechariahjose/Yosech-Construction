<?php
include("../config/database.php");
include("../includes/admin/helpers.php");
include("../includes/password_helpers.php");

adminRequireLogin('admin/amin_settings.php');

$adminEmployee     = adminCurrentEmployee($conn);
$adminPendingCount = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM Application WHERE Status = 'Pending'"))['total'];
$adminId           = (int) ($adminEmployee['EmployeeID'] ?? 0);

$success = '';
$error   = '';

// ── UPDATE OWN PROFILE ──────────────────────────────────────
if (isset($_POST['update_profile'])) {
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $contact  = trim($_POST['contact']);
    if ($username === '' || $email === '') {
        $error = 'Username and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $chk = mysqli_prepare($conn, "SELECT EmployeeID FROM Employee WHERE (Username=? OR Email=?) AND EmployeeID!=?");
        mysqli_stmt_bind_param($chk, "ssi", $username, $email, $adminId);
        mysqli_stmt_execute($chk);
        mysqli_stmt_store_result($chk);
        if (mysqli_stmt_num_rows($chk) > 0) {
            $error = 'Username or email is already taken by another account.';
        } else {
            $up = mysqli_prepare($conn, "UPDATE Employee SET Username=?, Email=?, ContactNumber=? WHERE EmployeeID=?");
            mysqli_stmt_bind_param($up, "sssi", $username, $email, $contact, $adminId);
            mysqli_stmt_execute($up) ? $success = 'Profile updated.' : $error = 'Failed to update profile.';
            $adminEmployee = adminCurrentEmployee($conn);
        }
    }
}

// ── CHANGE OWN PASSWORD ─────────────────────────────────────
if (isset($_POST['change_password'])) {
    $cur  = $_POST['current_password'];
    $new  = $_POST['new_password'];
    $conf = $_POST['confirm_password'];
    $row  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT Password FROM Employee WHERE EmployeeID={$adminId}"));
    if (!$row || (!password_verify($cur, $row['Password']) && $row['Password'] !== $cur)) {
        $error = 'Current password is incorrect.';
    } elseif ($new !== $conf) {
        $error = 'New passwords do not match.';
    } elseif ($pwErr = passwordStrengthError($new)) {
        $error = $pwErr;
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $pw = mysqli_prepare($conn, "UPDATE Employee SET Password=? WHERE EmployeeID=?");
        mysqli_stmt_bind_param($pw, "si", $hash, $adminId);
        mysqli_stmt_execute($pw);
        $success = 'Password changed successfully.';
    }
}

// ── ADD EMPLOYEE ────────────────────────────────────────────
if (isset($_POST['add_employee'])) {
    $newUser  = trim($_POST['new_username']);
    $newEmail = trim($_POST['new_email']);
    $newType  = in_array($_POST['new_usertype'], ['Admin','Manager']) ? $_POST['new_usertype'] : 'Manager';
    $newPw    = $_POST['new_password'];
    $newConf  = $_POST['new_confirm_password'];
    if ($newUser === '' || $newEmail === '' || $newPw === '') {
        $error = 'All fields are required to add an employee.';
    } elseif ($newPw !== $newConf) {
        $error = 'Passwords do not match.';
    } elseif ($pwErr = passwordStrengthError($newPw)) {
        $error = $pwErr;
    } else {
        $hash = password_hash($newPw, PASSWORD_DEFAULT);
        $ins  = mysqli_prepare($conn, "INSERT INTO Employee (UserType,Username,Password,Email) VALUES (?,?,?,?)");
        mysqli_stmt_bind_param($ins, "ssss", $newType, $newUser, $hash, $newEmail);
        mysqli_stmt_execute($ins) ? $success = "Employee '{$newUser}' added." : $error = 'Failed — username or email already exists.';
    }
}

// ── REMOVE EMPLOYEE ─────────────────────────────────────────
if (isset($_POST['remove_employee'], $_POST['target_employee_id'])) {
    $tid = (int) $_POST['target_employee_id'];
    if ($tid === $adminId) {
        $error = 'You cannot remove your own account.';
    } else {
        $del = mysqli_prepare($conn, "DELETE FROM Employee WHERE EmployeeID=?");
        mysqli_stmt_bind_param($del, "i", $tid);
        mysqli_stmt_execute($del) ? $success = 'Employee removed.' : $error = 'Failed to remove employee.';
    }
}

// ── RESET EMPLOYEE PASSWORD (default: 123) ─────────────────
if (isset($_POST['reset_employee_pw'], $_POST['target_employee_id'])) {
    $tid  = (int) $_POST['target_employee_id'];
    $hash = password_hash('123', PASSWORD_DEFAULT);
    $up   = mysqli_prepare($conn, "UPDATE Employee SET Password=? WHERE EmployeeID=?");
    mysqli_stmt_bind_param($up, "si", $hash, $tid);
    mysqli_stmt_execute($up) ? $success = 'Password reset to default (123). The user should change it after logging in.' : $error = 'Failed to reset password.';
}

// ── TOGGLE CLIENT SUSPEND ───────────────────────────────────
if (isset($_POST['toggle_suspend'], $_POST['target_client_id'])) {
    $tid      = (int) $_POST['target_client_id'];
    $newState = isset($_POST['do_suspend']) ? 1 : 0;
    $label    = $newState ? 'suspended' : 're-enabled';
    $st = mysqli_prepare($conn, "UPDATE Client SET is_suspended = ? WHERE UserID = ?");
    mysqli_stmt_bind_param($st, "ii", $newState, $tid);
    mysqli_stmt_execute($st)
        ? $success = "Client account has been {$label}."
        : $error   = "Failed to update client status.";
}

// ── DATA ────────────────────────────────────────────────────
$staffRows   = mysqli_query($conn, "SELECT EmployeeID,UserType,Username,Email,ContactNumber FROM Employee ORDER BY UserType,Username");
$clientRows  = mysqli_query($conn, "SELECT UserID,Client_FirstName,Client_MI,Client_LastName,Client_Username,Client_Email,is_suspended FROM Client ORDER BY Client_LastName,Client_FirstName");
$staffCount  = (int) mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS t FROM Employee"))['t'];
$clientCount = (int) mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS t FROM Client"))['t'];

$activeTab = $_GET['tab'] ?? 'account';
if (!in_array($activeTab, ['account','users','clients'])) $activeTab = 'account';

$adminActiveNav    = 'settings';
$adminPageTitle    = 'Settings';
$adminPageSubtitle = '';
$adminPageActions  = '';

include("../includes/admin/layout_start.php");
?>

<?php if ($success): ?>
    <div class="admin-alert" style="background:#f0fdf4;color:#166534;border:1px solid #bbf7d0;margin-bottom:20px;display:flex;align-items:center;gap:8px;">
        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
        <?= htmlspecialchars($success) ?>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="admin-alert" style="background:#fef2f2;color:#991b1b;border:1px solid #fecaca;margin-bottom:20px;display:flex;align-items:center;gap:8px;">
        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg>
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<div class="adm-st-layout">

    <!-- ── SIDEBAR ── -->
    <aside class="adm-st-sidebar">
        <div class="adm-st-identity">
            <div class="adm-st-avatar"><?= strtoupper(substr($adminEmployee['Username'] ?? 'A', 0, 1)) ?></div>
            <div class="adm-st-name"><?= htmlspecialchars($adminEmployee['Username'] ?? 'Admin') ?></div>
            <div class="adm-st-role">ADMINISTRATOR</div>
        </div>
        <nav class="adm-st-nav">
            <a href="?tab=account" class="adm-st-navitem <?= $activeTab==='account' ? 'active' : '' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                Account
            </a>
            <a href="?tab=users" class="adm-st-navitem <?= $activeTab==='users' ? 'active' : '' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                User Management
            </a>
            <a href="?tab=clients" class="adm-st-navitem <?= $activeTab==='clients' ? 'active' : '' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                Client Access
            </a>
        </nav>
        <div class="adm-st-divider"></div>
        <a href="../logout.php" class="adm-st-navitem adm-st-signout">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Sign Out
        </a>
    </aside>

    <!-- ── MAIN ── -->
    <main class="adm-st-main">

    <?php if ($activeTab === 'account'): ?>

    <!-- Profile header card -->
    <div class="admin-card" style="margin-bottom:20px;">
        <div style="display:flex;align-items:center;gap:18px;padding:4px 0;">
            <div class="adm-st-avatar adm-st-avatar-lg"><?= strtoupper(substr($adminEmployee['Username'] ?? 'A', 0, 1)) ?></div>
            <div>
                <div style="font-size:1.05rem;font-weight:700;color:var(--admin-text);margin-bottom:3px;"><?= htmlspecialchars($adminEmployee['Username'] ?? '') ?></div>
                <div style="font-size:0.78rem;color:var(--admin-muted);">Administrator · <?= htmlspecialchars($adminEmployee['Email'] ?? '') ?></div>
            </div>
        </div>
    </div>

    <!-- Profile edit -->
    <div class="admin-card" style="margin-bottom:20px;">
        <div class="adm-st-card-head"><div class="adm-st-card-title">Profile Information</div><div class="adm-st-card-sub">Update your username, email and contact number.</div></div>
        <form method="POST" id="profileForm">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div class="admin-field">
                    <label>Username</label>
                    <input type="text" name="username" value="<?= htmlspecialchars($adminEmployee['Username'] ?? '') ?>" data-original="<?= htmlspecialchars($adminEmployee['Username'] ?? '') ?>" required>
                </div>
                <div class="admin-field">
                    <label>Email Address</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($adminEmployee['Email'] ?? '') ?>" data-original="<?= htmlspecialchars($adminEmployee['Email'] ?? '') ?>" required>
                </div>
            </div>
            <div class="admin-field">
                <label>Contact Number</label>
                <input type="text" name="contact" value="<?= htmlspecialchars($adminEmployee['ContactNumber'] ?? '') ?>" data-original="<?= htmlspecialchars($adminEmployee['ContactNumber'] ?? '') ?>">
            </div>
            <div class="adm-st-form-footer">
                <button type="submit" name="update_profile" id="saveProfileBtn" class="admin-btn admin-btn-primary" disabled>Save Changes</button>
            </div>
        </form>
    </div>

    <!-- Change password -->
    <div class="admin-card">
        <div class="adm-st-card-head"><div class="adm-st-card-title">Security</div><div class="adm-st-card-sub">Change your administrator password.</div></div>
        <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;padding:14px 0;border-bottom:1px solid var(--admin-border);">
            <div style="display:flex;align-items:center;gap:12px;">
                <div class="adm-st-sec-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></div>
                <div><div style="font-size:.88rem;font-weight:600;color:var(--admin-text);margin-bottom:2px;">Password</div><div style="font-size:.76rem;color:var(--admin-muted);">Keep your account secure with a strong password.</div></div>
            </div>
            <div style="display:flex;align-items:center;gap:10px;flex-shrink:0;">
                <span class="adm-st-badge-active"><svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path d="M20 6L9 17l-5-5"/></svg>Active</span>
                <button type="button" class="admin-btn admin-btn-outline admin-btn-sm" onclick="toggleEl('pwWrap')">Change Password</button>
            </div>
        </div>
        <div id="pwWrap" style="display:none;">
            <div style="background:var(--ysc-bg);border:1px solid var(--admin-border);border-radius:6px;padding:20px;margin-top:16px;">
                <form method="POST" id="pwForm">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                        <div class="admin-field" style="grid-column:1/-1;">
                            <label>Current Password</label>
                            <div class="adm-pw-wrap"><input type="password" name="current_password" id="cur_pw" required style="padding-right:38px;"><button type="button" class="adm-pw-eye" onclick="togglePw('cur_pw',this)" tabindex="-1"><svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg></button></div>
                        </div>
                        <div class="admin-field">
                            <label>New Password</label>
                            <div class="adm-pw-wrap"><input type="password" name="new_password" id="new_pw" required minlength="8" style="padding-right:38px;"><button type="button" class="adm-pw-eye" onclick="togglePw('new_pw',this)" tabindex="-1"><svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg></button></div>
                        </div>
                        <div class="admin-field">
                            <label>Confirm New Password</label>
                            <div class="adm-pw-wrap"><input type="password" name="confirm_password" id="conf_pw" required minlength="8" style="padding-right:38px;"><button type="button" class="adm-pw-eye" onclick="togglePw('conf_pw',this)" tabindex="-1"><svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg></button></div>
                        </div>
                    </div>
                    <ul class="adm-pw-rules" id="pwRules">
                        <li data-rule="length">At least 8 characters</li><li data-rule="upper">One uppercase letter</li>
                        <li data-rule="lower">One lowercase letter</li><li data-rule="number">One number</li>
                        <li data-rule="special">One special character</li>
                    </ul>
                    <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:4px;">
                        <button type="button" class="admin-btn admin-btn-outline admin-btn-sm" onclick="toggleEl('pwWrap')">Cancel</button>
                        <button type="submit" name="change_password" id="updatePwBtn" class="admin-btn admin-btn-primary admin-btn-sm" disabled>Update Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php elseif ($activeTab === 'users'): ?>

    <!-- Add employee -->
    <div class="admin-card" style="margin-bottom:20px;">
        <div class="adm-st-card-head"><div class="adm-st-card-title">Add Employee Account</div><div class="adm-st-card-sub">Create a new Admin or Project Manager login.</div></div>
        <form method="POST" id="addEmpForm">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div class="admin-field">
                    <label>Username</label>
                    <input type="text" name="new_username" required data-track>
                </div>
                <div class="admin-field">
                    <label>Role</label>
                    <select name="new_usertype" data-track>
                        <option value="Manager">Project Manager</option>
                        <option value="Admin">Admin</option>
                    </select>
                </div>
                <div class="admin-field" style="grid-column:1/-1;">
                    <label>Email Address</label>
                    <input type="email" name="new_email" required data-track>
                </div>
                <div class="admin-field">
                    <label>Password</label>
                    <div class="adm-pw-wrap"><input type="password" name="new_password" id="add_pw" required style="padding-right:38px;" data-track><button type="button" class="adm-pw-eye" onclick="togglePw('add_pw',this)" tabindex="-1"><svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg></button></div>
                </div>
                <div class="admin-field">
                    <label>Confirm Password</label>
                    <div class="adm-pw-wrap"><input type="password" name="new_confirm_password" id="add_conf" required style="padding-right:38px;" data-track><button type="button" class="adm-pw-eye" onclick="togglePw('add_conf',this)" tabindex="-1"><svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg></button></div>
                </div>
            </div>
            <div class="adm-st-form-footer">
                <button type="submit" name="add_employee" id="addEmpBtn" class="admin-btn admin-btn-primary" disabled>Add Employee</button>
            </div>
        </form>
    </div>

    <!-- Staff table -->
    <div class="admin-panel">
        <div class="admin-panel-head">
            <h2 class="admin-panel-title">Current Staff</h2>
            <span class="admin-badge admin-badge-track"><?= $staffCount ?> accounts</span>
        </div>
        <table class="admin-table">
            <thead><tr><th>Username</th><th>Role</th><th>Email</th><th>Contact</th><th>Actions</th></tr></thead>
            <tbody>
            <?php
            $staffRows = mysqli_query($conn, "SELECT EmployeeID,UserType,Username,Email,ContactNumber FROM Employee ORDER BY UserType,Username");
            while ($emp = mysqli_fetch_assoc($staffRows)):
                $isMe = (int)$emp['EmployeeID'] === $adminId;
                $roleClass = $emp['UserType'] === 'Admin' ? 'admin-badge-pending' : 'admin-badge-track';
            ?>
            <tr>
                <td><span class="admin-table-project"><?= htmlspecialchars($emp['Username']) ?></span><?php if ($isMe): ?><span class="admin-table-sub">You</span><?php endif; ?></td>
                <td><span class="admin-badge <?= $roleClass ?>"><?= htmlspecialchars($emp['UserType']) ?></span></td>
                <td><?= htmlspecialchars($emp['Email']) ?></td>
                <td><?= htmlspecialchars($emp['ContactNumber'] ?: '—') ?></td>
                <td>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Reset password for <?= htmlspecialchars(addslashes($emp['Username'])) ?> to the default (123)? They should change it immediately after logging in.');">
                            <input type="hidden" name="target_employee_id" value="<?= (int)$emp['EmployeeID'] ?>">
                            <button type="submit" name="reset_employee_pw" class="admin-btn admin-btn-outline admin-btn-sm">Reset to Default</button>
                        </form>
                        <?php if (!$isMe): ?>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Remove <?= htmlspecialchars(addslashes($emp['Username'])) ?>? This cannot be undone.');">
                            <input type="hidden" name="target_employee_id" value="<?= (int)$emp['EmployeeID'] ?>">
                            <button type="submit" name="remove_employee" class="admin-btn admin-btn-danger admin-btn-sm">Remove</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <?php else: ?>

    <!-- Clients table with suspend/unsuspend -->
    <div class="admin-panel">
        <div class="admin-panel-head">
            <h2 class="admin-panel-title">Client Accounts</h2>
            <span class="admin-badge admin-badge-track"><?= $clientCount ?> clients</span>
        </div>
        <table class="admin-table">
            <thead><tr><th>Name</th><th>Username</th><th>Email</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php
            $clientRows = mysqli_query($conn, "SELECT UserID,Client_FirstName,Client_MI,Client_LastName,Client_Username,Client_Email,is_suspended FROM Client ORDER BY Client_LastName,Client_FirstName");
            while ($cli = mysqli_fetch_assoc($clientRows)):
                $fullName = trim($cli['Client_FirstName'].' '.($cli['Client_MI'] ? $cli['Client_MI'].'. ' : '').$cli['Client_LastName']);
                $isSuspended = !empty($cli['is_suspended']);
            ?>
            <tr>
                <td><span class="admin-table-project"><?= htmlspecialchars($fullName) ?></span><span class="admin-table-sub">ID #<?= (int)$cli['UserID'] ?></span></td>
                <td><?= htmlspecialchars($cli['Client_Username']) ?></td>
                <td><?= htmlspecialchars($cli['Client_Email']) ?></td>
                <td>
                    <?php if ($isSuspended): ?>
                        <span style="display:inline-flex;align-items:center;gap:4px;font-size:0.7rem;font-weight:700;padding:3px 9px;border-radius:20px;background:#fee2e2;color:#991b1b;letter-spacing:.04em;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><circle cx="12" cy="12" r="10"/><path d="M4.93 4.93l14.14 14.14"/></svg>
                            Suspended
                        </span>
                    <?php else: ?>
                        <span style="display:inline-flex;align-items:center;gap:4px;font-size:0.7rem;font-weight:700;padding:3px 9px;border-radius:20px;background:#d1fae5;color:#065f46;letter-spacing:.04em;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path d="M20 6L9 17l-5-5"/></svg>
                            Active
                        </span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($isSuspended): ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="target_client_id" value="<?= (int)$cli['UserID'] ?>">
                        <input type="hidden" name="toggle_suspend" value="1">
                        <button type="submit" name="do_unsuspend" class="admin-btn admin-btn-outline admin-btn-sm"
                                style="color:#059669;border-color:#bbf7d0;"
                                onclick="return confirm('Re-enable login for <?= htmlspecialchars(addslashes($fullName)) ?>?')">Unsuspend</button>
                    </form>
                    <?php else: ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="target_client_id" value="<?= (int)$cli['UserID'] ?>">
                        <input type="hidden" name="toggle_suspend" value="1">
                        <button type="submit" name="do_suspend" class="admin-btn admin-btn-outline admin-btn-sm"
                                style="color:#d97706;border-color:#fde68a;"
                                onclick="return confirm('Suspend <?= htmlspecialchars(addslashes($fullName)) ?>? They will be blocked from logging in.')">Suspend</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <?php endif; ?>

    </main>
</div><!-- /.adm-st-layout -->



<style>
/* ── Settings two-column layout ───────────────────────────── */
.adm-st-layout { display:grid; grid-template-columns:210px 1fr; gap:24px; align-items:start; }
.adm-st-sidebar { background:var(--admin-surface); border:1px solid var(--admin-border); border-radius:10px; overflow:hidden; position:sticky; top:80px; }
.adm-st-identity { padding:22px 16px 16px; border-bottom:1px solid var(--admin-border); display:flex; flex-direction:column; align-items:center; text-align:center; gap:8px; }
.adm-st-avatar { width:48px; height:48px; border-radius:50%; background:#f97316; color:#fff; font-size:1.1rem; font-weight:800; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.adm-st-avatar-lg { width:60px; height:60px; font-size:1.4rem; }
.adm-st-name { font-size:.88rem; font-weight:700; color:var(--admin-text); line-height:1.3; }
.adm-st-role { font-size:.7rem; font-weight:600; letter-spacing:.07em; color:var(--admin-muted); }
.adm-st-nav { padding:10px 10px 6px; display:flex; flex-direction:column; gap:2px; }
.adm-st-navitem { display:flex; align-items:center; gap:9px; padding:9px 12px; font-size:.82rem; font-weight:600; color:var(--admin-muted); text-decoration:none; border-radius:6px; transition:background .15s,color .15s; border:none; background:none; width:100%; text-align:left; cursor:pointer; }
.adm-st-navitem:hover { background:var(--ysc-bg); color:var(--admin-text); }
.adm-st-navitem.active { background:rgba(249,115,22,.1); color:#ea6c0a; }
.adm-st-divider { height:1px; background:var(--admin-border); margin:6px 10px; }
.adm-st-signout { margin:0 10px 10px; color:#b45309; width:calc(100% - 20px); }
.adm-st-signout:hover { background:#fef3c7 !important; color:#92400e !important; }
.adm-st-main { display:flex; flex-direction:column; gap:20px; min-width:0; }
.adm-st-card-head { padding-bottom:16px; margin-bottom:20px; border-bottom:1px solid var(--admin-border); }
.adm-st-card-title { font-size:.92rem; font-weight:700; color:var(--admin-text); margin-bottom:3px; }
.adm-st-card-sub { font-size:.78rem; color:var(--admin-muted); }
.adm-st-form-footer { display:flex; justify-content:flex-end; padding-top:16px; border-top:1px solid var(--ysc-border-light); margin-top:4px; }
.adm-st-sec-icon { width:36px; height:36px; border-radius:6px; background:var(--ysc-bg); border:1px solid var(--admin-border); display:flex; align-items:center; justify-content:center; color:var(--admin-muted); flex-shrink:0; }
.adm-st-badge-active { display:inline-flex; align-items:center; gap:4px; font-size:.7rem; font-weight:600; color:#059669; background:#d1fae5; padding:3px 9px; border-radius:20px; }
/* Password fields */
.adm-pw-wrap { position:relative; }
.adm-pw-wrap input { padding-right:38px; }
.adm-pw-eye { position:absolute; right:10px; top:50%; transform:translateY(-50%); background:none; border:none; padding:0; cursor:pointer; color:var(--admin-muted); display:flex; align-items:center; transition:color .15s; }
.adm-pw-eye:hover { color:var(--admin-text); }
.adm-pw-rules { list-style:none; margin:12px 0 16px; padding:10px 14px; background:var(--admin-surface); border:1px solid var(--admin-border); border-radius:6px; font-size:.76rem; color:var(--admin-muted); display:grid; grid-template-columns:1fr 1fr; gap:4px 16px; }
.adm-pw-rules li { position:relative; padding-left:16px; }
.adm-pw-rules li::before { content:'○'; position:absolute; left:0; color:#cbd5e1; }
.adm-pw-rules li.met { color:#059669; }
.adm-pw-rules li.met::before { content:'●'; color:#059669; }
/* Disabled */
.admin-btn:disabled { opacity:.45; cursor:not-allowed; pointer-events:none; }
@media (max-width:768px) { .adm-st-layout { grid-template-columns:1fr; } .adm-st-sidebar { position:static; } }
</style>

<script>
function toggleEl(id) { var el=document.getElementById(id); el.style.display=el.style.display==='none'?'block':'none'; }
function togglePw(id,btn) { var i=document.getElementById(id); i.type=i.type==='password'?'text':'password'; btn.style.color=i.type==='text'?'#f97316':''; }

// ── Profile change detection ─────────────────────────────────
(function(){
    var form=document.getElementById('profileForm'); if(!form) return;
    var btn=document.getElementById('saveProfileBtn');
    var tracked=form.querySelectorAll('[data-original]');
    function check(){ btn.disabled=!Array.from(tracked).some(function(el){return el.value!==el.dataset.original;}); }
    tracked.forEach(function(el){el.addEventListener('input',check);el.addEventListener('change',check);});
    check();
})();

// ── Own password: enable when all 3 fields filled ────────────
(function(){
    var btn=document.getElementById('updatePwBtn'),cur=document.getElementById('cur_pw'),nw=document.getElementById('new_pw'),conf=document.getElementById('conf_pw');
    if(!btn||!cur||!nw||!conf) return;
    function check(){btn.disabled=!(cur.value.trim()&&nw.value.trim()&&conf.value.trim());}
    [cur,nw,conf].forEach(function(el){el.addEventListener('input',check);});
    check();
})();

// ── Add employee: enable when all required inputs filled ──────
(function(){
    var form=document.getElementById('addEmpForm'); if(!form) return;
    var btn=document.getElementById('addEmpBtn');
    var inputs=form.querySelectorAll('[data-track]');
    function check(){ btn.disabled=!Array.from(inputs).every(function(el){return el.value.trim()!=='';});}
    inputs.forEach(function(el){el.addEventListener('input',check);el.addEventListener('change',check);});
    check();
})();



// ── Own password requirements checker ────────────────────────
(function(){
    var pw=document.getElementById('new_pw'), list=document.getElementById('pwRules'); if(!pw||!list) return;
    var rules={length:function(v){return v.length>=8;},upper:function(v){return /[A-Z]/.test(v);},lower:function(v){return /[a-z]/.test(v);},number:function(v){return /[0-9]/.test(v);},special:function(v){return /[^A-Za-z0-9]/.test(v);}};
    function update(){list.querySelectorAll('li').forEach(function(li){li.classList.toggle('met',rules[li.dataset.rule]&&rules[li.dataset.rule](pw.value));});}
    pw.addEventListener('input',update); update();
})();
</script>

<?php include("../includes/admin/layout_end.php"); ?>
