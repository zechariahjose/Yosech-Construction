<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$isLoggedIn = isset($_SESSION['user_type']);
$isClient = $isLoggedIn && $_SESSION['user_type'] === 'Client';
$isStaff = $isLoggedIn && in_array($_SESSION['user_type'], ['Admin', 'Manager']);
?>
<nav class="navbar navbar-expand-lg ysc-navbar">
    <div class="container">
        <a class="navbar-brand" href="<?= BASE_URL ?>/index.php">Yosech Construction</a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#nav" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="nav">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item">
                    <a class="nav-link<?= $currentPage === 'projects.php' ? ' active' : '' ?>" href="<?= BASE_URL ?>/projects.php">Projects</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= $currentPage === 'equipment.php' ? ' active' : '' ?>" href="<?= BASE_URL ?>/equipment.php">Equipment</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= $currentPage === 'apply.php' ? ' active' : '' ?>" href="<?= BASE_URL ?>/apply.php">Apply</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= $currentPage === 'contact.php' ? ' active' : '' ?>" href="<?= BASE_URL ?>/contact.php">Contact</a>
                </li>
            </ul>

            <div class="ysc-nav-actions ms-lg-3">
                <?php if ($isClient): ?>
                    <a class="ysc-btn-outline" href="<?= BASE_URL ?>/track_project.php">My Projects</a>
                    <a class="ysc-btn-primary" href="<?= BASE_URL ?>/logout.php">Logout</a>
                <?php elseif ($isStaff): ?>
                    <a class="ysc-btn-outline" href="<?= BASE_URL ?>/admin/amin_dashboard.php">Admin Panel</a>
                    <a class="ysc-btn-primary" href="<?= BASE_URL ?>/logout.php">Logout</a>
                <?php else: ?>
                    <a class="ysc-btn-outline<?= in_array($currentPage, ['login.php', 'signup.php']) ? ' active' : '' ?>" href="<?= BASE_URL ?>/login.php">Login</a>
                    <a class="ysc-btn-primary" href="<?= BASE_URL ?>/signup.php">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
