<?php
include("config/database.php");
include("includes/header.php");
include("includes/navbar.php");

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Client') {
    header('Location: login.php?redirect=' . urlencode('track_project.php'));
    exit;
}

$userId = (int) $_SESSION['user_id'];

$applications = mysqli_query(
    $conn,
    "SELECT a.ApplicationID, a.ApplicationType, a.ProjectTitle, a.ProjectLocation,
            a.ProposalBudget, a.ProjectStartDate, a.ProjectEndDate,
            a.Description AS ApplicationDescription,
            a.SubmissionDate, a.Status AS ApplicationStatus,
            p.ProjectID, p.ProjectStatus, p.ProjectPaymentStatus,
            p.StartDate, p.EndDate, p.Description AS ProjectDescription,
            p.ProposalBudget AS ApprovedBudget
     FROM Application a
     LEFT JOIN Project p ON a.ApplicationID = p.ApplicationID
     WHERE a.UserID = {$userId}
     ORDER BY a.SubmissionDate DESC"
);
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/home.css">

<div class="container">
    <div class="ysc-page-header">
        <h1 class="ysc-page-title">My Projects</h1>
        <p class="ysc-page-sub">Track your applications and view project updates after approval.</p>
    </div>

    <?php if (mysqli_num_rows($applications) === 0): ?>
        <div class="home-panel home-empty">
            <p>You haven't submitted any applications yet.</p>
            <span class="home-empty-sub">Apply for a new project or equipment rental to get started.</span>
            <a href="<?= BASE_URL ?>/apply.php" class="ysc-btn-primary mt-3">Submit Application</a>
        </div>
    <?php endif; ?>

    <?php while ($row = mysqli_fetch_assoc($applications)): ?>
    <?php
    $updates = [];
    if ($row['ProjectID']) {
        $pid = (int) $row['ProjectID'];
        $updateQuery = mysqli_query(
            $conn,
            "SELECT Description, Status, UpdateDate FROM Project_Update WHERE ProjectID = {$pid} ORDER BY UpdateDate DESC, UpdateID DESC"
        );
        while ($u = mysqli_fetch_assoc($updateQuery)) {
            $updates[] = $u;
        }
    }

    // Display name: use ProjectTitle if available, fallback to ApplicationType
    $displayName = !empty($row['ProjectTitle']) ? $row['ProjectTitle'] : $row['ApplicationType'];
    $isProject   = $row['ApplicationType'] === 'New Project';
    ?>
        <div class="home-panel mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                <div>
                    <h2 class="h5 mb-1"><?= htmlspecialchars($displayName) ?></h2>
                    <span class="text-muted small">
                        <?= htmlspecialchars($row['ApplicationType']) ?>
                        · Submitted <?= htmlspecialchars($row['SubmissionDate']) ?>
                        <?php if (!empty($row['ProjectLocation'])): ?>
                            · <?= htmlspecialchars($row['ProjectLocation']) ?>
                        <?php endif; ?>
                    </span>
                </div>
                <span class="home-badge home-badge-<?= strtolower($row['ApplicationStatus']) === 'approved' ? 'approved' : (strtolower($row['ApplicationStatus']) === 'rejected' ? 'rejected' : 'reviewed') ?>">
                    <?= htmlspecialchars($row['ApplicationStatus']) ?>
                </span>
            </div>

            <?php if ($row['ApplicationStatus'] === 'Pending'): ?>
                <div class="home-info-note">Your application is under review. Project updates will appear here once approved.</div>

            <?php elseif ($row['ApplicationStatus'] === 'Rejected'): ?>
                <div class="home-info-note home-info-note-muted">This application was not approved. You may submit a new application if needed.</div>

            <?php elseif ($row['ProjectID']): ?>

                <!-- Project details -->
                <div class="row g-3 mb-3 small">
                    <div class="col-sm-3">
                        <span class="text-muted">Project Status</span><br>
                        <strong><?= htmlspecialchars($row['ProjectStatus']) ?></strong>
                    </div>
                    <div class="col-sm-3">
                        <span class="text-muted">Payment</span><br>
                        <strong><?= htmlspecialchars($row['ProjectPaymentStatus']) ?></strong>
                    </div>
                    <div class="col-sm-3">
                        <span class="text-muted">Start Date</span><br>
                        <strong><?= htmlspecialchars($row['StartDate'] ?: 'TBD') ?></strong>
                    </div>
                    <div class="col-sm-3">
                        <span class="text-muted">End Date</span><br>
                        <strong><?= htmlspecialchars($row['EndDate'] ?: 'In progress') ?></strong>
                    </div>
                    <?php if ($isProject && !empty($row['ApprovedBudget'])): ?>
                    <div class="col-sm-4">
                        <span class="text-muted">Approved Budget</span><br>
                        <strong>₱<?= number_format((float)$row['ApprovedBudget'], 2) ?></strong>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($row['ProjectDescription'])): ?>
                    <div class="col-12">
                        <span class="text-muted">Scope of Work</span><br>
                        <span><?= nl2br(htmlspecialchars($row['ProjectDescription'])) ?></span>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Field updates -->
                <div class="home-panel-header border-top pt-3">
                    <span class="home-panel-title">Project Updates</span>
                </div>
                <?php if (empty($updates)): ?>
                    <p class="small text-muted mb-0">No updates posted yet. Check back soon.</p>
                <?php else: ?>
                    <div class="home-timeline">
                        <?php foreach ($updates as $update): ?>
                            <div class="home-timeline-item">
                                <div class="home-timeline-date"><?= date('M d, Y', strtotime($update['UpdateDate'])) ?></div>
                                <div class="home-timeline-body">
                                    <span class="home-badge home-badge-<?= strtolower($update['Status']) ?>"><?= htmlspecialchars($update['Status']) ?></span>
                                    <p class="mb-0 mt-2"><?= htmlspecialchars($update['Description']) ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            <?php endif; ?>
        </div>
    <?php endwhile; ?>
</div>

<?php include("includes/footer.php"); ?>
