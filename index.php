<?php
include("includes/header.php");

if (isset($_SESSION['user_type'])) {
    if ($_SESSION['user_type'] === 'Client') {
        header("Location: " . BASE_URL . "/track_project.php");
    } else {
        header("Location: " . BASE_URL . "/admin/amin_dashboard.php");
    }
    exit;
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include("config/database.php");

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Check Client table
    $stmt = mysqli_prepare($conn, "SELECT UserID, Client_FirstName, Client_Password FROM Client WHERE Client_Username = ? OR Client_Email = ?");
    mysqli_stmt_bind_param($stmt, "ss", $username, $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        if (password_verify($password, $row['Client_Password'])) {
            $_SESSION['user_id']   = $row['UserID'];
            $_SESSION['user_name'] = $row['Client_FirstName'];
            $_SESSION['user_type'] = 'Client';
            header("Location: " . BASE_URL . "/track_project.php");
            exit;
        }
    }

    // Check Employee table (Admin, Manager, Employee)
    $stmt2 = mysqli_prepare($conn, "SELECT EmployeeID, Username, Password, UserType FROM Employee WHERE Username = ? OR Email = ?");
    mysqli_stmt_bind_param($stmt2, "ss", $username, $username);
    mysqli_stmt_execute($stmt2);
    $result2 = mysqli_stmt_get_result($stmt2);

    if ($row2 = mysqli_fetch_assoc($result2)) {
        if (password_verify($password, $row2['Password'])) {
            $_SESSION['user_id']   = $row2['EmployeeID'];
            $_SESSION['user_name'] = $row2['Username'];
            $_SESSION['user_type'] = $row2['UserType'];
            header("Location: " . BASE_URL . "/admin/amin_dashboard.php");
            exit;
        }
    }

    $error = "Invalid username or password.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yosech Construction</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="newlogin.css">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg" id="main-navbar">
    <div class="container-fluid px-4">
        <a class="navbar-brand fw-bold text-primary" href="index.php">Yosech Construction</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item"><a class="nav-link" href="projects.php">PROJECTS</a></li>
                <li class="nav-item"><a class="nav-link" href="equipment.php">EQUIPMENT</a></li>
                <li class="nav-item"><a class="nav-link" href="#">SERVICES</a></li>
                <li class="nav-item"><a class="nav-link" href="#">ABOUT</a></li>
                <li class="nav-item"><a class="nav-link" href="#">CONTACT</a></li>
            </ul>
            <div class="d-flex gap-2">
                <a href="index.php" class="btn btn-outline-secondary btn-sm px-3">CLIENT LOGIN</a>
                <a href="apply.php" class="btn btn-primary btn-sm px-3">PROJECT INQUIRY</a>
            </div>
        </div>
    </div>
</nav>

<!-- LOGIN SECTION -->
<div class="login-wrapper">
    <div class="login-card">

        <!-- Icon -->
        <div class="login-icon-wrap">
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="none" viewBox="0 0 24 24" stroke="#bbb" stroke-width="1.2">
                <rect x="3" y="3" width="18" height="18" rx="2"/>
                <path d="M3 3l18 18M3 21L21 3"/>
            </svg>
        </div>

        <h5 class="login-title">Client Portal</h5>
        <p class="login-subtitle">Manage your construction milestones and documents.</p>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="index.php">
            <div class="mb-3">
                <label class="form-label login-label">Username or Email</label>
                <div class="input-icon-wrap">
                    <input type="text" name="username" class="form-control login-input" placeholder="name@company.com" required>
                    <span class="input-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#aaa" stroke-width="1.8">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                    </span>
                </div>
            </div>

            <div class="mb-2">
                <div class="d-flex justify-content-between">
                    <label class="form-label login-label">Password</label>
                    <a href="#" class="login-forgot">Forgot Password?</a>
                </div>
                <div class="input-icon-wrap">
                    <input type="password" name="password" class="form-control login-input" value="••••••••" required>
                    <span class="input-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#aaa" stroke-width="1.8">
                            <rect x="3" y="11" width="18" height="11" rx="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                    </span>
                </div>
            </div>

            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="remember">
                    <label class="form-check-label login-remember" for="remember">Remember this device for 30 days</label>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 login-btn">SIGN IN →</button>
        </form>

        <div class="login-divider">New Partnership</div>

        <a href="apply.php" class="btn btn-outline-secondary w-100 login-request-btn">REQUEST ACCESS</a>

    </div>
</div>

<!-- FOOTER -->
<footer class="site-footer">
    <div class="container-fluid px-5">
        <div class="footer-top row">
            <div class="col-md-3">
                <div class="footer-brand">Yosech</div>
                <p class="footer-desc">Precision engineering and structural integrity for the future of Zamboanga del Norte.</p>
            </div>
            <div class="col-md-3">
                <div class="footer-heading">QUICK LINKS</div>
                <ul class="footer-links">
                    <li><a href="#">Safety Standards</a></li>
                    <li><a href="#">Sitemap</a></li>
                    <li><a href="#">Careers</a></li>
                </ul>
            </div>
            <div class="col-md-3">
                <div class="footer-heading">LEGAL</div>
                <ul class="footer-links">
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Terms of Service</a></li>
                </ul>
            </div>
            <div class="col-md-3">
                <div class="footer-heading">CONTACT</div>
                <p class="footer-contact">Dipolog City, 7100</p>
                <p class="footer-contact">+63 (065) 212 4588</p>
            </div>
        </div>
        <div class="footer-bottom">
            <span>© 2024 Yosech Construction & Civil Engineering. Dipolog City, Zamboanga del Norte.</span>
            <div class="footer-icons">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 0 20M12 2a15.3 15.3 0 0 0 0 20"/></svg>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
