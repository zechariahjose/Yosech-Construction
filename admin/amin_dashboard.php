<?php
include("../config/database.php");
include("../includes/header.php");
include("../includes/navbar.php");

if (!isset($_SESSION['user_type']) || !in_array($_SESSION['user_type'], ['Admin', 'Manager'])) {
    header('Location: ../login.php');
    exit;
}

$pendingApps = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM Application WHERE Status = 'Pending'"))['total'];
$activeProjects = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM Project WHERE ProjectStatus = 'Ongoing'"))['total'];
$availableEquipment = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM Equipment WHERE AvailabilityStatus = 'Available'"))['total'];
$managers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM Employee WHERE UserType = 'Manager'"))['total'];
?>

<div class="container mt-5">
    <h2>Admin Dashboard</h2>
    <div class="row g-4 mt-3">
        <div class="col-md-3">
            <div class="card text-white bg-primary h-100">
                <div class="card-body">
                    <h5 class="card-title">Pending Applications</h5>
                    <p class="card-text display-6"><?= (int) $pendingApps ?></p>
                    <a href="amin_applications.php" class="text-white">Review applications</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success h-100">
                <div class="card-body">
                    <h5 class="card-title">Ongoing Projects</h5>
                    <p class="card-text display-6"><?= (int) $activeProjects ?></p>
                    <a href="amin_projects.php" class="text-white">View projects</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-info h-100">
                <div class="card-body">
                    <h5 class="card-title">Available Equipment</h5>
                    <p class="card-text display-6"><?= (int) $availableEquipment ?></p>
                    <a href="amin_equipment.php" class="text-white">Manage equipment</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-secondary h-100">
                <div class="card-body">
                    <h5 class="card-title">Project Managers</h5>
                    <p class="card-text display-6"><?= (int) $managers ?></p>
                    <span class="text-white">Manager accounts</span>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <p>Use the links above to navigate the admin system and manage applications, projects, and equipment.</p>
    </div>
</div>

<?php include("../includes/footer.php"); ?>