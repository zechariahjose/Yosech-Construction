<?php
include("../config/database.php");
include("../includes/admin/helpers.php");

adminRequireLogin('admin/amin_dashboard.php');

$adminEmployee     = adminCurrentEmployee($conn);
$adminPendingCount = (int) mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS total FROM Application WHERE Status = 'Pending'"))['total'];

// ── OPERATIONAL KPIs ────────────────────────────────────────
$activeSites = (int) mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS total FROM Project WHERE ProjectStatus = 'Ongoing'"))['total'];

$sitesThisMonth = (int) mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS total FROM Project
     WHERE ProjectStatus = 'Ongoing' AND StartDate >= DATE_FORMAT(CURDATE(),'%Y-%m-01')"))['total'];

$equipmentUtilization = adminEquipmentUtilization($conn);
$complianceScore      = adminComplianceScore($conn);

$lastAuditRow   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT MAX(UpdateDate) AS last_audit FROM Project_Update"));
$lastAuditLabel = adminTimeAgo($lastAuditRow['last_audit'] ?? null);

// ── FINANCIAL KPIs ──────────────────────────────────────────
// Approved revenue — this month
$revenueThisMonth = (float) mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COALESCE(SUM(ProposalBudget),0) AS total FROM Application
     WHERE Status='Approved' AND ApplicationType='New Project'
     AND SubmissionDate >= DATE_FORMAT(CURDATE(),'%Y-%m-01')"))['total'];

// Approved revenue — last month
$revenueLastMonth = (float) mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COALESCE(SUM(ProposalBudget),0) AS total FROM Application
     WHERE Status='Approved' AND ApplicationType='New Project'
     AND SubmissionDate >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH),'%Y-%m-01')
     AND SubmissionDate <  DATE_FORMAT(CURDATE(),'%Y-%m-01')"))['total'];

// Total approved pipeline (all time)
$totalPipeline = (float) mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COALESCE(SUM(ProposalBudget),0) AS total FROM Application
     WHERE Status='Approved' AND ApplicationType='New Project'"))['total'];

// Pending project value (not yet approved)
$pendingPipeline = (float) mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COALESCE(SUM(ProposalBudget),0) AS total FROM Application
     WHERE Status='Pending' AND ApplicationType='New Project'"))['total'];

// Month-over-month change
$momChange  = $revenueLastMonth > 0
    ? round((($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100, 1)
    : ($revenueThisMonth > 0 ? 100 : 0);
$momPositive = $momChange >= 0;

// Approved rentals this month
$rentalsThisMonth = (int) mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS total FROM Application
     WHERE Status='Approved' AND ApplicationType='Equipment Rental'
     AND SubmissionDate >= DATE_FORMAT(CURDATE(),'%Y-%m-01')"))['total'];

// ── REVENUE BREAKDOWN ────────────────────────────────────────
$revenueToday = (float) mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COALESCE(SUM(ProposalBudget),0) AS total FROM Application
     WHERE Status='Approved' AND ApplicationType='New Project'
     AND SubmissionDate = CURDATE()"))['total'];

$revenueLast30 = (float) mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COALESCE(SUM(ProposalBudget),0) AS total FROM Application
     WHERE Status='Approved' AND ApplicationType='New Project'
     AND SubmissionDate >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"))['total'];

$revenueThisYear = (float) mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COALESCE(SUM(ProposalBudget),0) AS total FROM Application
     WHERE Status='Approved' AND ApplicationType='New Project'
     AND YEAR(SubmissionDate) = YEAR(CURDATE())"))['total'];

// Year-over-year change
$revenueLastYear = (float) mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COALESCE(SUM(ProposalBudget),0) AS total FROM Application
     WHERE Status='Approved' AND ApplicationType='New Project'
     AND YEAR(SubmissionDate) = YEAR(CURDATE()) - 1"))['total'];

$yoyChange   = $revenueLastYear > 0
    ? round((($revenueThisYear - $revenueLastYear) / $revenueLastYear) * 100, 1)
    : ($revenueThisYear > 0 ? 100 : 0);
$yoyPositive = $yoyChange >= 0;

// ── PROJECT OVERSIGHT TABLE ─────────────────────────────────
$searchQuery = trim($_GET['q'] ?? '');
$projectSql  = "
    SELECT p.ProjectID, p.ProjectStatus, p.StartDate, p.EndDate,
           a.ProjectTitle, a.ProjectLocation, a.ApplicationType,
           c.Client_FirstName, c.Client_LastName,
           sup.Username AS SupervisorName,
           latest.Description AS LatestUpdate,
           latest.Status AS LatestUpdateStatus
    FROM Project p
    JOIN Application a ON p.ApplicationID = a.ApplicationID
    JOIN Client c ON a.UserID = c.UserID
    LEFT JOIN (
        SELECT pu.ProjectID, pu.Description, pu.Status, pu.UpdateDate, pu.EmployeeID
        FROM Project_Update pu
        INNER JOIN (
            SELECT ProjectID, MAX(UpdateDate) AS MaxDate FROM Project_Update GROUP BY ProjectID
        ) mx ON pu.ProjectID = mx.ProjectID AND pu.UpdateDate = mx.MaxDate
    ) latest ON latest.ProjectID = p.ProjectID
    LEFT JOIN Employee sup ON latest.EmployeeID = sup.EmployeeID
    WHERE p.ProjectStatus IN ('Ongoing','On Hold')
";
if ($searchQuery !== '') {
    $esc = mysqli_real_escape_string($conn, $searchQuery);
    $projectSql .= " AND (CAST(p.ProjectID AS CHAR) LIKE '%{$esc}%'
        OR a.ProjectTitle LIKE '%{$esc}%' OR a.ProjectLocation LIKE '%{$esc}%'
        OR c.Client_FirstName LIKE '%{$esc}%' OR c.Client_LastName LIKE '%{$esc}%')";
}
$projectSql   .= " ORDER BY p.ProjectID DESC LIMIT 8";
$projectsResult = mysqli_query($conn, $projectSql);

// ── RECENT ACTIVITY ─────────────────────────────────────────
$activityResult = mysqli_query($conn,
    "SELECT action_type, action_title, action_detail, action_date FROM (
        SELECT 'application' AS action_type,
               CONCAT('Application #', a.ApplicationID, ' submitted') AS action_title,
               CONCAT(a.ApplicationType, ' · ', c.Client_FirstName, ' ', c.Client_LastName) AS action_detail,
               a.SubmissionDate AS action_date
        FROM Application a JOIN Client c ON a.UserID = c.UserID
        UNION ALL
        SELECT 'update',
               CONCAT('Project #', pu.ProjectID, ' update posted'),
               LEFT(pu.Description, 120),
               pu.UpdateDate
        FROM Project_Update pu
     ) actions ORDER BY action_date DESC LIMIT 6"
);

