<?php
include("../config/database.php");
include("../includes/manager/helpers.php");

managerRequireLogin('manager/mgr_projects.php');

$mgrEmployee       = adminCurrentEmployee($conn);
$mgrPendingRentals = managerPendingRentals($conn);
$employeeId        = (int) $_SESSION['user_id'];

$successMsg = '';
$errorMsg   = '';
$reopenAddModal = false;

// ── UNPUBLISH FROM WEBSITE ──────────────────────────────────
if (isset($_POST['unpublish_from_website'], $_POST['showcase_id'], $_POST['project_id'])) {
    $scDel  = (int) $_POST['showcase_id'];
    $projId = (int) $_POST['project_id'];
    if ($scDel > 0) {
        mysqli_query($conn, "DELETE FROM ProjectShowcase WHERE ProjectShowcaseID = {$scDel}");

        // Notify client
        $unpubMsg = mysqli_real_escape_string($conn,
            "This project has been removed from the public website showcase.");
        mysqli_query($conn,
            "INSERT INTO Project_Update (ProjectID, EmployeeID, Status, Description, UpdateDate)
             VALUES ({$projId}, {$employeeId}, 'Reviewed', '{$unpubMsg}', CURDATE())"
        );
        $successMsg = "Project removed from the website.";
    }
}

// ── PUBLISH PROJECT TO WEBSITE ──────────────────────────────
if (isset($_POST['publish_to_website'], $_POST['project_id'])) {
    $pubId      = (int) $_POST['project_id'];
    $pubTitle   = trim(mysqli_real_escape_string($conn, $_POST['pub_title']));
    $pubSummary = trim(mysqli_real_escape_string($conn, $_POST['pub_summary']));
    $pubStatus  = mysqli_real_escape_string($conn, $_POST['pub_status']);

    // Absolute path for file upload (script lives in manager/, assets/ is one level up)
    $uploadDir = dirname(__DIR__) . '/assets/projects/';
    $uploadUrl = 'assets/projects/'; // stored in DB as relative path from site root

    // Fetch project details for client update
    $projRow = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT p.StartDate, p.EndDate, a.ProjectTitle, a.ProjectLocation, a.UserID
         FROM Project p
         JOIN Application a ON p.ApplicationID = a.ApplicationID
         WHERE p.ProjectID = {$pubId} LIMIT 1"
    ));

    // Check if PM has marked it as started
    $startedCheck = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT UpdateID FROM Project_Update
         WHERE ProjectID = {$pubId} AND Description LIKE '%marked as started%'
         LIMIT 1"
    ));

    if ($pubTitle === '') {
        $errorMsg = "Title is required to publish.";
    } elseif (!$startedCheck) {
        $errorMsg = "You must mark this project as started before publishing it to the website.";
    } elseif (empty($_FILES['pub_photo']['name'])) {
        $errorMsg = "A photo is required to publish the project to the website.";
    } else {
        $ext = strtolower(pathinfo($_FILES['pub_photo']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','webp'])) {
            $errorMsg = "Invalid image format. Use JPG, PNG, or WEBP.";
        } else {
            // Ensure directory exists
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $filename = 'proj_' . $pubId . '_' . time() . '.' . $ext;
            $destAbs  = $uploadDir . $filename;          // absolute — for move_uploaded_file
            $destRel  = $uploadUrl . $filename;          // relative — stored in DB / used by website

            if (!move_uploaded_file($_FILES['pub_photo']['tmp_name'], $destAbs)) {
                $errorMsg = "Failed to upload image. Please try again.";
            } else {
                $escImg   = mysqli_real_escape_string($conn, $destRel);
                $startVal = !empty($projRow['StartDate'])
                    ? "'" . mysqli_real_escape_string($conn, $projRow['StartDate']) . "'"
                    : 'NULL';
                $endVal   = !empty($projRow['EndDate'])
                    ? "'" . mysqli_real_escape_string($conn, $projRow['EndDate']) . "'"
                    : 'NULL';

                // Check if already on website
                $exists = mysqli_fetch_assoc(mysqli_query($conn,
                    "SELECT ProjectShowcaseID FROM ProjectShowcase WHERE Title='{$pubTitle}' LIMIT 1"
                ));

                if ($exists) {
                    $scID = (int) $exists['ProjectShowcaseID'];
                    mysqli_query($conn,
                        "UPDATE ProjectShowcase SET Summary='{$pubSummary}', Status='{$pubStatus}',
                         StartDate={$startVal}, EndDate={$endVal}, ImageURL='{$escImg}'
                         WHERE ProjectShowcaseID={$scID}"
                    );
                    $successMsg = "Website showcase updated.";
                } else {
                    mysqli_query($conn,
                        "INSERT INTO ProjectShowcase (Title, Summary, StartDate, EndDate, Status, ImageURL)
                         VALUES ('{$pubTitle}', '{$pubSummary}', {$startVal}, {$endVal}, '{$pubStatus}', '{$escImg}')"
                    );
                    $successMsg = "Project published to website successfully.";
                }

                // Post client-visible update
                $siteStatus = $pubStatus === 'Ongoing' ? 'has started' : 'has been added to the website';
                $clientMsg  = "Project \"{$pubTitle}\" {$siteStatus}"
                            . (!empty($projRow['ProjectLocation']) ? " at {$projRow['ProjectLocation']}" : "")
                            . ". You can now view it on our projects page.";
                $clientMsg  = mysqli_real_escape_string($conn, $clientMsg);
                mysqli_query($conn,
                    "INSERT INTO Project_Update (ProjectID, EmployeeID, Status, Description, UpdateDate)
                     VALUES ({$pubId}, {$employeeId}, 'Approved', '{$clientMsg}', CURDATE())"
                );
            }
        }
    }
}

