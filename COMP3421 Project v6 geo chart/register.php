<?php
require_once 'config/database.php';
require_once 'classes/User.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate username
    if (empty($_POST['username'])) {
        $errors[] = "Username is required";
    } elseif (strlen($_POST['username']) < 3) {
        $errors[] = "Username must be at least 3 characters";
    } else {
        $user->username = htmlspecialchars(strip_tags($_POST['username']));
    }
    
    // Validate email
    if (empty($_POST['email'])) {
        $errors[] = "Email is required";
    } elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    } else {
        $user->email = htmlspecialchars(strip_tags($_POST['email']));
    }
    
    // Validate password
    if (empty($_POST['password'])) {
        $errors[] = "Password is required";
    } elseif (strlen($_POST['password']) < 6) {
        $errors[] = "Password must be at least 6 characters";
    } elseif ($_POST['password'] !== $_POST['confirm_password']) {
        $errors[] = "Passwords do not match";
    } else {
        $user->password = $_POST['password'];
    }
    
    // If no errors, try to create user
    if (empty($errors)) {
        if ($user->create()) {
            header("Location: login.php?registered=1");
            exit;
        } else {
            $errors[] = "Registration failed. Email or username may already be in use.";
        }
    }
}

require_once 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h4>Register</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Register</button>
                </form>
            </div>
            <div class="card-footer text-muted">
                Already have an account? <a href="login.php">Login</a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 