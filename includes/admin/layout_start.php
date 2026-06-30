<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('BASE_URL')) {
    $scriptPath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    $appBase = preg_replace('#/(admin|manager)$#', '', $scriptPath);
    $appBase = rtrim($appBase, '/');
    if ($appBase === '') {
        $appBase = '/';
    }
    define('BASE_URL', $appBase);
}

$adminActiveNav = $adminActiveNav ?? 'dashboard';
$adminPageTitle = $adminPageTitle ?? 'Admin Dashboard';
$adminPageSubtitle = $adminPageSubtitle ?? '';
$adminPageActions = $adminPageActions ?? '';
$adminEmployee = $adminEmployee ?? null;
$adminDisplayName = $adminEmployee['Username'] ?? ($_SESSION['username'] ?? 'Admin User');
$adminRoleLabel = strtoupper($adminEmployee['UserType'] ?? $_SESSION['user_type'] ?? 'ADMIN');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($adminPageTitle) ?> · Yosech Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
</head>
<body class="admin-body">
<div class="admin-shell">
    <aside class="admin-sidebar admin-console-sidebar">
        <div class="admin-console-banner">Administrator Console</div>

        <div class="admin-brand">
            <div class="admin-brand-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4"/></svg>
            </div>
            <div>
                <div class="admin-brand-name">Yosech Admin</div>
                <div class="admin-brand-sub">Operations Console</div>
            </div>
        </div>

        <nav class="admin-nav">
            <a href="<?= BASE_URL ?>/admin/amin_dashboard.php" class="admin-nav-link<?= $adminActiveNav === 'dashboard' ? ' active' : '' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                Dashboard
            </a>
            <a href="<?= BASE_URL ?>/admin/amin_projects.php" class="admin-nav-link<?= $adminActiveNav === 'projects' ? ' active' : '' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M2 20h20M5 20V8l7-4 7 4v12"/></svg>
                Projects
            </a>
            <a href="<?= BASE_URL ?>/admin/amin_applications.php" class="admin-nav-link<?= $adminActiveNav === 'history' ? ' active' : '' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M12 8v4l3 3M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>
                History
            </a>
            <a href="<?= BASE_URL ?>/admin/amin_clients.php" class="admin-nav-link<?= $adminActiveNav === 'clients' ? ' active' : '' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Clients
            </a>
            <a href="<?= BASE_URL ?>/admin/amin_equipment.php" class="admin-nav-link<?= $adminActiveNav === 'equipment' ? ' active' : '' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><rect x="1" y="3" width="15" height="13" rx="2"/><path d="M16 8h4l3 5v3h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                Equipment
            </a>

            <a href="<?= BASE_URL ?>/admin/amin_settings.php" class="admin-nav-link<?= $adminActiveNav === 'settings' ? ' active' : '' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="3"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
                Settings
            </a>
        </nav>

        <div class="admin-sidebar-footer">
            <a href="<?= BASE_URL ?>/admin/amin_applications.php" class="admin-btn-new">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 8v4l3 3M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>
                View History
            </a>
            <a href="<?= BASE_URL ?>/logout.php" class="admin-signout">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
                Sign Out
            </a>
        </div>
    </aside>

    <div class="admin-main">
        <header class="admin-topbar">
            <span class="console-identity console-identity-admin">Admin</span>

            <form class="admin-search" method="get" action="<?= BASE_URL ?>/admin/amin_dashboard.php" id="adminGlobalSearchForm">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                <input type="search" name="q" placeholder="Search project IDs, client names..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" aria-label="Search admin records">
            </form>

            <div class="admin-topbar-actions">
                <a href="<?= BASE_URL ?>/admin/amin_applications.php?status=Pending" class="admin-icon-btn" title="Pending applications history">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                    <?php if (!empty($adminPendingCount) && (int) $adminPendingCount > 0): ?>
                        <span class="admin-icon-dot"></span>
                    <?php endif; ?>
                </a>
                <div class="admin-user">
                    <div class="admin-user-meta">
                        <div class="admin-user-name"><?= htmlspecialchars($adminDisplayName) ?></div>
                        <div class="admin-user-role"><?= htmlspecialchars($adminRoleLabel) ?></div>
                    </div>
                    <div class="admin-user-avatar"><?= strtoupper(substr($adminDisplayName, 0, 1)) ?></div>
                </div>
            </div>
        </header>

        <main class="admin-content">
            <div class="admin-page-head">
                <div>
                    <h1 class="admin-page-title"><?= htmlspecialchars($adminPageTitle) ?></h1>
                    <?php if ($adminPageSubtitle !== ''): ?>
                        <p class="admin-page-sub"><?= htmlspecialchars($adminPageSubtitle) ?></p>
                    <?php endif; ?>
                </div>
                <?php if ($adminPageActions !== ''): ?>
                    <div class="admin-page-actions"><?= $adminPageActions ?></div>
                <?php endif; ?>
            </div>