// ── USER ACTIVITY ───────────────────────────────────────────
$totalClients = (int) mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS t FROM Client"))['t'];
$totalStaff   = (int) mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS t FROM Employee"))['t'];

// Check if last_active columns exist before querying
$hasClientActive = (bool) mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='Client' AND COLUMN_NAME='last_active'"));
$hasEmpActive = (bool) mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='Employee' AND COLUMN_NAME='last_active'"));

$onlineClients = $hasClientActive
    ? (int) mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) AS t FROM Client WHERE last_active >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)"))['t']
    : null;
$onlineStaff   = $hasEmpActive
    ? (int) mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) AS t FROM Employee WHERE last_active >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)"))['t']
    : null;
$totalOnline   = ($onlineClients ?? 0) + ($onlineStaff ?? 0);

// ── PENDING APPLICATIONS BREAKDOWN ─────────────────────────
$pendingBreakdown = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT
        SUM(ApplicationType='New Project')    AS pending_projects,
        SUM(ApplicationType='Equipment Rental') AS pending_rentals
     FROM Application WHERE Status='Pending'"
));

$adminActiveNav    = 'dashboard';
$adminPageTitle    = 'Admin Dashboard';
$adminPageSubtitle = 'Real-time operational and financial oversight for Yosech Construction.';
$adminPageActions  = '
    <a href="' . BASE_URL . '/admin/amin_applications.php" class="admin-btn admin-btn-outline">Application Overview</a>
';

include("../includes/admin/layout_start.php");
?>

