<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("config/database.php");

$message = '';
$success = '';
$loginPrompt = isset($_GET['redirect']) && $_GET['redirect'] === 'login';
$isClient = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Client';

$user = null;
if ($isClient) {
    $userId = (int) $_SESSION['user_id'];
    $result = mysqli_query($conn, "SELECT * FROM Client WHERE UserID = {$userId} LIMIT 1");
    $user = mysqli_fetch_assoc($result);
}

$equipmentOfferings = [];
$equipmentResult = mysqli_query(
    $conn,
    "SELECT eo.*, e.EquipmentID AS LinkedEquipmentID, e.AvailabilityStatus AS FleetStatus
     FROM EquipmentOffering eo
     LEFT JOIN Equipment e ON e.EquipmentOfferingID = eo.EquipmentOfferingID
     ORDER BY eo.Name ASC"
);
while ($row = mysqli_fetch_assoc($equipmentResult)) {
    $equipmentOfferings[] = $row;
}

function applyFieldValue(array $pending, string $key, $fallback = '')
{
    return htmlspecialchars($pending[$key] ?? $fallback, ENT_QUOTES, 'UTF-8');
}

function isEquipmentAvailable(array $offering): bool
{
    if ($offering['AvailabilityStatus'] !== 'Available') {
        return false;
    }
    if (!empty($offering['FleetStatus']) && $offering['FleetStatus'] !== 'Available') {
        return false;
    }
    return true;
}

