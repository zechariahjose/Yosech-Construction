<?php
include("../config/database.php");
include("../includes/admin/helpers.php");

adminRequireLogin('admin/amin_dashboard.php');

$adminEmployee = adminCurrentEmployee($conn);
$adminPendingCount = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM Application WHERE Status = 'Pending'"))['total'];

$activeSites = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM Project WHERE ProjectStatus = 'Ongoing'"))['total'];
$sitesThisMonth = (int) mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM Project WHERE ProjectStatus = 'Ongoing' AND StartDate >= DATE_FORMAT(CURDATE(), '%Y-%m-01')"
))['total'];

$equipmentUtilization = adminEquipmentUtilization($conn);
$pendingInquiries = $adminPendingCount;
$complianceScore = adminComplianceScore($conn);

$lastAuditRow = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT MAX(UpdateDate) AS last_audit FROM Project_Update"
));
$lastAuditLabel = adminTimeAgo($lastAuditRow['last_audit'] ?? null);

$searchQuery = trim($_GET['q'] ?? '');
$projectSql = "
    SELECT p.ProjectID, p.ProjectStatus, p.Description, p.StartDate, p.EndDate,
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
            SELECT ProjectID, MAX(UpdateDate) AS MaxDate
            FROM Project_Update
            GROUP BY ProjectID
        ) mx ON pu.ProjectID = mx.ProjectID AND pu.UpdateDate = mx.MaxDate
    ) latest ON latest.ProjectID = p.ProjectID
    LEFT JOIN Employee sup ON latest.EmployeeID = sup.EmployeeID
    WHERE p.ProjectStatus IN ('Ongoing', 'On Hold')
";

if ($searchQuery !== '') {
    $escSearch = mysqli_real_escape_string($conn, $searchQuery);
    $projectSql .= " AND (
        CAST(p.ProjectID AS CHAR) LIKE '%{$escSearch}%'
        OR a.ProjectTitle LIKE '%{$escSearch}%'
        OR a.ProjectLocation LIKE '%{$escSearch}%'
        OR c.Client_FirstName LIKE '%{$escSearch}%'
        OR c.Client_LastName LIKE '%{$escSearch}%'
        OR p.Description LIKE '%{$escSearch}%'
    )";
}

$projectSql .= " ORDER BY p.ProjectID DESC LIMIT 8";
$projectsResult = mysqli_query($conn, $projectSql);

$activityResult = mysqli_query(
    $conn,
    "SELECT action_type, action_title, action_detail, action_date FROM (
        SELECT 'application' AS action_type,
               CONCAT('Application #', a.ApplicationID, ' submitted') AS action_title,
               CONCAT(a.ApplicationType, ' · ', c.Client_FirstName, ' ', c.Client_LastName) AS action_detail,
               a.SubmissionDate AS action_date
        FROM Application a
        JOIN Client c ON a.UserID = c.UserID
        UNION ALL
        SELECT 'update',
               CONCAT('Project #', pu.ProjectID, ' update posted'),
               LEFT(pu.Description, 120),
               pu.UpdateDate
        FROM Project_Update pu
    ) actions
     ORDER BY action_date DESC
     LIMIT 6"
);

$dbLoadRow = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT
        (SELECT COUNT(*) FROM Application) +
        (SELECT COUNT(*) FROM Project) +
        (SELECT COUNT(*) FROM Client) +
        (SELECT COUNT(*) FROM Equipment) AS record_count"
));
$dbLoadPercent = min(100, max(8, (int) round(((int) ($dbLoadRow['record_count'] ?? 0)) / 2)));

$adminActiveNav = 'dashboard';
$adminPageTitle = 'Admin Dashboard';
$adminPageSubtitle = 'Real-time operational oversight for Yosech Construction.';
$adminPageActions = '
    <a href="' . BASE_URL . '/admin/amin_applications.php" class="admin-btn admin-btn-outline">Review Applications</a>
    <a href="' . BASE_URL . '/index.php" class="admin-btn admin-btn-primary" target="_blank" rel="noopener">View Public Site</a>
';

include("../includes/admin/layout_start.php");
?>

