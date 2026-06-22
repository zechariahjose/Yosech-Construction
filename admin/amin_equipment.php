<?php
include("../config/database.php");
include("../includes/header.php");
include("../includes/navbar.php");

if (!isset($_SESSION['user_type']) || !in_array($_SESSION['user_type'], ['Admin', 'Manager'])) {
    header('Location: ../login.php');
    exit;
}

if (isset($_POST['equipment_id'], $_POST['availability_status'])) {
    $equipmentId = (int) $_POST['equipment_id'];
    $status = mysqli_real_escape_string($conn, $_POST['availability_status']);
    mysqli_query($conn, "UPDATE Equipment SET AvailabilityStatus = '{$status}' WHERE EquipmentID = {$equipmentId}");
}

$result = mysqli_query($conn, "SELECT * FROM Equipment ORDER BY EquipmentID ASC");
?>

<div class="container mt-5">
    <h2>Equipment Management</h2>

    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Specification</th>
                <th>Availability</th>
                <th>Operator Needed</th>
                <th>Payment</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?= $row['EquipmentID'] ?></td>
                    <td><?= htmlspecialchars($row['Specification']) ?></td>
                    <td><?= htmlspecialchars($row['AvailabilityStatus']) ?></td>
                    <td><?= $row['NeedsOperator'] ? 'Yes' : 'No' ?></td>
                    <td><?= htmlspecialchars($row['PaymentStatus']) ?></td>
                    <td>
                        <form method="post" class="d-flex gap-2">
                            <input type="hidden" name="equipment_id" value="<?= $row['EquipmentID'] ?>">
                            <select class="form-select form-select-sm" name="availability_status">
                                <option value="Available" <?= $row['AvailabilityStatus'] === 'Available' ? 'selected' : '' ?>>Available</option>
                                <option value="Rented" <?= $row['AvailabilityStatus'] === 'Rented' ? 'selected' : '' ?>>Rented</option>
                                <option value="Under Maintenance" <?= $row['AvailabilityStatus'] === 'Under Maintenance' ? 'selected' : '' ?>>Under Maintenance</option>
                            </select>
                            <button type="submit" class="btn btn-sm btn-primary">Update</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php include("../includes/footer.php");
