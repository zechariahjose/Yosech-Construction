<?php
include("includes/header.php");
include("includes/navbar.php");

$isLoggedIn = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Client';
$userName = $isLoggedIn ? $_SESSION['user_name'] : null;

$recentActivity = [];
if ($isLoggedIn) {
    include("config/database.php");
    $uid = (int) $_SESSION['user_id'];
    $stmt = mysqli_prepare($conn, "
        SELECT pu.Description, pu.Status, pu.UpdateDate, p.ProjectID, a.ApplicationType
        FROM Project_Update pu
        JOIN Project p ON pu.ProjectID = p.ProjectID
        JOIN Application a ON p.ApplicationID = a.ApplicationID
        WHERE a.UserID = ? AND a.Status = 'Approved'
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
<div class="home-hero">
    <div class="container">
        <h2 class="hero-title">Welcome back, <?= htmlspecialchars($userName) ?></h2>
        <p class="hero-sub">Track your applications and receive project updates once your submission has been approved by our team.</p>
    </div>
</div>

<div class="container home-content">
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="home-card">
                <div class="home-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                </div>
                <div class="home-card-title">My Projects</div>
                <div class="home-card-desc">View your applications, approved projects, and the latest updates from our project managers.</div>
                <a href="<?= BASE_URL ?>/track_project.php" class="home-card-link">View My Projects →</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="home-card">
                <div class="home-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><rect x="1" y="3" width="15" height="13" rx="2"/><path d="M16 8h4l3 5v3h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                </div>
                <div class="home-card-title">Equipment Fleet</div>
                <div class="home-card-desc">Browse available construction equipment and submit a rental application.</div>
                <a href="<?= BASE_URL ?>/equipment.php" class="home-card-link">Browse Equipment →</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="home-card">
                <div class="home-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg>
                </div>
                <div class="home-card-title">Submit Application</div>
                <div class="home-card-desc">Apply for a new construction project or request equipment rental from Yosech Construction.</div>
                <a href="<?= BASE_URL ?>/apply.php" class="home-card-link">Open Application Form →</a>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="home-panel">
                <div class="home-panel-header">
                    <span class="home-panel-title">Recent Project Updates</span>
                </div>
                <?php if (empty($recentActivity)): ?>
                    <div class="home-empty">
                        <p>No project updates yet.</p>
                        <span class="home-empty-sub">Updates will appear here once your application is approved and our team begins posting progress.</span>
                        <a href="<?= BASE_URL ?>/apply.php" class="ysc-btn-primary btn-sm mt-3">Submit an Application</a>
                    </div>
                <?php else: ?>
                    <table class="home-table">
                        <thead>
                            <tr>
                                <th>Update</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentActivity as $act): ?>
                                <tr>
                                    <td><?= htmlspecialchars($act['Description']) ?></td>
                                    <td><span class="home-badge home-badge-<?= strtolower($act['Status']) ?>"><?= htmlspecialchars($act['Status']) ?></span></td>
                                    <td><?= date('M d, Y', strtotime($act['UpdateDate'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <a href="<?= BASE_URL ?>/track_project.php" class="home-view-all">View All Updates</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="home-panel home-info-panel">
                <div class="home-panel-title mb-3">How It Works</div>
                <ol class="home-steps">
                    <li>Submit an application for a new project or equipment rental.</li>
                    <li>Our admin or project manager reviews your submission.</li>
                    <li>Once approved, your project goes live and you receive updates here.</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<?php else: ?>
<div class="home-hero guest-hero">
    <div class="container">
        <h2 class="hero-title">Building the Future!</h2>
        <p class="hero-sub">Explore our project portfolio and equipment fleet. Sign up to submit applications and track your projects with Yosech Construction.</p>
        <div class="hero-actions">
            <a href="<?= BASE_URL ?>/projects.php" class="ysc-btn-outline btn-lg">View Projects</a>
            <a href="<?= BASE_URL ?>/equipment.php" class="ysc-btn-outline btn-lg">Browse Equipment</a>
            <a href="<?= BASE_URL ?>/signup.php" class="ysc-btn-primary btn-lg">Sign Up</a>
        </div>
    </div>
</div>

<div class="container home-content">
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="home-card guest-card">
                <div class="home-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                </div>
                <div class="home-card-title">Project Portfolio</div>
                <div class="home-card-desc">See completed and ongoing construction projects across Zamboanga del Norte.</div>
                <a href="<?= BASE_URL ?>/projects.php" class="home-card-link">View Projects →</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="home-card guest-card">
                <div class="home-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><rect x="1" y="3" width="15" height="13" rx="2"/><path d="M16 8h4l3 5v3h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                </div>
                <div class="home-card-title">Equipment Fleet</div>
                <div class="home-card-desc">Browse our rental fleet with specs, availability, and rates for every job site.</div>
                <a href="<?= BASE_URL ?>/equipment.php" class="home-card-link">Browse Equipment →</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="home-card guest-card">
                <div class="home-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg>
                </div>
                <div class="home-card-title">Submit Application</div>
                <div class="home-card-desc">Apply for a new project or equipment rental. Create an account to submit and track your application.</div>
                <a href="<?= BASE_URL ?>/signup.php" class="home-card-link">Sign Up to Apply →</a>
            </div>
        </div>
    </div>

    <div class="home-panel home-info-panel text-center">
        <div class="home-panel-title mb-2">For Registered Clients</div>
        <p class="home-empty-sub mb-3">Log in to submit applications and receive project updates after approval.</p>
        <a href="<?= BASE_URL ?>/login.php" class="ysc-btn-primary">Client Login</a>
    </div>
</div>
<?php endif; ?>

<?php include("includes/footer.php"); ?>
