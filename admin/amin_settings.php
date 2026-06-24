<?php
include("../config/database.php");
include("../includes/admin/helpers.php");

adminRequireLogin('admin/amin_settings.php');

$adminEmployee = adminCurrentEmployee($conn);
$adminPendingCount = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM Application WHERE Status = 'Pending'"))['total'];

$staffResult = mysqli_query($conn, "SELECT EmployeeID, UserType, Username, Email, ContactNumber FROM Employee ORDER BY UserType, Username");
$staffCount = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM Employee"))['total'];
$clientCount = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM Client"))['total'];

$adminActiveNav = 'settings';
$adminPageTitle = 'Admin Settings';
$adminPageSubtitle = 'System-wide configuration, user roles, and platform branding.';
$adminPageActions = '
    <a href="' . BASE_URL . '/logout.php" class="admin-btn admin-btn-outline">Sign Out</a>
';

include("../includes/admin/layout_start.php");
?>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="admin-card">
            <h3 class="admin-card-title">Your Account</h3>
            <?php if ($adminEmployee): ?>
                <div class="admin-meta-grid">
                    <div class="admin-meta-item"><span>Username</span><?= htmlspecialchars($adminEmployee['Username']) ?></div>
                    <div class="admin-meta-item"><span>Role</span><?= htmlspecialchars($adminEmployee['UserType']) ?></div>
                    <div class="admin-meta-item"><span>Email</span><?= htmlspecialchars($adminEmployee['Email']) ?></div>
                    <div class="admin-meta-item"><span>Contact</span><?= htmlspecialchars($adminEmployee['ContactNumber'] ?: '—') ?></div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="admin-card">
            <h3 class="admin-card-title">Platform Branding</h3>
            <div class="admin-meta-grid">
                <div class="admin-meta-item"><span>Company Name</span>Yosech Construction &amp; Civil Engineering</div>
                <div class="admin-meta-item"><span>Public Site Title</span>Yosech Construction</div>
                <div class="admin-meta-item"><span>Admin Console</span>Yosech Admin · Operations Console</div>
                <div class="admin-meta-item"><span>PM Console</span>Yosech Field Ops · Project Manager</div>
            </div>
            <p class="small text-muted mb-0">Branding values are configured in the application layout files. Contact your developer to update logos and company identity.</p>
        </div>
    </div>

    <div class="col-12">
        <div class="admin-panel">
            <div class="admin-panel-head">
                <h2 class="admin-panel-title">User Management</h2>
                <span class="admin-btn admin-btn-outline admin-btn-sm" style="cursor:default;"><?= $staffCount ?> staff · <?= $clientCount ?> clients</span>
            </div>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Email</th>
                        <th>Contact</th>
                        <th>Console Access</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($staff = mysqli_fetch_assoc($staffResult)):
                        $console = match ($staff['UserType']) {
                            'Admin' => 'Admin Panel',
                            'Manager' => 'Project Manager',
                            default => 'Staff (no console)',
                        };
                    ?>
                    <tr>
                        <td>#<?= (int) $staff['EmployeeID'] ?></td>
                        <td><span class="admin-table-project"><?= htmlspecialchars($staff['Username']) ?></span></td>
                        <td><?= htmlspecialchars($staff['UserType']) ?></td>
                        <td><?= htmlspecialchars($staff['Email']) ?></td>
                        <td><?= htmlspecialchars($staff['ContactNumber'] ?: '—') ?></td>
                        <td><?= htmlspecialchars($console) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="col-12">
        <div class="admin-card">
            <h3 class="admin-card-title">System Integrations</h3>
            <p class="small text-muted mb-3">Third-party service connections are not yet configured for this deployment.</p>
            <div class="admin-meta-grid">
                <div class="admin-meta-item"><span>Email Notifications</span>Not connected</div>
                <div class="admin-meta-item"><span>Cloud Storage</span>Not connected</div>
                <div class="admin-meta-item"><span>Accounting Export</span>Not connected</div>
                <div class="admin-meta-item"><span>Public Website</span><a href="<?= BASE_URL ?>/index.php" target="_blank" rel="noopener">Open site →</a></div>
            </div>
        </div>
    </div>
</div>

<?php include("../includes/admin/layout_end.php"); ?>
