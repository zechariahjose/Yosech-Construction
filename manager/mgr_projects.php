<?php
include("../config/database.php");
include("../includes/manager/helpers.php");

managerRequireLogin('manager/mgr_projects.php');

$mgrEmployee       = adminCurrentEmployee($conn);
$mgrPendingRentals = managerPendingRentals($conn);
$employeeId        = (int) $_SESSION['user_id'];

$successMsg = '';
$errorMsg   = '';

// ── EDIT SHOWCASE PROJECT ───────────────────────────────────
if (isset($_POST['edit_showcase'], $_POST['showcase_id'])) {
    $scID    = (int) $_POST['showcase_id'];
    $scTitle = mysqli_real_escape_string($conn, trim($_POST['showcase_title']));
    $scSum   = mysqli_real_escape_string($conn, trim($_POST['showcase_summary']));
    $scStat  = mysqli_real_escape_string($conn, $_POST['showcase_status']);
    $scStart = !empty($_POST['showcase_start_date'])
                   ? "'" . mysqli_real_escape_string($conn, $_POST['showcase_start_date']) . "'"
                   : 'NULL';
    $scEnd   = !empty($_POST['showcase_end_date'])
                   ? "'" . mysqli_real_escape_string($conn, $_POST['showcase_end_date']) . "'"
                   : 'NULL';
    if ($scTitle !== '') {
        mysqli_query($conn, "UPDATE ProjectShowcase SET Title='{$scTitle}', Summary='{$scSum}', Status='{$scStat}', StartDate={$scStart}, EndDate={$scEnd} WHERE ProjectShowcaseID={$scID}");
        $successMsg = "Website project updated.";
    } else {
        $errorMsg = "Title cannot be empty.";
    }
}

// ── UPDATE SHOWCASE STATUS (quick inline) ──────────────────
if (isset($_POST['update_showcase'], $_POST['showcase_id'])) {
    $scID     = (int) $_POST['showcase_id'];
    $scStatus = mysqli_real_escape_string($conn, $_POST['showcase_status']);
    $scEnd    = !empty($_POST['showcase_end_date'])
                    ? "'" . mysqli_real_escape_string($conn, $_POST['showcase_end_date']) . "'"
                    : 'NULL';
    mysqli_query($conn, "UPDATE ProjectShowcase SET Status='{$scStatus}', EndDate={$scEnd} WHERE ProjectShowcaseID={$scID}");
    $successMsg = "Website project status updated.";
}

// ── EDIT INTERNAL PROJECT ───────────────────────────────────
if (isset($_POST['edit_project'], $_POST['project_id'])) {
    $epID          = (int) $_POST['project_id'];
    $epTitle       = mysqli_real_escape_string($conn, trim($_POST['edit_title']));
    $epLocation    = mysqli_real_escape_string($conn, trim($_POST['edit_location']));
    $epDesc        = mysqli_real_escape_string($conn, trim($_POST['edit_description']));
    $epStatus      = mysqli_real_escape_string($conn, $_POST['edit_project_status']);
    $epPayment     = mysqli_real_escape_string($conn, $_POST['edit_payment_status']);
    $epStart       = !empty($_POST['edit_start_date'])
                         ? "'" . mysqli_real_escape_string($conn, $_POST['edit_start_date']) . "'"
                         : 'NULL';
    $epEnd         = !empty($_POST['edit_end_date'])
                         ? "'" . mysqli_real_escape_string($conn, $_POST['edit_end_date']) . "'"
                         : 'NULL';
    // Update Project table
    mysqli_query($conn, "UPDATE Project SET ProjectStatus='{$epStatus}', StartDate={$epStart}, EndDate={$epEnd}, Description='{$epDesc}', ProjectPaymentStatus='{$epPayment}' WHERE ProjectID={$epID}");
    // Update the linked Application's ProjectTitle and ProjectLocation
    mysqli_query($conn, "UPDATE Application a JOIN Project p ON p.ApplicationID=a.ApplicationID SET a.ProjectTitle='{$epTitle}', a.ProjectLocation='{$epLocation}' WHERE p.ProjectID={$epID}");
    $successMsg = "Project updated successfully.";
}

