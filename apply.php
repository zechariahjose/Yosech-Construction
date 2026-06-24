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

<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/home.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/auth.css">

<div class="container">
    <div class="ysc-page-header">
        <h1 class="ysc-page-title">Application Form</h1>
        <p class="ysc-page-sub">Submit a construction project proposal or request equipment rental. Choose your application type and complete the required details below.</p>
    </div>

    <div class="row justify-content-center pb-5">
        <div class="col-lg-8">
            <div class="home-panel">
                <?php if ($loginPrompt && !$isClient): ?>
                    <div class="auth-alert auth-alert-error mb-3">Please <a href="<?= BASE_URL ?>/login.php?redirect=<?= urlencode('apply.php') ?>">log in</a> or <a href="<?= BASE_URL ?>/signup.php?redirect=<?= urlencode('apply.php') ?>">sign up</a> to submit your application.</div>
                <?php endif; ?>

                <?php if ($message): ?>
                    <div class="auth-alert auth-alert-error mb-3"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="auth-alert auth-alert-success mb-3"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <?php if ($isClient && $user): ?>
                    <div class="home-info-note mb-4">
                        Submitting as <strong><?= htmlspecialchars(trim(($user['Client_FirstName'] ?? '') . ' ' . ($user['Client_LastName'] ?? ''))) ?></strong>
                        (<?= htmlspecialchars($user['Client_Username'] ?? '') ?>)
                    </div>
                <?php elseif (!$isClient): ?>
                    <p class="text-muted small mb-4">You can fill out the form now. You'll be asked to log in or sign up when you submit.</p>
                <?php endif; ?>

                <form method="post" action="apply.php" id="applicationForm">
                    <div class="auth-field">
                        <label for="application_type">Application Type</label>
                        <div class="auth-input-wrap">
                            <select id="application_type" name="application_type" class="form-select" style="padding:11px 14px;border-radius:6px;" required>
                                <option value="">Choose type</option>
                                <option value="New Project" <?= $applicationType === 'New Project' ? 'selected' : '' ?>>New Project</option>
                                <option value="Equipment Rental" <?= $applicationType === 'Equipment Rental' ? 'selected' : '' ?>>Equipment Rental</option>
                            </select>
                        </div>
                    </div>

                    <div id="equipment-section" class="apply-section" style="display:none;">
                        <div class="home-info-note home-info-note-muted mb-4">
                            Select equipment from our fleet. Only items marked as available can be submitted for rental.
                        </div>

                        <div class="auth-field">
                            <label for="equipment_offering_id">Equipment</label>
                            <div class="auth-input-wrap">
                                <select id="equipment_offering_id" name="equipment_offering_id" class="form-select" style="padding:11px 14px;border-radius:6px;">
                                    <option value="">Choose equipment</option>
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
                            </div>
                            <p id="equipmentAvailabilityNote" class="small text-muted mt-2 mb-0"></p>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="auth-field">
                                    <label for="rental_start_date">Rental Start Date</label>
                                    <div class="auth-input-wrap">
                                        <input type="date" id="rental_start_date" name="rental_start_date" class="form-control" style="padding:11px 14px;border-radius:6px;" value="<?= applyFieldValue($pending, 'rental_start_date') ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="auth-field">
                                    <label for="rental_end_date">Rental End Date</label>
                                    <div class="auth-input-wrap">
                                        <input type="date" id="rental_end_date" name="rental_end_date" class="form-control" style="padding:11px 14px;border-radius:6px;" value="<?= applyFieldValue($pending, 'rental_end_date') ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="auth-field">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="needs_operator" name="needs_operator" value="1" <?= !empty($pending['needs_operator']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="needs_operator">I need a certified operator for this equipment</label>
                            </div>
                        </div>

                        <div class="auth-field">
                            <label for="rental_notes">Additional Notes <span class="text-muted">(optional)</span></label>
                            <div class="auth-input-wrap">
                                <textarea id="rental_notes" name="rental_notes" class="form-control" rows="4" style="padding:11px 14px;border-radius:6px;" placeholder="Site address, delivery requirements, or other rental details..."><?= applyFieldValue($pending, 'rental_notes') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div id="project-section" class="apply-section" style="display:none;">
                        <?php if ($referenceProject !== ''): ?>
                            <div class="home-info-note mb-4">
                                Reference from portfolio: <strong><?= htmlspecialchars($referenceProject) ?></strong>. Enter your own project details below.
                            </div>
                        <?php endif; ?>

                        <div class="auth-field">
                            <label for="project_title">Project Title</label>
                            <div class="auth-input-wrap">
                                <input type="text" id="project_title" name="project_title" class="form-control" style="padding:11px 14px;border-radius:6px;" placeholder="e.g. Road Concreting – Barangay San Jose" value="<?= applyFieldValue($pending, 'project_title', $referenceProject) ?>">
                            </div>
                        </div>

                        <div class="auth-field">
                            <label for="project_location">Project Location</label>
                            <div class="auth-input-wrap">
                                <input type="text" id="project_location" name="project_location" class="form-control" style="padding:11px 14px;border-radius:6px;" placeholder="Barangay, municipality, or full site address in Zamboanga del Norte" value="<?= applyFieldValue($pending, 'project_location') ?>">
                            </div>
                        </div>

                        <div class="auth-field">
                            <label for="project_description">Project Description</label>
                            <div class="auth-input-wrap">
                                <textarea id="project_description" name="project_description" class="form-control" rows="5" style="padding:11px 14px;border-radius:6px;" placeholder="Describe what the project is about, scope of work, expected outcomes, and any special requirements..."><?= applyFieldValue($pending, 'project_description') ?></textarea>
                            </div>
                        </div>

                        <div class="auth-field">
                            <label for="proposal_budget">Proposed Budget (PHP)</label>
                            <div class="auth-input-wrap">
                                <input type="number" id="proposal_budget" name="proposal_budget" class="form-control" style="padding:11px 14px;border-radius:6px;" min="1" step="0.01" placeholder="Estimated project budget" value="<?= applyFieldValue($pending, 'proposal_budget') ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="auth-field">
                                    <label for="project_start_date">Estimated Start Date</label>
                                    <div class="auth-input-wrap">
                                        <input type="date" id="project_start_date" name="project_start_date" class="form-control" style="padding:11px 14px;border-radius:6px;" value="<?= applyFieldValue($pending, 'project_start_date') ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="auth-field">
                                    <label for="project_end_date">Estimated End Date</label>
                                    <div class="auth-input-wrap">
                                        <input type="date" id="project_end_date" name="project_end_date" class="form-control" style="padding:11px 14px;border-radius:6px;" value="<?= applyFieldValue($pending, 'project_end_date') ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="auth-field">
                            <label for="project_notes">Additional Notes <span class="text-muted">(optional)</span></label>
                            <div class="auth-input-wrap">
                                <textarea id="project_notes" name="project_notes" class="form-control" rows="3" style="padding:11px 14px;border-radius:6px;" placeholder="Permits, access constraints, preferred timeline, or other details..."><?= applyFieldValue($pending, 'project_notes') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <button type="submit" name="submit" id="submitApplication" class="auth-submit">Submit Application</button>
                </form>

                <?php if (!$isClient): ?>
                    <p class="auth-switch mt-3">Don't have an account? <a href="<?= BASE_URL ?>/signup.php?redirect=<?= urlencode('apply.php') ?>">Sign up</a> · <a href="<?= BASE_URL ?>/login.php?redirect=<?= urlencode('apply.php') ?>">Log in</a></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var typeSelect = document.getElementById('application_type');
    var equipmentSection = document.getElementById('equipment-section');
    var projectSection = document.getElementById('project-section');
    var equipmentSelect = document.getElementById('equipment_offering_id');
    var availabilityNote = document.getElementById('equipmentAvailabilityNote');
    var submitButton = document.getElementById('submitApplication');

    var equipmentFields = ['equipment_offering_id', 'rental_start_date', 'rental_end_date'];
    var projectFields = ['project_title', 'project_location', 'project_description', 'proposal_budget', 'project_start_date', 'project_end_date'];

    function setRequired(fields, required) {
        fields.forEach(function (id) {
            var el = document.getElementById(id);
            if (el) {
                el.required = required;
            }
        });
    }

    function updateEquipmentNote() {
        if (!equipmentSelect || !availabilityNote) {
            return;
        }
        var option = equipmentSelect.options[equipmentSelect.selectedIndex];
        if (!option || !option.value) {
            availabilityNote.textContent = '';
            submitButton.disabled = false;
            return;
        }
        if (option.dataset.available === '1') {
            availabilityNote.textContent = 'This equipment is available for rental.';
            availabilityNote.className = 'small text-success mt-2 mb-0';
            submitButton.disabled = false;
        } else {
            availabilityNote.textContent = 'This equipment is currently ' + option.dataset.status + ' and cannot be submitted.';
            availabilityNote.className = 'small text-danger mt-2 mb-0';
            submitButton.disabled = true;
        }
    }

    function toggleSections() {
        var type = typeSelect.value;
        equipmentSection.style.display = type === 'Equipment Rental' ? 'block' : 'none';
        projectSection.style.display = type === 'New Project' ? 'block' : 'none';
        setRequired(equipmentFields, type === 'Equipment Rental');
        setRequired(projectFields, type === 'New Project');
        if (type === 'Equipment Rental') {
            updateEquipmentNote();
        } else {
            submitButton.disabled = false;
        }
    }

    typeSelect.addEventListener('change', toggleSections);
    if (equipmentSelect) {
        equipmentSelect.addEventListener('change', updateEquipmentNote);
    }
    toggleSections();
})();
</script>

<?php include("includes/footer.php"); ?>
