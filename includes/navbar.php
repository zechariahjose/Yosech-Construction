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

<ul class="navbar-nav ms-auto align-items-center">

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

<li class="nav-item">
<a class="nav-link px-2" href="<?= BASE_URL ?>/settings.php" title="Account Settings">
<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
<path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
<path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
</svg>
</a>
</li>

</ul>

</div>

</div>
</nav>
