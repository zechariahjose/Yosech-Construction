<?php
include("../config/database.php");
include("../includes/manager/helpers.php");

managerRequireLogin('manager/mgr_clients.php');

$mgrEmployee = adminCurrentEmployee($conn);
$mgrPendingRentals = managerPendingRentals($conn);

$searchQuery = trim($_GET['q'] ?? '');
$sql = "
    SELECT c.*,
           COUNT(DISTINCT p.ProjectID) AS active_projects,
           MAX(pu.UpdateDate) AS last_interaction,
           GROUP_CONCAT(DISTINCT a.ProjectTitle ORDER BY a.ProjectTitle SEPARATOR ' · ') AS project_names
    FROM Client c
    INNER JOIN Application app ON app.UserID = c.UserID
    INNER JOIN Project p ON p.ApplicationID = app.ApplicationID AND p.ProjectStatus IN ('Ongoing', 'On Hold')
    LEFT JOIN Application a ON a.UserID = c.UserID AND a.ProjectTitle IS NOT NULL
    LEFT JOIN Project_Update pu ON pu.ProjectID = p.ProjectID
    WHERE 1=1
";

if ($searchQuery !== '') {
    $escSearch = mysqli_real_escape_string($conn, $searchQuery);
    $sql .= " AND (c.Client_FirstName LIKE '%{$escSearch}%'
              OR c.Client_LastName LIKE '%{$escSearch}%'
              OR a.ProjectTitle LIKE '%{$escSearch}%'
              OR a.ProjectLocation LIKE '%{$escSearch}%')";
}

$sql .= " GROUP BY c.UserID ORDER BY last_interaction DESC, c.UserID DESC";
$result = mysqli_query($conn, $sql);

$mgrActiveNav = 'clients';
$mgrPageTitle = 'Site Clients & Stakeholders';
$mgrPageSubtitle = 'Clients and contacts tied to your active developments — interaction history from project updates.';
$mgrPageActions = '';

include("../includes/manager/layout_start.php");
?>

<div class="admin-panel">
    <?php if (mysqli_num_rows($result) === 0): ?>
        <div class="admin-empty">
            <strong>No active site clients</strong>
            Clients appear here when they have ongoing or on-hold projects under your management.
        </div>
    <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Stakeholder</th>
                    <th>Active Developments</th>
                    <th>Contact</th>
                    <th>Last Interaction</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($client = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td>
                        <span class="admin-table-project">
                            <?= htmlspecialchars(trim($client['Client_FirstName'] . ' ' . ($client['Client_MI'] ? $client['Client_MI'] . '. ' : '') . $client['Client_LastName'])) ?>
                        </span>
                        <span class="admin-table-sub"><?= htmlspecialchars($client['Client_Username']) ?></span>
                    </td>
                    <td>
                        <span class="admin-table-project"><?= (int) $client['active_projects'] ?> site(s)</span>
                        <?php if (!empty($client['project_names'])): ?>
                            <span class="admin-table-sub"><?= htmlspecialchars(strlen($client['project_names']) > 80 ? substr($client['project_names'], 0, 77) . '…' : $client['project_names']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= htmlspecialchars($client['Client_Email']) ?>
                        <?php if ($client['Client_ContactNumber']): ?>
                            <span class="admin-table-sub"><?= htmlspecialchars($client['Client_ContactNumber']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars(adminTimeAgo($client['last_interaction'])) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include("../includes/manager/layout_end.php"); ?>
