<?php
include("../config/database.php");
include("../includes/manager/helpers.php");

managerRequireLogin('manager/mgr_equipment.php');

$mgrEmployee       = adminCurrentEmployee($conn);
$mgrPendingRentals = managerPendingRentals($conn);

$successMsg     = '';
$errorMsg       = '';
$reopenAddModal = false;

// ── ADD EQUIPMENT ───────────────────────────────────────────
if (isset($_POST['add_equipment'])) {
    $name    = trim($_POST['eq_name']    ?? '');
    $model   = trim($_POST['eq_model']   ?? '');
    $desc    = trim($_POST['eq_description'] ?? '');
    $specs   = trim($_POST['eq_specs']   ?? '');
    $hourly  = (float) ($_POST['eq_hourly']  ?? 0);
    $daily   = (float) ($_POST['eq_daily']   ?? 0);
    $weekly  = (float) ($_POST['eq_weekly']  ?? 0);
    $monthly = (float) ($_POST['eq_monthly'] ?? 0);
    $status  = $_POST['eq_availability'] ?? 'Available';

    if ($name === '') {
        $errorMsg = "Equipment name is required.";
        $reopenAddModal = true;
    } else {
        // Handle photo upload
        $imgPath = null;
        if (!empty($_FILES['eq_photo']['name'])) {
            $uploadDir = dirname(__DIR__) . '/assets/equipment/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $ext = strtolower(pathinfo($_FILES['eq_photo']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png','webp'])) {
                $errorMsg = "Invalid image format. Use JPG, PNG, or WEBP.";
                $reopenAddModal = true;
            } else {
                $filename = 'eq_' . time() . '_' . preg_replace('/[^a-z0-9]/', '', strtolower($name)) . '.' . $ext;
                $destAbs  = $uploadDir . $filename;
                if (move_uploaded_file($_FILES['eq_photo']['tmp_name'], $destAbs)) {
                    $imgPath = 'assets/equipment/' . $filename;
                } else {
                    $errorMsg = "Failed to upload image. Please try again.";
                    $reopenAddModal = true;
                }
            }
        }

        if (!$errorMsg) {
            $eName   = mysqli_real_escape_string($conn, $name);
            $eModel  = mysqli_real_escape_string($conn, $model);
            $eDesc   = mysqli_real_escape_string($conn, $desc);
            $eSpecs  = mysqli_real_escape_string($conn, $specs);
            $eStatus = mysqli_real_escape_string($conn, $status);
            $eImg    = $imgPath ? "'" . mysqli_real_escape_string($conn, $imgPath) . "'" : 'NULL';

            // Insert into EquipmentOffering (drives public website)
            mysqli_query($conn,
                "INSERT INTO EquipmentOffering
                 (Name, Model, Description, Specs, HourlyRate, DailyRate, WeeklyRate, MonthlyRate, AvailabilityStatus, ImageURL, DateAdded)
                 VALUES ('{$eName}', '{$eModel}', '{$eDesc}', '{$eSpecs}',
                         {$hourly}, {$daily}, {$weekly}, {$monthly}, '{$eStatus}', {$eImg}, NOW())"
            );
            $newEoId = (int) mysqli_insert_id($conn);

            if ($newEoId > 0) {
                // Insert matching Equipment unit
                $unitStatus = match($status) {
                    'Available'         => 'Available',
                    'Under Maintenance' => 'Under Maintenance',
                    default             => 'Rented',
                };
                $specStr = mysqli_real_escape_string($conn,
                    $name . ($model ? " ({$model})" : '')
                );
                mysqli_query($conn,
                    "INSERT INTO Equipment
                     (EquipmentOfferingID, Specification, AvailabilityStatus, NeedsOperator, EquipmentPaymentStatus)
                     VALUES ({$newEoId}, '{$specStr}', '{$unitStatus}', 0, 'Unpaid')"
                );
                $successMsg = "Equipment \"{$name}\" added to the catalog.";
            } else {
                $errorMsg = "Failed to add equipment. The name may already exist.";
                $reopenAddModal = true;
            }
        }
    }
}