// ── DELETE PROJECT ──────────────────────────────────────────
if (isset($_POST['delete_project'], $_POST['project_id'])) {
    $delId = (int) $_POST['project_id'];
    $del   = mysqli_prepare($conn, "DELETE FROM Project WHERE ProjectID = ?");
    mysqli_stmt_bind_param($del, "i", $delId);
    if (mysqli_stmt_execute($del)) {
        $successMsg = "Project #$delId has been deleted.";
    } else {
        $errorMsg = "Failed to delete project.";
    }
}

// ── ADD PROJECT ─────────────────────────────────────────────
if (isset($_POST['add_project'])) {
    $appID          = (int) $_POST['application_id'];
    $proposalDate   = $_POST['proposal_date']   ?: null;
    $proposalBudget = $_POST['proposal_budget'] ?: null;
    $proposalStatus = $_POST['proposal_status'];
    $startDate      = $_POST['start_date']  ?: null;
    $endDate        = $_POST['end_date']    ?: null;
    $description    = trim($_POST['description']);
    $paymentStatus  = $_POST['payment_status'];
    $projectStatus  = $_POST['project_status'];

    $chk = mysqli_prepare($conn, "SELECT ProjectID FROM Project WHERE ApplicationID = ?");
    mysqli_stmt_bind_param($chk, "i", $appID);
    mysqli_stmt_execute($chk);
    mysqli_stmt_store_result($chk);

    if (mysqli_stmt_num_rows($chk) > 0) {
        $errorMsg = "This application already has a linked project.";
    } else {
        $ins = mysqli_prepare($conn, "INSERT INTO Project (ApplicationID, ProposalDate, ProposalBudget, ProposalStatus, StartDate, EndDate, ProjectStatus, Description, ProjectPaymentStatus) VALUES (?,?,?,?,?,?,?,?,?)");
        mysqli_stmt_bind_param($ins, "idsssssss", $appID, $proposalDate, $proposalBudget, $proposalStatus, $startDate, $endDate, $projectStatus, $description, $paymentStatus);
        if (mysqli_stmt_execute($ins)) {
            $successMsg = "Project created successfully.";
        } else {
            $errorMsg = "Failed to create project: " . mysqli_error($conn);
        }
    }
}

// ── UPDATE PROJECT STATUS ───────────────────────────────────
if (isset($_POST['project_id'], $_POST['project_status']) && !isset($_POST['add_project'], $_POST['delete_project'], $_POST['edit_project'])) {
    $projectId     = (int) $_POST['project_id'];
    $projectStatus = mysqli_real_escape_string($conn, $_POST['project_status']);
    mysqli_query($conn, "UPDATE Project SET ProjectStatus='{$projectStatus}' WHERE ProjectID={$projectId}");
}

// ── UPDATE PAYMENT STATUS ───────────────────────────────────
if (isset($_POST['update_payment'], $_POST['project_id'], $_POST['payment_status'])) {
    $projectId     = (int) $_POST['project_id'];
    $paymentStatus = mysqli_real_escape_string($conn, $_POST['payment_status']);
    mysqli_query($conn, "UPDATE Project SET ProjectPaymentStatus='{$paymentStatus}' WHERE ProjectID={$projectId}");
    $successMsg = "Payment status updated.";
}

