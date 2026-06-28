<?php
include("config/database.php");
include("includes/header.php");
include("includes/navbar.php");

$isClient = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Client';

$result = mysqli_query(
    $conn,
    "SELECT * FROM EquipmentOffering ORDER BY EquipmentOfferingID ASC"
);

$equipment = [];
while ($row = mysqli_fetch_assoc($result)) {
    $equipment[] = $row;
}

// Derive categories from Name (group by first word as a rough category)
// Since the DB has no Type column, we hide the category section if all are the same
$categories = [];
foreach ($equipment as $eq) {
    $name = trim($eq['Name'] ?? '');
    if ($name !== '' && !in_array($name, $categories)) {
        $categories[] = $name;
    }
}
$showCategories = count($categories) > 1;
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/equipment.css">

<!-- ── Hero ─────────────────────────────────────────────────── -->
<section class="eq-hero">
    <div class="eq-hero-bg" style="background-image:url('<?= BASE_URL ?>/assets/equipment/equipmentfleet.jpg');"></div>
    <div class="eq-hero-overlay"></div>
    <div class="container eq-hero-content">
        <div class="hero-eyebrow">Premium Fleet Rental</div>
        <h1 class="hero-title">Heavy Equipment<br>Solutions</h1>
        <p class="hero-sub">High-performance equipment designed to support complex infrastructure, civil engineering, and large-scale construction projects with efficiency and reliability.</p>
        <div class="hero-actions">
            <a href="#eq-listings" class="ysc-btn-primary">Explore Catalog</a>
            <?php if (!$isClient): ?>
                <a href="<?= BASE_URL ?>/apply.php" class="ysc-btn-outline">Apply Now</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ── Main content ──────────────────────────────────────────── -->
<div class="container eq-body" id="eq-listings">
    <div class="eq-layout">

        <!-- ── Sidebar ──────────────────────────────────────── -->
        <aside class="eq-sidebar">

            <div class="eq-sidebar-block">
                <div class="eq-sidebar-heading">Categories</div>
                <ul class="eq-cat-list" id="eqCatList">
                    <li>
                        <button class="eq-cat-btn active" data-cat="All">All Equipment</button>
                    </li>
                    <?php foreach ($categories as $cat): ?>
                        <li>
                            <button class="eq-cat-btn" data-cat="<?= htmlspecialchars($cat) ?>">
                                <?= htmlspecialchars($cat) ?>
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="eq-sidebar-block">
                <div class="eq-sidebar-heading">Search Fleet</div>
                <div class="eq-search-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                    <input type="search" id="equipmentSearch" placeholder="e.g. Dump Truck..." aria-label="Search equipment">
                </div>
            </div>

            <?php if (!$isClient): ?>
            <div class="eq-sidebar-block eq-sidebar-inquiry">
                <div class="eq-sidebar-heading">Inquiries</div>
                <p>Have questions about availability or rates? Reach out to our fleet team before submitting an application.</p>
                <a href="<?= BASE_URL ?>/contact.php" class="ysc-btn-orange eq-full-btn">Contact Us</a>
            </div>
            <?php endif; ?>

        </aside>

        <!-- ── Listings ─────────────────────────────────────── -->
        <div class="eq-listings-col">

            <div class="eq-listings-header">
                <span class="eq-listings-count" id="eqCount">
                    Showing <strong><?= count($equipment) ?></strong> units
                </span>
                <div class="eq-view-toggle">
                    <button class="eq-view-btn active" id="viewGrid" title="Grid view" aria-label="Grid view">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    </button>
                    <button class="eq-view-btn" id="viewList" title="List view" aria-label="List view">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                    </button>
                </div>
            </div>

            <div class="eq-grid" id="eqGrid">
                <?php foreach ($equipment as $row):
                    $isAvailable = $row['AvailabilityStatus'] === 'Available';
                    $rentalUrl   = BASE_URL . '/apply.php?type=Equipment+Rental&equipment_id=' . (int) $row['EquipmentOfferingID'];
                    $loginUrl    = BASE_URL . '/login.php?redirect=' . urlencode('apply.php?type=Equipment+Rental&equipment_id=' . (int) $row['EquipmentOfferingID']);
                ?>
                <div class="eq-card equipment-item"
                     data-name="<?= htmlspecialchars(strtolower($row['Name'] . ' ' . $row['Model'])) ?>"
                     data-cat="<?= htmlspecialchars($row['Name']) ?>">

                    <div class="eq-card-img-wrap">
                        <img src="<?= !empty($row['ImageURL']) ? BASE_URL . '/' . ltrim(htmlspecialchars($row['ImageURL']), '/') : 'https://placehold.co/800x450/111111/ffffff?text=Equipment' ?>"
                             alt="<?= htmlspecialchars($row['Name']) ?>"
                             class="eq-card-img">
                        <span class="eq-status-badge <?= $isAvailable ? 'eq-badge-available' : 'eq-badge-unavailable' ?>">
                            <?= htmlspecialchars($row['AvailabilityStatus']) ?>
                        </span>
                    </div>

                    <div class="eq-card-body">
                        <div class="eq-card-meta">
                            <h3 class="eq-card-name"><?= htmlspecialchars($row['Name']) ?></h3>
                            <span class="eq-card-model"><?= htmlspecialchars($row['Model']) ?></span>
                        </div>

                        <?php if (!empty($row['Specs'])): ?>
                        <div class="eq-specs">
                            <?php
                            $specs = array_slice(
                                array_filter(array_map('trim', explode('·', $row['Specs']))),
                                0, 3
                            );
                            foreach ($specs as $spec):
                                $parts = explode(':', $spec, 2);
                            ?>
                                <div class="eq-spec-row">
                                    <span class="eq-spec-label"><?= htmlspecialchars(trim($parts[0])) ?></span>
                                    <span class="eq-spec-val"><?= htmlspecialchars(trim($parts[1] ?? '')) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <div class="eq-rates">
                            <div class="eq-rate-item">
                                <span class="eq-rate-label">Daily</span>
                                <span class="eq-rate-val">₱<?= number_format($row['DailyRate'], 0) ?></span>
                            </div>
                            <div class="eq-rate-divider"></div>
                            <div class="eq-rate-item">
                                <span class="eq-rate-label">Weekly</span>
                                <span class="eq-rate-val">₱<?= number_format($row['WeeklyRate'], 0) ?></span>
                            </div>
                        </div>

                        <?php if (!$isAvailable): ?>
                            <span class="eq-btn-disabled">Currently Unavailable</span>
                        <?php elseif ($isClient): ?>
                            <a href="<?= $rentalUrl ?>" class="eq-btn-apply">
                                Inquire Now
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                            </a>
                        <?php else: ?>
                            <a href="<?= $loginUrl ?>" class="eq-btn-apply">
                                Inquire Now
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div><!-- /eq-grid -->

            <p class="eq-no-results" id="eqNoResults" style="display:none;">
                No equipment matched your search.
            </p>

        </div><!-- /eq-listings-col -->
    </div><!-- /eq-layout -->