// ── EDIT EQUIPMENT DETAILS ──────────────────────────────────
if (isset($_POST['edit_equipment'], $_POST['equipment_offering_id'])) {
    $eoID    = (int) $_POST['equipment_offering_id'];
    $name    = mysqli_real_escape_string($conn, trim($_POST['edit_name']));
    $model   = mysqli_real_escape_string($conn, trim($_POST['edit_model']));
    $desc    = mysqli_real_escape_string($conn, trim($_POST['edit_description']));
    $specs   = mysqli_real_escape_string($conn, trim($_POST['edit_specs']));
    $hourly  = (float) $_POST['edit_hourly'];
    $daily   = (float) $_POST['edit_daily'];
    $weekly  = (float) $_POST['edit_weekly'];
    $monthly = (float) $_POST['edit_monthly'];
    $status  = mysqli_real_escape_string($conn, $_POST['edit_availability']);

    if ($name !== '') {
        mysqli_query($conn,
            "UPDATE EquipmentOffering
             SET Name='{$name}', Model='{$model}', Description='{$desc}', Specs='{$specs}',
                 HourlyRate={$hourly}, DailyRate={$daily}, WeeklyRate={$weekly}, MonthlyRate={$monthly},
                 AvailabilityStatus='{$status}'
             WHERE EquipmentOfferingID={$eoID}"
        );
        // Sync linked Equipment unit availability
        $unitStatus = match($status) {
            'Available'         => 'Available',
            'Under Maintenance' => 'Under Maintenance',
            default             => 'Rented',
        };
        mysqli_query($conn, "UPDATE Equipment SET AvailabilityStatus='{$unitStatus}' WHERE EquipmentOfferingID={$eoID}");
        $successMsg = "Equipment updated successfully.";
    } else {
        $errorMsg = "Name cannot be empty.";
    }
}

// ── UPDATE EQUIPMENT AVAILABILITY (quick) ──────────────────
if (isset($_POST['update_availability'], $_POST['equipment_offering_id'])) {
    $eoID      = (int) $_POST['equipment_offering_id'];
    $newStatus = mysqli_real_escape_string($conn, $_POST['availability_status']);
    mysqli_query($conn, "UPDATE EquipmentOffering SET AvailabilityStatus='{$newStatus}' WHERE EquipmentOfferingID={$eoID}");
    $unitStatus = match($newStatus) {
        'Available'         => 'Available',
        'Under Maintenance' => 'Under Maintenance',
        default             => 'Rented',
    };
    mysqli_query($conn, "UPDATE Equipment SET AvailabilityStatus='{$unitStatus}' WHERE EquipmentOfferingID={$eoID}");
    $successMsg = "Availability updated.";
}

// ── FETCH EQUIPMENT CATALOG ─────────────────────────────────
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
        'Available'         => $availableCount++,
        'Unavailable'       => $unavailCount++,
        'Under Maintenance' => $maintCount++,
        default             => null,
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

$assignmentRows    = [];
$activeAssignments = 0;
$endingSoon        = 0;
$today             = date('Y-m-d');
$soonDate          = date('Y-m-d', strtotime('+7 days'));

while ($row = mysqli_fetch_assoc($assignmentsResult)) {
    $assignmentRows[] = $row;
    if ($row['AppStatus'] === 'Approved') $activeAssignments++;
    if (!empty($row['RentalEndDate']) && $row['RentalEndDate'] <= $soonDate && $row['RentalEndDate'] >= $today) $endingSoon++;
}

$mgrActiveNav    = 'equipment';
$mgrPageTitle    = 'Equipment';
$mgrPageSubtitle = 'Manage the equipment catalog and track active rental assignments.';
$mgrPageActions  = '
    <a href="' . BASE_URL . '/manager/mgr_applications.php?type=rental&status=Pending" class="admin-btn admin-btn-outline">Pending Assignments</a>
    <button class="admin-btn admin-btn-primary" onclick="document.getElementById(\'addEquipmentModal\').style.display=\'flex\'">+ Add Equipment</button>
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
     SECTION 1 — EQUIPMENT CATALOG
     ══════════════════════════════════════════════════════════ -->
