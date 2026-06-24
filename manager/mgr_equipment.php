<?php
include("../config/database.php");
include("../includes/manager/helpers.php");

managerRequireLogin('manager/mgr_equipment.php');

$mgrEmployee = adminCurrentEmployee($conn);
$mgrPendingRentals = managerPendingRentals($conn);

$result = mysqli_query(
    $conn,
    "SELECT a.ApplicationID, a.Status AS AppStatus, a.RentalStartDate, a.RentalEndDate, a.NeedsOperator,
            c.Client_FirstName, c.Client_LastName,
            eo.Name AS EquipmentName, eo.Model,
            e.EquipmentID, e.AvailabilityStatus
     FROM Application a
     JOIN Client c ON a.UserID = c.UserID
     LEFT JOIN Equipment e ON a.EquipmentID = e.EquipmentID
     LEFT JOIN EquipmentOffering eo ON e.EquipmentOfferingID = eo.EquipmentOfferingID
     WHERE a.ApplicationType = 'Equipment Rental'
       AND a.Status IN ('Approved', 'Pending')
       AND (a.RentalEndDate IS NULL OR a.RentalEndDate >= CURDATE())
     ORDER BY a.RentalStartDate ASC, a.ApplicationID DESC"
);

$activeAssignments = 0;
$endingSoon = 0;
$rows = [];
$today = date('Y-m-d');
$soonDate = date('Y-m-d', strtotime('+7 days'));

while ($row = mysqli_fetch_assoc($result)) {
    $rows[] = $row;
    if ($row['AppStatus'] === 'Approved') {
        $activeAssignments++;
    }
    if (!empty($row['RentalEndDate']) && $row['RentalEndDate'] <= $soonDate && $row['RentalEndDate'] >= $today) {
        $endingSoon++;
    }
}

$mgrActiveNav = 'equipment';
$mgrPageTitle = 'Assigned Equipment';
$mgrPageSubtitle = 'Track assets allocated to your active sites and monitor rental durations — not the full company fleet.';
$mgrPageActions = '
    <a href="' . BASE_URL . '/manager/mgr_applications.php?type=rental&status=Pending" class="admin-btn admin-btn-primary">Pending Assignments</a>
';

include("../includes/manager/layout_start.php");
?>

<div class="admin-kpi-grid" style="grid-template-columns:repeat(3,1fr);">
    <div class="admin-kpi-card">
        <div class="admin-kpi-label">Active Assignments</div>
        <div class="admin-kpi-value"><?= $activeAssignments ?></div>
        <div class="admin-kpi-meta">Approved rentals in the field</div>
    </div>
    <div class="admin-kpi-card manager-kpi-warn">
        <div class="admin-kpi-label">Ending Within 7 Days</div>
        <div class="admin-kpi-value"><?= $endingSoon ?></div>
        <div class="admin-kpi-meta">Schedule returns or extensions</div>
    </div>
    <div class="admin-kpi-card">
        <div class="admin-kpi-label">Pending Requests</div>
        <div class="admin-kpi-value"><?= $mgrPendingRentals ?></div>
        <div class="admin-kpi-meta">Awaiting approval</div>
    </div>
</div>

<div class="admin-panel">
    <?php if (count($rows) === 0): ?>
        <div class="admin-empty">
            <strong>No equipment assigned to your sites</strong>
            Approved rental applications will appear here with their rental periods.
        </div>
    <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Equipment</th>
                    <th>Assigned To / Client</th>
                    <th>Rental Period</th>
                    <th>Operator</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row):
                    $statusClass = $row['AppStatus'] === 'Approved' ? 'admin-badge-track' : 'admin-badge-pending';
                    $daysLeft = !empty($row['RentalEndDate']) ? (int) floor((strtotime($row['RentalEndDate']) - time()) / 86400) : null;
                ?>
                <tr>
                    <td>
                        <span class="admin-table-project"><?= htmlspecialchars($row['EquipmentName'] ?? 'Equipment #' . $row['EquipmentID']) ?></span>
                        <?php if (!empty($row['Model'])): ?>
                            <span class="admin-table-sub"><?= htmlspecialchars($row['Model']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="admin-table-project"><?= htmlspecialchars($row['Client_FirstName'] . ' ' . $row['Client_LastName']) ?></span>
                        <span class="admin-table-sub">App #<?= (int) $row['ApplicationID'] ?></span>
                    </td>
                    <td>
                        <?= htmlspecialchars(($row['RentalStartDate'] ?? '—') . ' → ' . ($row['RentalEndDate'] ?? '—')) ?>
                        <?php if ($daysLeft !== null && $row['AppStatus'] === 'Approved'): ?>
                            <span class="admin-table-sub"><?= $daysLeft >= 0 ? $daysLeft . ' days remaining' : 'Rental ended' ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?= !empty($row['NeedsOperator']) ? 'Required' : '—' ?></td>
                    <td>
                        <span class="admin-badge <?= $statusClass ?>"><?= htmlspecialchars($row['AppStatus']) ?></span>
                        <?php if (!empty($row['AvailabilityStatus'])): ?>
                            <span class="admin-table-sub">Fleet: <?= htmlspecialchars($row['AvailabilityStatus']) ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<p class="small text-muted mt-3">Fleet maintenance and global allocation are managed in the <strong>Admin Equipment</strong> console.</p>

<?php include("../includes/manager/layout_end.php"); ?>
