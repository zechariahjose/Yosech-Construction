<?php
include("config/database.php");
include("includes/header.php");
include("includes/navbar.php");

$isClient = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Client';

$result = mysqli_query(
    $conn,
    "SELECT * FROM ProjectShowcase ORDER BY StartDate DESC"
);

$projects = [];
while ($row = mysqli_fetch_assoc($result)) {
    $projects[] = $row;
}

$totalCompleted = count(array_filter($projects, fn($p) => $p['Status'] === 'Completed'));
$totalOngoing   = count(array_filter($projects, fn($p) => $p['Status'] === 'Ongoing'));
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/projects.css">

<!-- ── Hero ─────────────────────────────────────────────────── -->
<section class="pj-hero">
    <div class="pj-hero-bg" style="background-image:url('<?= BASE_URL ?>/assets/projects/multiPurposeBuilding.jpg');"></div>
    <div class="pj-hero-overlay"></div>
    <div class="container pj-hero-content">
        <div class="hero-eyebrow">Architectural Excellence</div>
        <h1 class="hero-title">Building Tomorrow's<br>Dipolog Today.</h1>
        <p class="hero-sub">A showcase of structural integrity and modern urban development across the Zamboanga del Norte region.</p>
    </div>
</section>

<!-- ── Stats bar ─────────────────────────────────────────────── -->
<div class="pj-stats-bar">
    <div class="container pj-stats-inner">
        <div class="pj-stat">
            <span class="pj-stat-num"><?= count($projects) ?></span>
            <span class="pj-stat-label">Total Projects</span>
        </div>
        <div class="pj-stat-divider"></div>
        <div class="pj-stat">
            <span class="pj-stat-num"><?= $totalCompleted ?></span>
            <span class="pj-stat-label">Completed</span>
        </div>
        <div class="pj-stat-divider"></div>
        <div class="pj-stat">
            <span class="pj-stat-num"><?= $totalOngoing ?></span>
            <span class="pj-stat-label">Ongoing</span>
        </div>

    </div>
</div>

<!-- ── Main content ──────────────────────────────────────────── -->
<div class="container pj-body">

    <!-- Filter bar -->
    <div class="pj-filter-bar">
        <div class="pj-filter-pills">
            <button class="pj-filter-btn active" data-filter="all">All Projects</button>
            <button class="pj-filter-btn" data-filter="Completed">Completed</button>
            <button class="pj-filter-btn" data-filter="Ongoing">Ongoing</button>
        </div>
        <div class="pj-search-wrap">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
            <input type="search" id="projectSearch" placeholder="Search projects..." aria-label="Search projects">
        </div>
    </div>

    <!-- Grid -->
    <div class="pj-grid" id="pjGrid">
        <?php foreach ($projects as $i => $project):
            $isAvailable = $project['Status'] === 'Completed';
            $inquireUrl  = BASE_URL . '/apply.php?type=New+Project&project=' . urlencode($project['Title']);
            $loginUrl    = BASE_URL . '/login.php?redirect=' . urlencode('apply.php?type=New+Project&project=' . urlencode($project['Title']));
            $imgSrc      = !empty($project['ImageURL'])
                ? BASE_URL . '/' . ltrim(htmlspecialchars($project['ImageURL']), '/')
                : 'https://placehold.co/1200x800/111111/ffffff?text=Project';
            $summary     = htmlspecialchars(strlen($project['Summary']) > 100
                ? substr($project['Summary'], 0, 100) . '…'
                : $project['Summary']);
            // First card spans two columns for the hero-card look
            $isHero = ($i === 0);
        ?>
        <div class="pj-card<?= $isHero ? ' pj-card-hero' : '' ?> project-item"
             data-name="<?= htmlspecialchars(strtolower($project['Title'])) ?>"
             data-status="<?= htmlspecialchars($project['Status']) ?>">

            <div class="pj-card-img-wrap">
                <img src="<?= $imgSrc ?>"
                     alt="<?= htmlspecialchars($project['Title']) ?>"
                     class="pj-card-img">
                <span class="pj-status-chip pj-status-<?= strtolower($project['Status']) ?>">
                    <?= htmlspecialchars($project['Status']) ?>
                </span>
            </div>

            <div class="pj-card-body">
                <div class="pj-card-top">
                    <div class="pj-card-date">
                        <?= date('M Y', strtotime($project['StartDate'])) ?>
                        <?php if (!empty($project['EndDate'])): ?>
                            – <?= date('M Y', strtotime($project['EndDate'])) ?>
                        <?php endif; ?>
                    </div>
                    <h3 class="pj-card-title"><?= htmlspecialchars($project['Title']) ?></h3>
                    <?php if ($isHero): ?>
                        <p class="pj-card-summary"><?= $summary ?></p>
                    <?php endif; ?>
                </div>
                <a href="<?= $isClient ? $inquireUrl : $loginUrl ?>" class="pj-card-apply">
                    Apply for Project
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <p class="pj-no-results" id="pjNoResults" style="display:none;">
        No projects matched your search.
    </p>

</div>

<script>
(function () {
    var searchInput = document.getElementById('projectSearch');
    var filterBtns  = document.querySelectorAll('.pj-filter-btn');
    var cards       = document.querySelectorAll('.project-item');
    var noResults   = document.getElementById('pjNoResults');

    var activeFilter = 'all';
    var query        = '';

    function filter() {
        var visible = 0;
        cards.forEach(function (card) {
            var nameMatch   = card.dataset.name.includes(query);
            var statusMatch = activeFilter === 'all' || card.dataset.status === activeFilter;
            if (nameMatch && statusMatch) {
                card.style.display = '';
                visible++;
            } else {
                card.style.display = 'none';
            }
        });
        noResults.style.display = visible === 0 ? 'block' : 'none';
    }

    searchInput.addEventListener('input', function () {
        query = this.value.toLowerCase();
        filter();
    });

    filterBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            filterBtns.forEach(function (b) { b.classList.remove('active'); });
            this.classList.add('active');
            activeFilter = this.dataset.filter;
            filter();
        });
    });
})();
</script>

<?php include("includes/footer.php"); ?>
