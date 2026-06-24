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

if (isset($_POST['submit'])) {
    if (!$isClient) {
        $_SESSION['pending_application'] = $_POST;
        header('Location: login.php?redirect=' . urlencode('apply.php'));
        exit;
    }

    $applicationType = trim(mysqli_real_escape_string($conn, $_POST['application_type'] ?? ''));
    $description = trim(mysqli_real_escape_string($conn, $_POST['description'] ?? ''));

    if ($applicationType === '' || $description === '') {
        $message = 'Please complete all required fields.';
    } else {
        $userId = (int) $_SESSION['user_id'];
        mysqli_query($conn, "INSERT INTO Application (UserID, ApplicationType, Description, SubmissionDate, Status) VALUES ({$userId}, '{$applicationType}', '{$description}', CURDATE(), 'Pending')");
        $success = 'Your application has been submitted. You will receive project updates here once it is approved.';
        unset($_SESSION['pending_application']);
    }
}

$pending = $_SESSION['pending_application'] ?? [];
$applicationType = $pending['application_type'] ?? ($_GET['type'] ?? '');
$description = $pending['description'] ?? '';

if (!empty($_GET['equipment'])) {
    $equipmentNote = 'Equipment rental request: ' . $_GET['equipment'];
    if ($description === '' || strpos($description, $equipmentNote) === false) {
        $description = $equipmentNote . ($description !== '' ? "\n\n" . $description : '');
    }
    if ($applicationType === '') {
        $applicationType = 'Equipment Rental';
    }
}

if (!empty($_GET['project'])) {
    $projectNote = 'New project inquiry: ' . $_GET['project'];
    if ($description === '' || strpos($description, $projectNote) === false) {
        $description = $projectNote . ($description !== '' ? "\n\n" . $description : '');
    }
    if ($applicationType === '') {
        $applicationType = 'New Project';
    }
}

include("includes/header.php");
include("includes/navbar.php");
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/home.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/auth.css">

<div class="container">
    <div class="ysc-page-header">
        <h1 class="ysc-page-title">Application Form</h1>
        <p class="ysc-page-sub">Apply for a new construction project or request equipment rental. Once approved, you'll receive updates on your project dashboard.</p>
    </div>

    <div class="row justify-content-center pb-5">
        <div class="col-lg-7">
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

                <form method="post" action="apply.php">
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

                    <div class="auth-field">
                        <label for="description">Description</label>
                        <div class="auth-input-wrap">
                            <textarea id="description" name="description" class="form-control" rows="6" style="padding:11px 14px;border-radius:6px;" placeholder="Describe your project requirements or equipment rental needs..." required><?= htmlspecialchars($description) ?></textarea>
                        </div>
                    </div>

                    <button type="submit" name="submit" class="auth-submit">Submit Application</button>
                </form>

                <?php if (!$isClient): ?>
                    <p class="auth-switch mt-3">Don't have an account? <a href="<?= BASE_URL ?>/signup.php?redirect=<?= urlencode('apply.php') ?>">Sign up</a> · <a href="<?= BASE_URL ?>/login.php?redirect=<?= urlencode('apply.php') ?>">Log in</a></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include("includes/footer.php"); ?>
