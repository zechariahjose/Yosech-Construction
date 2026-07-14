<?php
// Resolve display name safely
$_navDisplayName = '';
if (isset($_SESSION['user_name']) && trim($_SESSION['user_name']) !== '') {
    $_navDisplayName = $_SESSION['user_name'];
} elseif (isset($_SESSION['username']) && trim($_SESSION['username']) !== '') {
    $_navDisplayName = $_SESSION['username'];
}
if ($_navDisplayName === '' && isset($_SESSION['user_type'], $_SESSION['user_id'])) {
    if (!isset($conn) && isset($GLOBALS['conn'])) $conn = $GLOBALS['conn'];
    if (isset($conn)) {
        $uid = (int) $_SESSION['user_id'];
        if ($_SESSION['user_type'] === 'Client') {
            $nr = mysqli_fetch_assoc(mysqli_query($conn,
                "SELECT Client_FirstName, Client_LastName, Client_Username FROM Client WHERE UserID={$uid} LIMIT 1"));
            if ($nr) {
                $_navDisplayName = trim(($nr['Client_FirstName'] ?? '') . ' ' . ($nr['Client_LastName'] ?? ''));
                if ($_navDisplayName === '') $_navDisplayName = $nr['Client_Username'] ?? '';
                $_SESSION['user_name'] = $_navDisplayName;
            }
        } else {
            $nr = mysqli_fetch_assoc(mysqli_query($conn,
                "SELECT Username FROM Employee WHERE EmployeeID={$uid} LIMIT 1"));
            if ($nr) { $_navDisplayName = $nr['Username'] ?? ''; $_SESSION['username'] = $_navDisplayName; }
        }
    }
}
?>
<nav class="ysc-topbar">
    <div class="container ysc-topbar-inner">

        <!-- Brand — far left -->
        <a class="ysc-topbar-brand" href="<?= BASE_URL ?>/index.php">
            <img src="<?= BASE_URL ?>/assets/other/logo.png" alt="Yosech Construction" class="brand-logo">
            <span class="brand-text">
                <span class="brand-name">YOSECH</span>
                <span class="brand-tagline">Construction</span>
            </span>
        </a>

        <!-- Centre group: nav tabs + actions all together -->
        <div class="ysc-topbar-centre" id="yscNav">

            <ul class="ysc-topbar-nav">
                <li><a href="<?= BASE_URL ?>/index.php">Home</a></li>
                <li><a href="<?= BASE_URL ?>/projects.php">Projects</a></li>
                <li><a href="<?= BASE_URL ?>/equipment.php">Equipment</a></li>
                <li><a href="<?= BASE_URL ?>/contact.php">Contact</a></li>
                <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Client'): ?>
                <li><a href="<?= BASE_URL ?>/track_project.php">Applications</a></li>
                <?php endif; ?>
            </ul>

            <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Client'): ?>
                <div class="ysc-topbar-actions">
                    <a href="<?= BASE_URL ?>/settings.php" class="ysc-nav-settings" title="Settings">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </a>
                    <a href="<?= BASE_URL ?>/logout.php" class="ysc-btn-outline-dark">Sign Out</a>
                </div>

            <?php elseif (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Admin'): ?>
                <div class="ysc-topbar-actions">
                    <a href="<?= BASE_URL ?>/admin/amin_dashboard.php" class="ysc-btn-ghost">Admin Panel</a>
                    <a href="<?= BASE_URL ?>/logout.php" class="ysc-btn-outline-dark">Sign Out</a>
                </div>

            <?php elseif (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Manager'): ?>
                <div class="ysc-topbar-actions">
                    <a href="<?= BASE_URL ?>/manager/mgr_dashboard.php" class="ysc-btn-ghost">Manager Panel</a>
                    <a href="<?= BASE_URL ?>/logout.php" class="ysc-btn-outline-dark">Sign Out</a>
                </div>

            <?php else: ?>
                <div class="ysc-topbar-actions">
                    <a href="<?= BASE_URL ?>/login.php" class="ysc-btn-ghost">Sign In</a>
                    <a href="<?= BASE_URL ?>/apply.php" class="ysc-btn-orange">
                        Start a Project
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </a>
                </div>
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
            <?php elseif (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Admin'): ?>
                <a href="<?= BASE_URL ?>/admin/amin_dashboard.php" class="ysc-btn-ghost">Admin Panel</a>
                <a href="<?= BASE_URL ?>/logout.php" class="ysc-btn-outline-dark">Sign Out</a>
            <?php elseif (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Manager'): ?>
                <a href="<?= BASE_URL ?>/manager/mgr_dashboard.php" class="ysc-btn-ghost">Manager Panel</a>
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
    var btn  = document.getElementById('yscToggler');
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
