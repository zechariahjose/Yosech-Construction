<?php
include("../config/database.php");
include("../includes/manager/helpers.php");

managerRequireLogin('manager/mgr_applications.php');

$mgrEmployee = adminCurrentEmployee($conn);
$mgrPendingRentals = managerPendingRentals($conn);
$employeeId = (int) $_SESSION['user_id'];

if (isset($_POST['action'], $_POST['application_id'])) {
    $applicationId = (int) $_POST['application_id'];

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
                    $updateMsg = mysqli_real_escape_string($conn, 'Project approved and assigned to field operations.');
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

$typeFilter = $_GET['type'] ?? '';
$statusFilter = $_GET['status'] ?? 'Pending';

$querySql = "
    SELECT a.*, c.Client_FirstName, c.Client_LastName, c.Client_Email, c.Client_ContactNumber,
           eo.Name AS EquipmentName, eo.Model AS EquipmentModel
    FROM Application a
    JOIN Client c ON a.UserID = c.UserID
    LEFT JOIN Equipment e ON a.EquipmentID = e.EquipmentID
    LEFT JOIN EquipmentOffering eo ON e.EquipmentOfferingID = eo.EquipmentOfferingID
    WHERE 1=1
";

if ($statusFilter !== '') {
    $escStatus = mysqli_real_escape_string($conn, $statusFilter);
    $querySql .= " AND a.Status = '{$escStatus}'";
}

if ($typeFilter === 'rental') {
    $querySql .= " AND a.ApplicationType = 'Equipment Rental'";
} elseif ($typeFilter === 'project') {
    $querySql .= " AND a.ApplicationType = 'New Project'";
}

$querySql .= " ORDER BY a.SubmissionDate ASC";
$query = mysqli_query($conn, $querySql);

$mgrActiveNav = 'applications';
$mgrPageTitle = 'Application Review';
$mgrPageSubtitle = 'Review pending project proposals and equipment rental requests for your active sites.';
$mgrPageActions = '
    <a href="' . BASE_URL . '/manager/mgr_applications.php?type=rental&status=Pending" class="admin-btn admin-btn-primary">Pending Rentals</a>
    <a href="' . BASE_URL . '/manager/mgr_applications.php?type=project&status=Pending" class="admin-btn admin-btn-outline">Pending Projects</a>
    <a href="' . BASE_URL . '/manager/mgr_applications.php" class="admin-btn admin-btn-outline">All Pending</a>
';

include("../includes/manager/layout_start.php");
?>

<?php if (mysqli_num_rows($query) === 0): ?>
    <div class="admin-alert admin-alert-info">No applications in this queue.</div>
<?php endif; ?>

<div class="admin-card-grid">
    <?php while ($app = mysqli_fetch_assoc($query)):
        $isRental = $app['ApplicationType'] === 'Equipment Rental';
        $statusClass = match ($app['Status']) {
            'Approved' => 'admin-badge-approved',
            'Rejected' => 'admin-badge-rejected',
            default => $isRental ? 'admin-badge-rental' : 'admin-badge-pending',
        };
    ?>
    <div class="admin-card">
        <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
            <div>
                <h3 class="admin-card-title mb-1"><?= $isRental ? 'Rental Request' : 'Project Proposal' ?> #<?= (int) $app['ApplicationID'] ?></h3>
                <span class="admin-badge <?= $statusClass ?>"><?= htmlspecialchars($app['Status']) ?></span>
            </div>
        </div>

        <div class="admin-meta-grid">
            <div class="admin-meta-item"><span>Client</span><?= htmlspecialchars($app['Client_FirstName'] . ' ' . $app['Client_LastName']) ?></div>
            <div class="admin-meta-item"><span>Contact</span><?= htmlspecialchars($app['Client_ContactNumber'] ?: $app['Client_Email']) ?></div>
            <div class="admin-meta-item"><span>Submitted</span><?= htmlspecialchars($app['SubmissionDate'] ?? '—') ?></div>
        </div>

        <?php if ($isRental): ?>
            <div class="admin-meta-grid">
                <div class="admin-meta-item"><span>Equipment</span><?= htmlspecialchars(trim(($app['EquipmentName'] ?? '—') . ($app['EquipmentModel'] ? ' (' . $app['EquipmentModel'] . ')' : ''))) ?></div>
                <div class="admin-meta-item"><span>Rental Period</span><?= htmlspecialchars(($app['RentalStartDate'] ?? '—') . ' → ' . ($app['RentalEndDate'] ?? '—')) ?></div>
                <div class="admin-meta-item"><span>Operator</span><?= !empty($app['NeedsOperator']) ? 'Required' : 'Not required' ?></div>
            </div>
        <?php else: ?>
            <div class="admin-meta-grid">
                <div class="admin-meta-item"><span>Project Title</span><?= htmlspecialchars($app['ProjectTitle'] ?? '—') ?></div>
                <div class="admin-meta-item"><span>Site Location</span><?= htmlspecialchars($app['ProjectLocation'] ?? '—') ?></div>
                <div class="admin-meta-item"><span>Timeline</span><?= htmlspecialchars(($app['ProjectStartDate'] ?? '—') . ' → ' . ($app['ProjectEndDate'] ?? '—')) ?></div>
                <div class="admin-meta-item"><span>Proposed Budget</span><?= $app['ProposalBudget'] !== null ? '₱' . number_format((float) $app['ProposalBudget'], 2) : '—' ?></div>
            </div>
        <?php endif; ?>

        <div class="admin-field">
            <label>Details</label>
            <div class="small text-muted" style="line-height:1.6;"><?= nl2br(htmlspecialchars($app['Description'])) ?></div>
        </div>

        <?php if ($app['Status'] === 'Pending'): ?>
        <form method="post" class="d-flex gap-2">
            <input type="hidden" name="application_id" value="<?= (int) $app['ApplicationID'] ?>">
            <button type="submit" name="action" value="approve" class="admin-btn admin-btn-success admin-btn-sm">Approve for Site</button>
            <button type="submit" name="action" value="reject" class="admin-btn admin-btn-danger admin-btn-sm">Decline</button>
        </form>
        <?php endif; ?>
    </div>
    <?php endwhile; ?>
</div>

<?php include("../includes/manager/layout_end.php"); ?>