// ── ADD UPDATE ──────────────────────────────────────────────
if (isset($_POST['add_update'], $_POST['project_id'], $_POST['update_description'])) {
    $projectId = (int) $_POST['project_id'];
    $desc      = trim(mysqli_real_escape_string($conn, $_POST['update_description']));
    $status    = mysqli_real_escape_string($conn, $_POST['update_status'] ?? 'Reviewed');
    if ($desc !== '') {
        mysqli_query($conn, "INSERT INTO Project_Update (ProjectID, EmployeeID, Status, Description, UpdateDate)
                             VALUES ({$projectId}, {$employeeId}, '{$status}', '{$desc}', CURDATE())");
    }
}

// ── REVIEW UPDATE ───────────────────────────────────────────
if (isset($_POST['review_update'], $_POST['update_id'])) {
    $updateId     = (int) $_POST['update_id'];
    $reviewStatus = mysqli_real_escape_string($conn, $_POST['review_status'] ?? 'Reviewed');
    mysqli_query($conn, "UPDATE Project_Update SET Status='{$reviewStatus}' WHERE UpdateID={$updateId}");
}

// ── FETCH SHOWCASE PROJECTS (website) — split by status ────
$showcaseResult = mysqli_query($conn,
    "SELECT * FROM ProjectShowcase ORDER BY StartDate DESC"
);
$showcaseActive = [];  // Ongoing
$showcaseRest   = [];  // everything else
while ($row = mysqli_fetch_assoc($showcaseResult)) {
    $row['_source'] = 'showcase';
    if ($row['Status'] === 'Ongoing') {
        $showcaseActive[] = $row;
    } else {
        $showcaseRest[] = $row;
    }
}

// ── FETCH INTERNAL PROJECTS — Ongoing only ─────────────────
$internalActiveResult = mysqli_query($conn,
    "SELECT p.*, a.ApplicationType, a.ProjectTitle, a.ProjectLocation,
            c.Client_FirstName, c.Client_LastName, c.Client_ContactNumber
     FROM Project p
     JOIN Application a ON p.ApplicationID = a.ApplicationID
     JOIN Client c ON a.UserID = c.UserID
     WHERE p.ProjectStatus = 'Ongoing'
     ORDER BY p.ProjectID DESC"
);
$internalActiveRows = [];
while ($row = mysqli_fetch_assoc($internalActiveResult)) {
    $row['_source'] = 'internal';
    $internalActiveRows[] = $row;
}

// ── FETCH INTERNAL PROJECTS — Non-ongoing ──────────────────
$internalRestResult = mysqli_query($conn,
    "SELECT p.*, a.ApplicationType, a.ProjectTitle, a.ProjectLocation,
            c.Client_FirstName, c.Client_LastName, c.Client_ContactNumber
     FROM Project p
     JOIN Application a ON p.ApplicationID = a.ApplicationID
     JOIN Client c ON a.UserID = c.UserID
     WHERE p.ProjectStatus != 'Ongoing'
     ORDER BY FIELD(p.ProjectStatus,'On Hold','Completed','Cancelled'), p.ProjectID DESC"
);
$internalRestRows = [];
while ($row = mysqli_fetch_assoc($internalRestResult)) {
    $row['_source'] = 'internal';
    $internalRestRows[] = $row;
}

// Merge: Active = showcase Ongoing + internal Ongoing
// All   = showcase non-ongoing + internal non-ongoing
$activeRows = array_merge($showcaseActive, $internalActiveRows);
$allRows    = array_merge($showcaseRest, $internalRestRows);

$mgrActiveNav    = 'projects';
$mgrPageTitle    = 'Projects';
$mgrPageSubtitle = 'Manage website showcase projects and track internal site progress.';
$mgrPageActions  = '
    <button class="admin-btn admin-btn-primary" onclick="document.getElementById(\'addProjectModal\').style.display=\'flex\'">+ Add Project</button>
';

include("../includes/manager/layout_start.php");
?>

<?php if ($successMsg): ?>
    <div class="admin-alert admin-alert-success" style="margin-bottom:16px;"><?= htmlspecialchars($successMsg) ?></div>
<?php endif; ?>
<?php if ($errorMsg): ?>
    <div class="admin-alert admin-alert-danger" style="margin-bottom:16px;"><?= htmlspecialchars($errorMsg) ?></div>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════════════
     SECTION 1 — ACTIVE PROJECTS (Ongoing — website + internal)
     ══════════════════════════════════════════════════════════ -->
<div style="margin-bottom:8px;">
    <h2 class="admin-page-title" style="font-size:1rem;margin-bottom:4px;">
        <span style="display:inline-flex;align-items:center;gap:8px;">
            <span style="width:10px;height:10px;border-radius:50%;background:#f59e0b;flex-shrink:0;display:inline-block;"></span>
            Active Projects
        </span>
    </h2>
    <p class="admin-page-sub" style="margin-bottom:16px;">All currently ongoing projects — both website showcase and internal site records.</p>
</div>

<?php if (count($activeRows) === 0): ?>
    <div class="admin-alert admin-alert-info" style="margin-bottom:32px;">No ongoing projects at the moment.</div>
<?php else: ?>
    <div class="pj-list-stack" style="margin-bottom:40px;">
        <?php foreach ($activeRows as $project): ?>
            <?php if ($project['_source'] === 'showcase'): ?>
                <?php include __DIR__ . '/../includes/manager/_showcase_card.php'; ?>
            <?php else: ?>
                <?php
                $status        = managerProjectStatusLabel($project['ProjectStatus']);
                $updatesResult = mysqli_query($conn,
                    "SELECT pu.*, e.Username FROM Project_Update pu
                     LEFT JOIN Employee e ON pu.EmployeeID = e.EmployeeID
                     WHERE pu.ProjectID = " . (int) $project['ProjectID'] . "
                     ORDER BY pu.UpdateDate DESC LIMIT 4"
                );
                ?>
                <?php include __DIR__ . '/../includes/manager/_project_card.php'; ?>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<hr class="admin-divider" style="margin-bottom:32px;">

<!-- ══════════════════════════════════════════════════════════
     SECTION 2 — ALL PROJECTS (non-ongoing — website + internal)
     ══════════════════════════════════════════════════════════ -->
<div style="margin-bottom:8px;">
    <h2 class="admin-page-title" style="font-size:1rem;margin-bottom:4px;">All Projects</h2>
    <p class="admin-page-sub" style="margin-bottom:16px;">Complete history of all project records across every status.</p>
</div>

<?php if (count($allRows) === 0): ?>
    <div class="admin-alert admin-alert-info">No projects found. Use the <strong>+ Add Project</strong> button to create one from an approved application.</div>
<?php else: ?>
    <div class="pj-list-stack">
        <?php foreach ($allRows as $project): ?>
            <?php if ($project['_source'] === 'showcase'): ?>
                <?php include __DIR__ . '/../includes/manager/_showcase_card.php'; ?>
            <?php else: ?>
                <?php
                $status        = managerProjectStatusLabel($project['ProjectStatus']);
                $updatesResult = mysqli_query($conn,
                    "SELECT pu.*, e.Username FROM Project_Update pu
                     LEFT JOIN Employee e ON pu.EmployeeID = e.EmployeeID
                     WHERE pu.ProjectID = " . (int) $project['ProjectID'] . "
                     ORDER BY pu.UpdateDate DESC LIMIT 4"
                );
                ?>
                <?php include __DIR__ . '/../includes/manager/_project_card.php'; ?>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- ── ADD PROJECT MODAL ─────────────────────────────────── -->
<div id="addProjectModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:1000;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:10px;padding:32px;width:100%;max-width:560px;max-height:90vh;overflow-y:auto;position:relative;">
        <button onclick="document.getElementById('addProjectModal').style.display='none'" style="position:absolute;top:16px;right:16px;background:none;border:none;font-size:1.2rem;cursor:pointer;color:#6b7280;">✕</button>
        <h2 class="admin-page-title" style="font-size:1.1rem;margin-bottom:4px;">Add New Project</h2>
        <p class="admin-page-sub" style="margin-bottom:20px;">Select an approved application and fill in project details.</p>

        <form method="POST">
            <div class="admin-field">
                <label>Approved Application <span style="color:red">*</span></label>
                <select name="application_id" required>
                    <option value="">— Select Application —</option>
                    <?php
                    $appsForModal = mysqli_query($conn,
                        "SELECT a.ApplicationID, a.ProjectTitle, a.ApplicationType, a.ProjectLocation,
                                c.Client_FirstName, c.Client_LastName
                         FROM Application a
                         JOIN Client c ON a.UserID = c.UserID
                         LEFT JOIN Project p ON p.ApplicationID = a.ApplicationID
                         WHERE a.Status = 'Approved' AND p.ProjectID IS NULL
                         ORDER BY a.ApplicationID DESC"
                    );
                    while ($app = mysqli_fetch_assoc($appsForModal)):
                        $label  = $app['ProjectTitle'] ?: $app['ApplicationType'];
                        $label .= ' — ' . $app['Client_FirstName'] . ' ' . $app['Client_LastName'];
                        if ($app['ProjectLocation']) $label .= ' (' . $app['ProjectLocation'] . ')';
                    ?>
                        <option value="<?= (int) $app['ApplicationID'] ?>"><?= htmlspecialchars($label) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="admin-field">
                    <label>Proposal Date</label>
                    <input type="date" name="proposal_date">
                </div>
                <div class="admin-field">
                    <label>Proposed Budget (₱)</label>
                    <input type="number" name="proposal_budget" step="0.01" min="0" placeholder="0.00">
                </div>
                <div class="admin-field">
                    <label>Proposal Status</label>
                    <select name="proposal_status">
                        <option value="Draft">Draft</option>
                        <option value="Submitted">Submitted</option>
                        <option value="Approved" selected>Approved</option>
                        <option value="Rejected">Rejected</option>
                    </select>
                </div>
                <div class="admin-field">
                    <label>Project Status</label>
                    <select name="project_status">
                        <option value="Ongoing">Ongoing</option>
                        <option value="On Hold">On Hold</option>
                        <option value="Completed">Completed</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="admin-field">
                    <label>Start Date</label>
                    <input type="date" name="start_date">
                </div>
                <div class="admin-field">
                    <label>End Date</label>
                    <input type="date" name="end_date">
                </div>
            </div>

            <div class="admin-field">
                <label>Payment Status</label>
                <select name="payment_status">
                    <option value="Unpaid">Unpaid</option>
                    <option value="Partial">Partial</option>
                    <option value="Paid">Paid</option>
                </select>
            </div>

            <div class="admin-field">
                <label>Description / Scope of Work</label>
                <textarea name="description" rows="3" placeholder="Describe the project scope..."></textarea>
            </div>

            <div class="d-flex gap-2 justify-content-end mt-2">
                <button type="button" class="admin-btn admin-btn-outline" onclick="document.getElementById('addProjectModal').style.display='none'">Cancel</button>
                <button type="submit" name="add_project" class="admin-btn admin-btn-primary">Create Project</button>
            </div>
        </form>
    </div>
</div>

<style>
/* ════════════════════════════════════════════════
   Project List — polished stack layout
   ════════════════════════════════════════════════ */
.pj-list-stack {
    border: 1px solid var(--admin-border);
    border-radius: 10px;
    overflow: hidden;
    background: #fff;
    box-shadow: var(--ysc-shadow);
}
.pj-item { border-bottom: 1px solid var(--admin-border); }
.pj-item:last-child { border-bottom: none; }

/* Row */
.pj-row {
    display: grid;
    grid-template-columns: minmax(180px, 280px) 1fr auto;
    align-items: center;
    gap: 20px;
    padding: 16px 20px;
    transition: background 0.15s;
}
.pj-row:hover { background: #fafafa; }

/* Identity */
.pj-source-chip {
    display: inline-block;
    font-size: 0.6rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--ysc-primary);
    background: var(--ysc-primary-light);
    padding: 2px 8px;
    border-radius: 3px;
    margin-bottom: 5px;
}
.pj-source-web { color: #ea6c0a; background: rgba(249,115,22,.1); }
.pj-title { font-size: 0.9rem; font-weight: 700; color: var(--admin-text); line-height: 1.3; overflow: hidden; text-overflow: ellipsis; white-space: normal; word-break: break-word; }
.pj-subtitle { font-size: 0.74rem; color: var(--admin-muted); margin-top: 2px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.pj-dot { margin: 0 4px; }
.pj-thumb { width: 48px; height: 38px; object-fit: cover; border-radius: 5px; flex-shrink: 0; border: 1px solid var(--admin-border); }

/* Meta chips */
.pj-row-meta { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; }
.pj-meta-chip {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: 0.76rem; color: var(--admin-muted);
    background: var(--ysc-bg); border: 1px solid var(--admin-border);
    padding: 3px 10px; border-radius: 20px; white-space: nowrap;
}
.pj-meta-chip svg { flex-shrink: 0; }
.pj-meta-summary { white-space: normal; border-radius: 6px; max-width: 340px; line-height: 1.5; }
.pj-pay { font-weight: 600; }
.pj-pay-paid    { color: #059669; background: #d1fae5; border-color: #a7f3d0; }
.pj-pay-partial { color: #d97706; background: #fef3c7; border-color: #fde68a; }
.pj-pay-unpaid  { color: #dc2626; background: #fee2e2; border-color: #fecaca; }

/* Action buttons */
.pj-row-actions { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
.pj-action-btn {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 6px 13px; font-size: 0.74rem; font-weight: 600; letter-spacing: 0.03em;
    border: 1px solid var(--admin-border); border-radius: 6px;
    background: #fff; color: var(--admin-muted); cursor: pointer;
    transition: all 0.15s; white-space: nowrap;
}
.pj-action-btn:hover { border-color: var(--ysc-primary); color: var(--ysc-primary); background: var(--ysc-primary-light); }
.pj-action-btn.active { background: var(--ysc-primary-light); color: var(--ysc-primary); border-color: var(--ysc-primary); }
.pj-action-edit:hover { border-color: #f97316; color: #f97316; background: rgba(249,115,22,.06); }

/* Detail panel */
.pj-detail { border-top: 1px solid var(--admin-border); background: var(--ysc-bg); }
.pj-detail-inner { padding: 20px; display: flex; flex-direction: column; gap: 16px; }
.pj-detail-cols { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.pj-detail-block {}
.pj-detail-block-full { grid-column: 1 / -1; }
.pj-detail-label { font-size: 0.68rem; font-weight: 800; letter-spacing: 0.07em; text-transform: uppercase; color: var(--admin-muted); margin-bottom: 8px; }
.pj-detail-text { font-size: 0.84rem; color: var(--admin-text); line-height: 1.7; margin: 0; }
.pj-inline-form { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.pj-inline-form select,
.pj-inline-form input[type="date"] { flex: 1; min-width: 120px; padding: 7px 10px; border: 1px solid var(--admin-border); border-radius: 6px; font-size: 0.82rem; font-family: inherit; background: #fff; color: var(--admin-text); }
.pj-inline-form textarea { width: 100%; padding: 8px 10px; border: 1px solid var(--admin-border); border-radius: 6px; font-size: 0.82rem; font-family: inherit; background: #fff; color: var(--admin-text); resize: vertical; }
.pj-inline-form select:focus,
.pj-inline-form input:focus,
.pj-inline-form textarea:focus { outline: none; border-color: var(--ysc-primary); box-shadow: 0 0 0 3px rgba(107,127,148,.12); }

/* Updates log */
.pj-updates-list { display: flex; flex-direction: column; border: 1px solid var(--admin-border); border-radius: 8px; overflow: hidden; background: #fff; }
.pj-update-item { padding: 12px 16px; border-bottom: 1px solid var(--ysc-border-light); }
.pj-update-item:last-child { border-bottom: none; }
.pj-update-header { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 6px; }
.pj-update-date { font-size: 0.72rem; color: var(--admin-muted); font-weight: 500; }
.pj-update-desc { font-size: 0.82rem; color: var(--admin-text); line-height: 1.6; margin: 0; }
.pj-detail-footer { padding-top: 12px; border-top: 1px solid var(--admin-border); display: flex; justify-content: flex-end; }

/* Edit panel */
.pj-edit-panel { border-top: 2px solid #f97316; background: #fff; }
.pj-edit-inner { padding: 24px; }
.pj-edit-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid var(--admin-border); }
.pj-edit-title { font-size: 0.92rem; font-weight: 700; color: var(--admin-text); margin-bottom: 3px; }
.pj-edit-sub { font-size: 0.78rem; color: var(--admin-muted); }
.pj-edit-close { display: flex; align-items: center; justify-content: center; width: 30px; height: 30px; border-radius: 6px; border: 1px solid var(--admin-border); background: var(--ysc-bg); color: var(--admin-muted); cursor: pointer; flex-shrink: 0; transition: all 0.15s; }
.pj-edit-close:hover { background: #fee2e2; border-color: #fecaca; color: #dc2626; }
.pj-edit-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px; }
.pj-edit-actions { display: flex; justify-content: flex-end; align-items: center; gap: 8px; padding-top: 16px; border-top: 1px solid var(--ysc-border-light); margin-top: 4px; }

/* Disabled */
.admin-btn:disabled { opacity: 0.4; cursor: not-allowed; pointer-events: none; }

@media (max-width: 960px) {
    .pj-row { grid-template-columns: 1fr auto; }
    .pj-row-meta { display: none; }
    .pj-detail-cols, .pj-edit-grid { grid-template-columns: 1fr; }
}
@media (max-width: 560px) {
    .pj-row { padding: 12px 14px; }
    .pj-row-actions { flex-direction: column; align-items: flex-end; gap: 5px; }
}
</style>

<script>
function pjToggleDetail(detailId, editId, btn) {
    var detail = document.getElementById(detailId);
    var edit   = document.getElementById(editId);
    if (!detail) return;
    var isOpen = detail.style.display !== 'none';
    if (edit && edit.style.display !== 'none') edit.style.display = 'none';
    detail.style.display = isOpen ? 'none' : 'block';
    btn.textContent = isOpen ? (btn.dataset.labelOpen || 'Details') : (btn.dataset.labelClose || 'Close');
    btn.classList.toggle('active', !isOpen);
}

function pjToggleEdit(editId, detailId) {
    var edit   = document.getElementById(editId);
    var detail = document.getElementById(detailId);
    if (!edit) return;
    if (detail && detail.style.display !== 'none') {
        detail.style.display = 'none';
        var item = edit.closest('.pj-item');
        if (item) {
            var detBtn = item.querySelector('[data-label-open]');
            if (detBtn) { detBtn.textContent = detBtn.dataset.labelOpen; detBtn.classList.remove('active'); }
        }
    }
    edit.style.display = edit.style.display === 'none' ? 'block' : 'none';
}

function pjCloseEdit(editId) {
    var el = document.getElementById(editId);
    if (el) el.style.display = 'none';
}

(function () {
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

    document.querySelectorAll('.js-post-update-form').forEach(function(form) {
        var btn      = form.querySelector('button[type="submit"], button:not([type="button"])');
        var textarea = form.querySelector('textarea');
        if (!btn || !textarea) return;
        function check() { btn.disabled = textarea.value.trim() === ''; }
        textarea.addEventListener('input', check);
        check();
    });
})();
</script>

<?php include("../includes/manager/layout_end.php"); ?>
