<?php
include("../config/database.php");
include("../includes/admin/helpers.php");

adminRequireLogin('admin/amin_applications.php');

$adminEmployee     = adminCurrentEmployee($conn);
$adminPendingCount = (int) mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS total FROM Application WHERE Status = 'Pending'"))['total'];

// ── SUMMARY STATS ───────────────────────────────────────────
$stats = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT
        COUNT(*) AS total,
        SUM(Status = 'Pending')  AS pending,
        SUM(Status = 'Approved') AS approved,
        SUM(Status = 'Rejected') AS rejected,
        SUM(ApplicationType = 'New Project') AS projects,
        SUM(ApplicationType = 'Equipment Rental') AS rentals
     FROM Application"
));

// Financial pipeline
$pipeline = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT
        COALESCE(SUM(CASE WHEN Status='Approved' AND ApplicationType='New Project' THEN ProposalBudget END), 0) AS approved_project_value,
        COALESCE(SUM(CASE WHEN Status='Pending'  AND ApplicationType='New Project' THEN ProposalBudget END), 0) AS pending_project_value,
        COUNT(CASE WHEN Status='Approved' AND ApplicationType='Equipment Rental' THEN 1 END) AS approved_rentals,
        COUNT(CASE WHEN Status='Pending'  AND ApplicationType='Equipment Rental' THEN 1 END) AS pending_rentals
     FROM Application"
));

// Monthly trend — last 6 months
$trendResult = mysqli_query($conn,
    "SELECT DATE_FORMAT(SubmissionDate,'%b %Y') AS month,
            DATE_FORMAT(SubmissionDate,'%Y-%m') AS month_sort,
            COUNT(*) AS total,
            SUM(Status='Approved') AS approved,
            SUM(Status='Rejected') AS rejected
     FROM Application
     WHERE SubmissionDate >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
     GROUP BY month_sort, month
     ORDER BY month_sort ASC"
);
$trendRows = [];
while ($row = mysqli_fetch_assoc($trendResult)) $trendRows[] = $row;

// Top clients by approved value
$topClients = mysqli_query($conn,
    "SELECT c.Client_FirstName, c.Client_LastName, c.Client_Username,
            COUNT(a.ApplicationID) AS app_count,
            SUM(a.Status='Approved') AS approved_count,
            COALESCE(SUM(CASE WHEN a.Status='Approved' THEN a.ProposalBudget END),0) AS approved_value
     FROM Client c
     JOIN Application a ON a.UserID = c.UserID
     GROUP BY c.UserID
     ORDER BY approved_value DESC
     LIMIT 5"
);

// ── FILTERED TABLE ──────────────────────────────────────────
$statusFilter = $_GET['status'] ?? '';
$typeFilter   = $_GET['type']   ?? '';
$searchQ      = trim($_GET['q'] ?? '');
$dateFrom     = $_GET['date_from'] ?? '';
$dateTo       = $_GET['date_to']   ?? '';

$whereClauses = ['1=1'];
if ($statusFilter !== '') $whereClauses[] = "a.Status = '" . mysqli_real_escape_string($conn, $statusFilter) . "'";
if ($typeFilter === 'project') $whereClauses[] = "a.ApplicationType = 'New Project'";
if ($typeFilter === 'rental')  $whereClauses[] = "a.ApplicationType = 'Equipment Rental'";
if ($dateFrom !== '') $whereClauses[] = "a.SubmissionDate >= '" . mysqli_real_escape_string($conn, $dateFrom) . "'";
if ($dateTo   !== '') $whereClauses[] = "a.SubmissionDate <= '" . mysqli_real_escape_string($conn, $dateTo) . "'";
if ($searchQ  !== '') {
    $esc = mysqli_real_escape_string($conn, $searchQ);
    $whereClauses[] = "(c.Client_FirstName LIKE '%{$esc}%' OR c.Client_LastName LIKE '%{$esc}%'
                        OR a.ProjectTitle LIKE '%{$esc}%' OR a.ProjectLocation LIKE '%{$esc}%'
                        OR eo.Name LIKE '%{$esc}%' OR CAST(a.ApplicationID AS CHAR) LIKE '%{$esc}%')";
}

