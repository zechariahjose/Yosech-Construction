<?php
include("config/database.php");
include("includes/header.php");
include("includes/navbar.php");

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Client') {
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$query = mysqli_query(
    $conn,
    "SELECT a.ApplicationID, a.ApplicationType, a.Description AS ApplicationDescription, a.SubmissionDate, a.Status AS ApplicationStatus,
           p.ProjectID, p.ProjectStatus, p.PaymentStatus, p.StartDate, p.EndDate
     FROM Application a
     LEFT JOIN Project p ON a.ApplicationID = p.ApplicationID
     WHERE a.UserID = {$userId}
     ORDER BY a.SubmissionDate DESC"
);
?>

<div class="container mt-5">
    <h2>My Applications and Projects</h2>

    <?php if (mysqli_num_rows($query) === 0): ?>
        <div class="alert alert-info">You have not submitted any applications yet. <a href="apply.php">Apply now</a>.</div>
    <?php endif; ?>

    <?php while ($row = mysqli_fetch_assoc($query)): ?>
        <div class="card mb-3">
            <div class="card-body">
                <h5 class="card-title">Application #<?= $row['ApplicationID'] ?></h5>
                <p class="card-text"><strong>Type:</strong> <?= htmlspecialchars($row['ApplicationType']) ?></p>
                <p class="card-text"><strong>Description:</strong> <?= nl2br(htmlspecialchars($row['ApplicationDescription'])) ?></p>
                <p class="card-text"><strong>Submitted:</strong> <?= htmlspecialchars($row['SubmissionDate']) ?></p>
                <p class="card-text"><strong>Application Status:</strong> <?= htmlspecialchars($row['ApplicationStatus']) ?></p>

                <?php if ($row['ProjectID']): ?>
                    <hr>
                    <p class="card-text"><strong>Project #<?= $row['ProjectID'] ?></strong></p>
                    <p class="card-text"><strong>Project Status:</strong> <?= htmlspecialchars($row['ProjectStatus']) ?></p>
                    <p class="card-text"><strong>Payment Status:</strong> <?= htmlspecialchars($row['PaymentStatus']) ?></p>
                    <p class="card-text"><strong>Start Date:</strong> <?= htmlspecialchars($row['StartDate'] ?: 'TBD') ?></p>
                    <p class="card-text"><strong>End Date:</strong> <?= htmlspecialchars($row['EndDate'] ?: 'TBD') ?></p>
                <?php else: ?>
                    <div class="alert alert-secondary">Your application is still under review. A project will appear here once approved.</div>
                <?php endif; ?>
            </div>
        </div>
    <?php endwhile; ?>
</div>

<?php include("includes/footer.php"); ?>