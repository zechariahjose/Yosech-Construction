<?php
include("../config/database.php");
include("../includes/admin/helpers.php");

adminRequireLogin('admin/amin_equipment.php');

$adminEmployee = adminCurrentEmployee($conn);
$adminPendingCount = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM Application WHERE Status = 'Pending'"))['total'];

// Availability updates are managed by the assigned manager, not the admin.

$result = mysqli_query(
    $conn,
    "SELECT e.*, eo.Name AS OfferingName, eo.Model, eo.DailyRate, eo.WeeklyRate
     FROM Equipment e
     LEFT JOIN EquipmentOffering eo ON e.EquipmentOfferingID = eo.EquipmentOfferingID
     ORDER BY e.EquipmentID ASC"
);

$totalEquipment = 0;
$availableCount = 0;
$rentedCount = 0;
$maintenanceCount = 0;
$rows = [];
while ($row = mysqli_fetch_assoc($result)) {
    $rows[] = $row;
    $totalEquipment++;
    if ($row['AvailabilityStatus'] === 'Available') {
        $availableCount++;
    } elseif ($row['AvailabilityStatus'] === 'Rented') {
        $rentedCount++;
    } else {
        $maintenanceCount++;
    }
}

$adminActiveNav = 'equipment';
$adminPageTitle = 'Equipment Fleet';
$adminPageSubtitle = 'Enterprise fleet management — total units, maintenance status, and global site allocation.';
$adminPageActions = '
    <a href="' . BASE_URL . '/equipment.php" class="admin-btn admin-btn-outline" target="_blank" rel="noopener">Public Fleet Page</a>
';

include("../includes/admin/layout_start.php");
?>

<div class="admin-kpi-grid" style="grid-template-columns:repeat(4,1fr);">
    <div class="admin-kpi-card">
        <div class="admin-kpi-label">Total Units</div>
        <div class="admin-kpi-value"><?= $totalEquipment ?></div>
    </div>
    <div class="admin-kpi-card">
        <div class="admin-kpi-label">Available</div>
        <div class="admin-kpi-value"><?= $availableCount ?></div>
    </div>
    <div class="admin-kpi-card">
        <div class="admin-kpi-label">Rented</div>
        <div class="admin-kpi-value"><?= $rentedCount ?></div>
    </div>
    <div class="admin-kpi-card">
        <div class="admin-kpi-label">Under Maintenance</div>
        <div class="admin-kpi-value"><?= $maintenanceCount ?></div>
    </div>
</div>

<div class="admin-panel">
    <?php if (count($rows) === 0): ?>
        <div class="admin-empty">
            <strong>No equipment records found</strong>
            Run the database migration to link fleet units to the equipment catalog.
        </div>
    <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Equipment</th>
                    <th>Availability</th>
                    <th>Operator</th>
                    <th>Payment</th>
                    <th>Rates</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row):
                    $statusClass = match ($row['AvailabilityStatus']) {
                        'Available' => 'admin-badge-track',
                        'Rented' => 'admin-badge-delay',
                        default => 'admin-badge-inspection',
                    };
                ?>
                <tr>
                    <td>#<?= (int) $row['EquipmentID'] ?></td>
                    <td>
                        <span class="admin-table-project"><?= htmlspecialchars($row['OfferingName'] ?? $row['Specification']) ?></span>
                        <?php if (!empty($row['Model'])): ?>
                            <span class="admin-table-sub"><?= htmlspecialchars($row['Model']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><span class="admin-badge <?= $statusClass ?>"><?= htmlspecialchars($row['AvailabilityStatus']) ?></span></td>
                    <td><?= $row['NeedsOperator'] ? 'Required' : 'No' ?></td>
                    <td><?= htmlspecialchars($row['EquipmentPaymentStatus']) ?></td>
                    <td>
                        <?php if ($row['DailyRate']): ?>
                            <span class="admin-table-sub">Daily ₱<?= number_format((float) $row['DailyRate'], 0) ?></span>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include("../includes/admin/layout_end.php"); ?>
