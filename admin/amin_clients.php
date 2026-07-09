<?php
include("../config/database.php");
include("../includes/admin/helpers.php");

adminRequireLogin('admin/amin_clients.php');

$adminEmployee     = adminCurrentEmployee($conn);
$adminPendingCount = (int) mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS total FROM Application WHERE Status = 'Pending'"))['total'];

$success = '';
$error   = '';

// ── DELETE CLIENT ───────────────────────────────────────────
if (isset($_POST['delete_client'], $_POST['client_id'])) {
    $cid = (int) $_POST['client_id'];
    $del = mysqli_prepare($conn, "DELETE FROM Client WHERE UserID = ?");
    mysqli_stmt_bind_param($del, "i", $cid);
    mysqli_stmt_execute($del)
        ? $success = "Client account #$cid has been removed."
        : $error   = "Failed to remove client account.";
}

// ── Check if last_active exists ─────────────────────────────
$hasLastActive = (bool) mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='Client' AND COLUMN_NAME='last_active'"
));

// ── KPI counts ──────────────────────────────────────────────
$totalClients    = (int) mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS t FROM Client"))['t'];
$totalRevenue    = (float) mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COALESCE(SUM(ProposalBudget),0) AS t FROM Application WHERE Status='Approved' AND ApplicationType='New Project'"))['t'];
$activeClients   = (int) mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(DISTINCT UserID) AS t FROM Application WHERE Status='Approved'"))['t'];
$inactiveClients = (int) mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS t FROM Client c
     WHERE NOT EXISTS (SELECT 1 FROM Application a WHERE a.UserID = c.UserID)"))['t'];
$onlineClients   = $hasLastActive
    ? (int) mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) AS t FROM Client WHERE last_active >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)"))['t']
    : null;

// ── BUILD QUERY ─────────────────────────────────────────────
$searchQuery = trim($_GET['q'] ?? '');
$lastActiveSel = $hasLastActive ? ', c.last_active' : ', NULL AS last_active';

$sql = "
    SELECT c.UserID, c.Client_FirstName, c.Client_MI, c.Client_LastName,
           c.Client_Username, c.Client_Email, c.Client_ContactNumber
           {$lastActiveSel},
           (SELECT COUNT(*) FROM Application a WHERE a.UserID = c.UserID)                                        AS application_count,
           (SELECT COUNT(*) FROM Application a WHERE a.UserID = c.UserID AND a.Status = 'Approved')              AS approved_count,
           (SELECT COUNT(*) FROM Application a WHERE a.UserID = c.UserID AND a.Status = 'Rejected')              AS rejected_count,
           (SELECT COALESCE(SUM(a.ProposalBudget),0) FROM Application a WHERE a.UserID=c.UserID AND a.Status='Approved' AND a.ApplicationType='New Project') AS approved_revenue
    FROM Client c
";

if ($searchQuery !== '') {
    $esc = mysqli_real_escape_string($conn, $searchQuery);
    $sql .= " WHERE c.Client_FirstName LIKE '%{$esc}%'
               OR c.Client_LastName  LIKE '%{$esc}%'
               OR c.Client_Username  LIKE '%{$esc}%'
               OR c.Client_Email     LIKE '%{$esc}%'
               OR c.Client_ContactNumber LIKE '%{$esc}%'";
}
$sql .= " ORDER BY c.UserID DESC";
$result = mysqli_query($conn, $sql);

$adminActiveNav    = 'clients';
$adminPageTitle    = 'Clients';
$adminPageSubtitle = 'Client accounts, application history, and approved project revenue.';
$adminPageActions  = '
    <a href="' . BASE_URL . '/admin/amin_export.php?type=clients" class="admin-btn admin-btn-outline">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Export CSV
    </a>
';

include("../includes/admin/layout_start.php");
?>

<?php if ($success): ?>
<div class="admin-alert" style="background:#f0fdf4;color:#166534;border:1px solid #bbf7d0;margin-bottom:20px;display:flex;align-items:center;gap:8px;">
    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
    <?= htmlspecialchars($success) ?>