if (isset($_POST['submit'])) {
    if (!$isClient) {
        $_SESSION['pending_application'] = $_POST;
        header('Location: login.php?redirect=' . urlencode('apply.php'));
        exit;
    }

    $applicationType = trim($_POST['application_type'] ?? '');
    $userId = (int) $_SESSION['user_id'];

    if ($applicationType === 'Equipment Rental') {
        $offeringId = (int) ($_POST['equipment_offering_id'] ?? 0);
        $rentalStart = trim($_POST['rental_start_date'] ?? '');
        $rentalEnd = trim($_POST['rental_end_date'] ?? '');
        $needsOperator = isset($_POST['needs_operator']) ? 1 : 0;
        $description = trim($_POST['rental_notes'] ?? '');

        $selectedOffering = null;
        foreach ($equipmentOfferings as $offering) {
            if ((int) $offering['EquipmentOfferingID'] === $offeringId) {
                $selectedOffering = $offering;
                break;
            }
        }

        if (!$selectedOffering) {
            $message = 'Please select equipment from the fleet.';
        } elseif (!isEquipmentAvailable($selectedOffering)) {
            $message = 'The selected equipment is not available for rental right now. Please choose another item or check back later.';
        } elseif ($rentalStart === '' || $rentalEnd === '') {
            $message = 'Please provide the rental start and end dates.';
        } elseif ($rentalEnd < $rentalStart) {
            $message = 'Rental end date must be on or after the start date.';
        } else {
            $equipmentId = !empty($selectedOffering['LinkedEquipmentID']) ? (int) $selectedOffering['LinkedEquipmentID'] : 'NULL';
            $escType = mysqli_real_escape_string($conn, $applicationType);
            $escStart = mysqli_real_escape_string($conn, $rentalStart);
            $escEnd = mysqli_real_escape_string($conn, $rentalEnd);
            $rentalSummary = "Equipment rental request: {$selectedOffering['Name']}";
            if ($description !== '') {
                $rentalSummary .= "\n\n{$description}";
            }
            $escRentalSummary = mysqli_real_escape_string($conn, $rentalSummary);

            $sql = "INSERT INTO Application (
                        UserID, EquipmentID, ApplicationType, Description,
                        RentalStartDate, RentalEndDate, NeedsOperator, SubmissionDate, Status
                    ) VALUES (
                        {$userId}, {$equipmentId}, '{$escType}', '{$escRentalSummary}',
                        '{$escStart}', '{$escEnd}', {$needsOperator}, CURDATE(), 'Pending'
                    )";

            if (mysqli_query($conn, $sql)) {
                $success = 'Your equipment rental application has been submitted. We will review your request and notify you once it is approved.';
                unset($_SESSION['pending_application']);
            } else {
                $message = 'Unable to submit your application. Please try again or contact our office.';
            }
        }
    } elseif ($applicationType === 'New Project') {
        $projectTitle = trim($_POST['project_title'] ?? '');
        $projectLocation = trim($_POST['project_location'] ?? '');
        $projectDescription = trim($_POST['project_description'] ?? '');
        $proposalBudget = trim($_POST['proposal_budget'] ?? '');
        $projectStart = trim($_POST['project_start_date'] ?? '');
        $projectEnd = trim($_POST['project_end_date'] ?? '');
        $additionalNotes = trim($_POST['project_notes'] ?? '');

        if ($projectTitle === '' || $projectLocation === '' || $projectDescription === '') {
            $message = 'Please complete the project title, location, and description.';
        } elseif ($proposalBudget === '' || !is_numeric($proposalBudget) || (float) $proposalBudget <= 0) {
            $message = 'Please enter a valid proposed budget.';
        } elseif ($projectStart === '' || $projectEnd === '') {
            $message = 'Please provide the estimated project start and end dates.';
        } elseif ($projectEnd < $projectStart) {
            $message = 'Project end date must be on or after the start date.';
        } else {
            $escType = mysqli_real_escape_string($conn, $applicationType);
            $escTitle = mysqli_real_escape_string($conn, $projectTitle);
            $escLocation = mysqli_real_escape_string($conn, $projectLocation);
            $escBudget = number_format((float) $proposalBudget, 2, '.', '');
            $escStart = mysqli_real_escape_string($conn, $projectStart);
            $escEnd = mysqli_real_escape_string($conn, $projectEnd);

            $fullDescription = "Project: {$projectTitle}\nLocation: {$projectLocation}\n\n{$projectDescription}";
            if ($additionalNotes !== '') {
                $fullDescription .= "\n\nAdditional notes:\n{$additionalNotes}";
            }
            $escFullDescription = mysqli_real_escape_string($conn, $fullDescription);

            $sql = "INSERT INTO Application (
                        UserID, EquipmentID, ApplicationType, Description,
                        ProjectTitle, ProjectLocation, ProposalBudget,
                        ProjectStartDate, ProjectEndDate, SubmissionDate, Status
                    ) VALUES (
                        {$userId}, NULL, '{$escType}', '{$escFullDescription}',
                        '{$escTitle}', '{$escLocation}', {$escBudget},
                        '{$escStart}', '{$escEnd}', CURDATE(), 'Pending'
                    )";

            if (mysqli_query($conn, $sql)) {
                $success = 'Your project application has been submitted. You will receive updates on your dashboard once it is approved.';
                unset($_SESSION['pending_application']);
            } else {
                $message = 'Unable to submit your application. Please try again or contact our office.';
            }
        }
    } else {
        $message = 'Please choose an application type.';
    }
}

$pending = $_SESSION['pending_application'] ?? [];
$applicationType = $pending['application_type'] ?? ($_GET['type'] ?? '');
$selectedOfferingId = (int) ($pending['equipment_offering_id'] ?? ($_GET['equipment_id'] ?? 0));
$referenceProject = trim($_GET['project'] ?? '');

if ($referenceProject !== '' && $applicationType === '') {
    $applicationType = 'New Project';
}

include("includes/header.php");
include("includes/navbar.php");
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/apply.css">

<!-- ── Hero ─────────────────────────────────────────────────── -->
<section class="ap-hero">
    <div class="ap-hero-bg" style="background-image:url('<?= BASE_URL ?>/assets/projects/roadConcreting.jpg');"></div>
    <div class="ap-hero-overlay"></div>
    <div class="container ap-hero-content">
        <div class="hero-eyebrow">Client Applications</div>
        <h1 class="hero-title">Start Your Application.</h1>
        <p class="hero-sub">Whether you're planning a construction project or need heavy equipment for your next job, submit your request and our team will assist you every step of the way.</p>
    </div>
