<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("../config/database.php");

if (!isset($_SESSION['user_type']) || !in_array($_SESSION['user_type'], ['Admin', 'Manager'])) {
    header('Location: ../login.php?redirect=' . urlencode('admin/amin_projects.php'));
    exit;
}

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

include("../includes/header.php");
include("../includes/navbar.php");

$query = mysqli_query(
    $conn,
    "SELECT p.*, a.ApplicationType, a.Status AS ApplicationStatus
     FROM Project p
     JOIN Application a ON p.ApplicationID = a.ApplicationID
     ORDER BY p.ProjectID DESC"
);
?>

<div class="container mt-5">
    <h2>Projects</h2>
    <p class="text-muted mb-4">Manage project status and post updates that clients can view on their dashboard.</p>

    <?php if (mysqli_num_rows($query) === 0): ?>
        <div class="alert alert-info">No projects found. Approve an application to create one.</div>
    <?php else: ?>
        <div class="row">
            <?php while ($project = mysqli_fetch_assoc($query)): ?>
                <div class="col-lg-6">
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Project #<?= $project['ProjectID'] ?></h5>
                            <p class="card-text"><strong>Type:</strong> <?= htmlspecialchars($project['ApplicationType']) ?></p>
                            <p class="card-text"><strong>Description:</strong> <?= nl2br(htmlspecialchars($project['Description'])) ?></p>

                            <form method="post" class="row g-2 mb-3">
                                <input type="hidden" name="project_id" value="<?= $project['ProjectID'] ?>">
                                <div class="col-6">
                                    <label class="form-label small">Project Status</label>
                                    <select name="project_status" class="form-select form-select-sm">
                                        <?php foreach (['Ongoing', 'Completed', 'On Hold', 'Cancelled'] as $s): ?>
                                            <option value="<?= $s ?>" <?= $project['ProjectStatus'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small">Payment Status</label>
                                    <select name="payment_status" class="form-select form-select-sm">
                                        <?php foreach (['Unpaid', 'Paid', 'Partial'] as $s): ?>
                                            <option value="<?= $s ?>" <?= $project['ProjectPaymentStatus'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12 text-end">
                                    <button class="btn btn-sm btn-primary">Save Status</button>
                                </div>
                            </form>

                            <hr>
                            <h6 class="mb-2">Post Project Update</h6>
                            <form method="post">
                                <input type="hidden" name="project_id" value="<?= $project['ProjectID'] ?>">
                                <input type="hidden" name="add_update" value="1">
                                <div class="mb-2">
                                    <textarea name="update_description" class="form-control form-control-sm" rows="2" placeholder="e.g. Foundation work completed, site inspection passed..." required></textarea>
                                </div>
                                <div class="d-flex gap-2 align-items-center">
                                    <select name="update_status" class="form-select form-select-sm" style="max-width:140px;">
                                        <option value="Reviewed">Reviewed</option>
                                        <option value="Approved">Approved</option>
                                        <option value="Pending">Pending</option>
                                    </select>
                                    <button class="btn btn-sm btn-success">Post Update</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>

<?php include("../includes/footer.php");
