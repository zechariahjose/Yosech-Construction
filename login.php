<?php
include("config/database.php");
include("includes/header.php");
include("includes/navbar.php");

$message = '';

if (isset($_SESSION['user_type'])) {
    if ($_SESSION['user_type'] === 'Client') {
        header('Location: index.php');
        exit;
    }
    if (in_array($_SESSION['user_type'], ['Admin', 'Manager'])) {
        header('Location: admin/amin_dashboard.php');
        exit;
    }
}

if (isset($_POST['submit'])) {
    $username = trim(mysqli_real_escape_string($conn, $_POST['username']));
    $password = $_POST['password'];

    if ($username === '' || $password === '') {
        $message = 'Please enter both username and password.';
    } else {
        $result = mysqli_query($conn, "SELECT * FROM Employee WHERE Username = '{$username}' LIMIT 1");

        if (mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);
            $hash = $user['Password'];

            if (password_verify($password, $hash) || $hash === $password) {
                $_SESSION['user_id'] = $user['EmployeeID'];
                $_SESSION['user_type'] = $user['UserType'];
                $_SESSION['username'] = $user['Username'];

                header('Location: admin/amin_dashboard.php');
                exit;
            }
        } else {
            $result = mysqli_query($conn, "SELECT * FROM Client WHERE Username = '{$username}' LIMIT 1");
            if (mysqli_num_rows($result) > 0) {
                $user = mysqli_fetch_assoc($result);
                $hash = $user['Password'];

                if (password_verify($password, $hash) || $hash === $password) {
                    $_SESSION['user_id'] = $user['UserID'];
                    $_SESSION['user_type'] = 'Client';
                    $_SESSION['username'] = $user['Username'];

                    header('Location: index.php');
                    exit;
                }
            }
        }

        $message = 'Invalid username or password.';
    }
}
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="card-title mb-4">Login</h2>

                    <?php if ($message): ?>
                        <div class="alert alert-warning"><?= htmlspecialchars($message) ?></div>
                    <?php endif; ?>

                    <form method="post" action="login.php">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button type="submit" name="submit" class="btn btn-primary">Login</button>
                        <a href="apply.php" class="btn btn-link">Apply as Client</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include("includes/footer.php"); ?>