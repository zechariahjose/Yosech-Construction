<?php
include("../config/database.php");
include("../includes/manager/helpers.php");

managerRequireLogin('manager/mgr_dashboard.php');

$mgrEmployee       = adminCurrentEmployee($conn);
$employeeId        = (int) $_SESSION['user_id'];
$mgrPendingRentals = managerPendingRentals($conn);

// ── KPI counts ─────────────────────────────────────────────
// Active = Ongoing from both Project table AND ProjectShowcase
$activeInternal = (int) mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS total FROM Project WHERE ProjectStatus = 'Ongoing'"))['total'];
$activeShowcase = (int) mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS total FROM ProjectShowcase WHERE Status = 'Ongoing'"))['total'];
$activeProjects = $activeInternal + $activeShowcase;

$pendingRentals = $mgrPendingRentals;

$sitesOnHold = (int) mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS total FROM Project WHERE ProjectStatus = 'On Hold'"))['total'];

$pendingInspections = managerPendingInspections($conn);

// Total projects across both tables
$totalInternal = (int) mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS total FROM Project"))['total'];
$totalShowcase = (int) mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS total FROM ProjectShowcase"))['total'];
$totalProjects = $totalInternal + $totalShowcase;

// ── Active Project Progress — internal (Ongoing + On Hold) ─
$internalProjectsResult = mysqli_query($conn,
    "SELECT p.ProjectID, p.ProjectStatus, p.Description, p.StartDate,
            a.ProjectTitle, a.ProjectLocation,
            c.Client_FirstName, c.Client_LastName,
            latest.Description AS LatestUpdate,
            latest.Status      AS LatestUpdateStatus
     FROM Project p
     JOIN Application a ON p.ApplicationID = a.ApplicationID
     JOIN Client c ON a.UserID = c.UserID
     LEFT JOIN (
         SELECT pu.ProjectID, pu.Description, pu.Status, pu.UpdateDate
         FROM Project_Update pu
         INNER JOIN (
             SELECT ProjectID, MAX(UpdateDate) AS MaxDate
             FROM Project_Update GROUP BY ProjectID
         ) mx ON pu.ProjectID = mx.ProjectID AND pu.UpdateDate = mx.MaxDate
     ) latest ON latest.ProjectID = p.ProjectID
     WHERE p.ProjectStatus IN ('Ongoing', 'On Hold')
     ORDER BY FIELD(p.ProjectStatus, 'On Hold', 'Ongoing'), p.ProjectID DESC
     LIMIT 5"
);

// ── Active Project Progress — showcase (Ongoing) ───────────
$showcaseActiveResult = mysqli_query($conn,
    "SELECT ProjectShowcaseID, Title, Status, StartDate, EndDate, Summary
     FROM ProjectShowcase
     WHERE Status = 'Ongoing'
     ORDER BY StartDate DESC
     LIMIT 3"
);

// ── Rental queue ───────────────────────────────────────────
$rentalQueue = mysqli_query($conn,
    "SELECT a.ApplicationID, a.RentalStartDate, a.RentalEndDate,
            a.SubmissionDate, a.Status,
            c.Client_FirstName, c.Client_LastName,
            eo.Name AS EquipmentName
     FROM Application a
     JOIN Client c ON a.UserID = c.UserID
     LEFT JOIN Equipment e ON a.EquipmentID = e.EquipmentID
     LEFT JOIN EquipmentOffering eo ON e.EquipmentOfferingID = eo.EquipmentOfferingID
     WHERE a.ApplicationType = 'Equipment Rental' AND a.Status = 'Pending'
     ORDER BY a.SubmissionDate ASC
     LIMIT 5"
);

// ── Inspection queue ───────────────────────────────────────
$inspectionQueue = mysqli_query($conn,
    "SELECT pu.UpdateID, pu.Description, pu.UpdateDate, pu.Status,
            p.ProjectID, a.ProjectTitle
     FROM Project_Update pu
     JOIN Project p ON pu.ProjectID = p.ProjectID
     JOIN Application a ON p.ApplicationID = a.ApplicationID
     WHERE pu.Status = 'Pending'
     ORDER BY pu.UpdateDate DESC
     LIMIT 5"
);

