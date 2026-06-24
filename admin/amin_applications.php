<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("../config/database.php");

if (!isset($_SESSION['user_type']) || !in_array($_SESSION['user_type'], ['Admin', 'Manager'])) {
    header('Location: ../login.php?redirect=' . urlencode('admin/amin_applications.php'));
    exit;
}

if (isset($_POST['action'], $_POST['application_id'])) {
    $applicationId = (int) $_POST['application_id'];
    $employeeId = (int) $_SESSION['user_id'];

    if ($_POST['action'] === 'approve') {
        mysqli_query($conn, "UPDATE Application SET Status = 'Approved' WHERE ApplicationID = {$applicationId}");

        $exists = mysqli_query($conn, "SELECT ProjectID FROM Project WHERE ApplicationID = {$applicationId} LIMIT 1");
        if (mysqli_num_rows($exists) === 0) {
            $appResult = mysqli_query($conn, "SELECT Description FROM Application WHERE ApplicationID = {$applicationId} LIMIT 1");
            $appRow = mysqli_fetch_assoc($appResult);
            $desc = mysqli_real_escape_string($conn, $appRow['Description'] ?? 'Approved project.');

            mysqli_query($conn, "INSERT INTO Project (ApplicationID, StartDate, ProjectStatus, Description, ProjectPaymentStatus)
                                 VALUES ({$applicationId}, CURDATE(), 'Ongoing', '{$desc}', 'Unpaid')");
            $projectId = (int) mysqli_insert_id($conn);

            if ($projectId > 0) {
                $updateMsg = mysqli_real_escape_string($conn, 'Your application has been approved. Your project is now active.');
                mysqli_query($conn, "INSERT INTO Project_Update (ProjectID, EmployeeID, Status, Description, UpdateDate)
                                     VALUES ({$projectId}, {$employeeId}, 'Approved', '{$updateMsg}', CURDATE())");
            }
        }
    }

    if ($_POST['action'] === 'reject') {
        mysqli_query($conn, "UPDATE Application SET Status = 'Rejected' WHERE ApplicationID = {$applicationId}");
    }
}

include("../includes/header.php");
include("../includes/navbar.php");

$query = mysqli_query(
    $conn,
    "SELECT a.*, c.Client_FirstName, c.Client_LastName
     FROM Application a
     JOIN Client c ON a.UserID = c.UserID
     ORDER BY a.SubmissionDate DESC"
);
?>

<div class="container mt-5">
    <h2>Application Review</h2>
    <p class="text-muted mb-4">Approve applications to create a project and notify the client with an initial update.</p>

    <?php if (mysqli_num_rows($query) === 0): ?>
        <div class="alert alert-info">No applications found.</div>
    <?php endif; ?>

    <?php while ($app = mysqli_fetch_assoc($query)): ?>
        <div class="card mb-3">
            <div class="card-body">
                <h5 class="card-title">Application #<?= $app['ApplicationID'] ?> — <?= htmlspecialchars($app['ApplicationType']) ?></h5>
                <p class="card-text"><strong>Client:</strong> <?= htmlspecialchars($app['Client_FirstName'] . ' ' . $app['Client_LastName']) ?></p>
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