<div style="margin-bottom:8px;">
    <h2 class="admin-page-title" style="font-size:1rem;margin-bottom:4px;">Equipment Catalog</h2>
    <p class="admin-page-sub" style="margin-bottom:16px;">
        The catalog displayed on the public website. Edit details or update availability — changes reflect on the site immediately.
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
        $eqBadge  = $statusMap[$eq['AvailabilityStatus']] ?? ['class' => 'admin-badge-track', 'label' => $eq['AvailabilityStatus']];
        $imgSrc   = !empty($eq['ImageURL'])
            ? BASE_URL . '/' . ltrim(htmlspecialchars($eq['ImageURL']), '/')
            : null;
        $specs    = !empty($eq['Specs'])
            ? array_filter(array_map('trim', explode('·', $eq['Specs'])))
            : [];
        $editId   = 'editEq_' . (int) $eq['EquipmentOfferingID'];
    ?>
    <div class="admin-card">

        <!-- Header -->
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
            <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;">
                <span class="admin-badge <?= $eqBadge['class'] ?>"><?= $eqBadge['label'] ?></span>
                <button type="button" class="admin-btn admin-btn-outline admin-btn-sm"
                        onclick="document.getElementById('<?= $editId ?>').style.display='flex'">
                    Edit
                </button>
            </div>
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
            <div class="admin-meta-item"><span>Daily</span>₱<?= number_format((float)$eq['DailyRate'], 0) ?></div>
            <div class="admin-meta-item"><span>Weekly</span>₱<?= number_format((float)$eq['WeeklyRate'], 0) ?></div>
            <div class="admin-meta-item"><span>Monthly</span>₱<?= number_format((float)$eq['MonthlyRate'], 0) ?></div>
            <div class="admin-meta-item"><span>Hourly</span>₱<?= number_format((float)$eq['HourlyRate'], 0) ?></div>
        </div>

        <!-- Quick availability update -->
        <form method="POST" class="d-flex gap-2 align-items-end mb-0 js-track-form">
            <input type="hidden" name="equipment_offering_id" value="<?= (int) $eq['EquipmentOfferingID'] ?>">
            <div class="admin-field mb-0 flex-grow-1">
                <label>Availability</label>
                <select name="availability_status" data-original="<?= htmlspecialchars($eq['AvailabilityStatus']) ?>">
                    <option value="Available"         <?= $eq['AvailabilityStatus'] === 'Available'         ? 'selected' : '' ?>>Available</option>
                    <option value="Unavailable"       <?= $eq['AvailabilityStatus'] === 'Unavailable'       ? 'selected' : '' ?>>Unavailable</option>
                    <option value="Under Maintenance" <?= $eq['AvailabilityStatus'] === 'Under Maintenance' ? 'selected' : '' ?>>Under Maintenance</option>
                </select>
            </div>
            <button type="submit" name="update_availability" class="admin-btn admin-btn-primary admin-btn-sm" disabled>Save</button>
        </form>

    </div><!-- /.admin-card -->

    <!-- ── Edit Modal ──────────────────────────────────────── -->
    <div id="<?= $editId ?>"
         style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:2000;align-items:center;justify-content:center;"
         onclick="if(event.target===this)this.style.display='none'">
        <div style="background:#fff;border-radius:10px;padding:32px;width:100%;max-width:580px;max-height:90vh;overflow-y:auto;position:relative;">
            <button type="button"
                    onclick="document.getElementById('<?= $editId ?>').style.display='none'"
                    style="position:absolute;top:14px;right:16px;background:none;border:none;font-size:1.2rem;cursor:pointer;color:#6b7280;">✕</button>

            <h2 class="admin-page-title" style="font-size:1.05rem;margin-bottom:4px;">Edit Equipment</h2>
            <p class="admin-page-sub" style="margin-bottom:20px;">
                <?= htmlspecialchars($eq['Name']) ?> · ID #<?= (int) $eq['EquipmentOfferingID'] ?>
            </p>

            <form method="POST" class="js-edit-modal-form">
                <input type="hidden" name="equipment_offering_id" value="<?= (int) $eq['EquipmentOfferingID'] ?>">

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div class="admin-field">
                        <label>Name <span style="color:red">*</span></label>
                        <input type="text" name="edit_name"
                               value="<?= htmlspecialchars($eq['Name']) ?>"
                               data-original="<?= htmlspecialchars($eq['Name']) ?>"
                               required>
                    </div>
                    <div class="admin-field">
                        <label>Model</label>
                        <input type="text" name="edit_model"
                               value="<?= htmlspecialchars($eq['Model'] ?? '') ?>"
                               data-original="<?= htmlspecialchars($eq['Model'] ?? '') ?>">
                    </div>
                </div>

                <div class="admin-field">
                    <label>Description</label>
                    <textarea name="edit_description" rows="3"
                              data-original="<?= htmlspecialchars($eq['Description'] ?? '') ?>"><?= htmlspecialchars($eq['Description'] ?? '') ?></textarea>
                </div>

                <div class="admin-field">
                    <label>Specs <span class="admin-table-sub" style="text-transform:none;letter-spacing:0;">(label / value pairs)</span></label>
                    <?php
                    // Parse existing specs into rows
                    $existingSpecs = [];
                    if (!empty($eq['Specs'])) {
                        foreach (array_filter(array_map('trim', explode('·', $eq['Specs']))) as $entry) {
                            $parts = explode(':', $entry, 2);
                            $existingSpecs[] = [
                                'label' => trim($parts[0] ?? ''),
                                'value' => trim($parts[1] ?? ''),
                            ];
                        }
                    }
                    ?>
                    <div id="editSpecRows_<?= (int)$eq['EquipmentOfferingID'] ?>" style="display:flex;flex-direction:column;gap:8px;margin-bottom:8px;">
                        <?php foreach ($existingSpecs as $sp): ?>
                        <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:8px;align-items:center;">
                            <input type="text" class="spec-label" placeholder="Label (e.g. Load capacity)"
                                   value="<?= htmlspecialchars($sp['label']) ?>"
                                   style="padding:7px 10px;border:1px solid var(--admin-border);border-radius:6px;font-size:0.82rem;font-family:inherit;">
                            <input type="text" class="spec-value" placeholder="Value (e.g. 18 tons)"
                                   value="<?= htmlspecialchars($sp['value']) ?>"
                                   style="padding:7px 10px;border:1px solid var(--admin-border);border-radius:6px;font-size:0.82rem;font-family:inherit;">
                            <button type="button" onclick="this.closest('div').remove()"
                                    style="width:30px;height:30px;border-radius:6px;border:1px solid #fecaca;background:#fee2e2;color:#dc2626;cursor:pointer;font-size:1rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;">×</button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="admin-btn admin-btn-outline admin-btn-sm"
                            onclick="addSpecRow('editSpecRows_<?= (int)$eq['EquipmentOfferingID'] ?>')">+ Add Spec</button>
                    <input type="hidden" name="edit_specs" id="editSpecsHidden_<?= (int)$eq['EquipmentOfferingID'] ?>"
                           data-original="<?= htmlspecialchars($eq['Specs'] ?? '') ?>">
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div class="admin-field">
                        <label>Hourly Rate (₱)</label>
                        <input type="number" name="edit_hourly" step="0.01" min="0"
                               value="<?= (float)$eq['HourlyRate'] ?>"
                               data-original="<?= (float)$eq['HourlyRate'] ?>">
                    </div>
                    <div class="admin-field">
                        <label>Daily Rate (₱)</label>
                        <input type="number" name="edit_daily" step="0.01" min="0"
                               value="<?= (float)$eq['DailyRate'] ?>"
                               data-original="<?= (float)$eq['DailyRate'] ?>">
                    </div>
                    <div class="admin-field">
                        <label>Weekly Rate (₱)</label>
                        <input type="number" name="edit_weekly" step="0.01" min="0"
                               value="<?= (float)$eq['WeeklyRate'] ?>"
                               data-original="<?= (float)$eq['WeeklyRate'] ?>">
                    </div>
                    <div class="admin-field">
                        <label>Monthly Rate (₱)</label>
                        <input type="number" name="edit_monthly" step="0.01" min="0"
                               value="<?= (float)$eq['MonthlyRate'] ?>"
                               data-original="<?= (float)$eq['MonthlyRate'] ?>">
                    </div>
                </div>

                <div class="admin-field">
                    <label>Availability Status</label>
                    <select name="edit_availability" data-original="<?= htmlspecialchars($eq['AvailabilityStatus']) ?>">
                        <option value="Available"         <?= $eq['AvailabilityStatus'] === 'Available'         ? 'selected' : '' ?>>Available</option>
                        <option value="Unavailable"       <?= $eq['AvailabilityStatus'] === 'Unavailable'       ? 'selected' : '' ?>>Unavailable</option>
                        <option value="Under Maintenance" <?= $eq['AvailabilityStatus'] === 'Under Maintenance' ? 'selected' : '' ?>>Under Maintenance</option>
                    </select>
                </div>

                <div class="d-flex gap-2 justify-content-end mt-2">
                    <button type="button" class="admin-btn admin-btn-outline"
                            onclick="document.getElementById('<?= $editId ?>').style.display='none'">Cancel</button>
                    <button type="submit" name="edit_equipment" class="admin-btn admin-btn-primary" disabled>Save Changes</button>
                </div>
            </form>

            <!-- ── Publish / Unpublish ─────────────────────── -->
            <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--admin-border);">
                <?php if ($eq['AvailabilityStatus'] === 'Unavailable'): ?>
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                        <div>
                            <div style="font-size:0.75rem;font-weight:700;color:#dc2626;margin-bottom:2px;display:flex;align-items:center;gap:5px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M4.93 4.93l14.14 14.14"/></svg>
                                Not visible on website
                            </div>
                            <div style="font-size:0.76rem;color:var(--admin-muted);">This equipment is hidden from the public catalog.</div>
                        </div>
                        <form method="POST">
                            <input type="hidden" name="equipment_offering_id" value="<?= (int)$eq['EquipmentOfferingID'] ?>">
                            <input type="hidden" name="availability_status" value="Available">
                            <button type="submit" name="update_availability"
                                    class="admin-btn admin-btn-outline admin-btn-sm"
                                    style="color:#059669;border-color:#a7f3d0;"
                                    onclick="return confirm('Republish <?= htmlspecialchars(addslashes($eq['Name'])) ?> to the website?')">
                                Republish to Website
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                        <div>
                            <div style="font-size:0.75rem;font-weight:700;color:#059669;margin-bottom:2px;display:flex;align-items:center;gap:5px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
                                Visible on website
                            </div>
                            <div style="font-size:0.76rem;color:var(--admin-muted);">Shown in the public equipment catalog.</div>
                        </div>
                        <form method="POST">
                            <input type="hidden" name="equipment_offering_id" value="<?= (int)$eq['EquipmentOfferingID'] ?>">
                            <input type="hidden" name="availability_status" value="Unavailable">
                            <button type="submit" name="update_availability"
                                    class="admin-btn admin-btn-danger admin-btn-sm"
                                    onclick="return confirm('Unpublish <?= htmlspecialchars(addslashes($eq['Name'])) ?> from the website? It will be hidden from the public catalog.')">
                                Unpublish from Website
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div><!-- /.edit modal -->

    <?php endforeach; ?>
