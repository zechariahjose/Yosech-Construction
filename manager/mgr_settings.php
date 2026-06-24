<?php
include("../config/database.php");
include("../includes/manager/helpers.php");

managerRequireLogin('manager/mgr_settings.php');

$mgrEmployee = adminCurrentEmployee($conn);
$mgrPendingRentals = managerPendingRentals($conn);
$prefs = managerLoadPreferences();
$saved = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_preferences'])) {
    managerSavePreferences([
        'view_mode' => ($_POST['view_mode'] ?? 'list') === 'gantt' ? 'gantt' : 'list',
        'notify_rentals' => isset($_POST['notify_rentals']),
        'notify_on_hold' => isset($_POST['notify_on_hold']),
        'notify_inspections' => isset($_POST['notify_inspections']),
    ]);
    $prefs = managerLoadPreferences();
    $saved = true;
}

$mgrActiveNav = 'settings';
$mgrPageTitle = 'My Settings';
$mgrPageSubtitle = 'Personal profile, workspace layout, and notification thresholds for your assigned sites and equipment.';
$mgrPageActions = '';

include("../includes/manager/layout_start.php");
?>

<?php if ($saved): ?>
    <div class="admin-alert admin-alert-info" style="background:#dcfce7;border-color:#86efac;color:#166534;">Preferences saved.</div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="admin-card">
            <h3>Your Profile</h3>
            <?php if ($mgrEmployee): ?>
                <div class="admin-meta-grid">
                    <div class="admin-meta-item"><span>Username</span><?= htmlspecialchars($mgrEmployee['Username']) ?></div>
                    <div class="admin-meta-item"><span>Role</span><?= htmlspecialchars($mgrEmployee['UserType']) ?></div>
                    <div class="admin-meta-item"><span>Email</span><?= htmlspecialchars($mgrEmployee['Email']) ?></div>
                    <div class="admin-meta-item"><span>Contact</span><?= htmlspecialchars($mgrEmployee['ContactNumber'] ?: '—') ?></div>
                </div>
                <p class="small text-muted mb-0">Profile changes are managed by your system administrator.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-6">
        <form method="post" class="admin-card">
            <h3>Workspace Preferences</h3>
            <div class="admin-field">
                <label>Project view mode</label>
                <div class="manager-view-toggle">
                    <label>
                        <input type="radio" name="view_mode" value="list" <?= $prefs['view_mode'] === 'list' ? 'checked' : '' ?>>
                        List View
                    </label>
                    <label>
                        <input type="radio" name="view_mode" value="gantt" <?= $prefs['view_mode'] === 'gantt' ? 'checked' : '' ?>>
                        Timeline View
                    </label>
                </div>
                <p class="small text-muted mt-2 mb-0">Timeline view groups projects by start/end dates on the projects page.</p>
            </div>

            <h3 class="mt-4">Notification Thresholds</h3>
            <p class="small text-muted">Choose which items highlight on your dashboard queue.</p>

            <label class="manager-check-row">
                <input type="checkbox" name="notify_rentals" value="1" <?= !empty($prefs['notify_rentals']) ? 'checked' : '' ?>>
                <span>Alert when new equipment rental requests are submitted</span>
            </label>
            <label class="manager-check-row">
                <input type="checkbox" name="notify_on_hold" value="1" <?= !empty($prefs['notify_on_hold']) ? 'checked' : '' ?>>
                <span>Alert when a site moves to On Hold status</span>
            </label>
            <label class="manager-check-row">
                <input type="checkbox" name="notify_inspections" value="1" <?= !empty($prefs['notify_inspections']) ? 'checked' : '' ?>>
                <span>Alert when inspection forms are pending review</span>
            </label>

            <button type="submit" name="save_preferences" value="1" class="admin-btn admin-btn-primary mt-2">Save Preferences</button>
        </form>
    </div>
</div>

<?php include("../includes/manager/layout_end.php"); ?>
