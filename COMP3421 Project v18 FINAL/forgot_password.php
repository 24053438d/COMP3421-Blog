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
$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    
    if (empty($email)) {
        $error = "Please enter your email";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } else {
        $result = $user->requestPasswordReset($email);
        
        if ($result['success']) {
            // In a real application, you would send an email with the reset link
            // For this example, we'll just show the token
            $resetLink = "http://" . $_SERVER['HTTP_HOST'] . "/reset_password.php?token=" . $result['token'] . "&email=" . urlencode($result['email']);
            $success = "A password reset link has been sent to your email.<br>
                       <small class='text-muted'>(For demo purposes: <a href='$resetLink'>$resetLink</a>)</small>";
        } else {
            $error = "Email not found in our records";
        }
    }
}

require_once 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h4>Reset Password</h4>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php else: ?>
                    <p>Enter your email address and we'll send you a link to reset your password.</p>
                    <form method="POST">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Send Reset Link</button>
                    </form>
                <?php endif; ?>
            </div>
            <div class="card-footer text-muted">
                <a href="login.php">Back to Login</a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 