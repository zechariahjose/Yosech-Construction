<?php

include("config/database.php");
include("includes/header.php");
include("includes/navbar.php");

$isClient = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Client';

$result = mysqli_query(
    $conn,
    "SELECT * FROM EquipmentOffering ORDER BY EquipmentOfferingID ASC"
);

?>

<div class="container">
    <div class="ysc-page-header">
        <h1 class="ysc-page-title">Equipment Fleet</h1>
        <p class="ysc-page-sub">Browse our rental fleet with detailed specifications and flexible hourly, daily, weekly, and monthly rates.</p>
    </div>

    <div class="ysc-search mb-4">
        <span class="ysc-search-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        </span>
        <input type="search" id="equipmentSearch" placeholder="Search equipment by name or model..." aria-label="Search equipment">
    </div>

    <div class="row g-4 pb-5">
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
        <div class="col-md-6 col-lg-4 equipment-item" data-name="<?= htmlspecialchars(strtolower($row['Name'] . ' ' . $row['Model'])) ?>">
            <div class="ysc-card h-100">
                <img src="<?= !empty($row['ImageURL']) ? BASE_URL . '/' . ltrim(htmlspecialchars($row['ImageURL']), '/') : 'https://via.placeholder.com/800x450?text=Equipment' ?>"
                     class="w-100" style="height:200px;object-fit:cover;" alt="<?= htmlspecialchars($row['Name']) ?>">
                <div class="ysc-card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h5 class="mb-0" style="font-size:0.95rem;font-weight:700;"><?= htmlspecialchars($row['Name']) ?></h5>
                        <span class="ysc-badge ysc-badge-available"><?= htmlspecialchars($row['AvailabilityStatus']) ?></span>
                    </div>
                    <p class="small text-muted mb-3"><?= htmlspecialchars($row['Model']) ?></p>

                    <?php if (!empty($row['Specs'])): ?>
                    <div class="mb-3">
                        <?php foreach (explode('·', $row['Specs']) as $spec): ?>
                            <?php $spec = trim($spec); if ($spec === '') continue; ?>
                            <?php $parts = explode(':', $spec, 2); ?>
                            <div class="ysc-spec-row">
                                <span class="ysc-spec-label"><?= htmlspecialchars(trim($parts[0])) ?></span>
                                <span class="ysc-spec-value"><?= htmlspecialchars(trim($parts[1] ?? '')) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <div class="row g-2 mb-3 small">
                        <div class="col-6"><span class="text-muted d-block">Daily</span><strong>₱<?= number_format($row['DailyRate'], 0) ?></strong></div>
                        <div class="col-6"><span class="text-muted d-block">Weekly</span><strong>₱<?= number_format($row['WeeklyRate'], 0) ?></strong></div>
                    </div>

                    <?php
                    $rentalUrl = BASE_URL . '/apply.php?type=Equipment+Rental&equipment=' . urlencode($row['Name']);
                    $loginUrl = BASE_URL . '/login.php?redirect=' . urlencode('apply.php?type=Equipment+Rental&equipment=' . urlencode($row['Name']));
                    ?>
                    <?php if ($isClient): ?>
                        <a href="<?= $rentalUrl ?>" class="ysc-btn-primary w-100 text-center">Apply for Rental</a>
                    <?php else: ?>
                        <a href="<?= $loginUrl ?>" class="ysc-btn-primary w-100 text-center">Apply for Rental</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<script>
document.getElementById('equipmentSearch').addEventListener('input', function () {
    var q = this.value.toLowerCase();
    document.querySelectorAll('.equipment-item').forEach(function (el) {
        el.style.display = el.dataset.name.includes(q) ? '' : 'none';
    });
});
</script>

<?php include("includes/footer.php"); ?>
