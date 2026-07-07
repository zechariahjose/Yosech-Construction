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
            a.RentalStartDate, a.RentalEndDate, a.NeedsOperator,
            a.Description AS ApplicationDescription,
            a.SubmissionDate, a.Status AS ApplicationStatus,
            p.ProjectID, p.ProjectStatus, p.ProjectPaymentStatus,
            p.StartDate, p.EndDate, p.Description AS ProjectDescription,
            p.ProposalBudget AS ApprovedBudget,
            eo.Name AS EquipmentName, eo.Model AS EquipmentModel,
            eo.DailyRate, eo.WeeklyRate
     FROM Application a
     LEFT JOIN Project p ON a.ApplicationID = p.ApplicationID
     LEFT JOIN Equipment e ON a.EquipmentID = e.EquipmentID
     LEFT JOIN EquipmentOffering eo ON e.EquipmentOfferingID = eo.EquipmentOfferingID
     WHERE a.UserID = {$userId}
     ORDER BY a.SubmissionDate DESC"
);
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/home.css">

<div class="container">
    <div class="ysc-page-header">
        <h1 class="ysc-page-title">My Applications</h1>
        <p class="ysc-page-sub">Track your project proposals and equipment rental requests.</p>
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

    // Display name: use ProjectTitle if available, fallback to equipment name, then type
    $isProject = $row['ApplicationType'] === 'New Project';
    $isRental  = $row['ApplicationType'] === 'Equipment Rental';

    if ($isRental && !empty($row['EquipmentName'])) {
        $displayName = $row['EquipmentName'] . (!empty($row['EquipmentModel']) ? ' (' . $row['EquipmentModel'] . ')' : '');
    } elseif (!empty($row['ProjectTitle'])) {
        $displayName = $row['ProjectTitle'];
    } else {
        $displayName = $row['ApplicationType'];
    }
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
                <div class="home-info-note">Your application is under review. Updates will appear here once approved.</div>

            <?php elseif ($row['ApplicationStatus'] === 'Rejected'): ?>
                <div class="home-info-note home-info-note-muted">This application was not approved. You may submit a new application if needed.</div>

            <?php elseif ($isRental && $row['ApplicationStatus'] === 'Approved'): ?>
                <!-- ── Equipment rental approved ── -->
                <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:16px;margin-bottom:16px;display:flex;align-items:flex-start;gap:12px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="#059669" stroke-width="2" style="flex-shrink:0;margin-top:2px;"><path d="M20 6L9 17l-5-5"/></svg>
                    <div>
                        <div style="font-size:0.88rem;font-weight:700;color:#166534;margin-bottom:2px;">Rental Approved</div>
                        <div style="font-size:0.82rem;color:#166534;">Your equipment rental request has been approved. Please coordinate with our team for deployment details.</div>
                    </div>
                </div>

                <div class="row g-3 small">
                    <?php if (!empty($row['EquipmentName'])): ?>
                    <div class="col-sm-4">
                        <span class="text-muted">Equipment</span><br>
                        <strong><?= htmlspecialchars($row['EquipmentName']) ?></strong>
                        <?php if (!empty($row['EquipmentModel'])): ?>
                            <span class="text-muted"> · <?= htmlspecialchars($row['EquipmentModel']) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <div class="col-sm-4">
                        <span class="text-muted">Rental Start</span><br>
                        <strong><?= htmlspecialchars($row['RentalStartDate'] ?: 'TBD') ?></strong>
                    </div>
                    <div class="col-sm-4">
                        <span class="text-muted">Rental End</span><br>
                        <strong><?= htmlspecialchars($row['RentalEndDate'] ?: 'TBD') ?></strong>
                    </div>
                    <div class="col-sm-4">
                        <span class="text-muted">Operator</span><br>
                        <strong><?= !empty($row['NeedsOperator']) ? 'Included' : 'Not required' ?></strong>
                    </div>
                    <?php if (!empty($row['DailyRate'])): ?>
                    <div class="col-sm-4">
                        <span class="text-muted">Daily Rate</span><br>
                        <strong>₱<?= number_format((float)$row['DailyRate'], 0) ?></strong>
                    </div>
                    <?php endif; ?>
                </div>

            <?php elseif ($row['ProjectID']): ?>
                <!-- ── Project approved ── -->
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
