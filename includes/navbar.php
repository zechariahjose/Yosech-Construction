<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
<div class="container">

<a class="navbar-brand" href="<?= BASE_URL ?>/index.php">
Yosech Construction
</a>

<button class="navbar-toggler"
type="button"
data-bs-toggle="collapse"
data-bs-target="#nav">

<span class="navbar-toggler-icon"></span>

</button>

<div class="collapse navbar-collapse" id="nav">

<ul class="navbar-nav ms-auto">

<li class="nav-item">
<a class="nav-link" href="<?= BASE_URL ?>/index.php">Home</a>
</li>

<li class="nav-item">
<a class="nav-link" href="<?= BASE_URL ?>/equipment.php">Equipment</a>
</li>

<li class="nav-item">
<a class="nav-link" href="<?= BASE_URL ?>/projects.php">Projects</a>
</li>

<?php if(isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Client'): ?>
<li class="nav-item">
<a class="nav-link" href="<?= BASE_URL ?>/track_project.php">My Projects</a>
</li>
<li class="nav-item">
<a class="nav-link" href="<?= BASE_URL ?>/logout.php">Logout</a>
</li>
<?php elseif(isset($_SESSION['user_type']) && in_array($_SESSION['user_type'], ['Admin', 'Manager'])): ?>
<li class="nav-item">
<a class="nav-link" href="<?= BASE_URL ?>/admin/amin_dashboard.php">Admin Panel</a>
</li>
<li class="nav-item">
<a class="nav-link" href="<?= BASE_URL ?>/logout.php">Logout</a>
</li>
<?php else: ?>
<li class="nav-item">
<a class="nav-link" href="<?= BASE_URL ?>/login.php">Login</a>
</li>
<li class="nav-item">
<a class="nav-link" href="<?= BASE_URL ?>/apply.php">Apply</a>
</li>
<?php endif; ?>

</ul>

</div>

</div>
</nav>