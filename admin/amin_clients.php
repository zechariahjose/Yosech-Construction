<?php
include("../config/database.php");
include("../includes/admin/helpers.php");

adminRequireLogin('admin/amin_clients.php');

$adminEmployee     = adminCurrentEmployee($conn);
$adminPendingCount = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM Application WHERE Status = 'Pending'"))['total'];

$success = '';
$error   = '';

// ── DELETE CLIENT ───────────────────────────────────────────
if (isset($_POST['delete_client'], $_POST['client_id'])) {
    $cid = (int) $_POST['client_id'];
    $del = mysqli_prepare($conn, "DELETE FROM Client WHERE UserID = ?");
    mysqli_stmt_bind_param($del, "i", $cid);
    mysqli_stmt_execute($del)
        ? $success = "Client account #$cid has been removed."
        : $error   = "Failed to remove client account.";
}

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
    <a href="' . BASE_URL . '/admin/amin_export.php?type=clients" class="admin-btn admin-btn-outline">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Export CSV
    </a>
';

include("../includes/admin/layout_start.php");
?>

<?php if ($success): ?>
    <div class="admin-alert" style="background:#f0fdf4;color:#166534;border:1px solid #bbf7d0;margin-bottom:20px;display:flex;align-items:center;gap:8px;">
        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
        <?= htmlspecialchars($success) ?>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="admin-alert" style="background:#fef2f2;color:#991b1b;border:1px solid #fecaca;margin-bottom:20px;">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

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
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php while ($client = mysqli_fetch_assoc($result)):
                    $fullName = trim($client['Client_FirstName'] . ' ' . ($client['Client_MI'] ? $client['Client_MI'] . '. ' : '') . $client['Client_LastName']);
                ?>
                <tr>
                    <td>
                        <span class="admin-table-project"><?= htmlspecialchars($fullName) ?></span>
                        <span class="admin-table-sub">ID #<?= (int) $client['UserID'] ?></span>
                    </td>
                    <td><?= htmlspecialchars($client['Client_Username']) ?></td>
                    <td><?= htmlspecialchars($client['Client_Email']) ?></td>
                    <td><?= htmlspecialchars($client['Client_ContactNumber'] ?: '—') ?></td>
                    <td><?= (int) $client['application_count'] ?></td>
                    <td><?= (int) $client['approved_count'] ?></td>
                    <td>₱<?= number_format((float) $client['approved_revenue'], 0) ?></td>
                    <td>
                        <form method="POST" onsubmit="return confirm('Remove <?= htmlspecialchars(addslashes($fullName)) ?>? This will also delete all their applications and cannot be undone.');">
                            <input type="hidden" name="client_id" value="<?= (int) $client['UserID'] ?>">
                            <button type="submit" name="delete_client" class="admin-btn admin-btn-danger admin-btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include("../includes/admin/layout_end.php"); ?>