<!-- ── Row 1: Operational KPIs ──────────────────────────── -->
<div class="admin-kpi-grid" style="margin-bottom:14px;">
    <div class="admin-kpi-card">
        <div class="admin-kpi-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M2 20h20M5 20V8l7-4 7 4v12"/></svg>
        </div>
        <div class="admin-kpi-label">Active Sites</div>
        <div class="admin-kpi-value"><?= $activeSites ?></div>
        <div class="admin-kpi-meta<?= $sitesThisMonth > 0 ? ' positive' : '' ?>">
            <?= $sitesThisMonth > 0 ? "+{$sitesThisMonth} started this month" : 'No new sites this month' ?>
        </div>
    </div>

    <div class="admin-kpi-card">
        <div class="admin-kpi-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><rect x="1" y="3" width="15" height="13" rx="2"/><path d="M16 8h4l3 5v3h-7V8z"/></svg>
        </div>
        <div class="admin-kpi-label">Equipment Utilization</div>
        <div class="admin-kpi-value"><?= number_format($equipmentUtilization, 1) ?>%</div>
        <div class="admin-kpi-meta">
            <?= $equipmentUtilization >= 70 && $equipmentUtilization <= 95 ? 'Optimal range' : ($equipmentUtilization > 95 ? 'High demand' : 'Low utilization') ?>
        </div>
    </div>

    <div class="admin-kpi-card">
        <div class="admin-kpi-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M4 4h16v16H4z"/><path d="M8 8h8M8 12h8M8 16h5"/></svg>
        </div>
        <div class="admin-kpi-label">Pending Applications</div>
        <div class="admin-kpi-value"><?= $adminPendingCount ?></div>
        <div class="admin-kpi-meta<?= $adminPendingCount > 0 ? ' alert' : '' ?>">
            <?php if ($adminPendingCount > 0): ?>
                <?= (int)($pendingBreakdown['pending_projects'] ?? 0) ?> projects ·
                <?= (int)($pendingBreakdown['pending_rentals']  ?? 0) ?> rentals
            <?php else: ?>
                All reviewed
            <?php endif; ?>
        </div>
    </div>

    <div class="admin-kpi-card">
        <div class="admin-kpi-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        </div>
        <div class="admin-kpi-label">Compliance Score</div>
        <div class="admin-kpi-value"><?= $complianceScore ?><span style="font-size:1rem;font-weight:500;">/100</span></div>
        <div class="admin-kpi-meta">Last audit: <?= htmlspecialchars($lastAuditLabel) ?></div>
    </div>
</div>

<!-- ── Row 2: Financial KPIs ────────────────────────────── -->
<div class="admin-kpi-grid" style="margin-bottom:22px;">
    <div class="admin-kpi-card" style="border-top:3px solid #059669;">
        <div class="admin-kpi-label">Approved Pipeline (All Time)</div>
        <div class="admin-kpi-value" style="font-size:1.5rem;">₱<?= number_format($totalPipeline, 0) ?></div>
        <div class="admin-kpi-meta positive">Total approved project value</div>
    </div>

    <div class="admin-kpi-card" style="border-top:3px solid #6b7f94;">
        <div class="admin-kpi-label">Revenue This Month</div>
        <div class="admin-kpi-value" style="font-size:1.5rem;">₱<?= number_format($revenueThisMonth, 0) ?></div>
        <div class="admin-kpi-meta <?= $momPositive ? 'positive' : 'alert' ?>">
            <?php if ($revenueLastMonth > 0 || $revenueThisMonth > 0): ?>
                <?= $momPositive ? '▲' : '▼' ?> <?= abs($momChange) ?>% vs last month
            <?php else: ?>
                No data for comparison
            <?php endif; ?>
        </div>
    </div>

    <div class="admin-kpi-card" style="border-top:3px solid #f59e0b;">
        <div class="admin-kpi-label">Pending Pipeline</div>
        <div class="admin-kpi-value" style="font-size:1.5rem;">₱<?= number_format($pendingPipeline, 0) ?></div>
        <div class="admin-kpi-meta<?= $pendingPipeline > 0 ? ' alert' : '' ?>">
            <?= $pendingPipeline > 0 ? 'Awaiting PM approval' : 'No pending proposals' ?>
        </div>
    </div>

    <div class="admin-kpi-card" style="border-top:3px solid #6b7f94;">
        <div class="admin-kpi-label">Rentals Approved This Month</div>
        <div class="admin-kpi-value"><?= $rentalsThisMonth ?></div>
        <div class="admin-kpi-meta">Equipment rentals confirmed</div>
    </div>
</div>

