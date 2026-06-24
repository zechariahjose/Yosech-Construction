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

        $appResult = mysqli_query($conn, "SELECT * FROM Application WHERE ApplicationID = {$applicationId} LIMIT 1");
        $appRow = mysqli_fetch_assoc($appResult);

        if ($appRow && $appRow['ApplicationType'] === 'New Project') {
            $exists = mysqli_query($conn, "SELECT ProjectID FROM Project WHERE ApplicationID = {$applicationId} LIMIT 1");
            if (mysqli_num_rows($exists) === 0) {
                $desc = mysqli_real_escape_string($conn, $appRow['Description'] ?? 'Approved project.');
                $budget = $appRow['ProposalBudget'] !== null ? (float) $appRow['ProposalBudget'] : 'NULL';
                $budgetSql = $budget === 'NULL' ? 'NULL' : number_format($budget, 2, '.', '');
                $startDate = !empty($appRow['ProjectStartDate']) ? "'" . mysqli_real_escape_string($conn, $appRow['ProjectStartDate']) . "'" : 'NULL';
                $endDate = !empty($appRow['ProjectEndDate']) ? "'" . mysqli_real_escape_string($conn, $appRow['ProjectEndDate']) . "'" : 'NULL';

                mysqli_query($conn, "INSERT INTO Project (ApplicationID, ProposalDate, ProposalBudget, ProposalStatus, StartDate, EndDate, ProjectStatus, Description, ProjectPaymentStatus)
                                     VALUES ({$applicationId}, CURDATE(), {$budgetSql}, 'Approved', {$startDate}, {$endDate}, 'Ongoing', '{$desc}', 'Unpaid')");
                $projectId = (int) mysqli_insert_id($conn);

                if ($projectId > 0) {
                    $updateMsg = mysqli_real_escape_string($conn, 'Your project application has been approved. Your project is now active.');
                    mysqli_query($conn, "INSERT INTO Project_Update (ProjectID, EmployeeID, Status, Description, UpdateDate)
                                         VALUES ({$projectId}, {$employeeId}, 'Approved', '{$updateMsg}', CURDATE())");
                }
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
    "SELECT a.*, c.Client_FirstName, c.Client_LastName, eo.Name AS EquipmentName, eo.Model AS EquipmentModel
     FROM Application a
     JOIN Client c ON a.UserID = c.UserID
     LEFT JOIN Equipment e ON a.EquipmentID = e.EquipmentID
     LEFT JOIN EquipmentOffering eo ON e.EquipmentOfferingID = eo.EquipmentOfferingID
     ORDER BY a.SubmissionDate DESC"
);
?>

<div class="container mt-5">
    <h2>Application Review</h2>
    <p class="text-muted mb-4">Approve project applications to create a tracked project. Equipment rental approvals update fleet availability automatically.</p>

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

                <?php if ($app['ApplicationType'] === 'Equipment Rental'): ?>
                    <p class="card-text"><strong>Equipment:</strong> <?= htmlspecialchars(trim(($app['EquipmentName'] ?? 'Unknown') . ($app['EquipmentModel'] ? ' (' . $app['EquipmentModel'] . ')' : ''))) ?></p>
                    <p class="card-text"><strong>Rental Period:</strong> <?= htmlspecialchars($app['RentalStartDate'] ?? '—') ?> to <?= htmlspecialchars($app['RentalEndDate'] ?? '—') ?></p>
                    <p class="card-text"><strong>Operator Needed:</strong> <?= !empty($app['NeedsOperator']) ? 'Yes' : 'No' ?></p>
                <?php elseif ($app['ApplicationType'] === 'New Project'): ?>
                    <p class="card-text"><strong>Project Title:</strong> <?= htmlspecialchars($app['ProjectTitle'] ?? '—') ?></p>
                    <p class="card-text"><strong>Location:</strong> <?= htmlspecialchars($app['ProjectLocation'] ?? '—') ?></p>
                    <p class="card-text"><strong>Proposed Budget:</strong> <?= $app['ProposalBudget'] !== null ? '₱' . number_format((float) $app['ProposalBudget'], 2) : '—' ?></p>
                    <p class="card-text"><strong>Estimated Timeline:</strong> <?= htmlspecialchars($app['ProjectStartDate'] ?? '—') ?> to <?= htmlspecialchars($app['ProjectEndDate'] ?? '—') ?></p>
                <?php endif; ?>

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
