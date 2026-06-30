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

// ── SYSTEM STATUS ───────────────────────────────────────────
$dbLoadRow = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT (SELECT COUNT(*) FROM Application) +
            (SELECT COUNT(*) FROM Project) +
            (SELECT COUNT(*) FROM Client) +
            (SELECT COUNT(*) FROM Equipment) AS record_count"
));
$dbLoadPercent = min(100, max(8, (int) round((int)($dbLoadRow['record_count'] ?? 0) / 2)));

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

        <!-- System status -->
        <div class="admin-panel">
            <div class="admin-panel-head">
                <h2 class="admin-panel-title">System Status</h2>
            </div>
            <div class="admin-status-list">
                <div class="admin-status-row">
                    <span>Database</span>
                    <span class="admin-status-dot">Stable</span>
                </div>
                <div>
                    <div class="admin-status-row">
                        <span>Records Indexed</span>
                        <span><?= number_format((int)($dbLoadRow['record_count'] ?? 0)) ?></span>
                    </div>
                    <div class="admin-progress">
                        <div class="admin-progress-bar" style="width:<?= $dbLoadPercent ?>%;"></div>
                    </div>
                </div>
                <div class="admin-status-row">
                    <span>Server Time</span>
                    <span><?= gmdate('H:i') ?> UTC</span>
                </div>
                <div class="admin-status-row">
                    <span>Total Clients</span>
                    <span><?= (int)mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS t FROM Client"))['t'] ?></span>
                </div>
                <div class="admin-status-row">
                    <span>Total Staff</span>
                    <span><?= (int)mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS t FROM Employee"))['t'] ?></span>
                </div>
            </div>
        </div>

    </div>
</div>

<?php include("../includes/admin/layout_end.php"); ?>