// ── MARK SITE STARTED / NOT STARTED ────────────────────────
if (isset($_POST['mark_site_status'], $_POST['project_id'], $_POST['site_started'])) {
    $msId      = (int) $_POST['project_id'];
    $msStarted = $_POST['site_started'] === '1';

    // Fetch project info
    $msRow = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT p.StartDate, a.ProjectTitle, a.ProjectLocation
         FROM Project p
         JOIN Application a ON p.ApplicationID = a.ApplicationID
         WHERE p.ProjectID = {$msId} LIMIT 1"
    ));

    $label     = $msStarted ? 'started' : 'not yet started';
    $clientMsg = "Site update: " . htmlspecialchars_decode($msRow['ProjectTitle'] ?? 'Project')
               . " is now marked as {$label}.";
    if (!$msStarted && !empty($msRow['ProjectLocation'])) {
        $clientMsg .= " Location: {$msRow['ProjectLocation']}.";
    }
    $clientMsg = mysqli_real_escape_string($conn, $clientMsg);

    mysqli_query($conn,
        "INSERT INTO Project_Update (ProjectID, EmployeeID, Status, Description, UpdateDate)
         VALUES ({$msId}, {$employeeId}, 'Reviewed', '{$clientMsg}', CURDATE())"
    );
    $successMsg = "Site status posted to client updates.";
}
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

