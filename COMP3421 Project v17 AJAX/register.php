<?php
require_once 'config/database.php';
require_once 'classes/User.php';
require_once 'classes/InputValidator.php';
require_once 'classes/PasswordPolicy.php';

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
$password_suggestion = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate username
        $usernameValidation = InputValidator::validateUsername($_POST['username'] ?? '');
        if (!$usernameValidation['valid']) {
            $errors = array_merge($errors, $usernameValidation['errors']);
        } else {
            $user->username = $usernameValidation['sanitized'];
        }
        
        // Validate email
        $emailValidation = InputValidator::validateEmail($_POST['email'] ?? '');
        if (!$emailValidation['valid']) {
            $errors = array_merge($errors, $emailValidation['errors']);
        } else {
            $user->email = $emailValidation['sanitized'];
        }
        
        // Validate password
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Check if passwords match first
        if ($password !== $confirm_password) {
            $errors[] = "Passwords do not match";
        } else {
            // Validate password strength
            $passwordValidation = PasswordPolicy::validate($password);
            if (!$passwordValidation['valid']) {
                $errors = array_merge($errors, $passwordValidation['errors']);
                // Suggest a strong password
                $password_suggestion = PasswordPolicy::generateSecurePassword();
            }
            
            // Check if password is commonly used/breached
            if (PasswordPolicy::isBreachedPassword($password)) {
                $errors[] = "This password appears in data breaches and is not secure. Please choose a different password.";
                // Suggest a strong password if not already suggested
                if ($password_suggestion === null) {
                    $password_suggestion = PasswordPolicy::generateSecurePassword();
                }
            }
            
            if (empty($errors)) {
                $user->password = $password;
            }
        }
        
        // Check for attack patterns in all inputs
        foreach ($_POST as $key => $value) {
            $attackCheck = InputValidator::validateAgainstAttacks($value);
            if (!$attackCheck['valid']) {
                // Don't tell the user exactly what we found, just block the request
                $errors[] = "Invalid input detected";
                break;
            }
        }
        
        // If no errors, try to create user
        if (empty($errors)) {
            $result = $user->create();
            if ($result['success']) {
                header("Location: login.php?registered=1");
                exit;
            } else {
                // Handle errors from user creation
                if (isset($result['errors']) && is_array($result['errors'])) {
                    $errors = array_merge($errors, $result['errors']);
                } else {
                    $errors[] = "Registration failed. Please try again later.";
                }
                
                // If password was breached, suggest a secure password
                if (isset($result['is_breached']) && $result['is_breached']) {
                    $password_suggestion = PasswordPolicy::generateSecurePassword();
                }
            }
        }
    } catch (Exception $e) {
        // Log the error for debugging
        error_log("Registration error: " . $e->getMessage());
        $errors[] = "An unexpected error occurred. Please try again later.";
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
                
                <?php if ($password_suggestion): ?>
                    <div class="alert alert-info">
                        <p>Try a stronger password like: <code><?php echo htmlspecialchars($password_suggestion); ?></code></p>
                        <small class="text-muted">You can use this suggestion or create your own secure password.</small>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : (isset($user->username) ? htmlspecialchars($user->username) : ''); ?>" required>
                        <small class="form-text text-muted">Username must be at least 3 characters and contain only letters, numbers, underscores and hyphens.</small>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : (isset($user->email) ? htmlspecialchars($user->email) : ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <small class="form-text text-muted">
                            Password must be at least 8 characters long and include uppercase, lowercase, numbers and special characters.
                        </small>
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