<?php
include("../config/database.php");
include("../includes/manager/helpers.php");

managerRequireLogin('manager/mgr_projects.php');

$mgrEmployee       = adminCurrentEmployee($conn);
$mgrPendingRentals = managerPendingRentals($conn);
$employeeId        = (int) $_SESSION['user_id'];

$successMsg = '';
$errorMsg   = '';

// ── UPDATE SHOWCASE STATUS ──────────────────────────────────
if (isset($_POST['update_showcase'], $_POST['showcase_id'])) {
    $scID     = (int) $_POST['showcase_id'];
    $scStatus = mysqli_real_escape_string($conn, $_POST['showcase_status']);
    $scEnd    = !empty($_POST['showcase_end_date'])
                    ? "'" . mysqli_real_escape_string($conn, $_POST['showcase_end_date']) . "'"
                    : 'NULL';
    mysqli_query($conn, "UPDATE ProjectShowcase SET Status='{$scStatus}', EndDate={$scEnd} WHERE ProjectShowcaseID={$scID}");
    $successMsg = "Website project updated.";
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
if (isset($_POST['project_id'], $_POST['project_status']) && !isset($_POST['add_project'], $_POST['delete_project'])) {
    $projectId     = (int) $_POST['project_id'];
    $projectStatus = mysqli_real_escape_string($conn, $_POST['project_status']);
    mysqli_query($conn, "UPDATE Project SET ProjectStatus='{$projectStatus}' WHERE ProjectID={$projectId}");
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

// ── FETCH SHOWCASE PROJECTS (website) ──────────────────────
$showcaseResult = mysqli_query($conn,
    "SELECT * FROM ProjectShowcase ORDER BY StartDate DESC"
);
$showcaseRows = [];
while ($row = mysqli_fetch_assoc($showcaseResult)) {
    $showcaseRows[] = $row;
}

// ── FETCH INTERNAL PROJECTS — Ongoing only ─────────────────
$activeProjects = mysqli_query($conn,
    "SELECT p.*, a.ApplicationType, a.ProjectTitle, a.ProjectLocation,
            c.Client_FirstName, c.Client_LastName, c.Client_ContactNumber
     FROM Project p
     JOIN Application a ON p.ApplicationID = a.ApplicationID
     JOIN Client c ON a.UserID = c.UserID
     WHERE p.ProjectStatus = 'Ongoing'
     ORDER BY p.ProjectID DESC"
);
$activeRows = [];
while ($row = mysqli_fetch_assoc($activeProjects)) {
    $activeRows[] = $row;
}

// ── FETCH INTERNAL PROJECTS — All ──────────────────────────
$allProjects = mysqli_query($conn,
    "SELECT p.*, a.ApplicationType, a.ProjectTitle, a.ProjectLocation,
            c.Client_FirstName, c.Client_LastName, c.Client_ContactNumber
     FROM Project p
     JOIN Application a ON p.ApplicationID = a.ApplicationID
     JOIN Client c ON a.UserID = c.UserID
     ORDER BY FIELD(p.ProjectStatus,'Ongoing','On Hold','Completed','Cancelled'), p.ProjectID DESC"
);
$allRows = [];
while ($row = mysqli_fetch_assoc($allProjects)) {
    $allRows[] = $row;
}

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
     SECTION 1 — WEBSITE PROJECTS (ProjectShowcase)
     ══════════════════════════════════════════════════════════ -->
<div style="margin-bottom:8px;">
    <h2 class="admin-page-title" style="font-size:1rem;margin-bottom:4px;">
        <span style="display:inline-flex;align-items:center;gap:8px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="#f97316" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
            Website Projects
        </span>
    </h2>
    <p class="admin-page-sub" style="margin-bottom:16px;">Projects displayed on the public-facing website. You can update their status and end date here.</p>
</div>

<?php if (count($showcaseRows) === 0): ?>
    <div class="admin-alert admin-alert-info" style="margin-bottom:32px;">No website projects found.</div>
<?php else: ?>
<div class="admin-table-wrap" style="margin-bottom:40px;">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Title</th>
                <th>Summary</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Status</th>
                <th style="text-align:center;">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($showcaseRows as $sc):
            $scStatusMap = [
                'Ongoing'   => 'admin-badge-active',
                'Completed' => 'admin-badge-track',
                'On Hold'   => 'admin-badge-inspection',
                'Cancelled' => 'admin-badge-danger',
            ];
            $scBadge = $scStatusMap[$sc['Status']] ?? 'admin-badge-track';
            $imgSrc  = !empty($sc['ImageURL'])
                ? BASE_URL . '/' . ltrim(htmlspecialchars($sc['ImageURL']), '/')
                : null;
        ?>
        <tr>
            <td>
                <div style="display:flex;align-items:center;gap:10px;">
                    <?php if ($imgSrc): ?>
                        <img src="<?= $imgSrc ?>" alt="" style="width:44px;height:36px;object-fit:cover;border-radius:4px;flex-shrink:0;border:1px solid var(--admin-border);">
                    <?php endif; ?>
                    <div>
                        <div class="admin-card-title" style="font-size:0.85rem;margin-bottom:0;"><?= htmlspecialchars($sc['Title']) ?></div>
                        <span class="admin-table-sub">ID #<?= (int) $sc['ProjectShowcaseID'] ?></span>
                    </div>
                </div>
            </td>
            <td style="max-width:260px;">
                <span class="admin-table-sub" style="line-height:1.5;">
                    <?= htmlspecialchars(strlen($sc['Summary'] ?? '') > 90 ? substr($sc['Summary'], 0, 90) . '…' : ($sc['Summary'] ?? '—')) ?>
                </span>
            </td>
            <td><?= $sc['StartDate'] ? date('M d, Y', strtotime($sc['StartDate'])) : '—' ?></td>
            <td><?= $sc['EndDate']   ? date('M d, Y', strtotime($sc['EndDate']))   : '—' ?></td>
            <td><span class="admin-badge <?= $scBadge ?>"><?= htmlspecialchars($sc['Status']) ?></span></td>
            <td>
                <form method="POST" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;justify-content:center;">
                    <input type="hidden" name="showcase_id" value="<?= (int) $sc['ProjectShowcaseID'] ?>">
                    <select name="showcase_status" style="font-size:0.78rem;padding:5px 8px;border:1px solid var(--admin-border);border-radius:4px;">
                        <?php foreach (['Ongoing','On Hold','Completed','Cancelled'] as $s): ?>
                            <option value="<?= $s ?>" <?= $sc['Status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="date" name="showcase_end_date"
                           value="<?= htmlspecialchars($sc['EndDate'] ?? '') ?>"
                           style="font-size:0.78rem;padding:5px 8px;border:1px solid var(--admin-border);border-radius:4px;">
                    <button type="submit" name="update_showcase" class="admin-btn admin-btn-primary admin-btn-sm">Save</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<hr class="admin-divider" style="margin-bottom:32px;">

<!-- ══════════════════════════════════════════════════════════
     SECTION 2 — ACTIVE PROJECTS (Ongoing, internal)
     ══════════════════════════════════════════════════════════ -->
<div style="margin-bottom:8px;">
    <h2 class="admin-page-title" style="font-size:1rem;margin-bottom:4px;">
        <span style="display:inline-flex;align-items:center;gap:8px;">
            <span style="width:10px;height:10px;border-radius:50%;background:#f59e0b;flex-shrink:0;display:inline-block;"></span>
            Active Projects
        </span>
    </h2>
    <p class="admin-page-sub" style="margin-bottom:16px;">Currently ongoing internal project sites.</p>
</div>

<?php if (count($activeRows) === 0): ?>
    <div class="admin-alert admin-alert-info" style="margin-bottom:32px;">No ongoing projects at the moment.</div>
<?php else: ?>
    <div class="admin-card-grid" style="margin-bottom:40px;">
        <?php foreach ($activeRows as $project):
            $status        = managerProjectStatusLabel($project['ProjectStatus']);
            $updatesResult = mysqli_query($conn,
                "SELECT pu.*, e.Username FROM Project_Update pu
                 LEFT JOIN Employee e ON pu.EmployeeID = e.EmployeeID
                 WHERE pu.ProjectID = " . (int) $project['ProjectID'] . "
                 ORDER BY pu.UpdateDate DESC LIMIT 4"
            );
        ?>
            <?php include __DIR__ . '/../includes/manager/_project_card.php'; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<hr class="admin-divider" style="margin-bottom:32px;">

<!-- ══════════════════════════════════════════════════════════
     SECTION 3 — ALL INTERNAL PROJECTS
     ══════════════════════════════════════════════════════════ -->
<div style="margin-bottom:8px;">
    <h2 class="admin-page-title" style="font-size:1rem;margin-bottom:4px;">All Projects</h2>
    <p class="admin-page-sub" style="margin-bottom:16px;">Complete history of all internal project records across every status.</p>
</div>

<?php if (count($allRows) === 0): ?>
    <div class="admin-alert admin-alert-info">No projects found. Use the <strong>+ Add Project</strong> button to create one from an approved application.</div>
<?php else: ?>
    <div class="admin-card-grid">
        <?php foreach ($allRows as $project):
            $status        = managerProjectStatusLabel($project['ProjectStatus']);
            $updatesResult = mysqli_query($conn,
                "SELECT pu.*, e.Username FROM Project_Update pu
                 LEFT JOIN Employee e ON pu.EmployeeID = e.EmployeeID
                 WHERE pu.ProjectID = " . (int) $project['ProjectID'] . "
                 ORDER BY pu.UpdateDate DESC LIMIT 4"
            );
        ?>
            <?php include __DIR__ . '/../includes/manager/_project_card.php'; ?>
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

<?php include("../includes/manager/layout_end.php"); ?>
