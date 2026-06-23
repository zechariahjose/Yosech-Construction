<?php

include("config/database.php");
include("includes/header.php");
include("includes/navbar.php");

$result = mysqli_query(
    $conn,
    "SELECT * FROM EquipmentOffering ORDER BY EquipmentOfferingID ASC"
);

?>

<div class="container mt-5">

<h2>Equipment for Rent</h2>
<p class="mb-4">Browse our rental fleet with detailed specifications and flexible hourly, daily, weekly, and monthly rates.</p>

<div class="row gy-4">

<?php while($row = mysqli_fetch_assoc($result)){ ?>

<div class="col-lg-6">
    <div class="card shadow-sm showcase-card h-100">
        <img src="<?= !empty($row['ImageURL']) ? htmlspecialchars($row['ImageURL']) : 'https://via.placeholder.com/800x450?text=Add+Equipment+Photo' ?>" class="card-img-top showcase-image" alt="<?= htmlspecialchars($row['Name']) ?>">
        <div class="card-body">
            <h5 class="card-title"><?= htmlspecialchars($row['Name']) ?> <small class="text-muted"><?= htmlspecialchars($row['Model']) ?></small></h5>
            <p class="card-text"><?= nl2br(htmlspecialchars($row['Description'])) ?></p>
            <p class="mb-2"><strong>Specifications:</strong></p>
            <p class="small text-muted mb-3"><?= nl2br(htmlspecialchars($row['Specs'])) ?></p>

            <div class="row gx-2 gy-2 mb-3">
                <div class="col-6 col-sm-3"><span class="d-block text-secondary">Hourly</span><strong>₱<?= number_format($row['HourlyRate'], 2) ?></strong></div>
                <div class="col-6 col-sm-3"><span class="d-block text-secondary">Daily</span><strong>₱<?= number_format($row['DailyRate'], 2) ?></strong></div>
                <div class="col-6 col-sm-3"><span class="d-block text-secondary">Weekly</span><strong>₱<?= number_format($row['WeeklyRate'], 2) ?></strong></div>
                <div class="col-6 col-sm-3"><span class="d-block text-secondary">Monthly</span><strong>₱<?= number_format($row['MonthlyRate'], 2) ?></strong></div>
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <span class="badge bg-primary"><?= htmlspecialchars($row['AvailabilityStatus']) ?></span>
                <small class="text-muted">Image field available for later upload</small>
            </div>
        </div>
    </div>
</div>

<?php } ?>

</div>

</div>

<?php include("includes/footer.php"); ?>