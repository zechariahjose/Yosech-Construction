<?php
include("../config/database.php");
include("../includes/manager/helpers.php");

managerRequireLogin('manager/mgr_projects.php');

$mgrEmployee = adminCurrentEmployee($conn);
$mgrPendingRentals = managerPendingRentals($conn);
$employeeId = (int) $_SESSION['user_id'];

if (isset($_POST['project_id'], $_POST['project_status'])) {
    $projectId = (int) $_POST['project_id'];
    $projectStatus = mysqli_real_escape_string($conn, $_POST['project_status']);
    mysqli_query($conn, "UPDATE Project SET ProjectStatus = '{$projectStatus}' WHERE ProjectID = {$projectId}");
}

if (isset($_POST['add_update'], $_POST['project_id'], $_POST['update_description'])) {
    $projectId = (int) $_POST['project_id'];
    $desc = trim(mysqli_real_escape_string($conn, $_POST['update_description']));
    $status = mysqli_real_escape_string($conn, $_POST['update_status'] ?? 'Reviewed');

    if ($desc !== '') {
        mysqli_query($conn, "INSERT INTO Project_Update (ProjectID, EmployeeID, Status, Description, UpdateDate)
                             VALUES ({$projectId}, {$employeeId}, '{$status}', '{$desc}', CURDATE())");
    }
}

if (isset($_POST['review_update'], $_POST['update_id'])) {
    $updateId = (int) $_POST['update_id'];
    $reviewStatus = mysqli_real_escape_string($conn, $_POST['review_status'] ?? 'Reviewed');
    mysqli_query($conn, "UPDATE Project_Update SET Status = '{$reviewStatus}' WHERE UpdateID = {$updateId}");
}

$statusFilter = $_GET['status'] ?? '';
$querySql = "
    SELECT p.*, a.ApplicationType, a.ProjectTitle, a.ProjectLocation,
           c.Client_FirstName, c.Client_LastName, c.Client_ContactNumber
    FROM Project p
    JOIN Application a ON p.ApplicationID = a.ApplicationID
    JOIN Client c ON a.UserID = c.UserID
";

if ($statusFilter !== '') {
    $escStatus = mysqli_real_escape_string($conn, $statusFilter);
    $querySql .= " WHERE p.ProjectStatus = '{$escStatus}'";
} else {
    $querySql .= " WHERE p.ProjectStatus IN ('Ongoing', 'On Hold')";
}

$querySql .= " ORDER BY FIELD(p.ProjectStatus, 'On Hold', 'Ongoing'), p.ProjectID DESC";
$query = mysqli_query($conn, $querySql);

$mgrActiveNav = 'projects';
$mgrPageTitle = 'Active Projects';
$mgrPageSubtitle = 'Track site progress, post field updates, and review inspection submissions for your developments.';
$mgrPageActions = '
    <a href="' . BASE_URL . '/manager/mgr_projects.php?status=On Hold" class="admin-btn admin-btn-outline">On Hold</a>
    <a href="' . BASE_URL . '/manager/mgr_projects.php" class="admin-btn admin-btn-primary">Active Sites</a>
';

include("../includes/manager/layout_start.php");
?>

<?php if (mysqli_num_rows($query) === 0): ?>
    <div class="admin-alert admin-alert-info">No projects match this filter. Approve a project application to begin site tracking.</div>
<?php else: ?>
    <div class="admin-card-grid">
        <?php while ($project = mysqli_fetch_assoc($query)):
            $status = managerProjectStatusLabel($project['ProjectStatus']);
            $updatesResult = mysqli_query(
                $conn,
                "SELECT pu.*, e.Username FROM Project_Update pu
                 LEFT JOIN Employee e ON pu.EmployeeID = e.EmployeeID
                 WHERE pu.ProjectID = " . (int) $project['ProjectID'] . "
                 ORDER BY pu.UpdateDate DESC LIMIT 4"
            );
        ?>
        <div class="admin-card">
            <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                <div>
                    <h3 class="admin-card-title mb-1"><?= htmlspecialchars(adminProjectDisplayName($project)) ?></h3>
                    <span class="admin-table-sub">Site #<?= (int) $project['ProjectID'] ?> · <?= htmlspecialchars($project['ProjectLocation'] ?? '—') ?></span>
                </div>
                <span class="admin-badge <?= $status['class'] ?>"><?= htmlspecialchars($status['label']) ?></span>
            </div>

            <div class="admin-meta-grid">
                <div class="admin-meta-item"><span>Client Contact</span><?= htmlspecialchars($project['Client_FirstName'] . ' ' . $project['Client_LastName']) ?></div>
                <div class="admin-meta-item"><span>Phone / Email</span><?= htmlspecialchars($project['Client_ContactNumber'] ?: 'See client record') ?></div>
                <div class="admin-meta-item"><span>Site Timeline</span><?= htmlspecialchars(($project['StartDate'] ?? '—') . ' → ' . ($project['EndDate'] ?? '—')) ?></div>
            </div>

            <div class="admin-field">
                <label>Scope of Work</label>
                <div class="small text-muted" style="line-height:1.6;"><?= nl2br(htmlspecialchars($project['Description'])) ?></div>
            </div>

            <form method="post" class="d-flex gap-2 align-items-end mb-0">
                <input type="hidden" name="project_id" value="<?= (int) $project['ProjectID'] ?>">
                <div class="admin-field mb-0 flex-grow-1">
                    <label>Site Status</label>
                    <select name="project_status">
                        <?php foreach (['Ongoing', 'On Hold', 'Completed', 'Cancelled'] as $s): ?>
                            <option value="<?= $s ?>" <?= $project['ProjectStatus'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="admin-btn admin-btn-primary admin-btn-sm">Update Status</button>
            </form>

            <hr class="admin-divider">

            <h4 class="admin-card-title" style="font-size:0.88rem;">Field Updates &amp; Inspections</h4>
            <?php if (mysqli_num_rows($updatesResult) > 0): ?>
                <ul class="admin-activity-list mb-3" style="border:1px solid var(--admin-border);border-radius:6px;">
                    <?php while ($upd = mysqli_fetch_assoc($updatesResult)): ?>
                    <li class="admin-activity-item">
                        <div class="d-flex justify-content-between gap-2">
                            <span class="admin-badge <?= $upd['Status'] === 'Pending' ? 'admin-badge-inspection' : 'admin-badge-track' ?>"><?= htmlspecialchars($upd['Status']) ?></span>
                            <span class="admin-activity-time"><?= htmlspecialchars($upd['UpdateDate']) ?></span>
                        </div>
                        <div class="admin-activity-desc mt-2"><?= htmlspecialchars($upd['Description']) ?></div>
                        <?php if ($upd['Status'] === 'Pending'): ?>
                        <form method="post" class="d-flex gap-2 mt-2">
                            <input type="hidden" name="update_id" value="<?= (int) $upd['UpdateID'] ?>">
                            <input type="hidden" name="review_update" value="1">
                            <select name="review_status" class="form-select form-select-sm" style="max-width:140px;">
                                <option value="Reviewed">Mark Reviewed</option>
                                <option value="Approved">Approve</option>
                            </select>
                            <button class="admin-btn admin-btn-success admin-btn-sm">Submit Review</button>
                        </form>
                        <?php endif; ?>
                    </li>
                    <?php endwhile; ?>
                </ul>
            <?php endif; ?>

            <form method="post">
                <input type="hidden" name="project_id" value="<?= (int) $project['ProjectID'] ?>">
                <input type="hidden" name="add_update" value="1">
                <div class="admin-field">
                    <label>Post Site Update</label>
                    <textarea name="update_description" rows="3" placeholder="e.g. Foundation pour completed, awaiting inspection..." required></textarea>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <select name="update_status" style="max-width:160px;">
                        <option value="Reviewed">Reviewed</option>
                        <option value="Pending">Pending Inspection</option>
                        <option value="Approved">Approved</option>
                    </select>
                    <button class="admin-btn admin-btn-primary admin-btn-sm">Post Update</button>
                </div>
            </form>
        </div>
        <?php endwhile; ?>
    </div>
<?php endif; ?>

<?php include("../includes/manager/layout_end.php"); ?>