<div class="admin-kpi-grid">
    <div class="admin-kpi-card">
        <div class="admin-kpi-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
        </div>
        <div class="admin-kpi-label">Total Active Sites</div>
        <div class="admin-kpi-value"><?= $activeSites ?></div>
        <div class="admin-kpi-meta positive">+<?= $sitesThisMonth ?> this month</div>
    </div>

    <div class="admin-kpi-card">
        <div class="admin-kpi-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><rect x="1" y="3" width="15" height="13" rx="2"/><path d="M16 8h4l3 5v3h-7V8z"/></svg>
        </div>
        <div class="admin-kpi-label">Equipment Utilization</div>
        <div class="admin-kpi-value"><?= number_format($equipmentUtilization, 1) ?>%</div>
        <div class="admin-kpi-meta"><?= $equipmentUtilization >= 70 && $equipmentUtilization <= 95 ? 'Optimal range' : 'Review fleet availability' ?></div>
    </div>

    <div class="admin-kpi-card">
        <div class="admin-kpi-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><path d="m22 6-10 7L2 6"/></svg>
        </div>
        <div class="admin-kpi-label">Pending Inquiries</div>
        <div class="admin-kpi-value"><?= $pendingInquiries ?></div>
        <div class="admin-kpi-meta<?= $pendingInquiries > 0 ? ' alert' : '' ?>"><?= $pendingInquiries > 0 ? 'Requires action' : 'All caught up' ?></div>
    </div>

    <div class="admin-kpi-card">
        <div class="admin-kpi-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        </div>
        <div class="admin-kpi-label">Project Compliance Score</div>
        <div class="admin-kpi-value"><?= $complianceScore ?>/100</div>
        <div class="admin-kpi-meta">Last update: <?= htmlspecialchars($lastAuditLabel) ?></div>
    </div>
</div>

<div class="admin-grid-main">
    <div class="admin-panel">
        <div class="admin-panel-head">
            <h2 class="admin-panel-title">Active Project Oversight</h2>
            <a href="<?= BASE_URL ?>/admin/amin_projects.php" class="admin-panel-link">View all →</a>
        </div>

        <?php if (mysqli_num_rows($projectsResult) === 0): ?>
            <div class="admin-empty">
                <strong>No active projects right now</strong>
                Approve a project application to start tracking work here.
            </div>
        <?php else: ?>
            <table class="admin-table" id="adminProjectTable">
                <thead>
                    <tr>
                        <th>Project Name</th>
                        <th>Site Supervisor</th>
                        <th>Current Phase</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($project = mysqli_fetch_assoc($projectsResult)):
                        $status = adminProjectStatusLabel($project['ProjectStatus'], $project['LatestUpdateStatus'] ?? null);
                        $phase = $project['LatestUpdate'] ?: $project['ProjectStatus'];
                        if (strlen($phase) > 80) {
                            $phase = substr($phase, 0, 77) . '…';
                        }
                    ?>
                    <tr>
                        <td>
                            <span class="admin-table-project"><?= htmlspecialchars(adminProjectDisplayName($project)) ?></span>
                            <?php if (!empty($project['ProjectLocation'])): ?>
                                <span class="admin-table-sub"><?= htmlspecialchars($project['ProjectLocation']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($project['SupervisorName'] ?? 'Unassigned') ?></td>
                        <td><?= htmlspecialchars($phase) ?></td>
                        <td><span class="admin-badge <?= $status['class'] ?>"><?= htmlspecialchars($status['label']) ?></span></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="d-flex flex-column gap-3">
        <div class="admin-panel">
            <div class="admin-panel-head">
                <h2 class="admin-panel-title">Recent Admin Actions</h2>
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

        <div class="admin-panel">
            <div class="admin-panel-head">
                <h2 class="admin-panel-title">System Status</h2>
            </div>
            <div class="admin-status-list">
                <div class="admin-status-row">
                    <span>Database Connectivity</span>
                    <span class="admin-status-dot">Stable</span>
                </div>
                <div>
                    <div class="admin-status-row">
                        <span>Database Load</span>
                        <span><?= $dbLoadPercent ?>%</span>
                    </div>
                    <div class="admin-progress">
                        <div class="admin-progress-bar" style="width: <?= $dbLoadPercent ?>%"></div>
                    </div>
                </div>
                <div class="admin-status-row">
                    <span>Last Sync</span>
                    <span><?= gmdate('H:i:s') ?> UTC</span>
                </div>
            </div>
            <div class="admin-status-note">
                All systems operational. <?= (int) ($dbLoadRow['record_count'] ?? 0) ?> records indexed across applications, projects, clients, and equipment.
            </div>
        </div>
    </div>
</div>

<?php include("../includes/admin/layout_end.php"); ?>