// ── ADD PROJECT (manual entry — no application required) ────
if (isset($_POST['add_project'])) {
    $projTitle     = trim($_POST['project_title'] ?? '');
    $projLocation  = trim($_POST['project_location'] ?? '');
    $projDesc      = trim($_POST['description'] ?? '');
    $clientName    = trim($_POST['client_name'] ?? '');
    $clientContact = trim($_POST['client_contact'] ?? '');
    $proposalBudget = !empty($_POST['proposal_budget']) ? (float) $_POST['proposal_budget'] : null;
    $startDate      = !empty($_POST['start_date'])  ? $_POST['start_date']  : null;
    $endDate        = !empty($_POST['end_date'])    ? $_POST['end_date']    : null;
    $paymentStatus  = $_POST['payment_status']  ?? 'Unpaid';
    $projectStatus  = $_POST['project_status']  ?? 'Ongoing';

    // Validation
    if ($projTitle === '') {
        $errorMsg = "Project title is required.";
    } elseif ($startDate && $endDate && $endDate < $startDate) {
        $errorMsg = "End date cannot be before start date.";
    } else {
        // We need a Client record to attach the Application to.
        // For physical/manual entries, look up or create a placeholder client.
        // Strategy: find an existing client by name, or use a generic "Walk-in" client.
        // Actually — the cleanest approach is to create a bare Application
        // with no UserID requirement by using a system/walk-in user approach.
        // Since Application.UserID is NOT NULL FK to Client, we'll use the PM's
        // employee record concept but Application needs a Client.
        // Best: create Application with the first available client (UserID=1 fallback),
        // OR better: we add a system client row. Let's use a dedicated walk-in placeholder.

        // Find or create a "Walk-in / Manual Entry" client placeholder
        $placeholder = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT UserID FROM Client WHERE Client_Username = 'manual_entry' LIMIT 1"
        ));

        if (!$placeholder) {
            $ph = mysqli_prepare($conn,
                "INSERT IGNORE INTO Client
                 (Client_FirstName, Client_LastName, Client_Username, Client_Password, Client_Email)
                 VALUES ('Manual','Entry','manual_entry',?,?)"
            );
            $phPw    = password_hash('placeholder_' . time(), PASSWORD_DEFAULT);
            $phEmail = 'manual_entry@yosech.internal';
            mysqli_stmt_bind_param($ph, "ss", $phPw, $phEmail);
            mysqli_stmt_execute($ph);
            $walkinId = (int) mysqli_insert_id($conn);
        } else {
            $walkinId = (int) $placeholder['UserID'];
        }

        // Build a description that includes client name/contact if provided
        $fullDesc = $projDesc;
        if ($clientName !== '') {
            $fullDesc = "Client: {$clientName}"
                      . ($clientContact ? " | Contact: {$clientContact}" : '')
                      . ($fullDesc !== '' ? "\n\n{$fullDesc}" : '');
        }

        $escTitle    = mysqli_real_escape_string($conn, $projTitle);
        $escLocation = mysqli_real_escape_string($conn, $projLocation);
        $escDesc     = mysqli_real_escape_string($conn, $fullDesc);
        $escBudget   = $proposalBudget !== null ? $proposalBudget : 'NULL';
        $escStart    = $startDate  ? "'" . mysqli_real_escape_string($conn, $startDate)  . "'" : 'NULL';
        $escEnd      = $endDate    ? "'" . mysqli_real_escape_string($conn, $endDate)    . "'" : 'NULL';

        // Create Application record
        mysqli_query($conn,
            "INSERT INTO Application
             (UserID, ApplicationType, Description, ProjectTitle, ProjectLocation,
              ProposalBudget, ProjectStartDate, ProjectEndDate, SubmissionDate, Status)
             VALUES
             ({$walkinId}, 'New Project', '{$escDesc}', '{$escTitle}', '{$escLocation}',
              {$escBudget}, {$escStart}, {$escEnd}, CURDATE(), 'Approved')"
        );
        $newAppId = (int) mysqli_insert_id($conn);

        if ($newAppId > 0) {
            // Create Project record linked to that Application
            $escPayment = mysqli_real_escape_string($conn, $paymentStatus);
            $escStatus  = mysqli_real_escape_string($conn, $projectStatus);

            mysqli_query($conn,
                "INSERT INTO Project
                 (ApplicationID, ProposalBudget, ProposalStatus,
                  StartDate, EndDate, ProjectStatus, Description, ProjectPaymentStatus)
                 VALUES
                 ({$newAppId}, {$escBudget}, 'Approved',
                  {$escStart}, {$escEnd}, '{$escStatus}', '{$escDesc}', '{$escPayment}')"
            );
            $successMsg = "Project \"{$projTitle}\" created successfully.";
        } else {
            $errorMsg = "Failed to create project. Please try again.";
        }
    }

    if ($errorMsg) {
        $reopenAddModal = true;
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
<div id="addProjectModal"
     style="display:<?= !empty($reopenAddModal) ? 'flex' : 'none' ?>;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;padding:16px;"
     onclick="if(event.target===this)this.style.display='none'">
    <div style="background:#fff;border-radius:12px;width:100%;max-width:600px;max-height:92vh;overflow-y:auto;position:relative;box-shadow:0 20px 60px rgba(0,0,0,0.2);">

        <!-- Header -->
        <div style="padding:24px 28px 18px;border-bottom:1px solid var(--admin-border);display:flex;align-items:flex-start;justify-content:space-between;gap:16px;position:sticky;top:0;background:#fff;z-index:1;">
            <div>
                <div style="font-size:1rem;font-weight:800;color:var(--admin-text);margin-bottom:3px;">Add New Project</div>
                <div style="font-size:0.78rem;color:var(--admin-muted);">Manually add a project — for physical applications or walk-in clients.</div>
            </div>
            <button type="button" onclick="document.getElementById('addProjectModal').style.display='none'"
                    style="width:32px;height:32px;border-radius:6px;border:1px solid var(--admin-border);background:var(--ysc-bg);color:var(--admin-muted);cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:1rem;">✕</button>
        </div>

        <!-- Body -->
        <div style="padding:24px 28px;">
            <form method="POST" id="addProjectForm">

                <!-- Project info -->
                <div style="font-size:0.7rem;font-weight:800;letter-spacing:0.07em;text-transform:uppercase;color:var(--admin-muted);margin-bottom:12px;">Project Information</div>

                <div class="admin-field">
                    <label>Project Title <span style="color:#dc2626;">*</span></label>
                    <input type="text" name="project_title"
                           value="<?= htmlspecialchars($_POST['project_title'] ?? '') ?>"
                           placeholder="e.g. Road Concreting – Barangay San Jose"
                           required>
                </div>

                <div class="admin-field">
                    <label>Location</label>
                    <input type="text" name="project_location"
                           value="<?= htmlspecialchars($_POST['project_location'] ?? '') ?>"
                           placeholder="Barangay, municipality, or full site address">
                </div>

                <div class="admin-field">
                    <label>Description / Scope of Work</label>
                    <textarea name="description" rows="4"
                              placeholder="Describe the scope of work, deliverables, and key milestones…"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                </div>

                <div style="height:1px;background:var(--admin-border);margin:16px 0;"></div>

                <!-- Client info -->
                <div style="font-size:0.7rem;font-weight:800;letter-spacing:0.07em;text-transform:uppercase;color:var(--admin-muted);margin-bottom:12px;">Client Information</div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                    <div class="admin-field">
                        <label>Client Name</label>
                        <input type="text" name="client_name"
                               value="<?= htmlspecialchars($_POST['client_name'] ?? '') ?>"
                               placeholder="Full name of the client">
                    </div>
                    <div class="admin-field">
                        <label>Client Contact</label>
                        <input type="text" name="client_contact"
                               value="<?= htmlspecialchars($_POST['client_contact'] ?? '') ?>"
                               placeholder="Phone or email">
                    </div>
                </div>

                <div style="height:1px;background:var(--admin-border);margin:16px 0;"></div>

                <!-- Project details -->
                <div style="font-size:0.7rem;font-weight:800;letter-spacing:0.07em;text-transform:uppercase;color:var(--admin-muted);margin-bottom:12px;">Project Details</div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                    <div class="admin-field">
                        <label>Start Date</label>
                        <input type="date" name="start_date"
                               value="<?= htmlspecialchars($_POST['start_date'] ?? '') ?>">
                    </div>
                    <div class="admin-field">
                        <label>End Date</label>
                        <input type="date" name="end_date"
                               value="<?= htmlspecialchars($_POST['end_date'] ?? '') ?>">
                    </div>
                    <div class="admin-field">
                        <label>Project Status</label>
                        <select name="project_status">
                            <?php foreach (['Ongoing','On Hold','Completed','Cancelled'] as $s): ?>
                                <option value="<?= $s ?>" <?= ($_POST['project_status'] ?? 'Ongoing')===$s?'selected':'' ?>><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="admin-field">
                        <label>Payment Status</label>
                        <select name="payment_status">
                            <?php foreach (['Unpaid','Partial','Paid'] as $ps): ?>
                                <option value="<?= $ps ?>" <?= ($_POST['payment_status'] ?? 'Unpaid')===$ps?'selected':'' ?>><?= $ps ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="admin-field">
                    <label>Proposed Budget (₱)</label>
                    <div style="position:relative;">
                        <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--admin-muted);font-size:0.86rem;pointer-events:none;">₱</span>
                        <input type="number" name="proposal_budget"
                               step="0.01" min="0" placeholder="0.00"
                               value="<?= htmlspecialchars($_POST['proposal_budget'] ?? '') ?>"
                               style="padding-left:28px;">
                    </div>
                </div>

                <!-- Footer -->
                <div style="display:flex;align-items:center;justify-content:flex-end;gap:10px;padding-top:16px;border-top:1px solid var(--admin-border);">
                    <button type="button" class="admin-btn admin-btn-outline"
                            onclick="document.getElementById('addProjectModal').style.display='none'">Cancel</button>
                    <button type="submit" name="add_project" class="admin-btn admin-btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                        Create Project
                    </button>
                </div>

            </form>
        </div>
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
function pjToggleEl(id, btn) {
    var el = document.getElementById(id);
    if (!el) return;
    var isOpen = el.style.display !== 'none';
    el.style.display = isOpen ? 'none' : 'block';
    if (btn) {
        btn.textContent = isOpen ? (btn.dataset.labelOpen || 'Show') : (btn.dataset.labelClose || 'Cancel');
        btn.classList.toggle('active', !isOpen);
    }
}

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