$where = implode(' AND ', $whereClauses);
$tableResult = mysqli_query($conn,
    "SELECT a.*, c.Client_FirstName, c.Client_LastName, c.Client_Email, c.Client_ContactNumber,
            eo.Name AS EquipmentName, eo.Model AS EquipmentModel
     FROM Application a
     JOIN Client c ON a.UserID = c.UserID
     LEFT JOIN Equipment e ON a.EquipmentID = e.EquipmentID
     LEFT JOIN EquipmentOffering eo ON e.EquipmentOfferingID = eo.EquipmentOfferingID
     WHERE {$where}
     ORDER BY a.SubmissionDate DESC"
);

$adminActiveNav    = 'history';
$adminPageTitle    = 'Application Overview';
$adminPageSubtitle = 'Business intelligence on client proposals, rental requests, and the approved project pipeline.';
$adminPageActions  = '
    <a href="' . BASE_URL . '/admin/amin_export.php?type=applications_excel" class="admin-btn admin-btn-outline">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Export Excel
    </a>
';

include("../includes/admin/layout_start.php");
?>

<!-- ── KPI Cards ─────────────────────────────────────────── -->
<div class="admin-kpi-grid">
    <div class="admin-kpi-card">
        <div class="admin-kpi-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M4 4h16v16H4z"/><path d="M8 8h8M8 12h8M8 16h5"/></svg>
        </div>
        <div class="admin-kpi-label">Total Applications</div>
        <div class="admin-kpi-value"><?= (int)$stats['total'] ?></div>
        <div class="admin-kpi-meta"><?= (int)$stats['projects'] ?> projects · <?= (int)$stats['rentals'] ?> rentals</div>
    </div>
    <div class="admin-kpi-card">
        <div class="admin-kpi-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
        </div>
        <div class="admin-kpi-label">Pending Review</div>
        <div class="admin-kpi-value"><?= (int)$stats['pending'] ?></div>
        <div class="admin-kpi-meta<?= (int)$stats['pending'] > 0 ? ' alert' : '' ?>"><?= (int)$stats['pending'] > 0 ? 'Awaiting PM action' : 'All reviewed' ?></div>
    </div>
    <div class="admin-kpi-card">
        <div class="admin-kpi-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        </div>
        <div class="admin-kpi-label">Approved Projects Value</div>
        <div class="admin-kpi-value" style="font-size:1.4rem;">₱<?= number_format((float)$pipeline['approved_project_value'], 0) ?></div>
        <div class="admin-kpi-meta positive">₱<?= number_format((float)$pipeline['pending_project_value'], 0) ?> pending</div>
    </div>
    <div class="admin-kpi-card">
        <div class="admin-kpi-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><rect x="1" y="3" width="15" height="13" rx="2"/><path d="M16 8h4l3 5v3h-7V8z"/></svg>
        </div>
        <div class="admin-kpi-label">Equipment Rentals</div>
        <div class="admin-kpi-value"><?= (int)$pipeline['approved_rentals'] ?> approved</div>
        <div class="admin-kpi-meta<?= (int)$pipeline['pending_rentals'] > 0 ? ' alert' : '' ?>"><?= (int)$pipeline['pending_rentals'] ?> pending assignment</div>
    </div>
</div>

