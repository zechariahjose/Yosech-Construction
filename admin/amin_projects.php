<?php
include("../config/database.php");
include("../includes/header.php");
include("../includes/navbar.php");

if (!isset($_SESSION['user_type']) || !in_array($_SESSION['user_type'], ['Admin', 'Manager'])) {
    header('Location: ../login.php');
    exit;
}

if (isset($_POST['project_id'], $_POST['project_status'], $_POST['payment_status'])) {
    $projectId = (int) $_POST['project_id'];
    $projectStatus = mysqli_real_escape_string($conn, $_POST['project_status']);
    $paymentStatus = mysqli_real_escape_string($conn, $_POST['payment_status']);
    mysqli_query($conn, "UPDATE Project SET ProjectStatus = '{$projectStatus}', PaymentStatus = '{$paymentStatus}' WHERE ProjectID = {$projectId}");
}

$query = mysqli_query($conn, "SELECT p.*, a.ApplicationType, a.Status AS ApplicationStatus FROM Project p JOIN Application a ON p.ApplicationID = a.ApplicationID ORDER BY p.ProjectID DESC");
?>

<div class="container mt-5">
    <h2>Projects</h2>

    <?php if (mysqli_num_rows($query) === 0): ?>
        <div class="alert alert-info">No projects found.</div>
    <?php else: ?>
        <div class="row">
            <?php while ($project = mysqli_fetch_assoc($query)): ?>
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Project #<?= $project['ProjectID'] ?></h5>
                            <p class="card-text"><strong>Application Type:</strong> <?= htmlspecialchars($project['ApplicationType']) ?></p>
                            <p class="card-text"><strong>Application Status:</strong> <?= htmlspecialchars($project['ApplicationStatus']) ?></p>
                            <p class="card-text"><strong>Description:</strong> <?= nl2br(htmlspecialchars($project['Description'])) ?></p>
                            <p class="card-text"><strong>Project Status:</strong> <?= htmlspecialchars($project['ProjectStatus']) ?></p>
                            <p class="card-text"><strong>Payment Status:</strong> <?= htmlspecialchars($project['PaymentStatus']) ?></p>
                            <form method="post" class="row g-2">
                                <input type="hidden" name="project_id" value="<?= $project['ProjectID'] ?>">
                                <div class="col-6">
                                    <select name="project_status" class="form-select form-select-sm">
                                        <option value="Ongoing" <?= $project['ProjectStatus'] === 'Ongoing' ? 'selected' : '' ?>>Ongoing</option>
                                        <option value="Completed" <?= $project['ProjectStatus'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                                        <option value="On Hold" <?= $project['ProjectStatus'] === 'On Hold' ? 'selected' : '' ?>>On Hold</option>
                                        <option value="Cancelled" <?= $project['ProjectStatus'] === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <select name="payment_status" class="form-select form-select-sm">
                                        <option value="Unpaid" <?= $project['PaymentStatus'] === 'Unpaid' ? 'selected' : '' ?>>Unpaid</option>
                                        <option value="Paid" <?= $project['PaymentStatus'] === 'Paid' ? 'selected' : '' ?>>Paid</option>
                                        <option value="Partial" <?= $project['PaymentStatus'] === 'Partial' ? 'selected' : '' ?>>Partial</option>
                                    </select>
                                </div>
                                <div class="col-12 text-end">
                                    <button class="btn btn-sm btn-primary">Save</button>
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
