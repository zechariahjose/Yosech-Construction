<?php
include("config/database.php");
include("includes/header.php");
include("includes/navbar.php");

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Client') {
    header('Location: login.php?redirect=' . urlencode('track_project.php'));
    exit;
}

$userId = (int) $_SESSION['user_id'];

$applications = mysqli_query($conn,
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

<div class="trk-page">
<div class="container trk-wrap">

    <!-- Page header -->
    <div class="trk-page-head">
        <div>
            <h1 class="trk-page-title">My Applications</h1>
            <p class="trk-page-sub">Track your project proposals and equipment rental requests.</p>
        </div>
        <a href="<?= BASE_URL ?>/apply.php" class="trk-apply-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
            New Application
        </a>
    </div>

    <?php if (mysqli_num_rows($applications) === 0): ?>
    <div class="trk-empty">
        <div class="trk-empty-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M4 4h16v16H4z"/><path d="M8 8h8M8 12h8M8 16h5"/></svg>
        </div>
        <div class="trk-empty-title">No applications yet</div>
        <p class="trk-empty-sub">Submit a project proposal or equipment rental request to get started.</p>
        <a href="<?= BASE_URL ?>/apply.php" class="trk-apply-btn">Submit Application</a>
    </div>
    <?php endif; ?>

    <?php while ($row = mysqli_fetch_assoc($applications)):
        $isProject = $row['ApplicationType'] === 'New Project';
        $isRental  = $row['ApplicationType'] === 'Equipment Rental';
        $status    = $row['ApplicationStatus'];

        // Updates for approved projects
        $updates = [];
        if ($row['ProjectID']) {
            $pid = (int) $row['ProjectID'];
            $uq  = mysqli_query($conn,
                "SELECT Description, Status, UpdateDate FROM Project_Update
                 WHERE ProjectID = {$pid} ORDER BY UpdateDate DESC, UpdateID DESC"
            );
            while ($u = mysqli_fetch_assoc($uq)) $updates[] = $u;
        }

        // Display name
        if ($isRental && !empty($row['EquipmentName'])) {
            $displayName = $row['EquipmentName'] . (!empty($row['EquipmentModel']) ? ' · ' . $row['EquipmentModel'] : '');
        } elseif (!empty($row['ProjectTitle'])) {
            $displayName = $row['ProjectTitle'];
        } else {
            $displayName = $row['ApplicationType'];
        }

        // Status styles
        $statusClass = match(strtolower($status)) {
            'approved' => 'trk-badge-approved',
            'rejected' => 'trk-badge-rejected',
            default    => 'trk-badge-pending',
        };

        // Project status colour
        $projStatusClass = '';
        if ($row['ProjectID']) {
            $projStatusClass = match($row['ProjectStatus']) {
                'Ongoing'   => 'trk-ps-ongoing',
                'Completed' => 'trk-ps-completed',
                'On Hold'   => 'trk-ps-hold',
                default     => 'trk-ps-cancelled',
            };
        }
    ?>

    <div class="trk-card">

        <!-- Card header -->
        <div class="trk-card-head">
            <div class="trk-card-head-left">
                <div class="trk-type-chip <?= $isRental ? 'trk-type-rental' : 'trk-type-project' ?>">
                    <?php if ($isRental): ?>
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13" rx="2"/><path d="M16 8h4l3 5v3h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                    <?php else: ?>
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M2 20h20M5 20V8l7-4 7 4v12"/></svg>
                    <?php endif; ?>
                    <?= htmlspecialchars($row['ApplicationType']) ?>
                </div>
                <h2 class="trk-card-title"><?= htmlspecialchars($displayName) ?></h2>
                <div class="trk-card-meta">
                    <span>App #<?= (int)$row['ApplicationID'] ?></span>
                    <span class="trk-meta-dot">·</span>
                    <span>Submitted <?= date('M d, Y', strtotime($row['SubmissionDate'])) ?></span>
                    <?php if (!empty($row['ProjectLocation'])): ?>
                        <span class="trk-meta-dot">·</span>
                        <span><?= htmlspecialchars($row['ProjectLocation']) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <span class="trk-badge <?= $statusClass ?>"><?= htmlspecialchars($status) ?></span>
        </div>

        <!-- Card body -->
        <div class="trk-card-body">

        <?php if ($status === 'Pending'): ?>

            <div class="trk-notice trk-notice-info">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg>
                <span>Your application is under review. We'll update you here once a decision has been made.</span>
            </div>

        <?php elseif ($status === 'Rejected'): ?>

            <div class="trk-notice trk-notice-muted">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6M9 9l6 6"/></svg>
                <span>This application was not approved. You may submit a new application if your requirements have changed.</span>
            </div>

        <?php elseif ($isRental && $status === 'Approved'): ?>

            <!-- ── RENTAL APPROVED ── -->
            <div class="trk-notice trk-notice-success">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
                <span><strong>Rental Approved.</strong> Your equipment rental has been confirmed. Please coordinate with our team for deployment and pickup details.</span>
            </div>

            <div class="trk-details-grid">
                <?php if (!empty($row['EquipmentName'])): ?>
                <div class="trk-detail-item">
                    <div class="trk-detail-label">Equipment</div>
                    <div class="trk-detail-value"><?= htmlspecialchars($row['EquipmentName']) ?></div>
                    <?php if (!empty($row['EquipmentModel'])): ?>
                        <div class="trk-detail-sub"><?= htmlspecialchars($row['EquipmentModel']) ?></div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <div class="trk-detail-item">
                    <div class="trk-detail-label">Rental Period</div>
                    <div class="trk-detail-value">
                        <?= htmlspecialchars($row['RentalStartDate'] ? date('M d, Y', strtotime($row['RentalStartDate'])) : 'TBD') ?>
                        <span style="color:var(--ysc-muted);"> → </span>
                        <?= htmlspecialchars($row['RentalEndDate']   ? date('M d, Y', strtotime($row['RentalEndDate']))   : 'TBD') ?>
                    </div>
                </div>
                <div class="trk-detail-item">
                    <div class="trk-detail-label">Operator</div>
                    <div class="trk-detail-value"><?= !empty($row['NeedsOperator']) ? 'Included' : 'Not required' ?></div>
                </div>
                <?php if (!empty($row['DailyRate'])): ?>
                <div class="trk-detail-item">
                    <div class="trk-detail-label">Daily Rate</div>
                    <div class="trk-detail-value">₱<?= number_format((float)$row['DailyRate'], 0) ?></div>
                </div>
                <?php endif; ?>
            </div>

        <?php elseif ($row['ProjectID']): ?>

            <!-- ── PROJECT APPROVED ── -->

            <!-- Status strip -->
            <div class="trk-project-strip">
                <div class="trk-strip-item">
                    <div class="trk-detail-label">Project Status</div>
                    <span class="trk-project-status <?= $projStatusClass ?>"><?= htmlspecialchars($row['ProjectStatus']) ?></span>
                </div>
                <div class="trk-strip-item">
                    <div class="trk-detail-label">Payment</div>
                    <div class="trk-detail-value"><?= htmlspecialchars($row['ProjectPaymentStatus']) ?></div>
                </div>
                <div class="trk-strip-item">
                    <div class="trk-detail-label">Start Date</div>
                    <div class="trk-detail-value"><?= $row['StartDate'] ? date('M d, Y', strtotime($row['StartDate'])) : 'TBD' ?></div>
                </div>
                <div class="trk-strip-item">
                    <div class="trk-detail-label">End Date</div>
                    <div class="trk-detail-value"><?= $row['EndDate'] ? date('M d, Y', strtotime($row['EndDate'])) : 'In progress' ?></div>
                </div>
                <?php if (!empty($row['ApprovedBudget'])): ?>
                <div class="trk-strip-item">
                    <div class="trk-detail-label">Approved Budget</div>
                    <div class="trk-detail-value">₱<?= number_format((float)$row['ApprovedBudget'], 0) ?></div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Scope of work -->
            <?php if (!empty($row['ProjectDescription'])): ?>
            <div class="trk-scope">
                <div class="trk-detail-label">Scope of Work</div>
                <p class="trk-scope-text"><?= nl2br(htmlspecialchars($row['ProjectDescription'])) ?></p>
            </div>
            <?php endif; ?>

            <!-- Updates timeline -->
            <div class="trk-updates-head">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                Project Updates
            </div>

            <?php if (empty($updates)): ?>
                <p class="trk-no-updates">No updates posted yet. Check back soon.</p>
            <?php else: ?>
                <div class="trk-timeline">
                    <?php foreach ($updates as $upd):
                        $updClass = match($upd['Status']) {
                            'Approved' => 'trk-upd-approved',
                            'Pending'  => 'trk-upd-pending',
                            default    => 'trk-upd-reviewed',
                        };
                    ?>
                    <div class="trk-timeline-item">
                        <div class="trk-timeline-dot <?= $updClass ?>"></div>
                        <div class="trk-timeline-content">
                            <div class="trk-timeline-header">
                                <span class="trk-upd-badge <?= $updClass ?>"><?= htmlspecialchars($upd['Status']) ?></span>
                                <span class="trk-timeline-date"><?= date('M d, Y', strtotime($upd['UpdateDate'])) ?></span>
                            </div>
                            <p class="trk-timeline-text"><?= htmlspecialchars($upd['Description']) ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php endif; ?>

        </div><!-- /.trk-card-body -->
    </div><!-- /.trk-card -->

    <?php endwhile; ?>

</div><!-- /.trk-wrap -->
</div><!-- /.trk-page -->

<?php include("includes/footer.php"); ?>
