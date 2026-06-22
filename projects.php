<?php

include("config/database.php");
include("includes/header.php");
include("includes/navbar.php");

$query = mysqli_query(
$conn,
"SELECT * FROM Project"
);

?>

<div class="container mt-5">

<h2>Projects</h2>

<div class="row">

<?php while($project = mysqli_fetch_assoc($query)){ ?>

<div class="col-md-4">

<div class="card mb-4">

<div class="card-body">

<h5>
Project #<?= $project['ProjectID'] ?>
</h5>

<p>
<?= $project['Description'] ?>
</p>

<p>
Status:
<strong>
<?= $project['ProjectStatus'] ?>
</strong>
</p>

<p>
Payment:
<?= $project['PaymentStatus'] ?>
</p>

</div>

</div>

</div>

<?php } ?>

</div>

</div>

<?php include("includes/footer.php"); ?>