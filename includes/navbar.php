<nav class="ysc-topbar">
    <div class="container ysc-topbar-inner">

        <!-- Brand -->
        <a class="ysc-topbar-brand" href="<?= BASE_URL ?>/index.php">
            <span class="brand-mark">YC</span>
            <span class="brand-text">
                <span class="brand-name">YOSECH</span>
                <span class="brand-tagline">Construction</span>
            </span>
        </a>

        <!-- Desktop nav -->
        <ul class="ysc-topbar-nav" id="yscNav">
            <li><a href="<?= BASE_URL ?>/index.php">Home</a></li>
            <li><a href="<?= BASE_URL ?>/projects.php">Projects</a></li>
            <li><a href="<?= BASE_URL ?>/equipment.php">Equipment</a></li>
            <li><a href="<?= BASE_URL ?>/contact.php">Contact</a></li>
        </ul>

        <!-- Desktop actions -->
        <div class="ysc-topbar-actions">
            <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Client'): ?>

                <a href="<?= BASE_URL ?>/track_project.php" class="ysc-btn-ghost">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M2 20h20M5 20V8l7-4 7 4v12"/></svg>
                    My Projects
                </a>

                <div class="ysc-user-menu">
                    <button class="ysc-user-btn" id="userMenuBtn" aria-expanded="false" aria-label="Account menu">
                        <span class="ysc-user-avatar">
                            <?php
                            $initials = strtoupper(substr($_SESSION['user_name'] ?? 'C', 0, 1));
                            echo htmlspecialchars($initials);
                            ?>
                        </span>
                        <span class="ysc-user-name"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Client') ?></span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <div class="ysc-user-dropdown" id="userDropdown">
                        <div class="ysc-dropdown-header">
                            <div class="ysc-dropdown-name"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Client') ?></div>
                            <div class="ysc-dropdown-role">Client Account</div>
                        </div>
                        <div class="ysc-dropdown-divider"></div>
                        <a href="<?= BASE_URL ?>/track_project.php" class="ysc-dropdown-item">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M2 20h20M5 20V8l7-4 7 4v12"/></svg>
                            My Projects
                        </a>
                        <a href="<?= BASE_URL ?>/settings.php" class="ysc-dropdown-item">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
                            Account Settings
                        </a>
                        <div class="ysc-dropdown-divider"></div>
                        <a href="<?= BASE_URL ?>/logout.php" class="ysc-dropdown-item ysc-dropdown-danger">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                            Sign Out
                        </a>
                    </div>
                </div>

            <?php elseif (isset($_SESSION['user_type']) && in_array($_SESSION['user_type'], ['Admin', 'Manager'])): ?>

                <a href="<?= BASE_URL ?>/<?= $_SESSION['user_type'] === 'Admin' ? 'admin/amin_dashboard.php' : 'manager/mgr_dashboard.php' ?>" class="ysc-btn-ghost">
                    <?= $_SESSION['user_type'] === 'Admin' ? 'Admin Panel' : 'Manager Panel' ?>
                </a>
                <a href="<?= BASE_URL ?>/logout.php" class="ysc-btn-outline-dark">Sign Out</a>

            <?php else: ?>

                <a href="<?= BASE_URL ?>/login.php" class="ysc-btn-ghost">Sign In</a>
                <a href="<?= BASE_URL ?>/apply.php" class="ysc-btn-orange">
                    Start a Project
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </a>

            <?php endif; ?>
        </div>

        <!-- Hamburger -->
        <button class="ysc-toggler" id="yscToggler" aria-label="Toggle navigation" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>
    </div>

    <!-- Mobile menu -->
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
                <a href="<?= BASE_URL ?>/settings.php" class="ysc-btn-ghost">Account Settings</a>
                <a href="<?= BASE_URL ?>/logout.php" class="ysc-btn-outline-dark">Sign Out</a>
            <?php elseif (isset($_SESSION['user_type']) && in_array($_SESSION['user_type'], ['Admin', 'Manager'])): ?>
                <a href="<?= BASE_URL ?>/<?= $_SESSION['user_type'] === 'Admin' ? 'admin/amin_dashboard.php' : 'manager/mgr_dashboard.php' ?>" class="ysc-btn-ghost">Panel</a>
                <a href="<?= BASE_URL ?>/logout.php" class="ysc-btn-outline-dark">Sign Out</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/login.php" class="ysc-btn-ghost">Sign In</a>
                <a href="<?= BASE_URL ?>/apply.php" class="ysc-btn-orange">Start a Project</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<script>
(function() {
    // Mobile toggle
    var btn  = document.getElementById('yscToggler');
    var menu = document.getElementById('yscMobile');
    if (btn && menu) {
        btn.addEventListener('click', function() {
            var open = menu.classList.toggle('is-open');
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            btn.classList.toggle('is-open', open);
        });
    }

    // User dropdown
    var userBtn  = document.getElementById('userMenuBtn');
    var dropdown = document.getElementById('userDropdown');
    if (userBtn && dropdown) {
        userBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            var isOpen = dropdown.classList.toggle('is-open');
            userBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
        document.addEventListener('click', function() {
            dropdown.classList.remove('is-open');
            userBtn.setAttribute('aria-expanded', 'false');
        });
    }
})();
</script>
