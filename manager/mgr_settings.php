<?php
include("../config/database.php");
include("../includes/manager/helpers.php");
include("../includes/password_helpers.php");

managerRequireLogin('manager/mgr_settings.php');

$mgrEmployee       = adminCurrentEmployee($conn);
$mgrPendingRentals = managerPendingRentals($conn);
$employeeId        = (int) $_SESSION['user_id'];

$success = '';
$error   = '';

// Helper to avoid repeating prepare+execute
function mysqli_prepare_and_execute(mysqli $conn, string $sql, string $types, ...$params): mysqli_stmt {
    $stmt = mysqli_prepare($conn, $sql);
    if ($params) mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    return $stmt;
}

// Fetch fresh employee row
$empRow = mysqli_fetch_assoc(mysqli_stmt_get_result(
    mysqli_prepare_and_execute($conn, "SELECT * FROM Employee WHERE EmployeeID = ?", "i", $employeeId)
));

// ── UPDATE PROFILE ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $contact  = trim($_POST['contact']);

    // Check username not taken by someone else
    $chk = mysqli_prepare($conn, "SELECT EmployeeID FROM Employee WHERE Username = ? AND EmployeeID != ?");
    mysqli_stmt_bind_param($chk, "si", $username, $employeeId);
    mysqli_stmt_execute($chk);
    mysqli_stmt_store_result($chk);

    if (mysqli_stmt_num_rows($chk) > 0) {
        $error = "That username is already taken.";
    } else {
        $up = mysqli_prepare($conn, "UPDATE Employee SET Username=?, Email=?, ContactNumber=? WHERE EmployeeID=?");
        mysqli_stmt_bind_param($up, "sssi", $username, $email, $contact, $employeeId);
        if (mysqli_stmt_execute($up)) {
            $_SESSION['username'] = $username;
            $success = "Profile updated successfully.";
            // Refresh row
            $empRow = mysqli_fetch_assoc(mysqli_stmt_get_result(
                mysqli_prepare_and_execute($conn, "SELECT * FROM Employee WHERE EmployeeID = ?", "i", $employeeId)
            ));
        } else {
            $error = "Failed to update profile.";
        }
    }
}

// ── CHANGE PASSWORD ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'];
    $new     = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];
    $hash    = $empRow['Password'];

    if (!password_verify($current, $hash) && $hash !== $current) {
        $error = "Current password is incorrect.";
    } elseif ($new !== $confirm) {
        $error = "New passwords do not match.";
    } elseif ($pwErr = passwordStrengthError($new)) {
        $error = $pwErr;
    } else {
        $newHash = password_hash($new, PASSWORD_DEFAULT);
        $pw = mysqli_prepare($conn, "UPDATE Employee SET Password=? WHERE EmployeeID=?");
        mysqli_stmt_bind_param($pw, "si", $newHash, $employeeId);
        mysqli_stmt_execute($pw);
        $success = "Password changed successfully.";
    }
}

$activeTab       = isset($_GET['tab']) && $_GET['tab'] === 'security' ? 'security' : 'profile';
$displayName     = $empRow['Username'] ?? 'Project Manager';

$mgrActiveNav    = 'settings';
$mgrPageTitle    = 'Settings';
$mgrPageSubtitle = '';
$mgrPageActions  = '';

include("../includes/manager/layout_start.php");
?>

<?php if ($success): ?>
    <div class="admin-alert admin-alert-success" style="margin-bottom:20px;"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="admin-alert admin-alert-danger" style="margin-bottom:20px;"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- ── Two-column layout ──────────────────────────────────── -->