<!-- ── Revenue Breakdown ─────────────────────────────────── -->
<div class="admin-panel" style="margin-bottom:22px;">
    <div class="admin-panel-head">
        <h2 class="admin-panel-title">Revenue Breakdown</h2>
        <span class="admin-table-sub">Approved project proposals only</span>
    </div>
    <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:0;">

        <!-- Today -->
        <div style="padding:18px 20px;border-right:1px solid var(--ysc-border-light);">
            <div class="admin-kpi-label">Today</div>
            <div style="font-size:1.25rem;font-weight:800;color:var(--ysc-text);letter-spacing:-0.02em;margin:6px 0 4px;">
                ₱<?= number_format($revenueToday, 0) ?>
            </div>
            <div style="font-size:0.73rem;color:var(--ysc-muted);"><?= date('M d, Y') ?></div>
        </div>

        <!-- This month -->
        <div style="padding:18px 20px;border-right:1px solid var(--ysc-border-light);">
            <div class="admin-kpi-label">This Month</div>
            <div style="font-size:1.25rem;font-weight:800;color:var(--ysc-text);letter-spacing:-0.02em;margin:6px 0 4px;">
                ₱<?= number_format($revenueThisMonth, 0) ?>
            </div>
            <div style="font-size:0.73rem;<?= $momPositive ? 'color:#059669;' : 'color:#dc2626;' ?>font-weight:600;">
                <?php if ($revenueLastMonth > 0 || $revenueThisMonth > 0): ?>
                    <?= $momPositive ? '▲' : '▼' ?> <?= abs($momChange) ?>% vs last month
                <?php else: ?>
                    <span style="color:var(--ysc-muted);font-weight:400;">No data to compare</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Last 30 days -->
        <div style="padding:18px 20px;border-right:1px solid var(--ysc-border-light);">
            <div class="admin-kpi-label">Last 30 Days</div>
            <div style="font-size:1.25rem;font-weight:800;color:var(--ysc-text);letter-spacing:-0.02em;margin:6px 0 4px;">
                ₱<?= number_format($revenueLast30, 0) ?>
            </div>
            <div style="font-size:0.73rem;color:var(--ysc-muted);">Rolling 30-day window</div>
        </div>

        <!-- This year -->
        <div style="padding:18px 20px;border-right:1px solid var(--ysc-border-light);">
            <div class="admin-kpi-label">This Year</div>
            <div style="font-size:1.25rem;font-weight:800;color:var(--ysc-text);letter-spacing:-0.02em;margin:6px 0 4px;">
                ₱<?= number_format($revenueThisYear, 0) ?>
            </div>
            <div style="font-size:0.73rem;<?= $yoyPositive ? 'color:#059669;' : 'color:#dc2626;' ?>font-weight:600;">
                <?php if ($revenueLastYear > 0 || $revenueThisYear > 0): ?>
                    <?= $yoyPositive ? '▲' : '▼' ?> <?= abs($yoyChange) ?>% vs <?= date('Y') - 1 ?>
                <?php else: ?>
                    <span style="color:var(--ysc-muted);font-weight:400;">First year of data</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Total -->
        <div style="padding:18px 20px;background:var(--ysc-bg);">
            <div class="admin-kpi-label">Total (All Time)</div>
            <div style="font-size:1.25rem;font-weight:800;color:#059669;letter-spacing:-0.02em;margin:6px 0 4px;">
                ₱<?= number_format($totalPipeline, 0) ?>
            </div>
            <div style="font-size:0.73rem;color:var(--ysc-muted);">Cumulative approved value</div>
        </div>

    </div>
</div>