</section>

<!-- ── Body ─────────────────────────────────────────────────── -->
<div class="container ap-body">
    <div class="ap-layout">

        <!-- ── Sidebar ──────────────────────────────────────── -->
        <aside class="ap-sidebar">

            <div class="ap-sidebar-dark">
                <div class="ap-sidebar-label">How It Works</div>
                <ol class="ap-steps">
                    <li>
                        <span class="ap-step-num">1</span>
                        <div>
                            <strong>Choose a type</strong>
                            <p>Select either a New Project or Equipment Rental application.</p>
                        </div>
                    </li>
                    <li>
                        <span class="ap-step-num">2</span>
                        <div>
                            <strong>Fill in the details</strong>
                            <p>Provide your project specs, location, budget, and timeline.</p>
                        </div>
                    </li>
                    <li>
                        <span class="ap-step-num">3</span>
                        <div>
                            <strong>Submit &amp; wait</strong>
                            <p>We'll review your submission and notify you within 2 business days.</p>
                        </div>
                    </li>
                </ol>
            </div>

            <div class="ap-sidebar-info">
                <div class="ap-sidebar-info-label">Need help?</div>
                <p>Contact our office directly for questions about your application or project requirements.</p>
                <a href="<?= BASE_URL ?>/contact.php" class="ap-contact-link">
                    Go to Contact Page →
                </a>
            </div>

        </aside>

        <!-- ── Form panel ────────────────────────────────────── -->
        <div class="ap-form-panel">

            <?php if ($loginPrompt && !$isClient): ?>
                <div class="ap-alert ap-alert-warn">
                    Please <a href="<?= BASE_URL ?>/login.php?redirect=<?= urlencode('apply.php') ?>">log in</a> or <a href="<?= BASE_URL ?>/signup.php?redirect=<?= urlencode('apply.php') ?>">sign up</a> to submit your application.
                </div>
            <?php endif; ?>

            <?php if ($message): ?>
                <div class="ap-alert ap-alert-error"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="ap-alert ap-alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <!-- Submitting-as notice -->
            <?php if ($isClient && $user): ?>
                <div class="ap-submitting-as">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    Submitting as <strong><?= htmlspecialchars(trim(($user['Client_FirstName'] ?? '') . ' ' . ($user['Client_LastName'] ?? ''))) ?></strong>
                    <span class="ap-submitting-username">(<?= htmlspecialchars($user['Client_Username'] ?? '') ?>)</span>
                </div>
            <?php elseif (!$isClient): ?>
                <div class="ap-guest-notice">
                    You can fill out the form now. You'll be asked to log in or sign up when you submit.
                </div>
            <?php endif; ?>

            <form method="post" action="apply.php" id="applicationForm">

                <!-- Application type -->
                <div class="ap-field">
                    <label for="application_type">Application Type</label>
                    <div class="ap-select-wrap">
                        <select id="application_type" name="application_type" required>
                            <option value="">Choose type…</option>
                            <option value="New Project"      <?= $applicationType === 'New Project'      ? 'selected' : '' ?>>New Project</option>
                            <option value="Equipment Rental" <?= $applicationType === 'Equipment Rental' ? 'selected' : '' ?>>Equipment Rental</option>
                        </select>
                        <svg class="ap-select-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                    </div>
                </div>

                <!-- ── Equipment Rental fields ─────────────── -->
                <div id="equipment-section" class="apply-section ap-section-block" style="display:none;">

                    <div class="ap-section-title">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13" rx="2"/><path d="M16 8h4l3 5v3h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                        Equipment Details
                    </div>

                    <div class="ap-info-note">
                        Select equipment from our fleet. Only items marked as available can be submitted for rental.
                    </div>

                    <div class="ap-field">
                        <label for="equipment_offering_id">Equipment</label>
                        <div class="ap-select-wrap">
                            <select id="equipment_offering_id" name="equipment_offering_id">
                                <option value="">Choose equipment…</option>
                                <?php foreach ($equipmentOfferings as $offering): ?>
                                    <?php $available = isEquipmentAvailable($offering); ?>
                                    <option value="<?= (int) $offering['EquipmentOfferingID'] ?>"
                                        data-available="<?= $available ? '1' : '0' ?>"
                                        data-status="<?= htmlspecialchars($offering['AvailabilityStatus']) ?>"
                                        <?= $selectedOfferingId === (int) $offering['EquipmentOfferingID'] ? 'selected' : '' ?>
                                        <?= !$available ? 'disabled' : '' ?>>
                                        <?= htmlspecialchars($offering['Name']) ?><?= $offering['Model'] ? ' (' . htmlspecialchars($offering['Model']) . ')' : '' ?>
                                        — <?= $available ? 'Available' : htmlspecialchars($offering['AvailabilityStatus']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <svg class="ap-select-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                        </div>
                        <p id="equipmentAvailabilityNote" class="ap-field-note"></p>
                    </div>

                    <div class="ap-row-2">
                        <div class="ap-field">
                            <label for="rental_start_date">Rental Start Date</label>
                            <input type="date" id="rental_start_date" name="rental_start_date"
                                   value="<?= applyFieldValue($pending, 'rental_start_date') ?>">
                        </div>
                        <div class="ap-field">
                            <label for="rental_end_date">Rental End Date</label>
                            <input type="date" id="rental_end_date" name="rental_end_date"
                                   value="<?= applyFieldValue($pending, 'rental_end_date') ?>">
                        </div>
                    </div>

                    <div class="ap-field">
                        <label class="ap-checkbox-label">
                            <input type="checkbox" id="needs_operator" name="needs_operator" value="1"
                                   <?= !empty($pending['needs_operator']) ? 'checked' : '' ?>>
                            <span class="ap-checkbox-box"></span>
                            I need a certified operator for this equipment
                        </label>
                    </div>

                    <div class="ap-field">
                        <label for="rental_notes">Additional Notes <span class="ap-optional">(optional)</span></label>
                        <textarea id="rental_notes" name="rental_notes" rows="4"
                                  placeholder="Site address, delivery requirements, or other rental details…"><?= applyFieldValue($pending, 'rental_notes') ?></textarea>
                    </div>
                </div>

                <!-- ── New Project fields ──────────────────── -->
                <div id="project-section" class="apply-section ap-section-block" style="display:none;">

                    <div class="ap-section-title">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                        Project Details
                    </div>

                    <?php if ($referenceProject !== ''): ?>
                        <div class="ap-info-note">
                            Reference from portfolio: <strong><?= htmlspecialchars($referenceProject) ?></strong>. Enter your own project details below.
                        </div>
                    <?php endif; ?>

                    <div class="ap-field">
                        <label for="project_title">Project Title</label>
                        <input type="text" id="project_title" name="project_title"
                               placeholder="e.g. Road Concreting – Barangay San Jose"
                               value="<?= applyFieldValue($pending, 'project_title', $referenceProject) ?>">
                    </div>

                    <div class="ap-field">
                        <label for="project_location">Project Location</label>
                        <input type="text" id="project_location" name="project_location"
                               placeholder="Barangay, municipality, or full site address in Zamboanga del Norte"
                               value="<?= applyFieldValue($pending, 'project_location') ?>">
                    </div>

                    <div class="ap-field">
                        <label for="project_description">Project Description</label>
                        <textarea id="project_description" name="project_description" rows="5"
                                  placeholder="Describe the scope of work, expected outcomes, and any special requirements…"><?= applyFieldValue($pending, 'project_description') ?></textarea>
                    </div>

                    <div class="ap-field">
                        <label for="proposal_budget">Proposed Budget (PHP)</label>
                        <div class="ap-input-prefix-wrap">
                            <span class="ap-input-prefix">₱</span>
                            <input type="number" id="proposal_budget" name="proposal_budget"
                                   min="1" step="0.01" placeholder="Estimated project budget"
                                   value="<?= applyFieldValue($pending, 'proposal_budget') ?>">
                        </div>
                    </div>

                    <div class="ap-row-2">
                        <div class="ap-field">
                            <label for="project_start_date">Estimated Start Date</label>
                            <input type="date" id="project_start_date" name="project_start_date"
                                   value="<?= applyFieldValue($pending, 'project_start_date') ?>">
                        </div>
                        <div class="ap-field">
                            <label for="project_end_date">Estimated End Date</label>
                            <input type="date" id="project_end_date" name="project_end_date"
                                   value="<?= applyFieldValue($pending, 'project_end_date') ?>">
                        </div>
                    </div>

                    <div class="ap-field">
                        <label for="project_notes">Additional Notes <span class="ap-optional">(optional)</span></label>
                        <textarea id="project_notes" name="project_notes" rows="3"
                                  placeholder="Permits, access constraints, preferred timeline, or other details…"><?= applyFieldValue($pending, 'project_notes') ?></textarea>
                    </div>
                </div>

                <button type="submit" name="submit" id="submitApplication" class="ap-submit">
                    Submit Application
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </button>

            </form>

            <?php if (!$isClient): ?>
                <p class="ap-switch">
                    Don't have an account?
                    <a href="<?= BASE_URL ?>/signup.php?redirect=<?= urlencode('apply.php') ?>">Sign up</a>
                    &nbsp;·&nbsp;
                    <a href="<?= BASE_URL ?>/login.php?redirect=<?= urlencode('apply.php') ?>">Log in</a>
                </p>
            <?php endif; ?>

        </div><!-- /ap-form-panel -->
    </div><!-- /ap-layout -->
</div>

<script>
(function () {
    var typeSelect       = document.getElementById('application_type');
    var equipmentSection = document.getElementById('equipment-section');
    var projectSection   = document.getElementById('project-section');
    var equipmentSelect  = document.getElementById('equipment_offering_id');
    var availabilityNote = document.getElementById('equipmentAvailabilityNote');
    var submitButton     = document.getElementById('submitApplication');

    var equipmentFields = ['equipment_offering_id', 'rental_start_date', 'rental_end_date'];
    var projectFields   = ['project_title', 'project_location', 'project_description', 'proposal_budget', 'project_start_date', 'project_end_date'];

    function setRequired(fields, required) {
        fields.forEach(function (id) {
            var el = document.getElementById(id);
            if (el) el.required = required;
        });
    }

    function updateEquipmentNote() {
        if (!equipmentSelect || !availabilityNote) return;
        var option = equipmentSelect.options[equipmentSelect.selectedIndex];
        if (!option || !option.value) {
            availabilityNote.textContent = '';
            availabilityNote.className = 'ap-field-note';
            submitButton.disabled = false;
            return;
        }
        if (option.dataset.available === '1') {
            availabilityNote.textContent = 'This equipment is available for rental.';
            availabilityNote.className = 'ap-field-note ap-field-note-success';
            submitButton.disabled = false;
        } else {
            availabilityNote.textContent = 'This equipment is currently ' + option.dataset.status + ' and cannot be submitted.';
            availabilityNote.className = 'ap-field-note ap-field-note-error';
            submitButton.disabled = true;
        }
    }

    function toggleSections() {
        var type = typeSelect.value;
        equipmentSection.style.display = type === 'Equipment Rental' ? 'block' : 'none';
        projectSection.style.display   = type === 'New Project'      ? 'block' : 'none';
        setRequired(equipmentFields, type === 'Equipment Rental');
        setRequired(projectFields,   type === 'New Project');
        if (type === 'Equipment Rental') {
            updateEquipmentNote();
        } else {
            submitButton.disabled = false;
        }
    }

    typeSelect.addEventListener('change', toggleSections);
    if (equipmentSelect) equipmentSelect.addEventListener('change', updateEquipmentNote);
    toggleSections();
})();
</script>

<?php include("includes/footer.php"); ?>