</div>
<?php endif; ?>
<?php if ($error): ?>
<div class="admin-alert" style="background:#fef2f2;color:#991b1b;border:1px solid #fecaca;margin-bottom:20px;">
    <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<!-- ── KPI Cards ─────────────────────────────────────────── -->
<div class="admin-kpi-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:24px;">

    <div class="admin-kpi-card">
        <div class="admin-kpi-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <div class="admin-kpi-label">Total Clients</div>
        <div class="admin-kpi-value"><?= $totalClients ?></div>
        <div class="admin-kpi-meta">
            <?php if ($onlineClients !== null): ?>
                <span style="color:#059669;font-weight:600;">● <?= $onlineClients ?> online now</span>
            <?php else: ?>
                Registered accounts
            <?php endif; ?>
        </div>
    </div>

    <div class="admin-kpi-card">
        <div class="admin-kpi-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="M22 4L12 14.01l-3-3"/></svg>
        </div>
        <div class="admin-kpi-label">Active Clients</div>
        <div class="admin-kpi-value"><?= $activeClients ?></div>
        <div class="admin-kpi-meta positive">Have at least one approved application</div>
    </div>

    <div class="admin-kpi-card">
        <div class="admin-kpi-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg>
        </div>
        <div class="admin-kpi-label">No Applications</div>
        <div class="admin-kpi-value"><?= $inactiveClients ?></div>
        <div class="admin-kpi-meta<?= $inactiveClients > 0 ? ' alert' : '' ?>">
            <?= $inactiveClients > 0 ? 'Have never applied' : 'All clients have applied' ?>
        </div>
    </div>

    <div class="admin-kpi-card">
        <div class="admin-kpi-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </div>
        <div class="admin-kpi-label">Approved Project Revenue</div>
        <div class="admin-kpi-value" style="font-size:1.4rem;">₱<?= number_format($totalRevenue, 0) ?></div>
        <div class="admin-kpi-meta positive">Total approved pipeline value</div>
    </div>

</div>

