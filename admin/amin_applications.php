<?php
include("../config/database.php");
include("../includes/admin/helpers.php");

adminRequireLogin('admin/amin_applications.php');

$adminEmployee = adminCurrentEmployee($conn);
$adminPendingCount = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM Application WHERE Status = 'Pending'"))['total'];

$statusFilter = $_GET['status'] ?? '';
$querySql = "
    SELECT a.*, c.Client_FirstName, c.Client_LastName, c.Client_Email,
           eo.Name AS EquipmentName, eo.Model AS EquipmentModel
    FROM Application a
    JOIN Client c ON a.UserID = c.UserID
    LEFT JOIN Equipment e ON a.EquipmentID = e.EquipmentID
    LEFT JOIN EquipmentOffering eo ON e.EquipmentOfferingID = eo.EquipmentOfferingID
";

if ($statusFilter !== '') {
    $escStatus = mysqli_real_escape_string($conn, $statusFilter);
    $querySql .= " WHERE a.Status = '{$escStatus}'";
}

$querySql .= " ORDER BY a.SubmissionDate DESC";
$query = mysqli_query($conn, $querySql);

$adminActiveNav = 'history';
$adminPageTitle = 'History';
$adminPageSubtitle = 'View the full record of client project proposals and equipment rental requests. Approvals are handled by project managers.';
$adminPageActions = '
    <a href="' . BASE_URL . '/admin/amin_applications.php?status=Pending" class="admin-btn admin-btn-outline">Pending only</a>
    <a href="' . BASE_URL . '/admin/amin_applications.php?status=Approved" class="admin-btn admin-btn-outline">Approved</a>
    <a href="' . BASE_URL . '/admin/amin_applications.php" class="admin-btn admin-btn-outline">All records</a>
';

include("../includes/admin/layout_start.php");
?>

<?php if (mysqli_num_rows($query) === 0): ?>
    <div class="admin-alert admin-alert-info">No applications found<?= $statusFilter !== '' ? ' with status ' . htmlspecialchars($statusFilter) : '' ?>.</div>
<?php endif; ?>

<div class="admin-card-grid">
    <?php while ($app = mysqli_fetch_assoc($query)):
        $statusClass = match ($app['Status']) {
            'Approved' => 'admin-badge-approved',
            'Rejected' => 'admin-badge-rejected',
            default => 'admin-badge-pending',
        };
    ?>
    <div class="admin-card">
        <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
            <div>
                <h3 class="admin-card-title mb-1">Application #<?= (int) $app['ApplicationID'] ?></h3>
                <div class="small text-muted"><?= htmlspecialchars($app['ApplicationType']) ?></div>
            </div>
            <span class="admin-badge <?= $statusClass ?>"><?= htmlspecialchars($app['Status']) ?></span>
        </div>

        <div class="admin-meta-grid">
            <div class="admin-meta-item">
                <span>Client</span>
                <?= htmlspecialchars($app['Client_FirstName'] . ' ' . $app['Client_LastName']) ?>
            </div>
            <div class="admin-meta-item">
                <span>Submitted</span>
                <?= htmlspecialchars($app['SubmissionDate'] ?? '—') ?>
            </div>
            <div class="admin-meta-item">
                <span>Email</span>
                <?= htmlspecialchars($app['Client_Email']) ?>
            </div>
        </div>

        <?php if ($app['ApplicationType'] === 'Equipment Rental'): ?>
            <div class="admin-meta-grid">
                <div class="admin-meta-item">
                    <span>Equipment</span>
                    <?= htmlspecialchars(trim(($app['EquipmentName'] ?? 'Unknown') . ($app['EquipmentModel'] ? ' (' . $app['EquipmentModel'] . ')' : ''))) ?>
                </div>
                <div class="admin-meta-item">
                    <span>Rental Period</span>
                    <?= htmlspecialchars(($app['RentalStartDate'] ?? '—') . ' to ' . ($app['RentalEndDate'] ?? '—')) ?>
                </div>
                <div class="admin-meta-item">
                    <span>Operator Needed</span>
                    <?= !empty($app['NeedsOperator']) ? 'Yes' : 'No' ?>
                </div>
            </div>
        <?php elseif ($app['ApplicationType'] === 'New Project'): ?>
            <div class="admin-meta-grid">
                <div class="admin-meta-item">
                    <span>Project Title</span>
                    <?= htmlspecialchars($app['ProjectTitle'] ?? '—') ?>
                </div>
                <div class="admin-meta-item">
                    <span>Location</span>
                    <?= htmlspecialchars($app['ProjectLocation'] ?? '—') ?>
                </div>
                <div class="admin-meta-item">
                    <span>Proposed Budget</span>
                    <?= $app['ProposalBudget'] !== null ? '₱' . number_format((float) $app['ProposalBudget'], 2) : '—' ?>
                </div>
                <div class="admin-meta-item">
                    <span>Timeline</span>
                    <?= htmlspecialchars(($app['ProjectStartDate'] ?? '—') . ' to ' . ($app['ProjectEndDate'] ?? '—')) ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="admin-field">
            <label>Description</label>
            <div class="small" style="line-height:1.6;color:#475569;"><?= nl2br(htmlspecialchars($app['Description'])) ?></div>
        </div>

        <?php if ($app['Status'] === 'Pending'): ?>
            <p class="small text-muted mb-0">Awaiting project manager review.</p>
        <?php endif; ?>
    </div>
    <?php endwhile; ?>
</div>

<?php include("../includes/admin/layout_end.php"); ?>
