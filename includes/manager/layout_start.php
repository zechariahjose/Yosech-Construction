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

$mgrActiveNav = $mgrActiveNav ?? 'dashboard';
$mgrPageTitle = $mgrPageTitle ?? 'Site Dashboard';
$mgrPageSubtitle = $mgrPageSubtitle ?? '';
$mgrPageActions = $mgrPageActions ?? '';
$mgrEmployee = $mgrEmployee ?? null;
$mgrDisplayName = $mgrEmployee['Username'] ?? ($_SESSION['username'] ?? 'Project Manager');
$mgrPendingRentals = $mgrPendingRentals ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($mgrPageTitle) ?> · Yosech PM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/manager.css">
</head>
<body class="admin-body manager-body">
<div class="admin-shell">
    <aside class="admin-sidebar manager-sidebar">
        <div class="manager-console-banner">Project Manager Console</div>

        <div class="admin-brand">
            <div class="admin-brand-icon manager-brand-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div>
                <div class="admin-brand-name">Yosech Projects</div>
                <div class="admin-brand-sub">Field Operations</div>
            </div>
        </div>

        <nav class="admin-nav">
            <a href="<?= BASE_URL ?>/manager/mgr_dashboard.php" class="admin-nav-link<?= $mgrActiveNav === 'dashboard' ? ' active' : '' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                Dashboard
            </a>
            <a href="<?= BASE_URL ?>/manager/mgr_projects.php" class="admin-nav-link<?= $mgrActiveNav === 'projects' ? ' active' : '' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M2 20h20M5 20V8l7-4 7 4v12"/></svg>
                Active Projects
            </a>
            <a href="<?= BASE_URL ?>/manager/mgr_applications.php" class="admin-nav-link<?= $mgrActiveNav === 'applications' ? ' active' : '' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M4 4h16v16H4z"/><path d="M8 8h8M8 12h8M8 16h5"/></svg>
                Applications
                <?php if ((int) $mgrPendingRentals > 0): ?>
                    <span class="admin-nav-badge"><?= (int) $mgrPendingRentals ?></span>
                <?php endif; ?>
            </a>
            <a href="<?= BASE_URL ?>/manager/mgr_clients.php" class="admin-nav-link<?= $mgrActiveNav === 'clients' ? ' active' : '' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Site Clients
            </a>
            <a href="<?= BASE_URL ?>/manager/mgr_equipment.php" class="admin-nav-link<?= $mgrActiveNav === 'equipment' ? ' active' : '' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><rect x="1" y="3" width="15" height="13" rx="2"/><path d="M16 8h4l3 5v3h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                Assigned Equipment
            </a>
            <a href="<?= BASE_URL ?>/manager/mgr_settings.php" class="admin-nav-link<?= $mgrActiveNav === 'settings' ? ' active' : '' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="3"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
                My Settings
            </a>
        </nav>

        <div class="admin-sidebar-footer">
            <a href="<?= BASE_URL ?>/manager/mgr_applications.php?type=rental&status=Pending" class="admin-btn-new manager-btn-new">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                Review Pending Rentals
            </a>
            <a href="<?= BASE_URL ?>/logout.php" class="admin-signout">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
                Sign Out
            </a>
        </div>
    </aside>

    <div class="admin-main">
        <header class="admin-topbar">
            <span class="console-identity console-identity-pm">PM</span>

            <form class="admin-search" method="get" action="<?= BASE_URL ?>/manager/mgr_dashboard.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                <input type="search" name="q" placeholder="Search active sites, clients..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" aria-label="Search manager records">
            </form>

            <div class="admin-topbar-actions">
                <a href="<?= BASE_URL ?>/manager/mgr_applications.php?type=rental&status=Pending" class="admin-icon-btn" title="Pending rentals">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                    <?php if ((int) $mgrPendingRentals > 0): ?>
                        <span class="admin-icon-dot"></span>
                    <?php endif; ?>
                </a>
                <button type="button" class="admin-icon-btn" title="View public site" onclick="document.getElementById('ysc-restricted-modal').style.display='flex'">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                </button>

                <div id="ysc-restricted-modal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.45);align-items:center;justify-content:center;" onclick="if(event.target===this)this.style.display='none'">
                    <div style="background:#fff;border-radius:10px;padding:36px 32px;max-width:380px;width:90%;box-shadow:0 8px 32px rgba(0,0,0,.18);text-align:center;">
                        <div style="width:52px;height:52px;border-radius:50%;background:#eef2f6;display:flex;align-items:center;justify-content:center;margin:0 auto 18px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="none" viewBox="0 0 24 24" stroke="#6b7f94" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                        </div>
                        <h2 style="font-size:1.05rem;font-weight:800;color:#111827;margin:0 0 10px;">Access Restricted</h2>
                        <p style="font-size:0.84rem;color:#6b7280;line-height:1.6;margin:0 0 24px;">The client interface is not accessible from the project manager console. You are restricted to the dashboard and its management tools only.</p>
                        <button type="button" onclick="document.getElementById('ysc-restricted-modal').style.display='none'" style="background:#111;color:#fff;border:none;border-radius:6px;padding:10px 28px;font-size:0.82rem;font-weight:700;letter-spacing:.04em;text-transform:uppercase;cursor:pointer;">Got it</button>
                    </div>
                </div>
                <div class="admin-user">
                    <div class="admin-user-meta">
                        <div class="admin-user-name"><?= htmlspecialchars($mgrDisplayName) ?></div>
                        <div class="admin-user-role">PROJECT MANAGER</div>
                    </div>
                    <div class="admin-user-avatar manager-user-avatar"><?= strtoupper(substr($mgrDisplayName, 0, 1)) ?></div>
                </div>
            </div>
        </header>

        <main class="admin-content">
            <div class="admin-page-head">
                <div>
                    <h1 class="admin-page-title"><?= htmlspecialchars($mgrPageTitle) ?></h1>
                    <?php if ($mgrPageSubtitle !== ''): ?>
                        <p class="admin-page-sub"><?= htmlspecialchars($mgrPageSubtitle) ?></p>
                    <?php endif; ?>
                </div>
                <?php if ($mgrPageActions !== ''): ?>
                    <div class="admin-page-actions"><?= $mgrPageActions ?></div>
                <?php endif; ?>
            </div>
