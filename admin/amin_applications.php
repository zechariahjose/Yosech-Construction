<?php
include("../config/database.php");
include("../includes/header.php");
include("../includes/navbar.php");

if (!isset($_SESSION['user_type']) || !in_array($_SESSION['user_type'], ['Admin', 'Manager'])) {
    header('Location: ../login.php');
    exit;
}

if (isset($_POST['action'], $_POST['application_id'])) {
    $applicationId = (int) $_POST['application_id'];
    if ($_POST['action'] === 'approve') {
        mysqli_query($conn, "UPDATE Application SET Status = 'Approved' WHERE ApplicationID = {$applicationId}");
        $exists = mysqli_query($conn, "SELECT * FROM Project WHERE ApplicationID = {$applicationId} LIMIT 1");
        if (mysqli_num_rows($exists) === 0) {
            mysqli_query($conn, "INSERT INTO Project (ApplicationID, ProposalID, StartDate, EndDate, ProjectStatus, Description, PaymentStatus) VALUES ({$applicationId}, NULL, CURDATE(), NULL, 'Ongoing', 'Project created from approved application.', 'Unpaid')");
        }
    }
    if ($_POST['action'] === 'reject') {
        mysqli_query($conn, "UPDATE Application SET Status = 'Rejected' WHERE ApplicationID = {$applicationId}");
    }
}

$query = mysqli_query($conn, "SELECT a.*, c.FirstName, c.LastName FROM Application a JOIN Client c ON a.UserID = c.UserID ORDER BY a.SubmissionDate DESC");
?>

<div class="container mt-5">
    <h2>Application Review</h2>

    <?php if (mysqli_num_rows($query) === 0): ?>
        <div class="alert alert-info">No applications found.</div>
    <?php endif; ?>

    <?php while ($app = mysqli_fetch_assoc($query)): ?>
        <div class="card mb-3">
            <div class="card-body">
                <h5 class="card-title">Application #<?= $app['ApplicationID'] ?> - <?= htmlspecialchars($app['ApplicationType']) ?></h5>
                <p class="card-text"><strong>Client:</strong> <?= htmlspecialchars($app['FirstName'] . ' ' . $app['LastName']) ?></p>
                <p class="card-text"><strong>Submitted:</strong> <?= htmlspecialchars($app['SubmissionDate']) ?></p>
                <p class="card-text"><strong>Status:</strong> <?= htmlspecialchars($app['Status']) ?></p>
                <p class="card-text"><strong>Description:</strong><br><?= nl2br(htmlspecialchars($app['Description'])) ?></p>

                <form method="post" class="d-flex gap-2">
                    <input type="hidden" name="application_id" value="<?= $app['ApplicationID'] ?>">
                    <button type="submit" name="action" value="approve" class="btn btn-success" <?= $app['Status'] === 'Approved' ? 'disabled' : '' ?>>Approve</button>
                    <button type="submit" name="action" value="reject" class="btn btn-danger" <?= $app['Status'] === 'Rejected' ? 'disabled' : '' ?>>Reject</button>
                </form>
            </div>
        </div>
    <?php endwhile; ?>
</div>

<?php include("../includes/footer.php");
