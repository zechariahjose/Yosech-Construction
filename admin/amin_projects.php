<?php
include("../config/database.php");
include("../includes/admin/helpers.php");

adminRequireLogin('admin/amin_projects.php');

$adminEmployee = adminCurrentEmployee($conn);
$adminPendingCount = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM Application WHERE Status = 'Pending'"))['total'];
$employeeId = (int) $_SESSION['user_id'];

if (isset($_POST['project_id'], $_POST['project_status'], $_POST['payment_status'])) {
    $projectId = (int) $_POST['project_id'];
    $projectStatus = mysqli_real_escape_string($conn, $_POST['project_status']);
    $paymentStatus = mysqli_real_escape_string($conn, $_POST['payment_status']);
    mysqli_query($conn, "UPDATE Project SET ProjectStatus = '{$projectStatus}', ProjectPaymentStatus = '{$paymentStatus}' WHERE ProjectID = {$projectId}");
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

$adminActiveNav = 'projects';
$adminPageTitle = 'Projects';
$adminPageSubtitle = 'Manage project status, payment tracking, and client-facing updates.';
$adminPageActions = '
    <a href="' . BASE_URL . '/admin/amin_projects.php?status=Ongoing" class="admin-btn admin-btn-outline">Ongoing</a>
    <a href="' . BASE_URL . '/admin/amin_projects.php?status=On Hold" class="admin-btn admin-btn-outline">On Hold</a>
    <a href="' . BASE_URL . '/admin/amin_projects.php" class="admin-btn admin-btn-primary">All Projects</a>
';

include("../includes/admin/layout_start.php");
?>

<?php if (mysqli_num_rows($query) === 0): ?>
    <div class="admin-alert admin-alert-info">No projects found. Approve a project application to create one.</div>
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
            </div>

            <div class="admin-field">
                <label>Description</label>
                <div class="small" style="line-height:1.6;color:#475569;"><?= nl2br(htmlspecialchars($project['Description'])) ?></div>
            </div>

            <form method="post" class="row g-2 mb-0">
                <input type="hidden" name="project_id" value="<?= (int) $project['ProjectID'] ?>">
                <div class="col-md-6">
                    <div class="admin-field mb-2">
                        <label>Project Status</label>
                        <select name="project_status">
                            <?php foreach (['Ongoing', 'Completed', 'On Hold', 'Cancelled'] as $s): ?>
                                <option value="<?= $s ?>" <?= $project['ProjectStatus'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="admin-field mb-2">
                        <label>Payment Status</label>
                        <select name="payment_status">
                            <?php foreach (['Unpaid', 'Paid', 'Partial'] as $s): ?>
                                <option value="<?= $s ?>" <?= $project['ProjectPaymentStatus'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-12">
                    <button class="admin-btn admin-btn-primary admin-btn-sm">Save Status</button>
                </div>
            </form>

            <hr class="admin-divider">

            <h4 class="admin-card-title" style="font-size:0.88rem;">Post Project Update</h4>
            <form method="post">
                <input type="hidden" name="project_id" value="<?= (int) $project['ProjectID'] ?>">
                <input type="hidden" name="add_update" value="1">
                <div class="admin-field">
                    <textarea name="update_description" rows="3" placeholder="e.g. Foundation work completed, site inspection passed..." required></textarea>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <select name="update_status" style="max-width:160px;">
                        <option value="Reviewed">Reviewed</option>
                        <option value="Approved">Approved</option>
                        <option value="Pending">Pending</option>
                    </select>
                    <button class="admin-btn admin-btn-success admin-btn-sm">Post Update</button>
                </div>
            </form>
        </div>
        <?php endwhile; ?>
    </div>
<?php endif; ?>

<?php include("../includes/admin/layout_end.php"); ?>
