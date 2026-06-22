<?php
include("config/database.php");
include("includes/header.php");
include("includes/navbar.php");

$message = '';
$success = '';

$user = null;
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Client') {
    $userId = (int) $_SESSION['user_id'];
    $result = mysqli_query($conn, "SELECT * FROM Client WHERE UserID = {$userId} LIMIT 1");
    $user = mysqli_fetch_assoc($result);
}

if (isset($_POST['submit'])) {
    $firstName = trim(mysqli_real_escape_string($conn, $_POST['first_name']));
    $mi = trim(mysqli_real_escape_string($conn, $_POST['mi']));
    $lastName = trim(mysqli_real_escape_string($conn, $_POST['last_name']));
    $username = trim(mysqli_real_escape_string($conn, $_POST['username']));
    $email = trim(mysqli_real_escape_string($conn, $_POST['email']));
    $contact = trim(mysqli_real_escape_string($conn, $_POST['contact']));
    $password = trim($_POST['password']);
    $applicationType = trim(mysqli_real_escape_string($conn, $_POST['application_type']));
    $description = trim(mysqli_real_escape_string($conn, $_POST['description']));

    if ($firstName === '' || $lastName === '' || $username === '' || $email === '' || $applicationType === '' || $description === '') {
        $message = 'Please complete all required fields.';
    } else {
        $existing = mysqli_query($conn, "SELECT * FROM Client WHERE Username = '{$username}' OR Email = '{$email}' LIMIT 1");
        if (mysqli_num_rows($existing) > 0) {
            $client = mysqli_fetch_assoc($existing);
            if (($client['Username'] === $username || $client['Email'] === $email) && $password !== '') {
                $hash = $client['Password'];
                if (!password_verify($password, $hash) && $hash !== $password) {
                    $message = 'A client account already exists with this username or email. Please log in with the correct password.';
                }
            }
            $userId = $client['UserID'];
        } else {
            $passwordHash = password_hash($password !== '' ? $password : bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
            mysqli_query($conn, "INSERT INTO Client (FirstName, MI, LastName, Username, Password, Email, ContactNumber) VALUES ('{$firstName}', '{$mi}', '{$lastName}', '{$username}', '{$passwordHash}', '{$email}', '{$contact}')");
            $userId = mysqli_insert_id($conn);
        }

        if ($message === '') {
            mysqli_query($conn, "INSERT INTO Application (UserID, ApplicationType, Description, SubmissionDate, Status) VALUES ({$userId}, '{$applicationType}', '{$description}', CURDATE(), 'Pending')");
            $success = 'Your application has been submitted. We will contact you soon.';
            if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Client') {
                $_SESSION['user_id'] = $userId;
                $_SESSION['user_type'] = 'Client';
                $_SESSION['username'] = $username;
            }
        }
    }
}
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="card-title mb-4">Client Application</h2>

                    <?php if ($message): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($message) ?></div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>

                    <form method="post" action="apply.php">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">First Name</label>
                                <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($user['FirstName'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="form-label">MI</label>
                                <input type="text" name="mi" class="form-control" value="<?= htmlspecialchars($user['MI'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Last Name</label>
                                <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($user['LastName'] ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user['Username'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['Email'] ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Contact Number</label>
                            <input type="text" name="contact" class="form-control" value="<?= htmlspecialchars($user['ContactNumber'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" placeholder="Choose a password for your account">
                            <small class="text-muted">If your username already exists, enter the existing password; otherwise a new account will be created.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Application Type</label>
                            <select class="form-select" name="application_type" required>
                                <option value="">Choose type</option>
                                <option value="New Project">New Project</option>
                                <option value="Equipment Rental">Equipment Rental</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Project Description</label>
                            <textarea name="description" class="form-control" rows="5" required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                        </div>

                        <button type="submit" name="submit" class="btn btn-success">Submit Application</button>
                        <a href="index.php" class="btn btn-link">Back to Home</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include("includes/footer.php"); ?>