</div><!-- /.admin-card-grid -->
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

<!-- ── ADD EQUIPMENT MODAL ────────────────────────────────── -->
<div id="addEquipmentModal"
     style="display:<?= !empty($reopenAddModal) ? 'flex' : 'none' ?>;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;padding:16px;"
     onclick="if(event.target===this)this.style.display='none'">
    <div style="background:#fff;border-radius:12px;width:100%;max-width:600px;max-height:92vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.2);">

        <!-- Header -->
        <div style="padding:22px 28px 16px;border-bottom:1px solid var(--admin-border);display:flex;align-items:flex-start;justify-content:space-between;gap:16px;position:sticky;top:0;background:#fff;z-index:1;">
            <div>
                <div style="font-size:1rem;font-weight:800;color:var(--admin-text);margin-bottom:3px;">Add New Equipment</div>
                <div style="font-size:0.78rem;color:var(--admin-muted);">Adds the item to the catalog and makes it available on the public website.</div>
            </div>
            <button type="button" onclick="document.getElementById('addEquipmentModal').style.display='none'"
                    style="width:32px;height:32px;border-radius:6px;border:1px solid var(--admin-border);background:var(--ysc-bg);color:var(--admin-muted);cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:1rem;">✕</button>
        </div>

        <!-- Body -->
        <div style="padding:24px 28px;">
            <form method="POST" enctype="multipart/form-data">

                <!-- Basic info -->
                <div style="font-size:0.68rem;font-weight:800;letter-spacing:0.07em;text-transform:uppercase;color:var(--admin-muted);margin-bottom:12px;">Equipment Information</div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                    <div class="admin-field">
                        <label>Name <span style="color:#dc2626;">*</span></label>
                        <input type="text" name="eq_name"
                               value="<?= htmlspecialchars($_POST['eq_name'] ?? '') ?>"
                               placeholder="e.g. Backhoe" required>
                    </div>
                    <div class="admin-field">
                        <label>Model</label>
                        <input type="text" name="eq_model"
                               value="<?= htmlspecialchars($_POST['eq_model'] ?? '') ?>"
                               placeholder="e.g. HEV-320">
                    </div>
                </div>

                <div class="admin-field">
                    <label>Description</label>
                    <textarea name="eq_description" rows="3"
                              placeholder="Brief description of the equipment and its uses…"><?= htmlspecialchars($_POST['eq_description'] ?? '') ?></textarea>
                </div>

                <div class="admin-field">
                    <label>
                        Specs
                        <span style="font-size:0.72rem;color:var(--admin-muted);font-weight:400;text-transform:none;letter-spacing:0;"> — add label/value pairs</span>
                    </label>
                    <div id="addSpecRows" style="display:flex;flex-direction:column;gap:8px;margin-bottom:8px;"></div>
                    <button type="button" class="admin-btn admin-btn-outline admin-btn-sm" onclick="addSpecRow('addSpecRows')">
                        + Add Spec
                    </button>
                    <!-- Hidden field that gets filled before submit -->
                    <input type="hidden" name="eq_specs" id="addSpecsHidden">
                </div>

                <div style="height:1px;background:var(--admin-border);margin:16px 0;"></div>

                <!-- Rates -->
                <div style="font-size:0.68rem;font-weight:800;letter-spacing:0.07em;text-transform:uppercase;color:var(--admin-muted);margin-bottom:12px;">Rental Rates (₱)</div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                    <div class="admin-field">
                        <label>Hourly Rate</label>
                        <input type="number" name="eq_hourly" step="0.01" min="0" placeholder="0.00"
                               value="<?= htmlspecialchars($_POST['eq_hourly'] ?? '') ?>">
                    </div>
                    <div class="admin-field">
                        <label>Daily Rate</label>
                        <input type="number" name="eq_daily" step="0.01" min="0" placeholder="0.00"
                               value="<?= htmlspecialchars($_POST['eq_daily'] ?? '') ?>">
                    </div>
                    <div class="admin-field">
                        <label>Weekly Rate</label>
                        <input type="number" name="eq_weekly" step="0.01" min="0" placeholder="0.00"
                               value="<?= htmlspecialchars($_POST['eq_weekly'] ?? '') ?>">
                    </div>
                    <div class="admin-field">
                        <label>Monthly Rate</label>
                        <input type="number" name="eq_monthly" step="0.01" min="0" placeholder="0.00"
                               value="<?= htmlspecialchars($_POST['eq_monthly'] ?? '') ?>">
                    </div>
                </div>

                <div style="height:1px;background:var(--admin-border);margin:16px 0;"></div>

                <!-- Photo + status -->
                <div style="font-size:0.68rem;font-weight:800;letter-spacing:0.07em;text-transform:uppercase;color:var(--admin-muted);margin-bottom:12px;">Photo &amp; Status</div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                    <div class="admin-field">
                        <label>Equipment Photo</label>
                        <input type="file" name="eq_photo" accept="image/jpeg,image/png,image/webp"
                               style="padding:6px 10px;font-size:0.82rem;">
                        <div style="font-size:0.72rem;color:var(--admin-muted);margin-top:4px;">JPG, PNG or WEBP. Shown on the website.</div>
                    </div>
                    <div class="admin-field">
                        <label>Availability Status</label>
                        <select name="eq_availability">
                            <option value="Available">Available</option>
                            <option value="Unavailable">Unavailable</option>
                            <option value="Under Maintenance">Under Maintenance</option>
                        </select>
                    </div>
                </div>

                <!-- Footer -->
                <div style="display:flex;align-items:center;justify-content:flex-end;gap:10px;padding-top:16px;border-top:1px solid var(--admin-border);margin-top:8px;">
                    <button type="button" class="admin-btn admin-btn-outline"
                            onclick="document.getElementById('addEquipmentModal').style.display='none'">Cancel</button>
                    <button type="submit" name="add_equipment" class="admin-btn admin-btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                        Add Equipment
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
// ── Spec row builder ─────────────────────────────────────────
function addSpecRow(containerId) {
    var container = document.getElementById(containerId);
    if (!container) return;
    var row = document.createElement('div');
    row.style.cssText = 'display:grid;grid-template-columns:1fr 1fr auto;gap:8px;align-items:center;';
    row.innerHTML =
        '<input type="text" class="spec-label" placeholder="Label (e.g. Load capacity)"' +
        ' style="padding:7px 10px;border:1px solid var(--admin-border);border-radius:6px;font-size:0.82rem;font-family:inherit;">' +
        '<input type="text" class="spec-value" placeholder="Value (e.g. 18 tons)"' +
        ' style="padding:7px 10px;border:1px solid var(--admin-border);border-radius:6px;font-size:0.82rem;font-family:inherit;">' +
        '<button type="button" onclick="this.closest(\'div\').remove()"' +
        ' style="width:30px;height:30px;border-radius:6px;border:1px solid #fecaca;background:#fee2e2;color:#dc2626;cursor:pointer;font-size:1rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;">×</button>';
    container.appendChild(row);
}