<!-- ── Summary panels ────────────────────────────────────── -->
<div class="admin-grid-main" style="margin-bottom:24px;">

    <!-- Monthly trend -->
    <div class="admin-panel">
        <div class="admin-panel-head">
            <h2 class="admin-panel-title">Submission Trend — Last 6 Months</h2>
        </div>
        <?php if (empty($trendRows)): ?>
            <div class="admin-empty"><strong>No submission data</strong></div>
        <?php else: ?>
        <table class="admin-table">
            <thead><tr><th>Month</th><th>Submitted</th><th>Approved</th><th>Rejected</th><th>Approval Rate</th></tr></thead>
            <tbody>
            <?php foreach ($trendRows as $tr):
                $rate = (int)$tr['total'] > 0 ? round(((int)$tr['approved'] / (int)$tr['total']) * 100) : 0;
            ?>
            <tr>
                <td><?= htmlspecialchars($tr['month']) ?></td>
                <td><?= (int)$tr['total'] ?></td>
                <td><span style="color:#059669;font-weight:600;"><?= (int)$tr['approved'] ?></span></td>
                <td><span style="color:#dc2626;font-weight:600;"><?= (int)$tr['rejected'] ?></span></td>
                <td>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div style="flex:1;height:6px;background:var(--ysc-border);border-radius:99px;overflow:hidden;">
                            <div style="width:<?= $rate ?>%;height:100%;background:#059669;border-radius:99px;"></div>
                        </div>
                        <span style="font-size:0.76rem;font-weight:600;min-width:30px;"><?= $rate ?>%</span>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- Top clients -->
    <div class="admin-panel">
        <div class="admin-panel-head">
            <h2 class="admin-panel-title">Top Clients by Pipeline Value</h2>
        </div>
        <?php if (mysqli_num_rows($topClients) === 0): ?>
            <div class="admin-empty"><strong>No client data yet</strong></div>
        <?php else: ?>
        <ul class="admin-activity-list">
            <?php while ($tc = mysqli_fetch_assoc($topClients)): ?>
            <li class="admin-activity-item">
                <div class="admin-activity-title"><?= htmlspecialchars($tc['Client_FirstName'] . ' ' . $tc['Client_LastName']) ?></div>
                <div class="admin-activity-desc">@<?= htmlspecialchars($tc['Client_Username']) ?> · <?= (int)$tc['app_count'] ?> applications · <?= (int)$tc['approved_count'] ?> approved</div>
                <div style="font-size:0.82rem;font-weight:700;color:#059669;margin-top:4px;">₱<?= number_format((float)$tc['approved_value'], 0) ?></div>
            </li>
            <?php endwhile; ?>
        </ul>
        <?php endif; ?>
    </div>

</div>

