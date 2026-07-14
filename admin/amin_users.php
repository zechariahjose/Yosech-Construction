<?php
include("../config/database.php");
include("../includes/admin/helpers.php");

adminRequireLogin('admin/amin_users.php');

$adminEmployee     = adminCurrentEmployee($conn);
$adminPendingCount = (int) mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS total FROM Application WHERE Status = 'Pending'"))['total'];

$success = '';
$error   = '';

// ── SET ROLE ─────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_role'])) {
    $targetId   = (int) ($_POST['target_id']   ?? 0);
    $sourceType = $_POST['source_type']  ?? '';   // 'client' | 'employee'
    $newRole    = $_POST['new_role']     ?? '';   // 'Client' | 'Manager' | 'Admin'

    $validRoles = ['Client', 'Manager', 'Admin'];
    if (!in_array($newRole, $validRoles, true) || $targetId <= 0) {
        $error = 'Invalid role or user.';
    } else {

        // ── Employee → Employee (just change UserType) ────────────────────────
        if ($sourceType === 'employee' && in_array($newRole, ['Manager', 'Admin'], true)) {
            // Prevent demoting yourself
            if ($targetId === (int) ($_SESSION['user_id'] ?? 0)) {
                $error = 'You cannot change your own role.';
            } else {
                $stmt = mysqli_prepare($conn,
                    "UPDATE Employee SET UserType = ? WHERE EmployeeID = ?");
                mysqli_stmt_bind_param($stmt, 'si', $newRole, $targetId);
                mysqli_stmt_execute($stmt)
                    ? $success = "Role updated to {$newRole} successfully."
                    : $error   = 'Failed to update role.';
            }

        // ── Employee → Client (demote: copy to Client, delete from Employee) ──
        } elseif ($sourceType === 'employee' && $newRole === 'Client') {
            if ($targetId === (int) ($_SESSION['user_id'] ?? 0)) {
                $error = 'You cannot change your own role.';
            } else {
                $emp = mysqli_fetch_assoc(mysqli_query($conn,
                    "SELECT * FROM Employee WHERE EmployeeID = {$targetId} LIMIT 1"));
                if (!$emp) {
                    $error = 'Employee not found.';
                } else {
                    // Insert into Client (split Username as first/last name fallback)
                    $uname = $emp['Username'];
                    $email = $emp['Email'];
                    $pass  = $emp['Password'];
                    $phone = $emp['ContactNumber'] ?? '';
                    $ins = mysqli_prepare($conn,
                        "INSERT INTO Client
                            (Client_FirstName, Client_MI, Client_LastName, Client_Username,
                             Client_Password, Client_Email, Client_ContactNumber)
                         VALUES (?, '', ?, ?, ?, ?, ?)");
                    mysqli_stmt_bind_param($ins, 'ssssss',
                        $uname, $uname, $uname, $pass, $email, $phone);
                    if (mysqli_stmt_execute($ins)) {
                        $del = mysqli_prepare($conn,
                            "DELETE FROM Employee WHERE EmployeeID = ?");
                        mysqli_stmt_bind_param($del, 'i', $targetId);
                        mysqli_stmt_execute($del)
                            ? $success = "User demoted to Client role."
                            : $error   = "Added to Client but could not remove Employee record.";
                    } else {
                        $error = 'Failed to create Client record. Username or email may already exist.';
                    }
                }
            }

        // ── Client → Employee (promote: copy to Employee, delete from Client) ─
        } elseif ($sourceType === 'client' && in_array($newRole, ['Manager', 'Admin'], true)) {
            $cli = mysqli_fetch_assoc(mysqli_query($conn,
                "SELECT * FROM Client WHERE UserID = {$targetId} LIMIT 1"));
            if (!$cli) {
                $error = 'Client not found.';
            } else {
                $uname = $cli['Client_Username'];
                $email = $cli['Client_Email'];
                $pass  = $cli['Client_Password'];
                $phone = $cli['Client_ContactNumber'] ?? '';
                $ins = mysqli_prepare($conn,
                    "INSERT INTO Employee (UserType, Username, Password, Email, ContactNumber)
                     VALUES (?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($ins, 'sssss',
                    $newRole, $uname, $pass, $email, $phone);
                if (mysqli_stmt_execute($ins)) {
                    // Delete client (cascades Application rows)
                    $del = mysqli_prepare($conn,
                        "DELETE FROM Client WHERE UserID = ?");
                    mysqli_stmt_bind_param($del, 'i', $targetId);
                    mysqli_stmt_execute($del)
                        ? $success = "User promoted to {$newRole}. Note: their application history has been removed."
                        : $error   = "Added to Employee but could not remove Client record.";
                } else {
                    $error = 'Failed to create Employee record. Username or email may already exist.';
                }
            }

        // ── Client stays Client ───────────────────────────────────────────────
        } elseif ($sourceType === 'client' && $newRole === 'Client') {
            $success = 'User is already a Client — no changes made.';
        } else {
            $error = 'Unsupported role transition.';
        }
    }
}

// ── FETCH ALL USERS ───────────────────────────────────────────────────────────
$searchQuery = trim($_GET['q'] ?? '');
$esc         = mysqli_real_escape_string($conn, $searchQuery);

$whereClient   = $searchQuery !== '' ? "WHERE Client_Username LIKE '%{$esc}%' OR Client_Email LIKE '%{$esc}%' OR Client_FirstName LIKE '%{$esc}%' OR Client_LastName LIKE '%{$esc}%'" : '';
$whereEmployee = $searchQuery !== '' ? "WHERE Username LIKE '%{$esc}%' OR Email LIKE '%{$esc}%'" : '';

$clients   = mysqli_query($conn, "SELECT UserID AS id, CONCAT(Client_FirstName,' ',Client_LastName) AS display_name, Client_Username AS username, Client_Email AS email, 'Client' AS role, 'client' AS source FROM Client {$whereClient} ORDER BY UserID DESC");
$employees = mysqli_query($conn, "SELECT EmployeeID AS id, Username AS display_name, Username AS username, Email AS email, UserType AS role, 'employee' AS source FROM Employee {$whereEmployee} ORDER BY EmployeeID DESC");

$totalClients   = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS t FROM Client"))['t'];
$totalEmployees = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS t FROM Employee"))['t'];

$adminActiveNav    = 'users';
$adminPageTitle    = 'User Management';
$adminPageSubtitle = 'View all accounts and assign roles across the system.';
$adminPageActions  = '';

include("../includes/admin/layout_start.php");
?>

<?php if ($success): ?>
<div class="admin-alert" style="background:#f0fdf4;color:#166534;border:1px solid #bbf7d0;margin-bottom:20px;display:flex;align-items:center;gap:8px;padding:12px 16px;border-radius:8px;">
    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
    <?= htmlspecialchars($success) ?>
</div>
<?php endif; ?>
<?php if ($error): ?>
<div class="admin-alert" style="background:#fef2f2;color:#991b1b;border:1px solid #fecaca;margin-bottom:20px;padding:12px 16px;border-radius:8px;">
    <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<!-- KPI Row -->
<div class="admin-kpi-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:24px;">
    <div class="admin-kpi-card">
        <div class="admin-kpi-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <div class="admin-kpi-label">Total Users</div>
        <div class="admin-kpi-value"><?= $totalClients + $totalEmployees ?></div>
        <div class="admin-kpi-meta">All accounts</div>
    </div>
    <div class="admin-kpi-card">
        <div class="admin-kpi-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        </div>
        <div class="admin-kpi-label">Clients</div>
        <div class="admin-kpi-value"><?= $totalClients ?></div>
        <div class="admin-kpi-meta">Registered client accounts</div>
    </div>
    <div class="admin-kpi-card">
        <div class="admin-kpi-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M6 20v-2a6 6 0 0 1 12 0v2"/><path d="M19 11l2 2-2 2"/></svg>
        </div>
        <div class="admin-kpi-label">Staff (Admin / Manager)</div>
        <div class="admin-kpi-value"><?= $totalEmployees ?></div>
        <div class="admin-kpi-meta">Admin & Manager accounts</div>
    </div>
</div>

<!-- Search + Table -->
<div class="admin-panel">
    <div style="padding:14px 20px;border-bottom:1px solid var(--admin-border);display:flex;align-items:center;gap:12px;background:var(--ysc-bg);">
        <form method="GET" style="display:flex;align-items:center;gap:8px;flex:1;max-width:420px;">
            <div style="position:relative;flex:1;">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--ysc-muted-light);pointer-events:none;"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                <input type="search" name="q" value="<?= htmlspecialchars($searchQuery) ?>"
                       placeholder="Search by name, username, email…"
                       style="width:100%;padding:8px 12px 8px 34px;border:1px solid var(--admin-border);border-radius:6px;font-size:0.84rem;font-family:inherit;background:#fff;">
            </div>
            <button type="submit" class="admin-btn admin-btn-primary admin-btn-sm">Search</button>
            <?php if ($searchQuery !== ''): ?>
                <a href="amin_users.php" class="admin-btn admin-btn-outline admin-btn-sm">Clear</a>
            <?php endif; ?>
        </form>
        <span class="admin-table-sub" style="margin-left:auto;">
            <?= mysqli_num_rows($clients) + mysqli_num_rows($employees) ?> of <?= $totalClients + $totalEmployees ?> users
        </span>
    </div>

    <table class="admin-table">
        <thead>
            <tr>
                <th>User</th>
                <th>Email</th>
                <th>Current Role</th>
                <th style="text-align:right;">Actions</th>
            </tr>
        </thead>
        <tbody>

        <?php
        // Render both result sets
        $allRows = [];
        while ($r = mysqli_fetch_assoc($clients))   $allRows[] = $r;
        while ($r = mysqli_fetch_assoc($employees)) $allRows[] = $r;

        if (empty($allRows)):
        ?>
            <tr><td colspan="4"><div class="admin-empty"><strong>No users found</strong> Try a different search term.</div></td></tr>
        <?php else: foreach ($allRows as $u):
            $isSelf    = ($u['source'] === 'employee' && (int)$u['id'] === (int)($_SESSION['user_id'] ?? 0));
            $roleBadge = match($u['role']) {
                'Admin'   => ['bg' => '#fee2e2', 'color' => '#991b1b',  'label' => 'Admin'],
                'Manager' => ['bg' => '#fef3c7', 'color' => '#92400e',  'label' => 'Project Manager'],
                default   => ['bg' => '#dbeafe', 'color' => '#1e40af',  'label' => 'Client'],
            };
        ?>
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div style="width:32px;height:32px;border-radius:50%;background:#e5e7eb;display:flex;align-items:center;justify-content:center;font-size:0.78rem;font-weight:700;color:#374151;flex-shrink:0;">
                            <?= strtoupper(substr($u['display_name'], 0, 1)) ?>
                        </div>
                        <div>
                            <span class="admin-table-project"><?= htmlspecialchars($u['display_name']) ?></span>
                            <span class="admin-table-sub">@<?= htmlspecialchars($u['username']) ?> · #<?= (int)$u['id'] ?></span>
                        </div>
                    </div>
                </td>
                <td>
                    <span style="font-size:0.84rem;color:var(--ysc-muted);"><?= htmlspecialchars($u['email']) ?></span>
                </td>
                <td>
                    <span style="display:inline-flex;align-items:center;padding:3px 10px;border-radius:20px;font-size:0.75rem;font-weight:600;background:<?= $roleBadge['bg'] ?>;color:<?= $roleBadge['color'] ?>;">
                        <?= $roleBadge['label'] ?>
                    </span>
                </td>
                <td style="text-align:right;">
                    <?php if ($isSelf): ?>
                        <span class="admin-table-sub" style="font-style:italic;">You</span>
                    <?php else: ?>
                        <button type="button"
                                class="admin-btn admin-btn-primary admin-btn-sm"
                                onclick="openRoleModal(<?= (int)$u['id'] ?>, '<?= $u['source'] ?>', '<?= htmlspecialchars(addslashes($u['display_name'])) ?>', '<?= $u['role'] ?>')">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            Set Role
                        </button>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<!-- ── Set Role Modal ──────────────────────────────────────────────────────── -->
<div id="roleModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;" onclick="if(event.target===this)closeRoleModal()">
    <div style="background:#fff;border-radius:12px;padding:32px;max-width:420px;width:90%;box-shadow:0 16px 48px rgba(0,0,0,.2);">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
            <div>
                <div style="font-size:1rem;font-weight:800;color:#111;">Set User Role</div>
                <div id="roleModalSub" style="font-size:0.82rem;color:var(--ysc-muted);margin-top:2px;"></div>
            </div>
            <button type="button" onclick="closeRoleModal()" style="background:none;border:none;cursor:pointer;color:#9ca3af;padding:4px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Role cards -->
        <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:24px;">
            <!-- Client -->
            <label id="card-Client" class="role-card" onclick="selectRole('Client')" style="display:flex;align-items:center;gap:14px;padding:14px 16px;border:2px solid #e5e7eb;border-radius:10px;cursor:pointer;transition:border-color .15s,background .15s;">
                <div style="width:36px;height:36px;border-radius:50%;background:#dbeafe;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#1e40af" stroke-width="1.8"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </div>
                <div style="flex:1;">
                    <div style="font-size:0.9rem;font-weight:700;color:#111;">Client</div>
                    <div style="font-size:0.78rem;color:var(--ysc-muted);">Can submit applications and track projects</div>
                </div>
                <span id="check-Client" style="display:none;color:#1e40af;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                </span>
            </label>

            <!-- Project Manager -->
            <label id="card-Manager" class="role-card" onclick="selectRole('Manager')" style="display:flex;align-items:center;gap:14px;padding:14px 16px;border:2px solid #e5e7eb;border-radius:10px;cursor:pointer;transition:border-color .15s,background .15s;">
                <div style="width:36px;height:36px;border-radius:50%;background:#fef3c7;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#92400e" stroke-width="1.8"><path d="M12 2 2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                </div>
                <div style="flex:1;">
                    <div style="font-size:0.9rem;font-weight:700;color:#111;">Project Manager</div>
                    <div style="font-size:0.78rem;color:var(--ysc-muted);">Can manage projects and post updates</div>
                </div>
                <span id="check-Manager" style="display:none;color:#92400e;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                </span>
            </label>

            <!-- Admin -->
            <label id="card-Admin" class="role-card" onclick="selectRole('Admin')" style="display:flex;align-items:center;gap:14px;padding:14px 16px;border:2px solid #e5e7eb;border-radius:10px;cursor:pointer;transition:border-color .15s,background .15s;">
                <div style="width:36px;height:36px;border-radius:50%;background:#fee2e2;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#991b1b" stroke-width="1.8"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                </div>
                <div style="flex:1;">
                    <div style="font-size:0.9rem;font-weight:700;color:#111;">Admin</div>
                    <div style="font-size:0.78rem;color:var(--ysc-muted);">Full access to all admin panel features</div>
                </div>
                <span id="check-Admin" style="display:none;color:#991b1b;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                </span>
            </label>
        </div>

        <!-- Warning for cross-table promotions -->
        <div id="roleWarning" style="display:none;background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:10px 14px;font-size:0.8rem;color:#92400e;margin-bottom:16px;line-height:1.5;">
            <strong>⚠ Note:</strong> Promoting a client to staff (or vice versa) will move their account to the other user table. Any application history tied to a promoted client will be removed.
        </div>

        <form method="POST" id="roleForm">
            <input type="hidden" name="set_role"    value="1">
            <input type="hidden" name="target_id"   id="modalTargetId">
            <input type="hidden" name="source_type" id="modalSourceType">
            <input type="hidden" name="new_role"    id="modalNewRole">
            <div style="display:flex;gap:10px;">
                <button type="button" class="admin-btn admin-btn-outline" style="flex:1;" onclick="closeRoleModal()">Cancel</button>
                <button type="submit" id="roleSubmitBtn" class="admin-btn admin-btn-primary" style="flex:1;" disabled>Apply Role</button>
            </div>
        </form>
    </div>
</div>

<script>
let _currentRole = '';
let _sourceType  = '';

function openRoleModal(id, source, name, currentRole) {
    _currentRole = currentRole;
    _sourceType  = source;
    document.getElementById('modalTargetId').value   = id;
    document.getElementById('modalSourceType').value = source;
    document.getElementById('modalNewRole').value    = '';
    document.getElementById('roleModalSub').textContent = name + ' · current: ' + (currentRole === 'Manager' ? 'Project Manager' : currentRole);
    document.getElementById('roleSubmitBtn').disabled = true;
    document.getElementById('roleWarning').style.display = 'none';
    // Reset cards
    ['Client','Manager','Admin'].forEach(r => {
        const card = document.getElementById('card-' + r);
        card.style.border = '2px solid #e5e7eb';
        card.style.background = '';
        document.getElementById('check-' + r).style.display = 'none';
    });
    document.getElementById('roleModal').style.display = 'flex';
}

function closeRoleModal() {
    document.getElementById('roleModal').style.display = 'none';
}

function selectRole(role) {
    ['Client','Manager','Admin'].forEach(r => {
        const card = document.getElementById('card-' + r);
        card.style.border = '2px solid #e5e7eb';
        card.style.background = '';
        document.getElementById('check-' + r).style.display = 'none';
    });

    const colors = { Client: '#1e40af', Manager: '#92400e', Admin: '#991b1b' };
    const bgs    = { Client: '#eff6ff', Manager: '#fffbeb', Admin: '#fff1f2' };
    const card   = document.getElementById('card-' + role);
    card.style.border     = '2px solid ' + colors[role];
    card.style.background = bgs[role];
    document.getElementById('check-' + role).style.display = '';

    document.getElementById('modalNewRole').value = role;
    const isSame = (role === _currentRole);
    document.getElementById('roleSubmitBtn').disabled = isSame;

    // Show warning if crossing tables
    const crossTable = (_sourceType === 'client' && role !== 'Client') ||
                       (_sourceType === 'employee' && role === 'Client');
    document.getElementById('roleWarning').style.display = crossTable ? 'block' : 'none';
}
</script>

<?php include("../includes/admin/layout_end.php"); ?>
