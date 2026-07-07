<?php
include("../config/database.php");
include("../includes/admin/helpers.php");

adminRequireLogin('admin/amin_equipment.php');

$adminEmployee     = adminCurrentEmployee($conn);
$adminPendingCount = (int) mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS total FROM Application WHERE Status = 'Pending'"))['total'];

// ── Fleet stats ─────────────────────────────────────────────
$result = mysqli_query($conn,
    "SELECT e.*, eo.Name AS OfferingName, eo.Model, eo.DailyRate, eo.WeeklyRate
     FROM Equipment e
     LEFT JOIN EquipmentOffering eo ON e.EquipmentOfferingID = eo.EquipmentOfferingID
     ORDER BY e.EquipmentID ASC"
);

$totalEquipment   = 0;
$availableCount   = 0;
$rentedCount      = 0;
$maintenanceCount = 0;
$rows             = [];
while ($row = mysqli_fetch_assoc($result)) {
    $rows[] = $row;
    $totalEquipment++;
    if ($row['AvailabilityStatus'] === 'Available')           $availableCount++;
    elseif ($row['AvailabilityStatus'] === 'Rented')          $rentedCount++;
    else                                                       $maintenanceCount++;
}

// ── Equipment addition history ──────────────────────────────
// Try to use DateAdded if the column exists, fall back gracefully if not
$hasDateAdded = false;
$colCheck = mysqli_query($conn,
    "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'EquipmentOffering' AND COLUMN_NAME = 'DateAdded'"
);
if (mysqli_num_rows($colCheck) > 0) $hasDateAdded = true;

$historyRows = [];
if ($hasDateAdded) {
    $hResult = mysqli_query($conn,
        "SELECT eo.EquipmentOfferingID, eo.Name, eo.Model, eo.AvailabilityStatus,
                eo.DailyRate, eo.ImageURL, eo.DateAdded
         FROM EquipmentOffering eo
         ORDER BY eo.DateAdded DESC"
    );
    while ($r = mysqli_fetch_assoc($hResult)) $historyRows[] = $r;
} else {
    // Fallback: list all offerings ordered by ID (no timestamp)
    $hResult = mysqli_query($conn,
        "SELECT eo.EquipmentOfferingID, eo.Name, eo.Model, eo.AvailabilityStatus,
                eo.DailyRate, eo.ImageURL, NULL AS DateAdded
         FROM EquipmentOffering eo
         ORDER BY eo.EquipmentOfferingID DESC"
    );
    while ($r = mysqli_fetch_assoc($hResult)) $historyRows[] = $r;
}

$adminActiveNav    = 'equipment';
$adminPageTitle    = 'Equipment Fleet';
$adminPageSubtitle = 'Fleet overview, maintenance status, and equipment addition history.';
$adminPageActions  = '';

include("../includes/admin/layout_start.php");

// Show migration notice if DateAdded column is missing
if (!$hasDateAdded): ?>
<div class="admin-alert admin-alert-info" style="margin-bottom:20px;display:flex;align-items:center;gap:10px;">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg>
    <span>Run <strong>config/migration_equipment_history.sql</strong> in phpMyAdmin to enable full timestamp tracking for equipment additions.</span>
</div>
<?php endif; ?>

<!-- ── KPI Cards ─────────────────────────────────────────── -->
<div class="admin-kpi-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:24px;">
    <div class="admin-kpi-card">
        <div class="admin-kpi-label">Total Units</div>
        <div class="admin-kpi-value"><?= $totalEquipment ?></div>
        <div class="admin-kpi-meta"><?= count($historyRows) ?> catalog entries</div>
    </div>
    <div class="admin-kpi-card">
        <div class="admin-kpi-label">Available</div>
        <div class="admin-kpi-value"><?= $availableCount ?></div>
        <div class="admin-kpi-meta">Ready for rental</div>
    </div>
    <div class="admin-kpi-card">
        <div class="admin-kpi-label">Rented</div>
        <div class="admin-kpi-value"><?= $rentedCount ?></div>
        <div class="admin-kpi-meta">Currently deployed</div>
    </div>
    <div class="admin-kpi-card">
        <div class="admin-kpi-label">Under Maintenance</div>
        <div class="admin-kpi-value"><?= $maintenanceCount ?></div>
        <div class="admin-kpi-meta">Off-line units</div>
    </div>
</div>

<!-- ── Fleet table ───────────────────────────────────────── -->
<div class="admin-panel" style="margin-bottom:32px;">
    <div class="admin-panel-head">
        <h2 class="admin-panel-title">Fleet Inventory</h2>
        <span class="admin-table-sub"><?= $totalEquipment ?> units</span>
    </div>
    <?php if (count($rows) === 0): ?>
        <div class="admin-empty">
            <strong>No equipment records found</strong>
            The PM can add equipment from the Project Manager console.
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
                    <th>Daily Rate</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row):
                    $statusClass = match($row['AvailabilityStatus']) {
                        'Available' => 'admin-badge-track',
                        'Rented'    => 'admin-badge-delay',
                        default     => 'admin-badge-inspection',
                    };
                ?>
                <tr>
                    <td>#<?= (int)$row['EquipmentID'] ?></td>
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
                            ₱<?= number_format((float)$row['DailyRate'], 0) ?>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- ── Equipment Addition History ────────────────────────── -->
<div class="admin-panel">
    <div class="admin-panel-head">
        <h2 class="admin-panel-title">Equipment Addition History</h2>
        <span class="admin-table-sub"><?= count($historyRows) ?> entries · added by Project Manager</span>
    </div>
    <?php if (count($historyRows) === 0): ?>
        <div class="admin-empty">
            <strong>No equipment added yet</strong>
            Equipment added by the Project Manager will appear here.
        </div>
    <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Equipment</th>
                    <th>Model</th>
                    <th>Daily Rate</th>
                    <th>Status</th>
                    <th>Date Added</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($historyRows as $h):
                    $imgSrc = !empty($h['ImageURL'])
                        ? BASE_URL . '/' . ltrim(htmlspecialchars($h['ImageURL']), '/')
                        : null;
                    $hBadge = match($h['AvailabilityStatus']) {
                        'Available'         => 'admin-badge-track',
                        'Unavailable'       => 'admin-badge-delay',
                        'Under Maintenance' => 'admin-badge-inspection',
                        default             => 'admin-badge-track',
                    };
                ?>
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <?php if ($imgSrc): ?>
                                <img src="<?= $imgSrc ?>" alt=""
                                     style="width:38px;height:30px;object-fit:cover;border-radius:4px;flex-shrink:0;border:1px solid var(--admin-border);">
                            <?php endif; ?>
                            <span class="admin-table-project"><?= htmlspecialchars($h['Name']) ?></span>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($h['Model'] ?? '—') ?></td>
                    <td><?= $h['DailyRate'] ? '₱' . number_format((float)$h['DailyRate'], 0) : '—' ?></td>
                    <td><span class="admin-badge <?= $hBadge ?>"><?= htmlspecialchars($h['AvailabilityStatus']) ?></span></td>
                    <td>
                        <?php if (!empty($h['DateAdded']) && $h['DateAdded'] !== null): ?>
                            <span class="admin-table-project" style="font-weight:500;">
                                <?= date('M d, Y', strtotime($h['DateAdded'])) ?>
                            </span>
                            <span class="admin-table-sub"><?= date('h:i A', strtotime($h['DateAdded'])) ?></span>
                        <?php else: ?>
                            <span class="admin-table-sub">Run migration to track dates</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include("../includes/admin/layout_end.php"); ?>