// Serialise spec rows into "Label: Value · Label: Value" into hidden input
function serializeSpecs(containerId, hiddenId) {
    var container = document.getElementById(containerId);
    var hidden    = document.getElementById(hiddenId);
    if (!container || !hidden) return;
    var parts = [];
    container.querySelectorAll('div').forEach(function(row) {
        var lbl = (row.querySelector('.spec-label') ? row.querySelector('.spec-label').value : '').trim();
        var val = (row.querySelector('.spec-value') ? row.querySelector('.spec-value').value : '').trim();
        if (lbl || val) parts.push(lbl + ': ' + val);
    });
    hidden.value = parts.join(' · ');
}

// Wire up the Add Equipment form — serialize specs on submit
document.addEventListener('DOMContentLoaded', function() {
    // Add equipment form
    var addForm = document.querySelector('#addEquipmentModal form');
    if (addForm) {
        addForm.addEventListener('submit', function() {
            serializeSpecs('addSpecRows', 'addSpecsHidden');
        });
    }

    // Edit equipment forms — each has a unique spec container + hidden
    document.querySelectorAll('.js-edit-modal-form').forEach(function(form) {
        form.addEventListener('submit', function() {
            var container = form.querySelector('[id^="editSpecRows_"]');
            var hidden    = form.querySelector('[id^="editSpecsHidden_"]');
            if (container && hidden) serializeSpecs(container.id, hidden.id);
        });
    });

    // Change-detection for quick-update forms
    function bindTrackForm(form) {
        var btn     = form.querySelector('button[type="submit"]');
        var tracked = form.querySelectorAll('[data-original]');
        if (!btn || !tracked.length) return;
        function check() {
            btn.disabled = !Array.from(tracked).some(function(el) {
                return el.value !== el.dataset.original;
            });
        }
        tracked.forEach(function(el) {
            el.addEventListener('change', check);
            el.addEventListener('input',  check);
        });
        check();
    }

    document.querySelectorAll('.js-track-form').forEach(bindTrackForm);
    document.querySelectorAll('.js-edit-modal-form').forEach(bindTrackForm);

    // Pre-populate add specs if modal re-opens after error
    <?php if (!empty($_POST['eq_specs'])): ?>
    (function() {
        var specs = <?= json_encode($_POST['eq_specs']) ?>;
        if (!specs) return;
        specs.split(' · ').forEach(function(entry) {
            var sep   = entry.indexOf(': ');
            var lbl   = sep >= 0 ? entry.substring(0, sep).trim() : entry.trim();
            var val   = sep >= 0 ? entry.substring(sep + 2).trim() : '';
            addSpecRow('addSpecRows');
            var rows = document.getElementById('addSpecRows').querySelectorAll(':scope > div');
            var last = rows[rows.length - 1];
            if (last) {
                if (last.querySelector('.spec-label')) last.querySelector('.spec-label').value = lbl;
                if (last.querySelector('.spec-value')) last.querySelector('.spec-value').value = val;
            }
        });
    })();
    <?php endif; ?>
});
</script>

<?php include("../includes/manager/layout_end.php"); ?>
