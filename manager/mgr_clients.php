<?php
include("../config/database.php");
include("../includes/manager/helpers.php");

managerRequireLogin('manager/mgr_clients.php');

$mgrEmployee       = adminCurrentEmployee($conn);
$mgrPendingRentals = managerPendingRentals($conn);

// ── FILTER ──────────────────────────────────────────────────
$activeFilter = $_GET['filter'] ?? 'all'; // all | active | completed
$searchQuery  = trim($_GET['q'] ?? '');

// ── KPI COUNTS ───────────────────────────────────────────────
$totalClients = (int) mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(DISTINCT c.UserID) AS t
     FROM Client c
     INNER JOIN Application a ON a.UserID = c.UserID
     INNER JOIN Project p ON p.ApplicationID = a.ApplicationID"))['t'];

$activeCount = (int) mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(DISTINCT c.UserID) AS t
     FROM Client c
     INNER JOIN Application a ON a.UserID = c.UserID
     INNER JOIN Project p ON p.ApplicationID = a.ApplicationID
     WHERE p.ProjectStatus = 'Ongoing'"))['t'];

$completedCount = (int) mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(DISTINCT c.UserID) AS t
     FROM Client c
     INNER JOIN Application a ON a.UserID = c.UserID
     INNER JOIN Project p ON p.ApplicationID = a.ApplicationID
     WHERE p.ProjectStatus = 'Completed'"))['t'];

$onHoldCount = (int) mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(DISTINCT c.UserID) AS t
     FROM Client c
     INNER JOIN Application a ON a.UserID = c.UserID
     INNER JOIN Project p ON p.ApplicationID = a.ApplicationID
     WHERE p.ProjectStatus = 'On Hold'"))['t'];

// ── BUILD QUERY — all clients with any project ───────────────
$whereFilter = '';
if ($activeFilter === 'active') {
    $whereFilter = "AND p.ProjectStatus = 'Ongoing'";
} elseif ($activeFilter === 'completed') {
    $whereFilter = "AND p.ProjectStatus = 'Completed'";
}

$searchWhere = '';
if ($searchQuery !== '') {
    $esc = mysqli_real_escape_string($conn, $searchQuery);
    $searchWhere = " AND (c.Client_FirstName LIKE '%{$esc}%'
                      OR c.Client_LastName  LIKE '%{$esc}%'
                      OR c.Client_Username  LIKE '%{$esc}%'
                      OR a.ProjectTitle     LIKE '%{$esc}%')";
}

$sql = "
    SELECT c.UserID,
           c.Client_FirstName, c.Client_MI, c.Client_LastName,
           c.Client_Username, c.Client_Email, c.Client_ContactNumber,
           COUNT(DISTINCT p.ProjectID)                                      AS total_projects,
           SUM(p.ProjectStatus = 'Ongoing')                                AS ongoing_count,
           SUM(p.ProjectStatus = 'On Hold')                                AS onhold_count,
           SUM(p.ProjectStatus = 'Completed')                              AS completed_count,
           SUM(p.ProjectStatus = 'Cancelled')                              AS cancelled_count,
           MAX(pu.UpdateDate)                                               AS last_interaction,
           GROUP_CONCAT(DISTINCT a.ProjectTitle ORDER BY a.ProjectTitle SEPARATOR '|||') AS project_titles
    FROM Client c
    INNER JOIN Application a ON a.UserID = c.UserID
    INNER JOIN Project p ON p.ApplicationID = a.ApplicationID
    LEFT JOIN Project_Update pu ON pu.ProjectID = p.ProjectID
    WHERE 1=1 {$whereFilter} {$searchWhere}
    GROUP BY c.UserID
    ORDER BY ongoing_count DESC, last_interaction DESC, c.UserID DESC
";
$result = mysqli_query($conn, $sql);
$rowCount = mysqli_num_rows($result);

// Build tab URL helper
function tabUrl(string $filter, string $q): string {
    $params = ['filter' => $filter];
    if ($q !== '') $params['q'] = $q;
    return 'mgr_clients.php?' . http_build_query($params);
}

$mgrActiveNav    = 'clients';
$mgrPageTitle    = 'Site Clients';
$mgrPageSubtitle = 'All clients with linked project records across every status.';
$mgrPageActions  = '';

include("../includes/manager/layout_start.php");
?>

<!-- ── KPI Cards ─────────────────────────────────────────── -->
<div class="admin-kpi-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:24px;">
    <div class="admin-kpi-card">
        <div class="admin-kpi-label">Total Site Clients</div>
        <div class="admin-kpi-value"><?= $totalClients ?></div>
        <div class="admin-kpi-meta">Have at least one project</div>
    </div>
    <div class="admin-kpi-card manager-kpi-ok">
        <div class="admin-kpi-label">Active (Ongoing)</div>
        <div class="admin-kpi-value"><?= $activeCount ?></div>
        <div class="admin-kpi-meta positive">Currently in progress</div>
    </div>
    <div class="admin-kpi-card manager-kpi-warn">
        <div class="admin-kpi-label">On Hold</div>
        <div class="admin-kpi-value"><?= $onHoldCount ?></div>
        <div class="admin-kpi-meta<?= $onHoldCount > 0 ? ' alert' : '' ?>">
            <?= $onHoldCount > 0 ? 'Requires follow-up' : 'None on hold' ?>
        </div>
    </div>
    <div class="admin-kpi-card">
        <div class="admin-kpi-label">Completed</div>
        <div class="admin-kpi-value"><?= $completedCount ?></div>
        <div class="admin-kpi-meta">Projects finished</div>
    </div>
</div>

