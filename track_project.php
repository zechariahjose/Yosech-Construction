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
    "SELECT a.ApplicationID, a.ApplicationType, a.Description AS ApplicationDescription,
            a.SubmissionDate, a.Status AS ApplicationStatus,
            p.ProjectID, p.ProjectStatus, p.ProjectPaymentStatus, p.StartDate, p.EndDate
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
        ?>
        <div class="home-panel mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                <div>
                    <h2 class="h5 mb-1"><?= htmlspecialchars($row['ApplicationType']) ?></h2>
                    <span class="text-muted small">Submitted <?= htmlspecialchars($row['SubmissionDate']) ?></span>
                </div>
                <span class="home-badge home-badge-<?= strtolower($row['ApplicationStatus']) === 'approved' ? 'approved' : (strtolower($row['ApplicationStatus']) === 'rejected' ? 'rejected' : 'reviewed') ?>">
                    <?= htmlspecialchars($row['ApplicationStatus']) ?>
                </span>
            </div>

            <p class="small text-muted mb-3"><?= nl2br(htmlspecialchars($row['ApplicationDescription'])) ?></p>

            <?php if ($row['ApplicationStatus'] === 'Pending'): ?>
                <div class="home-info-note">Your application is under review. Project updates will appear here once approved.</div>
            <?php elseif ($row['ApplicationStatus'] === 'Rejected'): ?>
                <div class="home-info-note home-info-note-muted">This application was not approved. You may submit a new application if needed.</div>
            <?php elseif ($row['ProjectID']): ?>
                <div class="row g-3 mb-3 small">
                    <div class="col-sm-4"><span class="text-muted">Project Status</span><br><strong><?= htmlspecialchars($row['ProjectStatus']) ?></strong></div>
                    <div class="col-sm-4"><span class="text-muted">Payment</span><br><strong><?= htmlspecialchars($row['ProjectPaymentStatus']) ?></strong></div>
                    <div class="col-sm-4"><span class="text-muted">Timeline</span><br><strong><?= htmlspecialchars($row['StartDate'] ?: 'TBD') ?> — <?= htmlspecialchars($row['EndDate'] ?: 'In progress') ?></strong></div>
                </div>

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
