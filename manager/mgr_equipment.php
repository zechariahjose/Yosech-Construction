<?php
include("../config/database.php");
include("../includes/manager/helpers.php");

managerRequireLogin('manager/mgr_equipment.php');

$mgrEmployee       = adminCurrentEmployee($conn);
$mgrPendingRentals = managerPendingRentals($conn);

$successMsg = '';
$errorMsg   = '';

// ── UPDATE EQUIPMENT AVAILABILITY ──────────────────────────
if (isset($_POST['update_availability'], $_POST['equipment_offering_id'])) {
    $eoID     = (int) $_POST['equipment_offering_id'];
    $newStatus = mysqli_real_escape_string($conn, $_POST['availability_status']);
    // Update EquipmentOffering (drives the website display)
    mysqli_query($conn, "UPDATE EquipmentOffering SET AvailabilityStatus='{$newStatus}' WHERE EquipmentOfferingID={$eoID}");
    // Also sync the linked Equipment unit
    mysqli_query($conn, "UPDATE Equipment SET AvailabilityStatus=CASE '{$newStatus}'
        WHEN 'Available' THEN 'Available'
        WHEN 'Unavailable' THEN 'Rented'
        ELSE 'Under Maintenance'
        END WHERE EquipmentOfferingID={$eoID}");
    $successMsg = "Equipment availability updated.";
}

// ── FETCH EQUIPMENT CATALOG (mirrors website) ───────────────
$catalogResult = mysqli_query($conn,
    "SELECT eo.*,
            e.EquipmentID, e.AvailabilityStatus AS UnitStatus, e.NeedsOperator, e.EquipmentPaymentStatus
     FROM EquipmentOffering eo
     LEFT JOIN Equipment e ON e.EquipmentOfferingID = eo.EquipmentOfferingID
     ORDER BY eo.EquipmentOfferingID ASC"
);
$catalogRows    = [];
$totalUnits     = 0;
$availableCount = 0;
$unavailCount   = 0;
$maintCount     = 0;

while ($row = mysqli_fetch_assoc($catalogResult)) {
    $catalogRows[] = $row;
    $totalUnits++;
    match ($row['AvailabilityStatus']) {
        'Available'          => $availableCount++,
        'Unavailable'        => $unavailCount++,
        'Under Maintenance'  => $maintCount++,
        default              => null,
    };
}

// ── FETCH ACTIVE RENTAL ASSIGNMENTS ────────────────────────
$assignmentsResult = mysqli_query($conn,
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

$assignmentRows  = [];
$activeAssignments = 0;
$endingSoon      = 0;
$today           = date('Y-m-d');
$soonDate        = date('Y-m-d', strtotime('+7 days'));

while ($row = mysqli_fetch_assoc($assignmentsResult)) {
    $assignmentRows[] = $row;
    if ($row['AppStatus'] === 'Approved') $activeAssignments++;
    if (!empty($row['RentalEndDate']) && $row['RentalEndDate'] <= $soonDate && $row['RentalEndDate'] >= $today) $endingSoon++;
}

$mgrActiveNav    = 'equipment';
$mgrPageTitle    = 'Equipment';
$mgrPageSubtitle = 'Manage the equipment catalog displayed on the website and track active rental assignments.';
$mgrPageActions  = '
    <a href="' . BASE_URL . '/manager/mgr_applications.php?type=rental&status=Pending" class="admin-btn admin-btn-primary">Pending Assignments</a>
    <a href="' . BASE_URL . '/equipment.php" class="admin-btn admin-btn-outline" target="_blank" rel="noopener">View Website →</a>
';

include("../includes/manager/layout_start.php");
?>

<?php if ($successMsg): ?>
    <div class="admin-alert admin-alert-success" style="margin-bottom:16px;"><?= htmlspecialchars($successMsg) ?></div>
<?php endif; ?>
<?php if ($errorMsg): ?>
    <div class="admin-alert admin-alert-danger" style="margin-bottom:16px;"><?= htmlspecialchars($errorMsg) ?></div>
<?php endif; ?>

<!-- ── KPI Cards ─────────────────────────────────────────── -->
<div class="admin-kpi-grid">
    <div class="admin-kpi-card manager-kpi-ok">
        <div class="admin-kpi-label">Total Units</div>
        <div class="admin-kpi-value"><?= $totalUnits ?></div>
        <div class="admin-kpi-meta">In the catalog</div>
    </div>
    <div class="admin-kpi-card manager-kpi-ok">
        <div class="admin-kpi-label">Available</div>
        <div class="admin-kpi-value"><?= $availableCount ?></div>
        <div class="admin-kpi-meta">Ready for rental</div>
    </div>
    <div class="admin-kpi-card manager-kpi-warn">
        <div class="admin-kpi-label">Active Assignments</div>
        <div class="admin-kpi-value"><?= $activeAssignments ?></div>
        <div class="admin-kpi-meta">Approved rentals in the field</div>
    </div>
    <div class="admin-kpi-card <?= $endingSoon > 0 ? 'manager-kpi-warn' : '' ?>">
        <div class="admin-kpi-label">Ending Within 7 Days</div>
        <div class="admin-kpi-value"><?= $endingSoon ?></div>
        <div class="admin-kpi-meta">Schedule returns or extensions</div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════
     SECTION 1 — EQUIPMENT CATALOG (mirrors website)
     ══════════════════════════════════════════════════════════ -->
<div style="margin-bottom:8px;">
    <h2 class="admin-page-title" style="font-size:1rem;margin-bottom:4px;">Equipment Catalog</h2>
    <p class="admin-page-sub" style="margin-bottom:16px;">
        The catalog displayed on the public website. Update availability status here — changes reflect on the site immediately.
    </p>
</div>

<?php if (count($catalogRows) === 0): ?>
    <div class="admin-alert admin-alert-info" style="margin-bottom:32px;">No equipment in the catalog.</div>
<?php else: ?>
<div class="admin-card-grid" style="margin-bottom:40px;">
    <?php foreach ($catalogRows as $eq):
        $statusMap = [
            'Available'         => ['class' => 'admin-badge-track',      'label' => 'Available'],
            'Unavailable'       => ['class' => 'admin-badge-delay',      'label' => 'Unavailable'],
            'Under Maintenance' => ['class' => 'admin-badge-inspection', 'label' => 'Under Maintenance'],
        ];
        $eqBadge = $statusMap[$eq['AvailabilityStatus']] ?? ['class' => 'admin-badge-track', 'label' => $eq['AvailabilityStatus']];
        $imgSrc  = !empty($eq['ImageURL'])
            ? BASE_URL . '/' . ltrim(htmlspecialchars($eq['ImageURL']), '/')
            : null;

        // Parse specs (dot-separated)
        $specs = !empty($eq['Specs'])
            ? array_filter(array_map('trim', explode('·', $eq['Specs'])))
            : [];
    ?>
    <div class="admin-card">

        <!-- Card header -->
        <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
            <div style="display:flex;align-items:center;gap:12px;">
                <?php if ($imgSrc): ?>
                    <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($eq['Name']) ?>"
                         style="width:56px;height:46px;object-fit:cover;border-radius:5px;flex-shrink:0;border:1px solid var(--admin-border);">
                <?php endif; ?>
                <div>
                    <h3 class="admin-card-title mb-1"><?= htmlspecialchars($eq['Name']) ?></h3>
                    <span class="admin-table-sub"><?= htmlspecialchars($eq['Model'] ?? '—') ?> · ID #<?= (int) $eq['EquipmentOfferingID'] ?></span>
                </div>
            </div>
            <span class="admin-badge <?= $eqBadge['class'] ?>"><?= $eqBadge['label'] ?></span>
        </div>

        <!-- Description -->
        <?php if (!empty($eq['Description'])): ?>
        <div class="admin-field">
            <label>Description</label>
            <div class="small text-muted" style="line-height:1.6;"><?= htmlspecialchars($eq['Description']) ?></div>
        </div>
        <?php endif; ?>

        <!-- Specs -->
        <?php if (!empty($specs)): ?>
        <div class="admin-meta-grid" style="margin-bottom:12px;">
            <?php foreach (array_slice($specs, 0, 4) as $spec):
                $parts = explode(':', $spec, 2);
            ?>
            <div class="admin-meta-item">
                <span><?= htmlspecialchars(trim($parts[0])) ?></span>
                <?= htmlspecialchars(trim($parts[1] ?? '—')) ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Rates -->
        <div class="admin-meta-grid" style="margin-bottom:14px;">
            <div class="admin-meta-item"><span>Daily Rate</span>₱<?= number_format((float)$eq['DailyRate'], 0) ?></div>
            <div class="admin-meta-item"><span>Weekly Rate</span>₱<?= number_format((float)$eq['WeeklyRate'], 0) ?></div>
            <div class="admin-meta-item"><span>Monthly Rate</span>₱<?= number_format((float)$eq['MonthlyRate'], 0) ?></div>
            <div class="admin-meta-item"><span>Hourly Rate</span>₱<?= number_format((float)$eq['HourlyRate'], 0) ?></div>
        </div>

        <!-- Update availability -->
        <form method="POST" class="d-flex gap-2 align-items-end mb-0 js-track-form">
            <input type="hidden" name="equipment_offering_id" value="<?= (int) $eq['EquipmentOfferingID'] ?>">
            <div class="admin-field mb-0 flex-grow-1">
                <label>Availability</label>
                <select name="availability_status" data-original="<?= htmlspecialchars($eq['AvailabilityStatus']) ?>">
                    <option value="Available"        <?= $eq['AvailabilityStatus'] === 'Available'        ? 'selected' : '' ?>>Available</option>
                    <option value="Unavailable"      <?= $eq['AvailabilityStatus'] === 'Unavailable'      ? 'selected' : '' ?>>Unavailable</option>
                    <option value="Under Maintenance"<?= $eq['AvailabilityStatus'] === 'Under Maintenance'? 'selected' : '' ?>>Under Maintenance</option>
                </select>
            </div>
            <button type="submit" name="update_availability" class="admin-btn admin-btn-primary admin-btn-sm" disabled>Save</button>
        </form>

    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<hr class="admin-divider" style="margin-bottom:32px;">

<!-- ══════════════════════════════════════════════════════════
     SECTION 2 — ACTIVE RENTAL ASSIGNMENTS
     ══════════════════════════════════════════════════════════ -->
<div style="margin-bottom:8px;">
    <h2 class="admin-page-title" style="font-size:1rem;margin-bottom:4px;">Active Rental Assignments</h2>
    <p class="admin-page-sub" style="margin-bottom:16px;">Approved and pending rentals currently in the field or awaiting deployment.</p>
</div>

<div class="admin-panel">
    <?php if (count($assignmentRows) === 0): ?>
        <div class="admin-empty">
            <strong>No active rental assignments</strong>
            Approved rental applications will appear here with their rental periods.
        </div>
    <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Equipment</th>
                    <th>Client</th>
                    <th>Rental Period</th>
                    <th>Operator</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($assignmentRows as $row):
                    $statusClass = $row['AppStatus'] === 'Approved' ? 'admin-badge-track' : 'admin-badge-pending';
                    $daysLeft    = !empty($row['RentalEndDate'])
                        ? (int) floor((strtotime($row['RentalEndDate']) - time()) / 86400)
                        : null;
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
                            <span class="admin-table-sub <?= $daysLeft <= 7 && $daysLeft >= 0 ? 'text-warning' : '' ?>">
                                <?= $daysLeft >= 0 ? $daysLeft . ' days remaining' : 'Rental ended' ?>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td><?= !empty($row['NeedsOperator']) ? 'Required' : '—' ?></td>
                    <td>
                        <span class="admin-badge <?= $statusClass ?>"><?= htmlspecialchars($row['AppStatus']) ?></span>
                        <?php if (!empty($row['AvailabilityStatus'])): ?>
                            <span class="admin-table-sub">Unit: <?= htmlspecialchars($row['AvailabilityStatus']) ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<style>
.admin-btn:disabled {
    opacity: 0.45;
    cursor: not-allowed;
    pointer-events: none;
}
.text-warning { color: #d97706 !important; font-weight: 600; }
</style>

<script>
(function () {
    document.querySelectorAll('.js-track-form').forEach(function (form) {
        var btn     = form.querySelector('button[type="submit"]');
        var tracked = form.querySelectorAll('[data-original]');
        if (!btn || !tracked.length) return;

        function check() {
            btn.disabled = !Array.from(tracked).some(function (el) {
                return el.value !== el.dataset.original;
            });
        }

        tracked.forEach(function (el) {
            el.addEventListener('change', check);
            el.addEventListener('input',  check);
        });
        check();
    });
})();
</script>

<?php include("../includes/manager/layout_end.php"); ?>