$mgrActiveNav    = 'dashboard';
$mgrPageTitle    = 'Site Dashboard';
$mgrPageSubtitle = 'Execution overview for active developments, pending rentals, and field inspections.';
$mgrPageActions  = '
    <a href="' . BASE_URL . '/manager/mgr_projects.php" class="admin-btn admin-btn-primary">Manage Projects</a>
    <a href="' . BASE_URL . '/manager/mgr_applications.php?type=rental" class="admin-btn admin-btn-outline">Review Rentals</a>
';

include("../includes/manager/layout_start.php");
?>

<!-- ── KPI Cards ─────────────────────────────────────────── -->
<div class="admin-kpi-grid">
    <div class="admin-kpi-card manager-kpi-ok">
        <div class="admin-kpi-label">Active Projects</div>
        <div class="admin-kpi-value"><?= $activeProjects ?></div>
        <div class="admin-kpi-meta">Sites currently in progress</div>
    </div>
    <div class="admin-kpi-card">
        <div class="admin-kpi-label">Total Projects</div>
        <div class="admin-kpi-value"><?= $totalProjects ?></div>
        <div class="admin-kpi-meta">Across website &amp; internal records</div>
    </div>
    <div class="admin-kpi-card manager-kpi-warn">
        <div class="admin-kpi-label">Sites On Hold</div>
        <div class="admin-kpi-value"><?= $sitesOnHold ?></div>
        <div class="admin-kpi-meta">Requires follow-up</div>
    </div>
    <div class="admin-kpi-card manager-kpi-warn">
        <div class="admin-kpi-label">Pending Inspections</div>
        <div class="admin-kpi-value"><?= $pendingInspections ?></div>
        <div class="admin-kpi-meta">Updates awaiting review</div>
    </div>
</div>

