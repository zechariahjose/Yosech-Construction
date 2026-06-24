<?php
include("includes/header.php");
include("includes/navbar.php");

$isLoggedIn = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Client';
$userName = $isLoggedIn ? $_SESSION['user_name'] : null;

$recentActivity = [];
if ($isLoggedIn) {
    include("config/database.php");
    $uid = $_SESSION['user_id'];
    $stmt = mysqli_prepare($conn, "
        SELECT pu.Description, pu.Status, pu.UpdateDate, p.ProjectID
        FROM Project_Update pu
        JOIN Project p ON pu.ProjectID = p.ProjectID
        JOIN Application a ON p.ApplicationID = a.ApplicationID
        WHERE a.UserID = ?
        ORDER BY pu.UpdateDate DESC
        LIMIT 5
    ");
    mysqli_stmt_bind_param($stmt, "i", $uid);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $recentActivity[] = $row;
    }
}
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/home.css">

<?php if ($isLoggedIn): ?>
<!-- ===================== LOGGED IN VIEW ===================== -->
<div class="home-hero">
    <div class="container">
        <h2 class="hero-title">Welcome back, <?= htmlspecialchars($userName) ?></h2>
        <p class="hero-sub">Your construction projects are moving forward with precision. Here is the latest overview of your active sites and safety compliance.</p>
    </div>
</div>

<div class="container home-content">
    <!-- 3 Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="home-card">
                <div class="home-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="#1d4ed8" stroke-width="1.8"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                </div>
                <div class="home-card-title">Active Projects</div>
                <div class="home-card-desc">View real-time progress, site photos, and timelines for 2 ongoing builds.</div>
                <a href="<?= BASE_URL ?>/track_project.php" class="home-card-link">Explore Projects →</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="home-card">
                <div class="home-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="#1d4ed8" stroke-width="1.8"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                </div>
                <div class="home-card-title">Safety Reports</div>
                <div class="home-card-desc">Review OSHA compliance records, daily logs, and site safety inspections.</div>
                <a href="#" class="home-card-link">View Compliance →</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="home-card">
                <div class="home-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="#1d4ed8" stroke-width="1.8"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                </div>
                <div class="home-card-title">Messages</div>
                <div class="home-card-desc">Direct communication with your project managers and site supervisors.</div>
                <a href="#" class="home-card-link">Open Inquiry →</a>
            </div>
        </div>
    </div>

    <!-- Activity + Site Capture -->
    <div class="row g-4">
        <div class="col-md-7">
            <div class="home-panel">
                <div class="home-panel-header">
                    <span class="home-panel-title">Recent Project Activity</span>
                    <span class="home-panel-dash">—</span>
                </div>
                <table class="home-table">
                    <thead>
                        <tr>
                            <th>Milestone</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentActivity)): ?>
                            <tr><td colspan="3" class="text-center text-muted py-3">No recent activity found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recentActivity as $act): ?>
                                <tr>
                                    <td><?= htmlspecialchars($act['Description']) ?></td>
                                    <td><span class="home-badge home-badge-<?= strtolower($act['Status']) ?>"><?= htmlspecialchars($act['Status']) ?></span></td>
                                    <td><?= date('M d, Y', strtotime($act['UpdateDate'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                <a href="<?= BASE_URL ?>/track_project.php" class="home-view-all">View All Activity Logs</a>
            </div>
        </div>
        <div class="col-md-5">
            <div class="home-panel">
                <div class="home-panel-header">
                    <span class="home-panel-title" style="font-size:0.7rem;letter-spacing:0.08em;text-transform:uppercase;">Latest Site Capture</span>
                </div>
                <div class="home-site-capture">
                    <div class="home-capture-placeholder">
                        <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" fill="none" viewBox="0 0 24 24" stroke="#ccc" stroke-width="1.2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 3l18 18M3 21L21 3"/></svg>
                    </div>
                    <div class="home-capture-label">
                        <span class="home-capture-name">Harbor Point – Block B</span>
                        <span class="home-capture-sub">Captured via Drone · 2h ago</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php else: ?>
<!-- ===================== GUEST VIEW ===================== -->
<div class="home-hero guest-hero">
    <div class="container">
        <h2 class="hero-title">Building the Future of Zamboanga del Norte</h2>
        <p class="hero-sub">Precision engineering and structural integrity for every project we undertake. Partner with Yosech Construction today.</p>
        <div class="d-flex gap-3 mt-4">
            <a href="<?= BASE_URL ?>/login.php" class="btn btn-primary px-4">Client Login</a>
            <a href="<?= BASE_URL ?>/apply.php" class="btn btn-outline-light px-4">Project Inquiry</a>
        </div>
    </div>
</div>

<div class="container home-content">
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="home-card guest-card">
                <div class="home-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="#1d4ed8" stroke-width="1.8"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                </div>
                <div class="home-card-title">Track Your Projects</div>
                <div class="home-card-desc">Monitor real-time progress, site photos, and timelines for your ongoing builds.</div>
                <a href="<?= BASE_URL ?>/login.php" class="home-card-link">Login to Access →</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="home-card guest-card">
                <div class="home-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="#1d4ed8" stroke-width="1.8"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                </div>
                <div class="home-card-title">View Compliance Reports</div>
                <div class="home-card-desc">Review safety standards, daily logs, and site compliance records for your projects.</div>
                <a href="<?= BASE_URL ?>/login.php" class="home-card-link">Login to Access →</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="home-card guest-card">
                <div class="home-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="#1d4ed8" stroke-width="1.8"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                </div>
                <div class="home-card-title">Message Your Team</div>
                <div class="home-card-desc">Communicate directly with your project managers and site supervisors anytime.</div>
                <a href="<?= BASE_URL ?>/login.php" class="home-card-link">Login to Access →</a>
            </div>
        </div>
    </div>

    <!-- Guest info row -->
    <div class="row g-4">
        <div class="col-md-7">
            <div class="home-panel">
                <div class="home-panel-header">
                    <span class="home-panel-title">Recent Project Activity</span>
                </div>
                <div class="guest-lock">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="#ccc" stroke-width="1.5"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    <p class="guest-lock-text">Login to view your project activity logs.</p>
                    <a href="<?= BASE_URL ?>/login.php" class="btn btn-primary btn-sm px-4">Login</a>
                </div>
            </div>
        </div>
        <div class="col-md-5">
            <div class="home-panel">
                <div class="home-panel-header">
                    <span class="home-panel-title" style="font-size:0.7rem;letter-spacing:0.08em;text-transform:uppercase;">Latest Site Capture</span>
                </div>
                <div class="home-site-capture">
                    <div class="home-capture-placeholder">
                        <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" fill="none" viewBox="0 0 24 24" stroke="#ccc" stroke-width="1.2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 3l18 18M3 21L21 3"/></svg>
                    </div>
                    <div class="home-capture-label">
                        <span class="home-capture-name">Login to view site captures</span>
                        <span class="home-capture-sub">Available to registered clients only</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include("includes/footer.php"); ?>
