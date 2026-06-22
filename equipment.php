<?php

include("config/database.php");
include("includes/header.php");
include("includes/navbar.php");

$result = mysqli_query(
    $conn,
    "SELECT * FROM Equipment"
);

?>

<div class="container mt-5">

<h2>Equipment Catalog</h2>

<table class="table table-bordered">

<thead>

<tr>
<th>ID</th>
<th>Equipment</th>
<th>Status</th>
<th>Operator</th>
</tr>

</thead>

<tbody>

<?php while($row = mysqli_fetch_assoc($result)){ ?>

<tr>

<td><?= $row['EquipmentID'] ?></td>

<td><?= $row['Specification'] ?></td>

<td><?= $row['AvailabilityStatus'] ?></td>

<td>
<?= $row['NeedsOperator'] ? 'Yes' : 'No' ?>
</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

<?php include("includes/footer.php"); ?>