<!-- ── Main grid ─────────────────────────────────────────── -->
<div class="admin-grid-main">

    <!-- Project table -->
    <div class="admin-panel">
        <div class="admin-panel-head">
            <h2 class="admin-panel-title">Active Project Oversight</h2>
            <a href="<?= BASE_URL ?>/admin/amin_projects.php" class="admin-panel-link">View all →</a>
        </div>

        <?php if (mysqli_num_rows($projectsResult) === 0): ?>
            <div class="admin-empty">
                <strong>No active projects right now</strong>
                Approved project applications will appear here.
            </div>
        <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Project / Location</th>
                        <th>Supervisor</th>
                        <th>Current Phase</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($project = mysqli_fetch_assoc($projectsResult)):
                        $status = adminProjectStatusLabel($project['ProjectStatus'], $project['LatestUpdateStatus'] ?? null);
                        $phase  = $project['LatestUpdate'] ?: $project['ProjectStatus'];
                        if (strlen($phase) > 80) $phase = substr($phase, 0, 77) . '…';
                    ?>
                    <tr>
                        <td>
                            <span class="admin-table-project"><?= htmlspecialchars(adminProjectDisplayName($project)) ?></span>
                            <?php if (!empty($project['ProjectLocation'])): ?>
                                <span class="admin-table-sub"><?= htmlspecialchars($project['ProjectLocation']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($project['SupervisorName'] ?? 'Unassigned') ?></td>
                        <td class="admin-table-sub" style="max-width:200px;"><?= htmlspecialchars($phase) ?></td>
                        <td><span class="admin-badge <?= $status['class'] ?>"><?= htmlspecialchars($status['label']) ?></span></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Right column -->
    <div class="d-flex flex-column gap-3">

        <!-- Pending applications callout -->
        <?php if ($adminPendingCount > 0): ?>
        <div class="admin-panel" style="border-left:3px solid #f59e0b;">
            <div class="admin-panel-head" style="background:#fffbeb;">
                <h2 class="admin-panel-title" style="color:#92400e;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;margin-right:6px;"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                    <?= $adminPendingCount ?> Pending Applications
                </h2>
                <a href="<?= BASE_URL ?>/admin/amin_applications.php?status=Pending" class="admin-panel-link">Review →</a>
            </div>
            <div style="padding:14px 20px;">
                <div style="display:flex;justify-content:space-between;font-size:0.84rem;margin-bottom:10px;">
                    <span style="color:var(--ysc-muted);">New Project proposals</span>
                    <strong><?= (int)($pendingBreakdown['pending_projects'] ?? 0) ?></strong>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:0.84rem;">
                    <span style="color:var(--ysc-muted);">Equipment rental requests</span>
                    <strong><?= (int)($pendingBreakdown['pending_rentals'] ?? 0) ?></strong>
                </div>
                <?php if ($pendingPipeline > 0): ?>
                <div style="margin-top:12px;padding-top:12px;border-top:1px solid var(--ysc-border-light);font-size:0.82rem;color:var(--ysc-muted);">
                    Pending project value: <strong style="color:var(--ysc-text);">₱<?= number_format($pendingPipeline, 0) ?></strong>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Recent activity -->
        <div class="admin-panel">
            <div class="admin-panel-head">
                <h2 class="admin-panel-title">Recent Activity</h2>
            </div>
            <ul class="admin-activity-list">
                <?php if (mysqli_num_rows($activityResult) === 0): ?>
                    <li class="admin-activity-item">
                        <div class="admin-activity-desc">No recent activity recorded yet.</div>
                    </li>
                <?php else: ?>
                    <?php while ($action = mysqli_fetch_assoc($activityResult)): ?>
                    <li class="admin-activity-item">
                        <div class="admin-activity-title"><?= htmlspecialchars($action['action_title']) ?></div>
                        <div class="admin-activity-desc"><?= htmlspecialchars($action['action_detail']) ?></div>
                        <div class="admin-activity-time"><?= htmlspecialchars(adminTimeAgo($action['action_date'])) ?></div>
                    </li>
                    <?php endwhile; ?>
                <?php endif; ?>
            </ul>
        </div>

        <!-- User activity -->
        <div class="admin-panel">
            <div class="admin-panel-head">
                <h2 class="admin-panel-title">User Activity</h2>
                <?php if (!$hasClientActive || !$hasEmpActive): ?>
                    <span class="admin-table-sub" style="font-size:0.72rem;">Run migration to enable online tracking</span>
                <?php endif; ?>
            </div>
            <div class="admin-status-list">

                <!-- Total Clients -->
                <div class="admin-status-row">
                    <span style="display:flex;align-items:center;gap:8px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        Total Clients
                    </span>
                    <strong><?= $totalClients ?></strong>
                </div>

                <!-- Total Staff -->
                <div class="admin-status-row">
                    <span style="display:flex;align-items:center;gap:8px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        Total Staff
                    </span>
                    <strong><?= $totalStaff ?></strong>
                </div>

                <!-- Online now -->
                <div class="admin-status-row" style="padding-top:12px;border-top:1px solid var(--ysc-border-light);margin-top:4px;">
                    <span style="display:flex;align-items:center;gap:8px;">
                        <?php if ($hasClientActive && $hasEmpActive): ?>
                            <span style="width:8px;height:8px;border-radius:50%;background:#059669;display:inline-block;flex-shrink:0;"></span>
                        <?php else: ?>
                            <span style="width:8px;height:8px;border-radius:50%;background:#d1d5db;display:inline-block;flex-shrink:0;"></span>
                        <?php endif; ?>
                        Online Now
                        <span style="font-size:0.72rem;color:var(--ysc-muted);">(last 15 min)</span>
                    </span>
                    <?php if ($hasClientActive && $hasEmpActive): ?>
                        <strong style="color:#059669;"><?= $totalOnline ?></strong>
                    <?php else: ?>
                        <span class="admin-table-sub">—</span>
                    <?php endif; ?>
                </div>

                <?php if ($hasClientActive && $hasEmpActive && $totalOnline > 0): ?>
                <div style="padding:10px 20px 4px;font-size:0.78rem;color:var(--ysc-muted);">
                    <?php if ($onlineClients > 0): ?>
                        <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
                            <span>Clients online</span><span><?= $onlineClients ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($onlineStaff > 0): ?>
                        <div style="display:flex;justify-content:space-between;">
                            <span>Staff online</span><span><?= $onlineStaff ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

            </div>
        </div>

    </div>
</div>

<?php include("../includes/admin/layout_end.php"); ?>