<div class="mgr-st-layout">

    <!-- SIDEBAR -->
    <aside class="mgr-st-sidebar">
        <!-- Identity -->
        <div class="mgr-st-identity">
            <div class="mgr-st-avatar">
                <?= strtoupper(substr($displayName, 0, 1)) ?>
            </div>
            <div class="mgr-st-name"><?= htmlspecialchars($displayName) ?></div>
            <div class="mgr-st-role"><?= htmlspecialchars(strtoupper($empRow['UserType'] ?? 'MANAGER')) ?></div>
        </div>

        <!-- Nav -->
        <nav class="mgr-st-nav">
            <a href="?tab=profile"   class="mgr-st-navitem <?= $activeTab === 'profile'   ? 'active' : '' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                Profile
            </a>
            <a href="?tab=security"  class="mgr-st-navitem <?= $activeTab === 'security'  ? 'active' : '' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                Security
            </a>
        </nav>

        <div class="mgr-st-divider"></div>

        <a href="../logout.php" class="mgr-st-navitem mgr-st-signout">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Sign Out
        </a>
    </aside>

    <!-- MAIN -->
    <main class="mgr-st-main">

    <?php if ($activeTab === 'profile'): ?>

        <!-- Profile header card -->
        <div class="admin-card" style="margin-bottom:20px;">
            <div style="display:flex;align-items:center;gap:18px;padding:4px 0;">
                <div class="mgr-st-avatar mgr-st-avatar-lg">
                    <?= strtoupper(substr($displayName, 0, 1)) ?>
                </div>
                <div>
                    <div style="font-size:1.05rem;font-weight:700;color:var(--admin-text);margin-bottom:3px;">
                        <?= htmlspecialchars($displayName) ?>
                    </div>
                    <div style="font-size:0.78rem;color:var(--admin-muted);">
                        <?= htmlspecialchars($empRow['UserType'] ?? 'Manager') ?> · <?= htmlspecialchars($empRow['Email'] ?? '') ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profile edit card -->
        <div class="admin-card">
            <div style="padding-bottom:16px;margin-bottom:20px;border-bottom:1px solid var(--admin-border);">
                <div style="font-size:0.92rem;font-weight:700;color:var(--admin-text);margin-bottom:3px;">Profile Information</div>
                <div style="font-size:0.78rem;color:var(--admin-muted);">Update your username, email address and contact number.</div>
            </div>

            <form method="POST" id="profileForm">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="admin-field">
                        <label>Username</label>
                        <input type="text" name="username"
                               value="<?= htmlspecialchars($empRow['Username'] ?? '') ?>"
                               data-original="<?= htmlspecialchars($empRow['Username'] ?? '') ?>"
                               required>
                    </div>
                    <div class="admin-field">
                        <label>Email Address</label>
                        <input type="email" name="email"
                               value="<?= htmlspecialchars($empRow['Email'] ?? '') ?>"
                               data-original="<?= htmlspecialchars($empRow['Email'] ?? '') ?>"
                               required>
                    </div>
                </div>
                <div class="admin-field">
                    <label>Contact Number</label>
                    <input type="text" name="contact"
                           value="<?= htmlspecialchars($empRow['ContactNumber'] ?? '') ?>"
                           data-original="<?= htmlspecialchars($empRow['ContactNumber'] ?? '') ?>">
                </div>

                <div style="display:flex;justify-content:flex-end;padding-top:16px;border-top:1px solid var(--admin-border-light, #f3f4f6);">
                    <button type="submit" name="update_profile" id="saveProfileBtn"
                            class="admin-btn admin-btn-primary" disabled>Save Changes</button>
                </div>
            </form>
        </div>

    <?php else: ?>

        <!-- Security card -->
        <div class="admin-card">
            <div style="padding-bottom:16px;margin-bottom:20px;border-bottom:1px solid var(--admin-border);">
                <div style="font-size:0.92rem;font-weight:700;color:var(--admin-text);margin-bottom:3px;">Security Settings</div>
                <div style="font-size:0.78rem;color:var(--admin-muted);">Manage your account password and keep your account secure.</div>
            </div>

            <!-- Password row -->
            <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;padding:14px 0;border-bottom:1px solid var(--admin-border-light, #f3f4f6);">
                <div style="display:flex;align-items:center;gap:12px;">
                    <div style="width:36px;height:36px;border-radius:6px;background:var(--admin-bg, #f8f9fa);border:1px solid var(--admin-border);display:flex;align-items:center;justify-content:center;color:var(--admin-muted);flex-shrink:0;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    </div>
                    <div>
                        <div style="font-size:0.88rem;font-weight:600;color:var(--admin-text);margin-bottom:2px;">Password</div>
                        <div style="font-size:0.76rem;color:var(--admin-muted);">Keep your account secure with a strong password.</div>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:10px;flex-shrink:0;">
                    <span style="display:inline-flex;align-items:center;gap:4px;font-size:0.7rem;font-weight:600;color:#059669;background:#d1fae5;padding:3px 9px;border-radius:20px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path d="M20 6L9 17l-5-5"/></svg>
                        Active
                    </span>
                    <button type="button" class="admin-btn admin-btn-outline admin-btn-sm"
                            onclick="document.getElementById('pwFormWrap').style.display=document.getElementById('pwFormWrap').style.display==='none'?'block':'none'">
                        Change Password
                    </button>
                </div>
            </div>

            <!-- Inline change password form -->
            <div id="pwFormWrap" style="display:none;">
                <div style="background:var(--admin-bg,#f8f9fa);border:1px solid var(--admin-border);border-radius:6px;padding:20px;margin-top:16px;">
                    <form method="POST" id="pwForm">
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                            <div class="admin-field" style="grid-column:1/-1;">
                                <label>Current Password</label>
                                <div style="position:relative;">
                                    <input type="password" name="current_password" id="cur_pw" required
                                           style="padding-right:38px;">
                                    <button type="button" class="mgr-pw-eye"
                                            onclick="togglePw('cur_pw',this)" tabindex="-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </button>
                                </div>
                            </div>
                            <div class="admin-field">
                                <label>New Password</label>
                                <div style="position:relative;">
                                    <input type="password" name="new_password" id="new_pw" required minlength="8"
                                           style="padding-right:38px;">
                                    <button type="button" class="mgr-pw-eye"
                                            onclick="togglePw('new_pw',this)" tabindex="-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </button>
                                </div>
                            </div>
                            <div class="admin-field">
                                <label>Confirm New Password</label>
                                <div style="position:relative;">
                                    <input type="password" name="confirm_password" id="conf_pw" required minlength="8"
                                           style="padding-right:38px;">
                                    <button type="button" class="mgr-pw-eye"
                                            onclick="togglePw('conf_pw',this)" tabindex="-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Password requirements -->
                        <ul class="mgr-pw-rules" id="pwRules">
                            <li data-rule="length">At least 8 characters</li>
                            <li data-rule="upper">One uppercase letter</li>
                            <li data-rule="lower">One lowercase letter</li>
                            <li data-rule="number">One number</li>
                            <li data-rule="special">One special character (!@#$%…)</li>
                        </ul>

                        <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:4px;">
                            <button type="button" class="admin-btn admin-btn-outline admin-btn-sm"
                                    onclick="document.getElementById('pwFormWrap').style.display='none'">Cancel</button>
                            <button type="submit" name="change_password" class="admin-btn admin-btn-primary admin-btn-sm" id="updatePwBtn" disabled>
                                Update Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>

    <?php endif; ?>

    </main>
</div><!-- /.mgr-st-layout -->

<style>
/* ── Settings layout ───────────────────────────────────────── */
.mgr-st-layout {
    display: grid;
    grid-template-columns: 200px 1fr;
    gap: 24px;
    align-items: start;
}

.mgr-st-sidebar {
    background: var(--admin-surface, #fff);
    border: 1px solid var(--admin-border);
    border-radius: 10px;
    overflow: hidden;
    position: sticky;
    top: 80px;
}

.mgr-st-identity {
    padding: 22px 16px 16px;
    border-bottom: 1px solid var(--admin-border);
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 8px;
}

.mgr-st-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: var(--ysc-primary);
    color: #fff;
    font-size: 1.1rem;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.mgr-st-avatar-lg {
    width: 60px;
    height: 60px;
    font-size: 1.4rem;
}

.mgr-st-name {
    font-size: 0.88rem;
    font-weight: 700;
    color: var(--admin-text);
    line-height: 1.3;
}

.mgr-st-role {
    font-size: 0.7rem;
    font-weight: 600;
    letter-spacing: 0.07em;
    color: var(--admin-muted);
}

.mgr-st-nav {
    padding: 10px 10px 6px;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.mgr-st-navitem {
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 9px 12px;
    font-size: 0.82rem;
    font-weight: 600;
    color: var(--admin-muted);
    text-decoration: none;
    border-radius: 6px;
    transition: background 0.15s, color 0.15s;
    border: none;
    background: none;
    width: 100%;
    text-align: left;
    cursor: pointer;
}

.mgr-st-navitem:hover {
    background: var(--admin-bg, #f8f9fa);
    color: var(--admin-text);
}

.mgr-st-navitem.active {
    background: var(--ysc-primary-light, #eef2f6);
    color: var(--ysc-primary);
}

.mgr-st-divider {
    height: 1px;
    background: var(--admin-border);
    margin: 6px 10px;
}

.mgr-st-signout {
    margin: 0 10px 10px;
    color: #b45309;
    width: calc(100% - 20px);
}

.mgr-st-signout:hover {
    background: #fef3c7 !important;
    color: #92400e !important;
}

.mgr-st-main {
    display: flex;
    flex-direction: column;
    gap: 20px;
    min-width: 0;
}

/* ── Password eye toggle ───────────────────────────────────── */
.mgr-pw-eye {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    padding: 0;
    cursor: pointer;
    color: var(--admin-muted);
    display: flex;
    align-items: center;
    transition: color 0.15s;
}
.mgr-pw-eye:hover { color: var(--admin-text); }

/* ── Password requirements ─────────────────────────────────── */
.mgr-pw-rules {
    list-style: none;
    margin: 12px 0 16px;
    padding: 10px 14px;
    background: var(--admin-surface, #fff);
    border: 1px solid var(--admin-border);
    border-radius: 6px;
    font-size: 0.76rem;
    color: var(--admin-muted);
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 4px 16px;
}

.mgr-pw-rules li {
    position: relative;
    padding-left: 16px;
}

.mgr-pw-rules li::before { content: '○'; position: absolute; left: 0; color: #cbd5e1; }
.mgr-pw-rules li.met { color: #059669; }
.mgr-pw-rules li.met::before { content: '●'; color: #059669; }

/* ── Disabled buttons ──────────────────────────────────────── */
.admin-btn:disabled {
    opacity: 0.45;
    cursor: not-allowed;
    pointer-events: none;
}

/* ── Responsive ────────────────────────────────────────────── */
@media (max-width: 768px) {
    .mgr-st-layout { grid-template-columns: 1fr; }
    .mgr-st-sidebar { position: static; }
}
</style>

<script>
// ── Toggle password visibility ───────────────────────────────
function togglePw(inputId, btn) {
    var inp = document.getElementById(inputId);
    if (!inp) return;
    inp.type = inp.type === 'password' ? 'text' : 'password';
    btn.style.color = inp.type === 'text' ? 'var(--ysc-primary)' : '';
}

// ── Profile change detection ─────────────────────────────────
(function () {
    var form = document.getElementById('profileForm');
    if (!form) return;
    var btn     = document.getElementById('saveProfileBtn');
    var tracked = form.querySelectorAll('[data-original]');

    function check() {
        btn.disabled = !Array.from(tracked).some(function (el) {
            return el.value !== el.dataset.original;
        });
    }
    tracked.forEach(function (el) {
        el.addEventListener('input',  check);
        el.addEventListener('change', check);
    });
    check();
})();

// ── Password form: enable Update button only when all three fields have text ─
(function () {
    var btn  = document.getElementById('updatePwBtn');
    var cur  = document.getElementById('cur_pw');
    var nw   = document.getElementById('new_pw');
    var conf = document.getElementById('conf_pw');
    if (!btn || !cur || !nw || !conf) return;

    function check() {
        btn.disabled = !(cur.value.trim() && nw.value.trim() && conf.value.trim());
    }
    [cur, nw, conf].forEach(function (el) { el.addEventListener('input', check); });
    check();
})();

// ── Password requirements live checker ───────────────────────
(function () {
    var pw    = document.getElementById('new_pw');
    var rules = document.getElementById('pwRules');
    if (!pw || !rules) return;

    var checks = {
        length:  function(v) { return v.length >= 8; },
        upper:   function(v) { return /[A-Z]/.test(v); },
        lower:   function(v) { return /[a-z]/.test(v); },
        number:  function(v) { return /[0-9]/.test(v); },
        special: function(v) { return /[^A-Za-z0-9]/.test(v); }
    };

    function update() {
        rules.querySelectorAll('li').forEach(function (li) {
            li.classList.toggle('met', checks[li.dataset.rule] && checks[li.dataset.rule](pw.value));
        });
    }
    pw.addEventListener('input', update);
    update();
})();
</script>

<?php include("../includes/manager/layout_end.php"); ?>
