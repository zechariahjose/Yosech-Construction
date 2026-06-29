<nav class="ysc-topbar">
    <div class="container ysc-topbar-inner">

        <a class="ysc-topbar-brand" href="<?= BASE_URL ?>/index.php">
            <span class="brand-mark">Y</span>
            YOSECH
        </a>

        <ul class="ysc-topbar-nav" id="yscNav">
            <li><a href="<?= BASE_URL ?>/index.php">Home</a></li>
            <li><a href="<?= BASE_URL ?>/projects.php">Projects</a></li>
            <li><a href="<?= BASE_URL ?>/equipment.php">Equipment</a></li>
            <li><a href="<?= BASE_URL ?>/contact.php">Contact</a></li>
        </ul>

        <div class="ysc-topbar-actions">
            <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Client'): ?>
                <a href="<?= BASE_URL ?>/track_project.php" class="ysc-btn-ghost">My Projects</a>
                <a href="<?= BASE_URL ?>/settings.php" class="ysc-btn-ghost ysc-icon-btn" title="Account Settings">
                    <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                </a>
                <a href="<?= BASE_URL ?>/logout.php" class="ysc-btn-outline-dark">Logout</a>
            <?php elseif (isset($_SESSION['user_type']) && in_array($_SESSION['user_type'], ['Admin', 'Manager'])): ?>
                <a href="<?= BASE_URL ?>/admin/amin_dashboard.php" class="ysc-btn-ghost">Admin Panel</a>
                <a href="<?= BASE_URL ?>/logout.php" class="ysc-btn-outline-dark">Logout</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/login.php" class="ysc-btn-ghost">Login</a>
                <a href="<?= BASE_URL ?>/settings.php" class="ysc-btn-ghost ysc-icon-btn" title="Settings">
                    <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                </a>
                <a href="<?= BASE_URL ?>/apply.php" class="ysc-btn-orange">Start Your Project →</a>
            <?php endif; ?>
        </div>

        <button class="ysc-toggler" id="yscToggler" aria-label="Toggle navigation" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>
    </div>

    <div class="ysc-mobile-menu" id="yscMobile">
        <ul class="ysc-mobile-nav">
            <li><a href="<?= BASE_URL ?>/index.php">Home</a></li>
            <li><a href="<?= BASE_URL ?>/projects.php">Projects</a></li>
            <li><a href="<?= BASE_URL ?>/equipment.php">Equipment</a></li>
            <li><a href="<?= BASE_URL ?>/contact.php">Contact</a></li>
        </ul>
        <div class="ysc-mobile-actions">
            <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Client'): ?>
                <a href="<?= BASE_URL ?>/track_project.php" class="ysc-btn-ghost">My Projects</a>
                <a href="<?= BASE_URL ?>/settings.php" class="ysc-btn-ghost">Settings</a>
                <a href="<?= BASE_URL ?>/logout.php" class="ysc-btn-outline-dark">Logout</a>
            <?php elseif (isset($_SESSION['user_type']) && in_array($_SESSION['user_type'], ['Admin', 'Manager'])): ?>
                <a href="<?= BASE_URL ?>/admin/amin_dashboard.php" class="ysc-btn-ghost">Admin Panel</a>
                <a href="<?= BASE_URL ?>/logout.php" class="ysc-btn-outline-dark">Logout</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/login.php" class="ysc-btn-ghost">Login</a>
                <a href="<?= BASE_URL ?>/settings.php" class="ysc-btn-ghost">Settings</a>
                <a href="<?= BASE_URL ?>/apply.php" class="ysc-btn-orange">Start Your Project →</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<script>
(function() {
    var btn = document.getElementById('yscToggler');
    var menu = document.getElementById('yscMobile');
    if (btn && menu) {
        btn.addEventListener('click', function() {
            var open = menu.classList.toggle('is-open');
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            btn.classList.toggle('is-open', open);
        });
    }
})();
</script>
