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
<!-- ═══════════════════════════════════════════════════════════
     LOGGED-IN VIEW
═══════════════════════════════════════════════════════════ -->

<!-- Hero -->
<section class="home-hero logged-hero">
    <div class="home-hero-bg" style="background-image:url('<?= BASE_URL ?>/assets/projects/roadConcreting.jpg');"></div>
    <div class="home-hero-overlay"></div>
    <div class="container home-hero-content">
        <div class="hero-eyebrow">Client Dashboard</div>
        <h1 class="hero-title">Welcome back,<br><?= htmlspecialchars($userName) ?></h1>
        <p class="hero-sub">Track your applications and receive project updates once your submission has been approved by our team.</p>
    </div>
</section>

<!-- Dashboard content -->
<div class="home-dash-wrapper">
    <div class="container">

        <!-- Quick-access cards -->
        <div class="dash-cards">
            <div class="home-card">
                <div class="home-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                </div>
                <div class="home-card-title">My Projects</div>
                <div class="home-card-desc">View your applications, approved projects, and the latest updates from our project managers.</div>
                <a href="<?= BASE_URL ?>/track_project.php" class="home-card-link">View My Projects →</a>
            </div>
            <div class="home-card">
                <div class="home-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><rect x="1" y="3" width="15" height="13" rx="2"/><path d="M16 8h4l3 5v3h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                </div>
                <div class="home-card-title">Equipment Fleet</div>
                <div class="home-card-desc">Browse available construction equipment and submit a rental application.</div>
                <a href="<?= BASE_URL ?>/equipment.php" class="home-card-link">Browse Equipment →</a>
            </div>
            <div class="home-card">
                <div class="home-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg>
                </div>
                <div class="home-card-title">Submit Application</div>
                <div class="home-card-desc">Apply for a new construction project or request equipment rental from Yosech Construction.</div>
                <a href="<?= BASE_URL ?>/apply.php" class="home-card-link">Open Application Form →</a>
            </div>
        </div>

        <!-- Activity + How it works -->
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
                            <a href="<?= BASE_URL ?>/apply.php" class="ysc-btn-orange mt-3 d-inline-block" style="border-radius:4px;padding:10px 24px;font-size:.8rem;font-weight:700;letter-spacing:.04em;text-transform:uppercase;">Submit an Application</a>
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
                        <a href="<?= BASE_URL ?>/track_project.php" class="home-view-all">View All Updates →</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="home-panel home-info-panel">
                    <div class="home-panel-title" style="margin-bottom:16px;padding-bottom:14px;border-bottom:1px solid #f3f4f6;">How It Works</div>
                    <ol class="home-steps">
                        <li>Submit an application for a new project or equipment rental.</li>
                        <li>Our admin or project manager reviews your submission.</li>
                        <li>Once approved, your project goes live and you receive updates here.</li>
                    </ol>
                </div>
            </div>
        </div>

    </div>
</div>

<?php else: ?>
<!-- ═══════════════════════════════════════════════════════════
     GUEST VIEW
═══════════════════════════════════════════════════════════ -->

<!-- ── Hero ──────────────────────────────────────────────── -->
<section class="home-hero">
    <div class="home-hero-bg"></div>
    <div class="home-hero-overlay"></div>
    <div class="container home-hero-content">
        <div class="hero-eyebrow">Your Trusted Construction Partner</div>
        <h1 class="hero-title">Building with Precision.<br>Delivering with Integrity.</h1>
        <p class="hero-sub">From infrastructure to commercial structures, Yosech Construction delivers precision engineering and structural integrity at every stage.</p>
        <div class="hero-actions">
            <a href="<?= BASE_URL ?>/apply.php" class="ysc-btn-primary">Start Your Project →</a>
            <a href="<?= BASE_URL ?>/projects.php" class="ysc-btn-outline">View Projects</a>
        </div>
    </div>
</section>

