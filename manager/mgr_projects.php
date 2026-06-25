<?php
include("../config/database.php");
include("../includes/manager/helpers.php");

managerRequireLogin('manager/mgr_projects.php');

$mgrEmployee = adminCurrentEmployee($conn);
$mgrPendingRentals = managerPendingRentals($conn);
$employeeId = (int) $_SESSION['user_id'];

$successMsg = '';
$errorMsg = '';

// ── DELETE PROJECT ──────────────────────────────────────────
if (isset($_POST['delete_project'], $_POST['project_id'])) {
    $delId = (int) $_POST['project_id'];
    $del = mysqli_prepare($conn, "DELETE FROM Project WHERE ProjectID = ?");
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
    $proposalDate   = $_POST['proposal_date'] ?: null;
    $proposalBudget = $_POST['proposal_budget'] ?: null;
    $proposalStatus = $_POST['proposal_status'];
    $startDate      = $_POST['start_date'] ?: null;
    $endDate        = $_POST['end_date'] ?: null;
    $description    = trim($_POST['description']);
    $paymentStatus  = $_POST['payment_status'];
    $projectStatus  = $_POST['project_status'];

    // Check application not already linked to a project
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
if (isset($_POST['project_id'], $_POST['project_status']) && !isset($_POST['add_project'], $_POST['delete_project'])) {
    $projectId    = (int) $_POST['project_id'];
    $projectStatus = mysqli_real_escape_string($conn, $_POST['project_status']);
    mysqli_query($conn, "UPDATE Project SET ProjectStatus = '{$projectStatus}' WHERE ProjectID = {$projectId}");
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
    mysqli_query($conn, "UPDATE Project_Update SET Status = '{$reviewStatus}' WHERE UpdateID = {$updateId}");
}

// ── FETCH APPROVED APPLICATIONS WITHOUT A PROJECT ───────────
$availableApps = mysqli_query($conn,
    "SELECT a.ApplicationID, a.ProjectTitle, a.ApplicationType, a.ProjectLocation,
            c.Client_FirstName, c.Client_LastName
     FROM Application a
     JOIN Client c ON a.UserID = c.UserID
     LEFT JOIN Project p ON p.ApplicationID = a.ApplicationID
     WHERE a.Status = 'Approved' AND p.ProjectID IS NULL
     ORDER BY a.ApplicationID DESC"
);

// ── FETCH PROJECTS ──────────────────────────────────────────
$statusFilter = $_GET['status'] ?? '';
$querySql = "
    SELECT p.*, a.ApplicationType, a.ProjectTitle, a.ProjectLocation,
           c.Client_FirstName, c.Client_LastName, c.Client_ContactNumber
    FROM Project p
    JOIN Application a ON p.ApplicationID = a.ApplicationID
    JOIN Client c ON a.UserID = c.UserID
";

if ($statusFilter !== '') {
    $escStatus = mysqli_real_escape_string($conn, $statusFilter);
    $querySql .= " WHERE p.ProjectStatus = '{$escStatus}'";
} else {
    $querySql .= " WHERE p.ProjectStatus IN ('Ongoing', 'On Hold')";
}
$querySql .= " ORDER BY FIELD(p.ProjectStatus, 'On Hold', 'Ongoing'), p.ProjectID DESC";
$query = mysqli_query($conn, $querySql);

$mgrActiveNav  = 'projects';
$mgrPageTitle  = 'Active Projects';
$mgrPageSubtitle = 'Track site progress, post field updates, and manage your project records.';
$mgrPageActions = '
    <button class="admin-btn admin-btn-primary" onclick="document.getElementById(\'addProjectModal\').style.display=\'flex\'">+ Add Project</button>
    <a href="' . BASE_URL . '/manager/mgr_projects.php?status=On Hold" class="admin-btn admin-btn-outline">On Hold</a>
    <a href="' . BASE_URL . '/manager/mgr_projects.php" class="admin-btn admin-btn-outline">Active Sites</a>
';

include("../includes/manager/layout_start.php");
?>

<?php if ($successMsg): ?>
    <div class="admin-alert admin-alert-success" style="margin-bottom:16px;"><?= htmlspecialchars($successMsg) ?></div>
<?php endif; ?>
<?php if ($errorMsg): ?>
    <div class="admin-alert admin-alert-danger" style="margin-bottom:16px;"><?= htmlspecialchars($errorMsg) ?></div>
<?php endif; ?>

<!-- ── ADD PROJECT MODAL ─────────────────────────────────── -->
<div id="addProjectModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45); z-index:1000; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:10px; padding:32px; width:100%; max-width:560px; max-height:90vh; overflow-y:auto; position:relative;">
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
                        $label = $app['ProjectTitle'] ?: $app['ApplicationType'];
                        $label .= ' — ' . $app['Client_FirstName'] . ' ' . $app['Client_LastName'];
                        if ($app['ProjectLocation']) $label .= ' (' . $app['ProjectLocation'] . ')';
                    ?>
                        <option value="<?= (int) $app['ApplicationID'] ?>"><?= htmlspecialchars($label) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
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

<!-- ── PROJECT CARDS ─────────────────────────────────────── -->
<?php if (mysqli_num_rows($query) === 0): ?>
    <div class="admin-alert admin-alert-info">No projects match this filter. Use the <strong>+ Add Project</strong> button to create one from an approved application.</div>
<?php else: ?>
    <div class="admin-card-grid">
        <?php while ($project = mysqli_fetch_assoc($query)):
            $status = managerProjectStatusLabel($project['ProjectStatus']);
            $updatesResult = mysqli_query(
                $conn,
                "SELECT pu.*, e.Username FROM Project_Update pu
                 LEFT JOIN Employee e ON pu.EmployeeID = e.EmployeeID
                 WHERE pu.ProjectID = " . (int) $project['ProjectID'] . "
                 ORDER BY pu.UpdateDate DESC LIMIT 4"
            );
        ?>
        <div class="admin-card">
            <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                <div>
                    <h3 class="admin-card-title mb-1"><?= htmlspecialchars(adminProjectDisplayName($project)) ?></h3>
                    <span class="admin-table-sub">Site #<?= (int) $project['ProjectID'] ?> · <?= htmlspecialchars($project['ProjectLocation'] ?? '—') ?></span>
                </div>
                <span class="admin-badge <?= $status['class'] ?>"><?= htmlspecialchars($status['label']) ?></span>
            </div>

            <div class="admin-meta-grid">
                <div class="admin-meta-item"><span>Client</span><?= htmlspecialchars($project['Client_FirstName'] . ' ' . $project['Client_LastName']) ?></div>
                <div class="admin-meta-item"><span>Contact</span><?= htmlspecialchars($project['Client_ContactNumber'] ?: '—') ?></div>
                <div class="admin-meta-item"><span>Timeline</span><?= htmlspecialchars(($project['StartDate'] ?? '—') . ' → ' . ($project['EndDate'] ?? '—')) ?></div>
                <div class="admin-meta-item"><span>Payment</span><?= htmlspecialchars($project['ProjectPaymentStatus']) ?></div>
            </div>

            <div class="admin-field">
                <label>Scope of Work</label>
                <div class="small text-muted" style="line-height:1.6;"><?= nl2br(htmlspecialchars($project['Description'] ?? '—')) ?></div>
            </div>

            <!-- Update Status -->
            <form method="post" class="d-flex gap-2 align-items-end mb-0">
                <input type="hidden" name="project_id" value="<?= (int) $project['ProjectID'] ?>">
                <div class="admin-field mb-0 flex-grow-1">
                    <label>Site Status</label>
                    <select name="project_status">
                        <?php foreach (['Ongoing', 'On Hold', 'Completed', 'Cancelled'] as $s): ?>
                            <option value="<?= $s ?>" <?= $project['ProjectStatus'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="admin-btn admin-btn-primary admin-btn-sm">Update Status</button>
            </form>

            <hr class="admin-divider">

            <!-- Field Updates -->
            <h4 class="admin-card-title" style="font-size:0.88rem;">Field Updates &amp; Inspections</h4>
            <?php if (mysqli_num_rows($updatesResult) > 0): ?>
                <ul class="admin-activity-list mb-3" style="border:1px solid var(--admin-border);border-radius:6px;">
                    <?php while ($upd = mysqli_fetch_assoc($updatesResult)): ?>
                    <li class="admin-activity-item">
                        <div class="d-flex justify-content-between gap-2">
                            <span class="admin-badge <?= $upd['Status'] === 'Pending' ? 'admin-badge-inspection' : 'admin-badge-track' ?>"><?= htmlspecialchars($upd['Status']) ?></span>
                            <span class="admin-activity-time"><?= htmlspecialchars($upd['UpdateDate']) ?></span>
                        </div>
                        <div class="admin-activity-desc mt-2"><?= htmlspecialchars($upd['Description']) ?></div>
                        <?php if ($upd['Status'] === 'Pending'): ?>
                        <form method="post" class="d-flex gap-2 mt-2">
                            <input type="hidden" name="update_id" value="<?= (int) $upd['UpdateID'] ?>">
                            <input type="hidden" name="review_update" value="1">
                            <select name="review_status" class="form-select form-select-sm" style="max-width:140px;">
                                <option value="Reviewed">Mark Reviewed</option>
                                <option value="Approved">Approve</option>
                            </select>
                            <button class="admin-btn admin-btn-success admin-btn-sm">Submit Review</button>
                        </form>
                        <?php endif; ?>
                    </li>
                    <?php endwhile; ?>
                </ul>
            <?php endif; ?>

            <form method="post">
                <input type="hidden" name="project_id" value="<?= (int) $project['ProjectID'] ?>">
                <input type="hidden" name="add_update" value="1">
                <div class="admin-field">
                    <label>Post Site Update</label>
                    <textarea name="update_description" rows="3" placeholder="e.g. Foundation pour completed, awaiting inspection..." required></textarea>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <select name="update_status" style="max-width:160px;">
                        <option value="Reviewed">Reviewed</option>
                        <option value="Pending">Pending Inspection</option>
                        <option value="Approved">Approved</option>
                    </select>
                    <button class="admin-btn admin-btn-primary admin-btn-sm">Post Update</button>
                </div>
            </form>

            <hr class="admin-divider">

            <!-- Delete Project -->
            <form method="post" onsubmit="return confirm('Are you sure you want to delete Project #<?= (int) $project['ProjectID'] ?>? This cannot be undone. The linked application will not be affected.');">
                <input type="hidden" name="project_id" value="<?= (int) $project['ProjectID'] ?>">
                <button type="submit" name="delete_project" class="admin-btn admin-btn-danger admin-btn-sm w-100">Delete Project</button>
            </form>
        </div>
        <?php endwhile; ?>
    </div>
<?php endif; ?>

<?php include("../includes/manager/layout_end.php"); ?>