<!-- ── Search + Table ─────────────────────────────────────── -->
<div class="admin-panel">

    <!-- Inline search -->
    <div style="padding:14px 20px;border-bottom:1px solid var(--admin-border);display:flex;align-items:center;gap:12px;background:var(--ysc-bg);">
        <form method="GET" style="display:flex;align-items:center;gap:8px;flex:1;max-width:400px;">
            <div style="position:relative;flex:1;">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--ysc-muted-light);pointer-events:none;"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                <input type="search" name="q" value="<?= htmlspecialchars($searchQuery) ?>"
                       placeholder="Search by name, email, username…"
                       style="width:100%;padding:8px 12px 8px 34px;border:1px solid var(--admin-border);border-radius:6px;font-size:0.84rem;font-family:inherit;background:#fff;">
            </div>
            <button type="submit" class="admin-btn admin-btn-primary admin-btn-sm">Search</button>
            <?php if ($searchQuery !== ''): ?>
                <a href="amin_clients.php" class="admin-btn admin-btn-outline admin-btn-sm">Clear</a>
            <?php endif; ?>
        </form>
        <span class="admin-table-sub" style="margin-left:auto;">
            <?= mysqli_num_rows($result) ?> of <?= $totalClients ?> clients
        </span>
    </div>

    <?php if (mysqli_num_rows($result) === 0): ?>
        <div class="admin-empty">
            <strong>No clients found</strong>
            <?= $searchQuery ? 'Try a different search term.' : 'Client accounts appear here after they sign up.' ?>
        </div>
    <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Contact</th>
                    <th>Applications</th>
                    <th>Approved</th>
                    <th>Rejected</th>
                    <th>Project Revenue</th>
                    <?php if ($hasLastActive): ?><th>Last Active</th><?php endif; ?>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php while ($client = mysqli_fetch_assoc($result)):
                    $fullName = trim(
                        $client['Client_FirstName'] . ' ' .
                        ($client['Client_MI'] ? $client['Client_MI'] . '. ' : '') .
                        $client['Client_LastName']
                    );

                    // Online status
                    $isOnline = $hasLastActive
                        && !empty($client['last_active'])
                        && strtotime($client['last_active']) >= (time() - 900);

                    // Last active label
                    $lastActiveLabel = '—';
                    if ($hasLastActive && !empty($client['last_active'])) {
                        $lastActiveLabel = adminTimeAgo($client['last_active']);
                    }
                ?>
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <?php if ($hasLastActive): ?>
                                <span style="width:8px;height:8px;border-radius:50%;flex-shrink:0;background:<?= $isOnline ? '#059669' : '#d1d5db' ?>;"></span>
                            <?php endif; ?>
                            <div>
                                <span class="admin-table-project"><?= htmlspecialchars($fullName) ?></span>
                                <span class="admin-table-sub">@<?= htmlspecialchars($client['Client_Username']) ?> · #<?= (int)$client['UserID'] ?></span>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="admin-table-project" style="font-weight:400;"><?= htmlspecialchars($client['Client_Email']) ?></span>
                        <span class="admin-table-sub"><?= htmlspecialchars($client['Client_ContactNumber'] ?: '—') ?></span>
                    </td>
                    <td><?= (int)$client['application_count'] ?></td>
                    <td>
                        <?php if ((int)$client['approved_count'] > 0): ?>
                            <span style="color:#059669;font-weight:600;"><?= (int)$client['approved_count'] ?></span>
                        <?php else: ?>
                            <span style="color:var(--ysc-muted-light);">0</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ((int)$client['rejected_count'] > 0): ?>
                            <span style="color:#dc2626;font-weight:600;"><?= (int)$client['rejected_count'] ?></span>
                        <?php else: ?>
                            <span style="color:var(--ysc-muted-light);">0</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ((float)$client['approved_revenue'] > 0): ?>
                            <span style="font-weight:600;">₱<?= number_format((float)$client['approved_revenue'], 0) ?></span>
                        <?php else: ?>
                            <span style="color:var(--ysc-muted-light);">—</span>
                        <?php endif; ?>
                    </td>
                    <?php if ($hasLastActive): ?>
                    <td>
                        <?php if ($isOnline): ?>
                            <span style="color:#059669;font-size:0.78rem;font-weight:600;">Online now</span>
                        <?php else: ?>
                            <span class="admin-table-sub"><?= htmlspecialchars($lastActiveLabel) ?></span>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                    <td>
                        <button type="button"
                                class="admin-btn admin-btn-outline admin-btn-sm"
                                onclick="document.getElementById('delModal_<?= (int)$client['UserID'] ?>').style.display='flex'"
                                style="color:#dc2626;border-color:#fecaca;">
                            Delete
                        </button>

                        <!-- Confirm delete modal -->
                        <div id="delModal_<?= (int)$client['UserID'] ?>"
                             style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:2000;align-items:center;justify-content:center;"
                             onclick="if(event.target===this)this.style.display='none'">
                            <div style="background:#fff;border-radius:10px;padding:28px 32px;max-width:380px;width:90%;box-shadow:0 8px 32px rgba(0,0,0,.18);text-align:center;">
                                <div style="width:48px;height:48px;border-radius:50%;background:#fee2e2;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="#dc2626" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                                </div>
                                <div style="font-size:0.98rem;font-weight:800;color:#111;margin-bottom:8px;">Remove Client?</div>
                                <p style="font-size:0.84rem;color:#6b7280;line-height:1.6;margin:0 0 20px;">
                                    <strong><?= htmlspecialchars($fullName) ?></strong> and all their applications will be permanently deleted. This cannot be undone.
                                </p>
                                <div style="display:flex;gap:10px;justify-content:center;">
                                    <button type="button" class="admin-btn admin-btn-outline admin-btn-sm"
                                            onclick="document.getElementById('delModal_<?= (int)$client['UserID'] ?>').style.display='none'">
                                        Cancel
                                    </button>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="client_id" value="<?= (int)$client['UserID'] ?>">
                                        <button type="submit" name="delete_client" class="admin-btn admin-btn-danger admin-btn-sm">
                                            Yes, Remove
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include("../includes/admin/layout_end.php"); ?>
