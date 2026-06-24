<?php
include("../config/database.php");
include("../includes/admin/helpers.php");

adminRequireLogin('admin/amin_settings.php');

$adminEmployee = adminCurrentEmployee($conn);
$adminPendingCount = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM Application WHERE Status = 'Pending'"))['total'];

$staffCount = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM Employee"))['total'];
$managerCount = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM Employee WHERE UserType = 'Manager'"))['total'];

$adminActiveNav = 'settings';
$adminPageTitle = 'Settings';
$adminPageSubtitle = 'Account information and system overview for the admin console.';
$adminPageActions = '
    <a href="' . BASE_URL . '/logout.php" class="admin-btn admin-btn-outline">Sign Out</a>
';

include("../includes/admin/layout_start.php");
?>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="admin-card">
            <h3 class="admin-card-title">Your Account</h3>
            <?php if ($adminEmployee): ?>
                <div class="admin-meta-grid">
                    <div class="admin-meta-item">
                        <span>Username</span>
                        <?= htmlspecialchars($adminEmployee['Username']) ?>
                    </div>
                    <div class="admin-meta-item">
                        <span>Role</span>
                        <?= htmlspecialchars($adminEmployee['UserType']) ?>
                    </div>
                    <div class="admin-meta-item">
                        <span>Email</span>
                        <?= htmlspecialchars($adminEmployee['Email']) ?>
                    </div>
                    <div class="admin-meta-item">
                        <span>Contact</span>
                        <?= htmlspecialchars($adminEmployee['ContactNumber'] ?: '—') ?>
                    </div>
                </div>
            <?php else: ?>
                <p class="small text-muted mb-0">Unable to load employee profile.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="admin-card">
            <h3 class="admin-card-title">Staff Overview</h3>
            <div class="admin-meta-grid">
                <div class="admin-meta-item">
                    <span>Total Staff Accounts</span>
                    <?= $staffCount ?>
                </div>
                <div class="admin-meta-item">
                    <span>Project Managers</span>
                    <?= $managerCount ?>
                </div>
            </div>
            <p class="small text-muted mb-0" style="line-height:1.6;">
                Staff accounts are stored in the <code>Employee</code> table. Only Admin and Manager roles can access this console.
            </p>
        </div>
    </div>

    <div class="col-12">
        <div class="admin-card">
            <h3 class="admin-card-title">Public Site</h3>
            <p class="small text-muted mb-3">The client-facing website uses a separate layout. Use the link below to preview what customers see.</p>
            <a href="<?= BASE_URL ?>/index.php" class="admin-btn admin-btn-primary" target="_blank" rel="noopener">Open Public Website</a>
        </div>
    </div>
</div>

<?php include("../includes/admin/layout_end.php"); ?>