<!-- ── The Yosech Standard (split) ───────────────────────── -->
<section class="standard-section">
    <div class="container">
        <div class="standard-split">
            <!-- Images side -->
            <div class="standard-images">
                <img src="<?= BASE_URL ?>/assets/projects/multiPurposeBuilding.jpg"
                     alt="Multi-purpose building project"
                     class="standard-img-main">
                <img src="<?= BASE_URL ?>/assets/projects/drainageCanal.png"
                     alt="Drainage canal project"
                     class="standard-img-sub">
                <img src="<?= BASE_URL ?>/assets/projects/tunnel.jpg"
                     alt="Tunnel project"
                     class="standard-img-sub">
                <div class="standard-stat">
                    <div class="standard-stat-num">99.8%</div>
                    <div class="standard-stat-label">Project completion rate<br>across all active sites.</div>
                </div>
            </div>
            <!-- Text side -->
            <div>
                <div class="section-label">Our Standard</div>
                <h2 class="section-title" style="margin-bottom:20px;">The Yosech<br>Standard</h2>
                <p class="section-desc" style="margin-bottom:28px;">Delivering excellence through a meticulous 3-stage construction lifecycle — from initial surveying to the final structural audit, we eliminate risk at every point of execution.</p>
                <ul style="list-style:none;padding:0;margin:0 0 32px;display:flex;flex-direction:column;gap:12px;">
                    <li style="font-size:.84rem;color:#444;display:flex;align-items:center;gap:10px;">
                        <span style="width:8px;height:8px;border-radius:50%;background:#f97316;flex-shrink:0;display:block;"></span>
                        Structural Engineering &amp; Site Management
                    </li>
                    <li style="font-size:.84rem;color:#444;display:flex;align-items:center;gap:10px;">
                        <span style="width:8px;height:8px;border-radius:50%;background:#f97316;flex-shrink:0;display:block;"></span>
                        Equipment Rental &amp; Logistics
                    </li>
                    <li style="font-size:.84rem;color:#444;display:flex;align-items:center;gap:10px;">
                        <span style="width:8px;height:8px;border-radius:50%;background:#f97316;flex-shrink:0;display:block;"></span>
                        Client Project Tracking &amp; Progress Updates
                    </li>
                </ul>
                <a href="<?= BASE_URL ?>/projects.php" class="ysc-btn-orange" style="border-radius:4px;padding:13px 28px;font-size:.8rem;font-weight:700;letter-spacing:.05em;text-transform:uppercase;text-decoration:none;">See Our Projects →</a>
            </div>
        </div>
    </div>
</section>

<!-- ── Services / Engineering Excellence ─────────────────── -->
<section class="services-section">
    <div class="container">
        <div class="text-center mb-5">
            <div class="section-label" style="justify-content:center;">What We Do</div>
            <h2 class="section-title" style="color:#fff;">Engineering Excellence</h2>
        </div>
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="service-card">
                    <div class="service-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    </div>
                    <div class="service-title">General Construction</div>
                    <div class="service-desc">Full-cycle construction management for residential, commercial, and industrial structures built to endure.</div>
                    <ul class="service-list">
                        <li>Structural Analysis</li>
                        <li>Site Supervision</li>
                        <li>Compliance &amp; Permits</li>
                    </ul>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="service-card">
                    <div class="service-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><rect x="1" y="3" width="15" height="13" rx="2"/><path d="M16 8h4l3 5v3h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                    </div>
                    <div class="service-title">Equipment Rental</div>
                    <div class="service-desc">Direct access to our fleet of heavy machinery and equipment for any job site need.</div>
                    <ul class="service-list">
                        <li>Hydraulic Excavators</li>
                        <li>Road Graders</li>
                        <li>Dump Trucks</li>
                    </ul>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="service-card">
                    <div class="service-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg>
                    </div>
                    <div class="service-title">Project Management</div>
                    <div class="service-desc">Dedicated project managers ensuring timelines are met, budgets are controlled, and clients stay informed.</div>
                    <ul class="service-list">
                        <li>Client Portal &amp; Updates</li>
                        <li>Progress Monitoring</li>
                        <li>Quality Control</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ── Project Portfolio ──────────────────────────────────── -->
<section class="projects-section">
    <div class="container">
        <div class="projects-header">
            <div>
                <div class="section-label">Project Portfolio</div>
                <h2 class="section-title">Our Impact</h2>
            </div>
            <a href="<?= BASE_URL ?>/projects.php" class="ysc-btn-outline-dark" style="border-radius:4px;padding:11px 22px;font-size:.78rem;font-weight:700;letter-spacing:.05em;text-transform:uppercase;text-decoration:none;">View All Projects →</a>
        </div>
        <div class="projects-grid">
            <div class="project-card">
                <img src="<?= BASE_URL ?>/assets/projects/roadConcreting.jpg" alt="Road Concreting" class="project-card-img">
                <div class="project-card-body">
                    <div class="project-card-type">Infrastructure</div>
                    <div class="project-card-name">Road Concreting Project</div>
                </div>
            </div>
            <div class="project-card">
                <img src="<?= BASE_URL ?>/assets/projects/floodBarrier.png" alt="Flood Barrier" class="project-card-img">
                <div class="project-card-body">
                    <div class="project-card-type">Flood Control</div>
                    <div class="project-card-name">Flood Barrier System</div>
                </div>
            </div>
            <div class="project-card">
                <img src="<?= BASE_URL ?>/assets/projects/2storyBuilding.jpg" alt="2-Story Building" class="project-card-img">
                <div class="project-card-body">
                    <div class="project-card-type">Commercial</div>
                    <div class="project-card-name">2-Storey Commercial Building</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ── CTA Strip ──────────────────────────────────────────── -->
<section class="guest-cta-strip">
    <div class="container">
        <h3>Ready to Start Your Project?</h3>
        <p>Sign up to submit your application and track its progress every step of the way.</p>
        <div style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap;">
            <a href="<?= BASE_URL ?>/signup.php" class="ysc-btn-primary">Create an Account</a>
            <a href="<?= BASE_URL ?>/contact.php" class="ysc-btn-outline">Contact Us</a>
        </div>
    </div>
</section>

<?php endif; ?>

<?php include("includes/footer.php"); ?>
