<?php
include("../config/database.php");
include("../includes/admin/helpers.php");

adminRequireLogin('admin/amin_projects.php');

$adminEmployee    = adminCurrentEmployee($conn);
$adminPendingCount = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM Application WHERE Status = 'Pending'"))['total'];

// Project editing, status updates, and field updates are managed
// exclusively by the Project Manager console (manager/mgr_projects.php).

$statusFilter = $_GET['status'] ?? '';
$querySql = "
    SELECT p.*, a.ApplicationType, a.ProjectTitle, a.ProjectLocation, a.Status AS ApplicationStatus,
           c.Client_FirstName, c.Client_LastName
    FROM Project p
    JOIN Application a ON p.ApplicationID = a.ApplicationID
    JOIN Client c ON a.UserID = c.UserID
";

if ($statusFilter !== '') {
    $escStatus = mysqli_real_escape_string($conn, $statusFilter);
    $querySql .= " WHERE p.ProjectStatus = '{$escStatus}'";
}

$querySql .= " ORDER BY p.ProjectID DESC";
$query = mysqli_query($conn, $querySql);

$adminActiveNav   = 'projects';
$adminPageTitle   = 'Projects';
$adminPageSubtitle = 'Read-only overview of all projects. Use the Project Manager console to edit, update, or delete projects.';
$adminPageActions = '
    <a href="' . BASE_URL . '/admin/amin_projects.php?status=Ongoing" class="admin-btn admin-btn-outline">Ongoing</a>
    <a href="' . BASE_URL . '/admin/amin_projects.php?status=On Hold" class="admin-btn admin-btn-outline">On Hold</a>
    <a href="' . BASE_URL . '/admin/amin_projects.php" class="admin-btn admin-btn-primary">All Projects</a>
    <a href="' . BASE_URL . '/admin/amin_export.php?type=projects" class="admin-btn admin-btn-outline">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Export CSV
    </a>
';

include("../includes/admin/layout_start.php");
?>

<div class="admin-alert admin-alert-info mb-4" style="display:flex;align-items:center;gap:10px;">
    <span>&#9432;</span>
    <span>Project editing, status changes, and field updates are handled in the <strong>Project Manager console</strong>. This page is for oversight only.</span>
</div>

<?php if (mysqli_num_rows($query) === 0): ?>
    <div class="admin-alert admin-alert-info">No projects found for this filter.</div>
<?php else: ?>
    <div class="admin-card-grid">
        <?php while ($project = mysqli_fetch_assoc($query)):
            $status = adminProjectStatusLabel($project['ProjectStatus']);
        ?>
        <div class="admin-card">
            <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                <div>
                    <h3 class="admin-card-title mb-1"><?= htmlspecialchars(adminProjectDisplayName($project)) ?></h3>
                    <div class="small text-muted">Project #<?= (int) $project['ProjectID'] ?> · <?= htmlspecialchars($project['ApplicationType']) ?></div>
                </div>
                <span class="admin-badge <?= $status['class'] ?>"><?= htmlspecialchars($status['label']) ?></span>
            </div>

            <div class="admin-meta-grid">
                <div class="admin-meta-item">
                    <span>Client</span>
                    <?= htmlspecialchars($project['Client_FirstName'] . ' ' . $project['Client_LastName']) ?>
                </div>
                <div class="admin-meta-item">
                    <span>Location</span>
                    <?= htmlspecialchars($project['ProjectLocation'] ?? '—') ?>
                </div>
                <div class="admin-meta-item">
                    <span>Budget</span>
                    <?= $project['ProposalBudget'] !== null ? '₱' . number_format((float) $project['ProposalBudget'], 2) : '—' ?>
                </div>
                <div class="admin-meta-item">
                    <span>Timeline</span>
                    <?= htmlspecialchars(($project['StartDate'] ?? '—') . ' to ' . ($project['EndDate'] ?? '—')) ?>
                </div>
                <div class="admin-meta-item">
                    <span>Project Status</span>
                    <?= htmlspecialchars($project['ProjectStatus']) ?>
                </div>
                <div class="admin-meta-item">
                    <span>Payment Status</span>
                    <?= htmlspecialchars($project['ProjectPaymentStatus']) ?>
                </div>
            </div>

            <?php if (!empty($project['Description'])): ?>
            <div class="admin-field mt-2">
                <label>Description</label>
                <div class="small" style="line-height:1.6;color:#475569;"><?= nl2br(htmlspecialchars($project['Description'])) ?></div>
            </div>
            <?php endif; ?>

            <?php
            // Show recent field updates (read-only)
            $updatesResult = mysqli_query($conn,
                "SELECT pu.*, e.Username FROM Project_Update pu
                 LEFT JOIN Employee e ON pu.EmployeeID = e.EmployeeID
                 WHERE pu.ProjectID = " . (int) $project['ProjectID'] . "
                 ORDER BY pu.UpdateDate DESC LIMIT 3"
            );
            if (mysqli_num_rows($updatesResult) > 0):
            ?>
            <hr class="admin-divider">
            <h4 class="admin-card-title" style="font-size:0.88rem;">Recent Field Updates</h4>
            <ul class="admin-activity-list" style="border:1px solid var(--admin-border);border-radius:6px;">
                <?php while ($upd = mysqli_fetch_assoc($updatesResult)): ?>
                <li class="admin-activity-item">
                    <div class="d-flex justify-content-between gap-2">
                        <span class="admin-badge <?= $upd['Status'] === 'Pending' ? 'admin-badge-inspection' : 'admin-badge-track' ?>"><?= htmlspecialchars($upd['Status']) ?></span>
                        <span class="admin-activity-time"><?= htmlspecialchars($upd['UpdateDate']) ?></span>
                    </div>
                    <div class="admin-activity-desc mt-2"><?= htmlspecialchars($upd['Description']) ?></div>
                    <?php if (!empty($upd['Username'])): ?>
                        <div class="small text-muted mt-1">Posted by <?= htmlspecialchars($upd['Username']) ?></div>
                    <?php endif; ?>
                </li>
                <?php endwhile; ?>
            </ul>
            <?php endif; ?>
        </div>
        <?php endwhile; ?>
    </div>
<?php endif; ?>

<?php include("../includes/admin/layout_end.php"); ?>