<!-- ── Full application table with filters ───────────────── -->
<div class="admin-panel">
    <div class="admin-panel-head">
        <h2 class="admin-panel-title">All Applications</h2>
        <span class="admin-table-sub"><?= mysqli_num_rows($tableResult) ?> records</span>
    </div>

    <!-- Filter bar -->
    <form method="GET" style="display:flex;flex-wrap:wrap;align-items:flex-end;gap:10px;padding:16px 20px;border-bottom:1px solid var(--ysc-border);background:var(--ysc-bg);">
        <div style="flex:1;min-width:180px;">
            <label style="display:block;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--ysc-muted);margin-bottom:4px;">Search</label>
            <input type="search" name="q" value="<?= htmlspecialchars($searchQ) ?>"
                   placeholder="Client name, project, equipment…"
                   style="width:100%;padding:8px 12px;border:1px solid var(--ysc-border);border-radius:6px;font-size:0.84rem;font-family:inherit;">
        </div>
        <div>
            <label style="display:block;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--ysc-muted);margin-bottom:4px;">Status</label>
            <select name="status" style="padding:8px 12px;border:1px solid var(--ysc-border);border-radius:6px;font-size:0.84rem;font-family:inherit;">
                <option value="">All Statuses</option>
                <option value="Pending"  <?= $statusFilter==='Pending'  ? 'selected' : '' ?>>Pending</option>
                <option value="Approved" <?= $statusFilter==='Approved' ? 'selected' : '' ?>>Approved</option>
                <option value="Rejected" <?= $statusFilter==='Rejected' ? 'selected' : '' ?>>Rejected</option>
            </select>
        </div>
        <div>
            <label style="display:block;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--ysc-muted);margin-bottom:4px;">Type</label>
            <select name="type" style="padding:8px 12px;border:1px solid var(--ysc-border);border-radius:6px;font-size:0.84rem;font-family:inherit;">
                <option value="">All Types</option>
                <option value="project" <?= $typeFilter==='project' ? 'selected' : '' ?>>New Project</option>
                <option value="rental"  <?= $typeFilter==='rental'  ? 'selected' : '' ?>>Equipment Rental</option>
            </select>
        </div>
        <div>
            <label style="display:block;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--ysc-muted);margin-bottom:4px;">From</label>
            <input type="date" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>"
                   style="padding:8px 12px;border:1px solid var(--ysc-border);border-radius:6px;font-size:0.84rem;font-family:inherit;">
        </div>
        <div>
            <label style="display:block;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--ysc-muted);margin-bottom:4px;">To</label>
            <input type="date" name="date_to" value="<?= htmlspecialchars($dateTo) ?>"
                   style="padding:8px 12px;border:1px solid var(--ysc-border);border-radius:6px;font-size:0.84rem;font-family:inherit;">
        </div>
        <div style="display:flex;gap:8px;align-self:flex-end;">
            <button type="submit" class="admin-btn admin-btn-primary admin-btn-sm">Filter</button>
            <a href="amin_applications.php" class="admin-btn admin-btn-outline admin-btn-sm">Reset</a>
        </div>
    </form>

    <?php if (mysqli_num_rows($tableResult) === 0): ?>
        <div class="admin-empty"><strong>No applications match your filters</strong></div>
    <?php else: ?>
    <table class="admin-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Type</th>
                <th>Client</th>
                <th>Details</th>
                <th>Budget / Equipment</th>
                <th>Submitted</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($app = mysqli_fetch_assoc($tableResult)):
            $isRental = $app['ApplicationType'] === 'Equipment Rental';
            $statusClass = match($app['Status']) {
                'Approved' => 'admin-badge-approved',
                'Rejected' => 'admin-badge-rejected',
                default    => 'admin-badge-pending',
            };
            $typeClass = $isRental ? 'admin-badge-inspection' : 'admin-badge-track';
        ?>
        <tr>
            <td><span class="admin-table-sub">#<?= (int)$app['ApplicationID'] ?></span></td>
            <td><span class="admin-badge <?= $typeClass ?>"><?= $isRental ? 'Rental' : 'Project' ?></span></td>
            <td>
                <span class="admin-table-project"><?= htmlspecialchars($app['Client_FirstName'] . ' ' . $app['Client_LastName']) ?></span>
                <span class="admin-table-sub"><?= htmlspecialchars($app['Client_Email']) ?></span>
            </td>
            <td>
                <?php if ($isRental): ?>
                    <span class="admin-table-project"><?= htmlspecialchars($app['EquipmentName'] ?? '—') ?></span>
                    <span class="admin-table-sub"><?= htmlspecialchars(($app['RentalStartDate'] ?? '—') . ' → ' . ($app['RentalEndDate'] ?? '—')) ?></span>
                <?php else: ?>
                    <span class="admin-table-project"><?= htmlspecialchars($app['ProjectTitle'] ?? '—') ?></span>
                    <span class="admin-table-sub"><?= htmlspecialchars($app['ProjectLocation'] ?? '—') ?></span>
                <?php endif; ?>
            </td>
            <td>
                <?php if ($isRental): ?>
                    <?= htmlspecialchars($app['EquipmentModel'] ?? '—') ?>
                    <?php if (!empty($app['NeedsOperator'])): ?>
                        <span class="admin-table-sub">Operator required</span>
                    <?php endif; ?>
                <?php else: ?>
                    <?= $app['ProposalBudget'] !== null ? '₱' . number_format((float)$app['ProposalBudget'], 0) : '—' ?>
                    <span class="admin-table-sub"><?= htmlspecialchars(($app['ProjectStartDate'] ?? '—') . ' → ' . ($app['ProjectEndDate'] ?? '—')) ?></span>
                <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($app['SubmissionDate'] ?? '—') ?></td>
            <td><span class="admin-badge <?= $statusClass ?>"><?= htmlspecialchars($app['Status']) ?></span></td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php include("../includes/admin/layout_end.php"); ?>
