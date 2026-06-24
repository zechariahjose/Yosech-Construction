<?php
include("../config/database.php");
include("../includes/admin/helpers.php");

adminRequireLogin('admin/amin_clients.php');

$adminEmployee = adminCurrentEmployee($conn);
$adminPendingCount = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM Application WHERE Status = 'Pending'"))['total'];

$searchQuery = trim($_GET['q'] ?? '');
$sql = "
    SELECT c.*,
           (SELECT COUNT(*) FROM Application a WHERE a.UserID = c.UserID) AS application_count,
           (SELECT COUNT(*) FROM Application a WHERE a.UserID = c.UserID AND a.Status = 'Approved') AS approved_count,
           (SELECT COALESCE(SUM(a.ProposalBudget), 0) FROM Application a WHERE a.UserID = c.UserID AND a.Status = 'Approved') AS approved_revenue
    FROM Client c
";

if ($searchQuery !== '') {
    $escSearch = mysqli_real_escape_string($conn, $searchQuery);
    $sql .= " WHERE c.Client_FirstName LIKE '%{$escSearch}%'
              OR c.Client_LastName LIKE '%{$escSearch}%'
              OR c.Client_Username LIKE '%{$escSearch}%'
              OR c.Client_Email LIKE '%{$escSearch}%'
              OR c.Client_ContactNumber LIKE '%{$escSearch}%'";
}

$sql .= " ORDER BY c.UserID DESC";
$result = mysqli_query($conn, $sql);

$totalClients = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM Client"))['total'];
$totalRevenue = (float) mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT COALESCE(SUM(ProposalBudget), 0) AS total FROM Application WHERE Status = 'Approved'"
))['total'];

$adminActiveNav = 'clients';
$adminPageTitle = 'Clients';
$adminPageSubtitle = 'Corporate client relationships, revenue from approved proposals, and account growth.';
$adminPageActions = '
    <span class="admin-btn admin-btn-outline" style="cursor:default;">' . $totalClients . ' clients</span>
    <span class="admin-btn admin-btn-primary" style="cursor:default;">₱' . number_format($totalRevenue, 0) . ' approved pipeline</span>
';

include("../includes/admin/layout_start.php");
?>

<div class="admin-panel">
    <?php if (mysqli_num_rows($result) === 0): ?>
        <div class="admin-empty">
            <strong>No clients found</strong>
            Client accounts appear here after they sign up on the public site.
        </div>
    <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Contact</th>
                    <th>Applications</th>
                    <th>Approved</th>
                    <th>Approved Revenue</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($client = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td>
                        <span class="admin-table-project">
                            <?= htmlspecialchars(trim($client['Client_FirstName'] . ' ' . ($client['Client_MI'] ? $client['Client_MI'] . '. ' : '') . $client['Client_LastName'])) ?>
                        </span>
                        <span class="admin-table-sub">ID #<?= (int) $client['UserID'] ?></span>
                    </td>
                    <td><?= htmlspecialchars($client['Client_Username']) ?></td>
                    <td><?= htmlspecialchars($client['Client_Email']) ?></td>
                    <td><?= htmlspecialchars($client['Client_ContactNumber'] ?: '—') ?></td>
                    <td><?= (int) $client['application_count'] ?></td>
                    <td><?= (int) $client['approved_count'] ?></td>
                    <td>₱<?= number_format((float) $client['approved_revenue'], 0) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include("../includes/admin/layout_end.php"); ?>
