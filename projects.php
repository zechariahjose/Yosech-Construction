<?php

include("config/database.php");
include("includes/header.php");
include("includes/navbar.php");

$query = mysqli_query(
    $conn,
    "SELECT * FROM ProjectShowcase ORDER BY StartDate DESC"
);

?>

<div class="container mt-5">

<h2>Finished and Ongoing Projects</h2>
<p class="mb-4">Explore our completed and current works, with timelines, highlights, and photo placeholders for later image uploads.</p>

<div class="row gy-4">

<?php while($project = mysqli_fetch_assoc($query)){ ?>

<div class="col-lg-6">
    <div class="card shadow-sm showcase-card h-100">
        <img src="<?= !empty($project['ImageURL']) ? htmlspecialchars($project['ImageURL']) : 'https://via.placeholder.com/800x450?text=Add+Project+Photo' ?>" class="card-img-top showcase-image" alt="<?= htmlspecialchars($project['Title']) ?>">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <h5 class="card-title mb-0"><?= htmlspecialchars($project['Title']) ?></h5>
                <span class="badge <?= $project['Status'] === 'Completed' ? 'bg-success' : ($project['Status'] === 'Ongoing' ? 'bg-warning text-dark' : 'bg-secondary') ?>"><?= htmlspecialchars($project['Status']) ?></span>
            </div>
            <p class="card-text mb-3"><?= nl2br(htmlspecialchars($project['Summary'])) ?></p>
            <p class="mb-1"><strong>Start Date:</strong> <?= htmlspecialchars($project['StartDate']) ?></p>
            <p class="mb-3"><strong>Completion Date:</strong> <?= $project['EndDate'] ? htmlspecialchars($project['EndDate']) : 'In progress' ?></p>
            <p class="small text-muted mb-0">Photo field available for later upload to showcase the finished site.</p>
        </div>
    </div>
</div>

<?php } ?>

</div>

</div>

<?php include("includes/footer.php"); ?>