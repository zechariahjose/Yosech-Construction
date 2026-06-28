<nav class="ysc-topbar">
    <div class="container d-flex align-items-center justify-content-between">

        <a class="ysc-topbar-brand" href="<?= BASE_URL ?>/index.php">
            <span class="brand-mark">Y</span>
            YOSECH
        </a>

        <button class="navbar-toggler ysc-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#yscNav" aria-controls="yscNav" aria-expanded="false" aria-label="Toggle navigation">
            <span></span><span></span><span></span>
        </button>

        <div class="collapse navbar-collapse" id="yscNav">
            <ul class="ysc-topbar-nav">
                <li><a href="<?= BASE_URL ?>/index.php">Home</a></li>
                <li><a href="<?= BASE_URL ?>/projects.php">Projects</a></li>
                <li><a href="<?= BASE_URL ?>/equipment.php">Equipment</a></li>
                <li><a href="<?= BASE_URL ?>/contact.php">Contact</a></li>
            </ul>

            <div class="ysc-topbar-actions">
                <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Client'): ?>
                    <a href="<?= BASE_URL ?>/track_project.php" class="ysc-btn-ghost">My Projects</a>
                    <a href="<?= BASE_URL ?>/settings.php" class="ysc-btn-ghost">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    </a>
                    <a href="<?= BASE_URL ?>/logout.php" class="ysc-btn-outline-dark">Logout</a>
                <?php elseif (isset($_SESSION['user_type']) && in_array($_SESSION['user_type'], ['Admin', 'Manager'])): ?>
                    <a href="<?= BASE_URL ?>/admin/amin_dashboard.php" class="ysc-btn-ghost">Admin Panel</a>
                    <a href="<?= BASE_URL ?>/logout.php" class="ysc-btn-outline-dark">Logout</a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/login.php" class="ysc-btn-ghost">Client Login</a>
                    <a href="<?= BASE_URL ?>/apply.php" class="ysc-btn-orange">Start Your Project →</a>
                <?php endif; ?>
            </div>
        </div>

    </div>
</nav>
