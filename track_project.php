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

<div class="container" style="max-width:780px;padding-top:48px;padding-bottom:80px;">

    <!-- Page header -->
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:32px;">
        <div>
            <h1 class="ysc-page-title" style="margin-bottom:4px;">My Applications</h1>
            <p class="ysc-page-sub">Track your project proposals and equipment rental requests.</p>
        </div>
        <a href="<?= BASE_URL ?>/apply.php" class="ysc-btn-orange" style="font-size:0.78rem;padding:10px 20px;flex-shrink:0;">
            + New Application
        </a>
    </div>

    <?php if (mysqli_num_rows($applications) === 0): ?>
        <div class="home-panel" style="text-align:center;padding:56px 24px;">
            <div style="width:52px;height:52px;background:#f3f4f6;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;color:#9ca3af;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M4 4h16v16H4z"/><path d="M8 8h8M8 12h8M8 16h5"/></svg>
            </div>
            <p style="font-weight:700;color:#111;margin-bottom:6px;">No applications yet</p>
            <span style="font-size:0.84rem;color:#9ca3af;display:block;margin-bottom:20px;">Submit a project or equipment rental request to get started.</span>
            <a href="<?= BASE_URL ?>/apply.php" class="ysc-btn-orange" style="font-size:0.78rem;padding:10px 24px;">Submit Application</a>
        </div>
    <?php endif; ?>

    <?php while ($row = mysqli_fetch_assoc($applications)):
        $isProject = $row['ApplicationType'] === 'New Project';
        $isRental  = $row['ApplicationType'] === 'Equipment Rental';
        $status    = $row['ApplicationStatus'];

        $updates = [];
        if ($row['ProjectID']) {
            $pid = (int) $row['ProjectID'];
            $uq  = mysqli_query($conn,
                "SELECT Description, Status, UpdateDate FROM Project_Update
                 WHERE ProjectID = {$pid} ORDER BY UpdateDate DESC, UpdateID DESC"
            );
            while ($u = mysqli_fetch_assoc($uq)) $updates[] = $u;
        }

        if ($isRental && !empty($row['EquipmentName'])) {
            $displayName = $row['EquipmentName'] . (!empty($row['EquipmentModel']) ? ' (' . $row['EquipmentModel'] . ')' : '');
        } elseif (!empty($row['ProjectTitle'])) {
            $displayName = $row['ProjectTitle'];
        } else {
            $displayName = $row['ApplicationType'];
        }

        $badgeClass = match(strtolower($status)) {
            'approved' => 'home-badge-approved',
            'rejected' => 'home-badge-rejected',
            default    => 'home-badge-pending',
        };
    ?>

    <div class="home-panel mb-4" style="padding:0;overflow:hidden;">

        <!-- Card header -->
        <div style="padding:20px 24px;border-bottom:1px solid #f3f4f6;">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                <div>
                    <!-- Type chip -->
                    <div style="display:inline-flex;align-items:center;gap:5px;font-size:0.65rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;padding:2px 8px;border-radius:3px;margin-bottom:8px;<?= $isRental ? 'background:#fff7ed;color:#c2410c;' : 'background:#eef2f6;color:#4b6478;' ?>">
                        <?php if ($isRental): ?>
                            <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13" rx="2"/><path d="M16 8h4l3 5v3h-7V8z"/></svg>
                        <?php else: ?>
                            <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M2 20h20M5 20V8l7-4 7 4v12"/></svg>
                        <?php endif; ?>
                        <?= htmlspecialchars($row['ApplicationType']) ?>
                    </div>
                    <h2 style="font-size:1rem;font-weight:700;color:#111;margin:0 0 5px;line-height:1.3;"><?= htmlspecialchars($displayName) ?></h2>
                    <div style="font-size:0.76rem;color:#9ca3af;display:flex;flex-wrap:wrap;gap:4px;align-items:center;">
                        <span>App #<?= (int)$row['ApplicationID'] ?></span>
                        <span style="color:#d1d5db;">·</span>
                        <span>Submitted <?= date('M d, Y', strtotime($row['SubmissionDate'])) ?></span>
                        <?php if (!empty($row['ProjectLocation'])): ?>
                            <span style="color:#d1d5db;">·</span>
                            <span><?= htmlspecialchars($row['ProjectLocation']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <span class="home-badge <?= $badgeClass ?>" style="font-size:0.72rem;padding:5px 14px;border-radius:20px;flex-shrink:0;">
                    <?= htmlspecialchars($status) ?>
                </span>
            </div>
        </div>

        <!-- Card body -->
        <div style="padding:20px 24px;">

        <?php if ($status === 'Pending'): ?>

            <div class="home-info-note" style="display:flex;align-items:flex-start;gap:10px;border-radius:8px;padding:13px 16px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="flex-shrink:0;margin-top:1px;"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg>
                <span style="font-size:0.84rem;line-height:1.6;">Your application is under review. We will update you here once a decision has been made.</span>
            </div>

        <?php elseif ($status === 'Rejected'): ?>

            <div class="home-info-note home-info-note-muted" style="display:flex;align-items:flex-start;gap:10px;border-radius:8px;padding:13px 16px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="flex-shrink:0;margin-top:1px;"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6M9 9l6 6"/></svg>
                <span style="font-size:0.84rem;line-height:1.6;">This application was not approved. You may <a href="<?= BASE_URL ?>/apply.php" style="color:inherit;font-weight:600;text-decoration:underline;">submit a new application</a> if your requirements have changed.</span>
            </div>

        <?php elseif ($isRental && $status === 'Approved'): ?>

            <!-- Rental approved banner -->
            <div style="display:flex;align-items:flex-start;gap:10px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:13px 16px;margin-bottom:20px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#059669" stroke-width="2.5" style="flex-shrink:0;margin-top:1px;"><path d="M20 6L9 17l-5-5"/></svg>
                <div style="font-size:0.84rem;line-height:1.6;color:#166534;">
                    <strong>Rental Approved.</strong> Your equipment rental has been confirmed. Please coordinate with our team for deployment details.
                </div>
            </div>

            <!-- Rental details -->
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:16px;background:#f9fafb;border:1px solid #f3f4f6;border-radius:8px;padding:16px 20px;">
                <?php if (!empty($row['EquipmentName'])): ?>
                <div>
                    <div style="font-size:0.67rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#9ca3af;margin-bottom:4px;">Equipment</div>
                    <div style="font-size:0.88rem;font-weight:600;color:#111;"><?= htmlspecialchars($row['EquipmentName']) ?></div>
                    <?php if (!empty($row['EquipmentModel'])): ?><div style="font-size:0.75rem;color:#9ca3af;margin-top:2px;"><?= htmlspecialchars($row['EquipmentModel']) ?></div><?php endif; ?>
                </div>
                <?php endif; ?>
                <div>
                    <div style="font-size:0.67rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#9ca3af;margin-bottom:4px;">Rental Start</div>
                    <div style="font-size:0.88rem;font-weight:600;color:#111;"><?= $row['RentalStartDate'] ? date('M d, Y', strtotime($row['RentalStartDate'])) : 'TBD' ?></div>
                </div>
                <div>
                    <div style="font-size:0.67rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#9ca3af;margin-bottom:4px;">Rental End</div>
                    <div style="font-size:0.88rem;font-weight:600;color:#111;"><?= $row['RentalEndDate'] ? date('M d, Y', strtotime($row['RentalEndDate'])) : 'TBD' ?></div>
                </div>
                <div>
                    <div style="font-size:0.67rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#9ca3af;margin-bottom:4px;">Operator</div>
                    <div style="font-size:0.88rem;font-weight:600;color:#111;"><?= !empty($row['NeedsOperator']) ? 'Included' : 'Not required' ?></div>
                </div>
                <?php if (!empty($row['DailyRate'])): ?>
                <div>
                    <div style="font-size:0.67rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#9ca3af;margin-bottom:4px;">Daily Rate</div>
                    <div style="font-size:0.88rem;font-weight:600;color:#111;">₱<?= number_format((float)$row['DailyRate'], 0) ?></div>
                </div>
                <?php endif; ?>
            </div>

        <?php elseif ($row['ProjectID']): ?>

            <!-- Project status strip -->
            <div style="display:flex;flex-wrap:wrap;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;margin-bottom:20px;">
                <?php
                $projStatusStyle = match($row['ProjectStatus']) {
                    'Ongoing'   => 'background:#fef3c7;color:#92400e;',
                    'Completed' => 'background:#d1fae5;color:#065f46;',
                    'On Hold'   => 'background:#fee2e2;color:#991b1b;',
                    default     => 'background:#f3f4f6;color:#6b7280;',
                };
                ?>
                <div style="flex:1;min-width:110px;padding:14px 18px;border-right:1px solid #f3f4f6;">
                    <div style="font-size:0.67rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#9ca3af;margin-bottom:6px;">Status</div>
                    <span style="display:inline-block;font-size:0.78rem;font-weight:700;padding:3px 10px;border-radius:4px;<?= $projStatusStyle ?>"><?= htmlspecialchars($row['ProjectStatus']) ?></span>
                </div>
                <div style="flex:1;min-width:110px;padding:14px 18px;border-right:1px solid #f3f4f6;">
                    <div style="font-size:0.67rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#9ca3af;margin-bottom:6px;">Payment</div>
                    <div style="font-size:0.88rem;font-weight:600;color:#111;"><?= htmlspecialchars($row['ProjectPaymentStatus']) ?></div>
                </div>
                <div style="flex:1;min-width:110px;padding:14px 18px;border-right:1px solid #f3f4f6;">
                    <div style="font-size:0.67rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#9ca3af;margin-bottom:6px;">Start Date</div>
                    <div style="font-size:0.88rem;font-weight:600;color:#111;"><?= $row['StartDate'] ? date('M d, Y', strtotime($row['StartDate'])) : 'TBD' ?></div>
                </div>
                <div style="flex:1;min-width:110px;padding:14px 18px;<?= !empty($row['ApprovedBudget']) ? 'border-right:1px solid #f3f4f6;' : '' ?>">
                    <div style="font-size:0.67rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#9ca3af;margin-bottom:6px;">End Date</div>
                    <div style="font-size:0.88rem;font-weight:600;color:#111;"><?= $row['EndDate'] ? date('M d, Y', strtotime($row['EndDate'])) : 'In progress' ?></div>
                </div>
                <?php if (!empty($row['ApprovedBudget'])): ?>
                <div style="flex:1;min-width:110px;padding:14px 18px;">
                    <div style="font-size:0.67rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#9ca3af;margin-bottom:6px;">Budget</div>
                    <div style="font-size:0.88rem;font-weight:600;color:#111;">₱<?= number_format((float)$row['ApprovedBudget'], 0) ?></div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Scope of work -->
            <?php if (!empty($row['ProjectDescription'])): ?>
            <div style="background:#f9fafb;border:1px solid #f3f4f6;border-radius:8px;padding:14px 18px;margin-bottom:20px;">
                <div style="font-size:0.67rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#9ca3af;margin-bottom:8px;">Scope of Work</div>
                <p style="font-size:0.86rem;color:#374151;line-height:1.7;margin:0;"><?= nl2br(htmlspecialchars($row['ProjectDescription'])) ?></p>
            </div>
            <?php endif; ?>

            <!-- Updates -->
            <div style="font-size:0.72rem;font-weight:800;letter-spacing:.07em;text-transform:uppercase;color:#9ca3af;margin-bottom:12px;display:flex;align-items:center;gap:6px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                Project Updates
            </div>

            <?php if (empty($updates)): ?>
                <p style="font-size:0.84rem;color:#9ca3af;margin:0;padding:12px 0;">No updates posted yet. Check back soon.</p>
            <?php else: ?>
                <div style="border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">
                    <?php foreach ($updates as $i => $upd):
                        $isLast = $i === count($updates) - 1;
                        $dotColor = match($upd['Status']) {
                            'Approved' => '#059669',
                            'Pending'  => '#f59e0b',
                            default    => '#3b82f6',
                        };
                        $badgeStyle = match($upd['Status']) {
                            'Approved' => 'background:#d1fae5;color:#065f46;',
                            'Pending'  => 'background:#fef3c7;color:#92400e;',
                            default    => 'background:#dbeafe;color:#1e40af;',
                        };
                    ?>
                    <div style="display:flex;gap:14px;padding:14px 18px;<?= !$isLast ? 'border-bottom:1px solid #f3f4f6;' : '' ?>align-items:flex-start;">
                        <div style="width:10px;height:10px;border-radius:50%;background:<?= $dotColor ?>;flex-shrink:0;margin-top:5px;"></div>
                        <div style="flex:1;min-width:0;">
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:7px;flex-wrap:wrap;">
                                <span style="display:inline-block;font-size:0.65rem;font-weight:700;letter-spacing:.05em;text-transform:uppercase;padding:2px 9px;border-radius:20px;<?= $badgeStyle ?>">
                                    <?= htmlspecialchars($upd['Status']) ?>
                                </span>
                                <span style="font-size:0.72rem;color:#9ca3af;white-space:nowrap;"><?= date('M d, Y', strtotime($upd['UpdateDate'])) ?></span>
                            </div>
                            <p style="font-size:0.85rem;color:#374151;line-height:1.65;margin:0;"><?= htmlspecialchars($upd['Description']) ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php endif; ?>

        </div><!-- /card body -->
    </div><!-- /.home-panel -->

    <?php endwhile; ?>
</div>

<?php include("includes/footer.php"); ?>
