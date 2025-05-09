<?php
require_once 'config/database.php';
require_once 'classes/User.php';
require_once 'classes/SecurityLogger.php';
require_once 'classes/InputValidator.php';

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
$error = null;
$success = null;

if (isset($_GET['registered']) && $_GET['registered'] == 1) {
    $success = "Registration successful! Please log in.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate email
    $emailValidator = InputValidator::validateEmail($_POST['email'] ?? '');
    
    // Validate password (just check if it's not empty)
    $password = $_POST['password'] ?? '';
    $passwordValidator = InputValidator::validateText($password, 1);  // Just checking it's not empty here
    
    if (!$emailValidator['valid'] || !$passwordValidator['valid']) {
        // Validation failed
        $error = "Please enter a valid email and password";
    } else {
        // Log login attempt with clean data
        $loginResult = $user->login($emailValidator['sanitized'], $password);
        
        if ($loginResult['success']) {
            header("Location: index.php");
            exit;
        } else {
            $error = $loginResult['message'] ?? "Invalid email or password";
        }
    }
}

require_once 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h4>Login</h4>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <a href="forgot_password.php">Forgot Password?</a>
                    </div>
                    <button type="submit" class="btn btn-primary">Login</button>
                </form>
            </div>
            <div class="card-footer text-muted">
                Don't have an account? <a href="register.php">Register</a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 