<!-- ── Main grid ─────────────────────────────────────────── -->
<div class="admin-grid-main">

    <!-- Left column: project tables -->
    <div style="display:flex;flex-direction:column;gap:20px;">

        <!-- Internal active projects -->
        <div class="admin-panel">
            <div class="admin-panel-head">
                <h2 class="admin-panel-title">Active Projects — Internal</h2>
                <a href="<?= BASE_URL ?>/manager/mgr_projects.php" class="admin-panel-link">Manage →</a>
            </div>
            <?php if (mysqli_num_rows($internalProjectsResult) === 0): ?>
                <div class="admin-empty">
                    <strong>No active internal sites</strong>
                    Create a project from an approved application to begin tracking.
                </div>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Project / Location</th>
                            <th>Client</th>
                            <th>Latest Update</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($project = mysqli_fetch_assoc($internalProjectsResult)):
                            $status = managerProjectStatusLabel($project['ProjectStatus'], $project['LatestUpdateStatus'] ?? null);
                            $phase  = $project['LatestUpdate'] ?: $project['ProjectStatus'];
                            if (strlen($phase) > 70) $phase = substr($phase, 0, 67) . '…';
                        ?>
                        <tr>
                            <td>
                                <span class="admin-table-project"><?= htmlspecialchars(adminProjectDisplayName($project)) ?></span>
                                <?php if (!empty($project['ProjectLocation'])): ?>
                                    <span class="admin-table-sub"><?= htmlspecialchars($project['ProjectLocation']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($project['Client_FirstName'] . ' ' . $project['Client_LastName']) ?></td>
                            <td class="admin-table-sub"><?= htmlspecialchars($phase) ?></td>
                            <td><span class="admin-badge <?= $status['class'] ?>"><?= htmlspecialchars($status['label']) ?></span></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Website showcase ongoing projects -->
        <div class="admin-panel">
            <div class="admin-panel-head">
                <h2 class="admin-panel-title">Active Projects — Website</h2>
                <a href="<?= BASE_URL ?>/manager/mgr_projects.php" class="admin-panel-link">Manage →</a>
            </div>
            <?php if (mysqli_num_rows($showcaseActiveResult) === 0): ?>
                <div class="admin-empty">
                    <strong>No ongoing website projects</strong>
                    All showcase projects are completed or on hold.
                </div>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($sc = mysqli_fetch_assoc($showcaseActiveResult)): ?>
                        <tr>
                            <td>
                                <span class="admin-table-project"><?= htmlspecialchars($sc['Title']) ?></span>
                                <span class="admin-table-sub">Website Showcase · ID #<?= (int) $sc['ProjectShowcaseID'] ?></span>
                            </td>
                            <td><?= $sc['StartDate'] ? date('M d, Y', strtotime($sc['StartDate'])) : '—' ?></td>
                            <td><?= $sc['EndDate']   ? date('M d, Y', strtotime($sc['EndDate']))   : '—' ?></td>
                            <td><span class="admin-badge admin-badge-track">On Track</span></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

    </div>

    <!-- Right column: queues -->
    <div class="d-flex flex-column gap-3">

        <!-- Pending rentals -->
        <div class="admin-panel">
            <div class="admin-panel-head">
                <h2 class="admin-panel-title">Pending Rental Requests</h2>
                <a href="<?= BASE_URL ?>/manager/mgr_applications.php?type=rental" class="admin-panel-link">Review →</a>
            </div>
            <ul class="admin-activity-list">
                <?php if (mysqli_num_rows($rentalQueue) === 0): ?>
                    <li class="admin-activity-item">
                        <div class="admin-activity-desc">No pending rental requests.</div>
                    </li>
                <?php else: ?>
                    <?php while ($rental = mysqli_fetch_assoc($rentalQueue)): ?>
                    <li class="admin-activity-item">
                        <div class="admin-activity-title"><?= htmlspecialchars($rental['EquipmentName'] ?? 'Equipment request') ?></div>
                        <div class="admin-activity-desc">
                            <?= htmlspecialchars($rental['Client_FirstName'] . ' ' . $rental['Client_LastName']) ?>
                            · <?= htmlspecialchars(($rental['RentalStartDate'] ?? '—') . ' to ' . ($rental['RentalEndDate'] ?? '—')) ?>
                        </div>
                        <div class="admin-activity-time">Submitted <?= htmlspecialchars(adminTimeAgo($rental['SubmissionDate'])) ?></div>
                    </li>
                    <?php endwhile; ?>
                <?php endif; ?>
            </ul>
        </div>

        <!-- Inspection queue -->
        <div class="admin-panel">
            <div class="admin-panel-head">
                <h2 class="admin-panel-title">Inspection &amp; Update Queue</h2>
                <a href="<?= BASE_URL ?>/manager/mgr_projects.php" class="admin-panel-link">View projects →</a>
            </div>
            <ul class="admin-activity-list">
                <?php if (mysqli_num_rows($inspectionQueue) === 0): ?>
                    <li class="admin-activity-item">
                        <div class="admin-activity-desc">No pending inspection forms.</div>
                    </li>
                <?php else: ?>
                    <?php while ($insp = mysqli_fetch_assoc($inspectionQueue)): ?>
                    <li class="admin-activity-item">
                        <div class="admin-activity-title">
                            Project #<?= (int) $insp['ProjectID'] ?> — <?= htmlspecialchars($insp['ProjectTitle'] ?? 'Site update') ?>
                        </div>
                        <div class="admin-activity-desc">
                            <?= htmlspecialchars(strlen($insp['Description']) > 90
                                ? substr($insp['Description'], 0, 87) . '…'
                                : $insp['Description']) ?>
                        </div>
                        <div class="admin-activity-time"><?= htmlspecialchars(adminTimeAgo($insp['UpdateDate'])) ?></div>
                    </li>
                    <?php endwhile; ?>
                <?php endif; ?>
            </ul>
        </div>

        <!-- Pending rentals KPI callout -->
        <?php if ($pendingRentals > 0): ?>
        <div class="admin-kpi-card" style="border-left:3px solid #f59e0b;background:#fffbeb;">
            <div class="admin-kpi-label">Pending Rentals</div>
            <div class="admin-kpi-value"><?= $pendingRentals ?></div>
            <div class="admin-kpi-meta alert">Awaiting your review</div>
            <a href="<?= BASE_URL ?>/manager/mgr_applications.php?type=rental&status=Pending"
               class="admin-btn admin-btn-primary admin-btn-sm" style="margin-top:10px;display:inline-flex;">
               Review Now
            </a>
        </div>
        <?php endif; ?>

    </div>
</div>

<?php include("../includes/manager/layout_end.php"); ?>