</div>

<script>
(function () {
    var searchInput = document.getElementById('equipmentSearch');
    var catBtns     = document.querySelectorAll('.eq-cat-btn');
    var cards       = document.querySelectorAll('.eq-card');
    var countEl     = document.getElementById('eqCount');
    var noResults   = document.getElementById('eqNoResults');
    var gridEl      = document.getElementById('eqGrid');
    var btnGrid     = document.getElementById('viewGrid');
    var btnList     = document.getElementById('viewList');

    var activecat = 'All';
    var query     = '';

    function filter() {
        var visible = 0;
        cards.forEach(function (card) {
            var nameMatch = card.dataset.name.includes(query);
            var catMatch  = activecat === 'All' || card.dataset.cat === activecat;
            if (nameMatch && catMatch) {
                card.style.display = '';
                visible++;
            } else {
                card.style.display = 'none';
            }
        });
        countEl.innerHTML = 'Showing <strong>' + visible + '</strong> unit' + (visible !== 1 ? 's' : '');
        noResults.style.display = visible === 0 ? 'block' : 'none';
    }

    searchInput.addEventListener('input', function () {
        query = this.value.toLowerCase();
        filter();
    });

    catBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            catBtns.forEach(function (b) { b.classList.remove('active'); });
            this.classList.add('active');
            activecat = this.dataset.cat;
            filter();
        });
    });

    btnGrid.addEventListener('click', function () {
        gridEl.classList.remove('eq-grid-list');
        btnGrid.classList.add('active');
        btnList.classList.remove('active');
    });

    btnList.addEventListener('click', function () {
        gridEl.classList.add('eq-grid-list');
        btnList.classList.add('active');
        btnGrid.classList.remove('active');
    });
})();
</script>

<?php include("includes/footer.php"); ?>
