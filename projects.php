<?php

include("config/database.php");
include("includes/header.php");
include("includes/navbar.php");

$isClient = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Client';

$query = mysqli_query(
    $conn,
    "SELECT * FROM ProjectShowcase ORDER BY StartDate DESC"
);

$filter = $_GET['filter'] ?? 'all';

?>

<div class="container">
    <div class="ysc-page-header">
        <h1 class="ysc-page-title">Project Portfolio</h1>
        <p class="ysc-page-sub">Explore our completed and current works, with timelines, highlights, and progress updates from across Zamboanga del Norte.</p>
    </div>

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 pb-3 border-bottom">
        <div class="d-flex flex-wrap gap-2">
            <a href="?filter=all" class="ysc-filter-pill<?= $filter === 'all' ? ' active' : '' ?>">All Projects</a>
            <a href="?filter=completed" class="ysc-filter-pill<?= $filter === 'completed' ? ' active' : '' ?>">Completed</a>
            <a href="?filter=ongoing" class="ysc-filter-pill<?= $filter === 'ongoing' ? ' active' : '' ?>">Ongoing</a>
        </div>
        <div class="ysc-search" style="max-width:260px;flex:1;">
            <span class="ysc-search-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
            </span>
            <input type="search" id="projectSearch" placeholder="Search by name..." aria-label="Search projects">
        </div>
    </div>

    <div class="row g-4 pb-4">
        <?php while ($project = mysqli_fetch_assoc($query)):
            if ($filter === 'completed' && $project['Status'] !== 'Completed') continue;
            if ($filter === 'ongoing' && $project['Status'] !== 'Ongoing') continue;
        ?>
        <div class="col-md-6 col-lg-4 project-item" data-name="<?= htmlspecialchars(strtolower($project['Title'])) ?>">
            <div class="ysc-card h-100">
                <img src="<?= !empty($project['ImageURL']) ? BASE_URL . '/' . ltrim(htmlspecialchars($project['ImageURL']), '/') : 'https://via.placeholder.com/800x450?text=Project' ?>"
                     class="w-100" style="height:200px;object-fit:cover;" alt="<?= htmlspecialchars($project['Title']) ?>">
                <div class="ysc-card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h5 class="mb-0" style="font-size:0.95rem;font-weight:700;"><?= htmlspecialchars($project['Title']) ?></h5>
                        <span class="ysc-badge ysc-badge-<?= strtolower($project['Status']) === 'completed' ? 'completed' : 'ongoing' ?>"><?= htmlspecialchars($project['Status']) ?></span>
                    </div>
                    <p class="small text-muted mb-3" style="line-height:1.6;"><?= htmlspecialchars(strlen($project['Summary']) > 120 ? substr($project['Summary'], 0, 120) . '…' : $project['Summary']) ?></p>
                    <p class="small mb-3"><span class="text-muted">Started:</span> <?= htmlspecialchars($project['StartDate']) ?></p>
                    <?php
                    $inquireUrl = BASE_URL . '/apply.php?type=New+Project&project=' . urlencode($project['Title']);
                    $loginUrl = BASE_URL . '/login.php?redirect=' . urlencode('apply.php?type=New+Project&project=' . urlencode($project['Title']));
                    ?>
                    <?php if ($isClient): ?>
                        <a href="<?= $inquireUrl ?>" class="ysc-btn-primary w-100 text-center">Apply for Project</a>
                    <?php else: ?>
                        <a href="<?= $loginUrl ?>" class="ysc-btn-primary w-100 text-center">Apply for Project</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<script>
document.getElementById('projectSearch').addEventListener('input', function () {
    var q = this.value.toLowerCase();
    document.querySelectorAll('.project-item').forEach(function (el) {
        el.style.display = el.dataset.name.includes(q) ? '' : 'none';
    });
});
</script>

<?php include("includes/footer.php"); ?>