<!-- ── Filter tabs + search ──────────────────────────────── -->
<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:16px;">

    <!-- Tabs -->
    <div style="display:flex;gap:4px;background:#fff;border:1px solid var(--admin-border);border-radius:8px;padding:4px;">
        <?php
        $tabs = [
            'all'       => "All ({$totalClients})",
            'active'    => "Active ({$activeCount})",
            'completed' => "Completed ({$completedCount})",
        ];
        foreach ($tabs as $key => $label):
            $isActive = $activeFilter === $key;
        ?>
        <a href="<?= tabUrl($key, $searchQuery) ?>"
           style="display:inline-flex;align-items:center;padding:6px 14px;font-size:0.78rem;font-weight:600;border-radius:5px;text-decoration:none;transition:all .15s;
                  <?= $isActive ? 'background:var(--ysc-primary);color:#fff;' : 'color:var(--admin-muted);' ?>">
            <?= $label ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Search -->
    <form method="GET" style="display:flex;align-items:center;gap:8px;">
        <input type="hidden" name="filter" value="<?= htmlspecialchars($activeFilter) ?>">
        <div style="position:relative;">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--ysc-muted-light);pointer-events:none;"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
            <input type="search" name="q" value="<?= htmlspecialchars($searchQuery) ?>"
                   placeholder="Search clients or projects…"
                   style="padding:7px 12px 7px 30px;border:1px solid var(--admin-border);border-radius:6px;font-size:0.82rem;font-family:inherit;width:220px;background:#fff;">
        </div>
        <button type="submit" class="admin-btn admin-btn-primary admin-btn-sm">Search</button>
        <?php if ($searchQuery !== ''): ?>
            <a href="<?= tabUrl($activeFilter, '') ?>" class="admin-btn admin-btn-outline admin-btn-sm">Clear</a>
        <?php endif; ?>
    </form>
</div>

<!-- ── Table ─────────────────────────────────────────────── -->
<div class="admin-panel">
    <?php if ($rowCount === 0): ?>
        <div class="admin-empty">
            <strong>No clients found</strong>
            <?php if ($searchQuery): ?>
                No results for "<?= htmlspecialchars($searchQuery) ?>". Try a different search.
            <?php elseif ($activeFilter === 'active'): ?>
                No clients with ongoing projects at the moment.
            <?php elseif ($activeFilter === 'completed'): ?>
                No clients with completed projects yet.
            <?php else: ?>
                Clients appear here when they have linked project records.
            <?php endif; ?>
        </div>
    <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Contact</th>
                    <th>Projects</th>
                    <th>Status Breakdown</th>
                    <th>Last Update</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($client = mysqli_fetch_assoc($result)):
                    $fullName = trim(
                        $client['Client_FirstName'] . ' ' .
                        ($client['Client_MI'] ? $client['Client_MI'] . '. ' : '') .
                        $client['Client_LastName']
                    );

                    // Parse project titles
                    $titles = !empty($client['project_titles'])
                        ? array_filter(explode('|||', $client['project_titles']))
                        : [];
                    $titlePreview = count($titles) > 0
                        ? (strlen($titles[0]) > 45 ? substr($titles[0], 0, 42) . '…' : $titles[0])
                          . (count($titles) > 1 ? ' +' . (count($titles) - 1) . ' more' : '')
                        : '—';
                ?>
                <tr>
                    <!-- Client -->
                    <td>
                        <span class="admin-table-project"><?= htmlspecialchars($fullName) ?></span>
                        <span class="admin-table-sub">@<?= htmlspecialchars($client['Client_Username']) ?></span>
                    </td>

                    <!-- Contact -->
                    <td>
                        <span style="font-size:0.82rem;"><?= htmlspecialchars($client['Client_Email']) ?></span>
                        <?php if ($client['Client_ContactNumber']): ?>
                            <span class="admin-table-sub"><?= htmlspecialchars($client['Client_ContactNumber']) ?></span>
                        <?php endif; ?>
                    </td>

                    <!-- Projects -->
                    <td>
                        <span class="admin-table-project"><?= (int)$client['total_projects'] ?> project<?= (int)$client['total_projects'] !== 1 ? 's' : '' ?></span>
                        <span class="admin-table-sub"><?= htmlspecialchars($titlePreview) ?></span>
                    </td>

                    <!-- Status breakdown chips -->
                    <td>
                        <div style="display:flex;flex-wrap:wrap;gap:4px;">
                            <?php if ((int)$client['ongoing_count'] > 0): ?>
                                <span class="admin-badge admin-badge-track"><?= (int)$client['ongoing_count'] ?> Ongoing</span>
                            <?php endif; ?>
                            <?php if ((int)$client['onhold_count'] > 0): ?>
                                <span class="admin-badge admin-badge-inspection"><?= (int)$client['onhold_count'] ?> On Hold</span>
                            <?php endif; ?>
                            <?php if ((int)$client['completed_count'] > 0): ?>
                                <span class="admin-badge admin-badge-complete"><?= (int)$client['completed_count'] ?> Completed</span>
                            <?php endif; ?>
                            <?php if ((int)$client['cancelled_count'] > 0): ?>
                                <span class="admin-badge admin-badge-cancelled"><?= (int)$client['cancelled_count'] ?> Cancelled</span>
                            <?php endif; ?>
                        </div>
                    </td>

                    <!-- Last interaction -->
                    <td>
                        <?php if (!empty($client['last_interaction'])): ?>
                            <span class="admin-table-project" style="font-weight:500;">
                                <?= adminTimeAgo($client['last_interaction']) ?>
                            </span>
                        <?php else: ?>
                            <span class="admin-table-sub">No updates yet</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include("../includes/manager/layout_end.php"); ?>
