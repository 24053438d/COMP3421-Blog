<?php
use PDOException;

class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $username;
    public $email;
    public $password;
    public $role;
    
    // Maximum number of failed login attempts before account is temporarily locked
    const MAX_LOGIN_ATTEMPTS = 5;
    
    // Time in minutes that an account is locked after too many failed attempts
    const ACCOUNT_LOCKOUT_DURATION = 30;

    public function __construct($db) {
        $this->conn = $db;
        $this->ensureSecurityColumns();
    }
    
    /**
     * Ensure the users table has the necessary security columns
     */
    private function ensureSecurityColumns() {
        // Check if login_attempts column exists
        $query = "SHOW COLUMNS FROM " . $this->table_name . " LIKE 'login_attempts'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            $this->conn->exec("ALTER TABLE " . $this->table_name . " ADD login_attempts INT DEFAULT 0");
        }
        
        // Check if last_failed_login column exists
        $query = "SHOW COLUMNS FROM " . $this->table_name . " LIKE 'last_failed_login'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            $this->conn->exec("ALTER TABLE " . $this->table_name . " ADD last_failed_login DATETIME DEFAULT NULL");
        }
        
        // Check if account_locked_until column exists
        $query = "SHOW COLUMNS FROM " . $this->table_name . " LIKE 'account_locked_until'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            $this->conn->exec("ALTER TABLE " . $this->table_name . " ADD account_locked_until DATETIME DEFAULT NULL");
        }
        
        // Check if password_last_changed column exists
        $query = "SHOW COLUMNS FROM " . $this->table_name . " LIKE 'password_last_changed'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            $this->conn->exec("ALTER TABLE " . $this->table_name . " ADD password_last_changed DATETIME DEFAULT NULL");
        }
    }

    public function create() {
        // Check if username or email already exists
        $existsCheck = $this->usernameOrEmailExists($this->username, $this->email);
        if ($existsCheck['exists']) {
            return [
                'success' => false,
                'errors' => $existsCheck['errors']
            ];
        }
        
        // Validate the password with PasswordPolicy
        require_once 'PasswordPolicy.php';
        $validation = PasswordPolicy::validate($this->password);
        
        // Check for breached password
        $isBreached = PasswordPolicy::isBreachedPassword($this->password);
        
        if (!$validation['valid'] || $isBreached) {
            // If validation fails or password is breached, return the errors
            return [
                'success' => false,
                'errors' => $validation['errors'],
                'is_breached' => $isBreached
            ];
        }
        
        // Create security logger
        require_once 'SecurityLogger.php';
        $securityLogger = new SecurityLogger($this->conn);
        
        $query = "INSERT INTO " . $this->table_name . "
                SET username=:username, 
                    email=:email, 
                    password=:password,
                    password_last_changed=NOW()";
        
        $stmt = $this->conn->prepare($query);
        
        // Hash password using PASSWORD_DEFAULT (currently bcrypt)
        // In the future, consider using PASSWORD_ARGON2ID when widely available
        $this->password = password_hash($this->password, PASSWORD_DEFAULT, ['cost' => 12]);
        
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", $this->password);
        
        try {
            if ($stmt->execute()) {
                // Log the registration event
                $userId = $this->conn->lastInsertId();
                $securityLogger->logEvent(
                    SecurityLogger::EVENT_REGISTRATION,
                    $userId,
                    ['username' => $this->username, 'email' => $this->email]
                );
                return ['success' => true];
            }
            
            return ['success' => false, 'errors' => ['Database error occurred during registration']];
        } catch (PDOException $e) {
            // If we somehow get here despite our earlier check, handle the unique constraint violation
            if ($e->getCode() == 23000) { // Integrity constraint violation
                $errorMessage = $e->getMessage();
                if (strpos($errorMessage, 'username') !== false) {
                    return ['success' => false, 'errors' => ['Username already exists']];
                } elseif (strpos($errorMessage, 'email') !== false) {
                    return ['success' => false, 'errors' => ['Email already exists']];
                }
            }
            
            // Log the exception for debugging
            error_log("Registration error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['An unexpected error occurred. Please try again later.']];
        }
    }

    /**
     * Check if username or email already exists in the database
     * 
     * @param string $username Username to check
     * @param string $email Email to check
     * @return array ['exists' => bool, 'errors' => array]
     */
    public function usernameOrEmailExists($username, $email) {
        $errors = [];
        
        // Check username
        $query = "SELECT id FROM " . $this->table_name . " WHERE username = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $username);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $errors[] = "Username already exists";
        }
        
        // Check email
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $errors[] = "Email already exists";
        }
        
        return [
            'exists' => !empty($errors),
            'errors' => $errors
        ];
    }

    public function login($email, $password) {
        // Create security logger
        require_once 'SecurityLogger.php';
        $securityLogger = new SecurityLogger($this->conn);
        
        // Check if IP is suspicious (too many failed attempts)
        if ($securityLogger->isSuspiciousIP(20, 60)) {
            // Log suspicious activity
            $securityLogger->logEvent(
                SecurityLogger::EVENT_SUSPICIOUS_ACTIVITY,
                null,
                ['email' => $email, 'reason' => 'Too many failed login attempts from IP']
            );
            
            return ['success' => false, 'message' => 'Too many failed login attempts from this IP. Please try again later.'];
        }
        
        $query = "SELECT id, username, password, role, login_attempts, account_locked_until 
                FROM " . $this->table_name . "
                WHERE email = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $email);
        $stmt->execute();
        
        if($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Check if account is locked
            if ($row['account_locked_until'] !== null && strtotime($row['account_locked_until']) > time()) {
                // Account is locked
                $securityLogger->logEvent(
                    SecurityLogger::EVENT_LOGIN_FAILED,
                    $row['id'],
                    ['email' => $email, 'reason' => 'Account locked']
                );
                
                $lockUntil = date('Y-m-d H:i:s', strtotime($row['account_locked_until']));
                return ['success' => false, 'message' => "Account is temporarily locked. Try again after $lockUntil"];
            }
            
            if(password_verify($password, $row['password'])) {
                // Successful login
                session_start();
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['role'] = $row['role'];
                
                // Reset login attempts counter
                $query = "UPDATE " . $this->table_name . "
                        SET login_attempts = 0, account_locked_until = NULL
                        WHERE id = ?";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(1, $row['id']);
                $stmt->execute();
                
                // Check if password needs rehashing (in case PHP default has changed)
                if (password_needs_rehash($row['password'], PASSWORD_DEFAULT, ['cost' => 12])) {
                    $newHash = password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);
                    $query = "UPDATE " . $this->table_name . "
                            SET password = ?
                            WHERE id = ?";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(1, $newHash);
                    $stmt->bindParam(2, $row['id']);
                    $stmt->execute();
                }
                
                // Log successful login
                $securityLogger->logEvent(
                    SecurityLogger::EVENT_LOGIN_SUCCESS,
                    $row['id'],
                    ['email' => $email]
                );
                
                return ['success' => true];
            } else {
                // Failed login
                // Increment login attempts
                $newAttempts = $row['login_attempts'] + 1;
                $query = "UPDATE " . $this->table_name . "
                        SET login_attempts = ?, last_failed_login = NOW()";
                
                // If max attempts reached, lock the account
                if ($newAttempts >= self::MAX_LOGIN_ATTEMPTS) {
                    $lockTime = date('Y-m-d H:i:s', strtotime('+' . self::ACCOUNT_LOCKOUT_DURATION . ' minutes'));
                    $query .= ", account_locked_until = ?";
                    
                    // Log account locked event
                    $securityLogger->logEvent(
                        SecurityLogger::EVENT_ACCOUNT_LOCKED,
                        $row['id'],
                        ['email' => $email, 'duration_minutes' => self::ACCOUNT_LOCKOUT_DURATION]
                    );
                }
                
                $query .= " WHERE id = ?";
                $stmt = $this->conn->prepare($query);
                
                if ($newAttempts >= self::MAX_LOGIN_ATTEMPTS) {
                    $stmt->bindParam(1, $newAttempts);
                    $stmt->bindParam(2, $lockTime);
                    $stmt->bindParam(3, $row['id']);
                } else {
                    $stmt->bindParam(1, $newAttempts);
                    $stmt->bindParam(2, $row['id']);
                }
                
                $stmt->execute();
                
                // Log failed login
                $securityLogger->logEvent(
                    SecurityLogger::EVENT_LOGIN_FAILED,
                    $row['id'],
                    ['email' => $email, 'attempts' => $newAttempts]
                );
                
                $remainingAttempts = self::MAX_LOGIN_ATTEMPTS - $newAttempts;
                if ($remainingAttempts > 0) {
                    return [
                        'success' => false, 
                        'message' => "Invalid email or password. $remainingAttempts attempts remaining before account is locked."
                    ];
                } else {
                    return [
                        'success' => false, 
                        'message' => "Too many failed login attempts. Account locked for " . self::ACCOUNT_LOCKOUT_DURATION . " minutes."
                    ];
                }
            }
        } else {
            // User not found - log the attempt but don't give specific error
            $securityLogger->logEvent(
                SecurityLogger::EVENT_LOGIN_FAILED,
                null,
                ['email' => $email, 'reason' => 'User not found']
            );
            
            return ['success' => false, 'message' => "Invalid email or password."];
        }
    }

    public function isAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }

    public function requestPasswordReset($email) {
        // Create security logger
        require_once 'SecurityLogger.php';
        $securityLogger = new SecurityLogger($this->conn);
        
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $email);
        $stmt->execute();
        
        if($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            $query = "UPDATE " . $this->table_name . "
                    SET reset_token = ?, reset_token_expiry = ?
                    WHERE id = ?";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $token);
            $stmt->bindParam(2, $expires);
            $stmt->bindParam(3, $row['id']);
            
            if($stmt->execute()) {
                // Log password reset request
                $securityLogger->logEvent(
                    SecurityLogger::EVENT_PASSWORD_RESET_REQUEST,
                    $row['id'],
                    ['email' => $email]
                );
                
                return [
                    'success' => true,
                    'token' => $token,
                    'email' => $email
                ];
            }
        } else {
            // Even if user doesn't exist, don't reveal this information
            // But still log the attempt
            $securityLogger->logEvent(
                SecurityLogger::EVENT_PASSWORD_RESET_REQUEST,
                null,
                ['email' => $email, 'status' => 'user_not_found']
            );
        }
        
        return ['success' => false];
    }

    public function resetPassword($token, $new_password) {
        // Validate the new password
        require_once 'PasswordPolicy.php';
        $validation = PasswordPolicy::validate($new_password);
        
        // Check for breached password
        $isBreached = PasswordPolicy::isBreachedPassword($new_password);
        
        if (!$validation['valid'] || $isBreached) {
            // If validation fails or password is breached, return the errors
            return [
                'success' => false,
                'errors' => $validation['errors'],
                'is_breached' => $isBreached
            ];
        }
        
        // Create security logger
        require_once 'SecurityLogger.php';
        $securityLogger = new SecurityLogger($this->conn);
        
        try {
            // First check if token exists and is valid
            $query = "SELECT id, email FROM " . $this->table_name . "
                    WHERE reset_token = ? AND reset_token_expiry > NOW()";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $token);
            $stmt->execute();
            
            if($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Token is valid, update the password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT, ['cost' => 12]);
                
                $query = "UPDATE " . $this->table_name . "
                        SET password = ?, reset_token = NULL, reset_token_expiry = NULL,
                        password_last_changed = NOW(), login_attempts = 0, account_locked_until = NULL
                        WHERE id = ?";
                
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(1, $hashed_password);
                $stmt->bindParam(2, $row['id']);
                
                if ($stmt->execute() && $stmt->rowCount() > 0) {
                    // Password was updated successfully
                    // Log password reset success
                    $securityLogger->logEvent(
                        SecurityLogger::EVENT_PASSWORD_RESET_SUCCESS,
                        $row['id'],
                        ['email' => $row['email']]
                    );
                    
                    return ['success' => true];
                } else {
                    // Query executed but no rows were affected
                    error_log("Password reset failed: No rows affected. User ID: " . $row['id']);
                    $securityLogger->logEvent(
                        SecurityLogger::EVENT_PASSWORD_RESET_SUCCESS,
                        $row['id'],
                        ['status' => 'failed', 'reason' => 'database_update_failed']
                    );
                    
                    return ['success' => false, 'message' => 'Failed to update password. Please try again.'];
                }
            } else {
                // Token is invalid or expired
                $securityLogger->logEvent(
                    SecurityLogger::EVENT_PASSWORD_RESET_SUCCESS,
                    null,
                    ['status' => 'invalid_token', 'token' => substr($token, 0, 8) . '...']
                );
                
                return ['success' => false, 'message' => 'Invalid or expired reset token. Please request a new reset link.'];
            }
        } catch (PDOException $e) {
            // Log the database error
            error_log("Password reset error: " . $e->getMessage());
            $securityLogger->logEvent(
                SecurityLogger::EVENT_PASSWORD_RESET_SUCCESS,
                null,
                ['status' => 'error', 'error' => $e->getMessage()]
            );
            
            return ['success' => false, 'message' => 'Database error occurred. Please try again later.'];
        }
    }
    
    /**
     * Change a user's password (when logged in)
     * 
     * @param int $userId User ID
     * @param string $current_password Current password for verification
     * @param string $new_password New password
     * @return array Success status and any error messages
     */
    public function changePassword($userId, $current_password, $new_password) {
        // Validate the new password
        require_once 'PasswordPolicy.php';
        $validation = PasswordPolicy::validate($new_password);
        
        // Check for breached password
        $isBreached = PasswordPolicy::isBreachedPassword($new_password);
        
        if (!$validation['valid'] || $isBreached) {
            // If validation fails or password is breached, return the errors
            return [
                'success' => false,
                'errors' => $validation['errors'],
                'is_breached' => $isBreached
            ];
        }
        
        // Create security logger
        require_once 'SecurityLogger.php';
        $securityLogger = new SecurityLogger($this->conn);
        
        // Verify current password
        $query = "SELECT password, email FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $userId);
        $stmt->execute();
        
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (password_verify($current_password, $row['password'])) {
                // Current password is correct, update to new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT, ['cost' => 12]);
                
                $query = "UPDATE " . $this->table_name . "
                        SET password = ?, password_last_changed = NOW()
                        WHERE id = ?";
                
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(1, $hashed_password);
                $stmt->bindParam(2, $userId);
                
                if ($stmt->execute()) {
                    // Log password change
                    $securityLogger->logEvent(
                        SecurityLogger::EVENT_PASSWORD_CHANGE,
                        $userId,
                        ['email' => $row['email']]
                    );
                    
                    return ['success' => true];
                }
            } else {
                // Log failed password change attempt
                $securityLogger->logEvent(
                    SecurityLogger::EVENT_PASSWORD_CHANGE,
                    $userId,
                    ['email' => $row['email'], 'status' => 'incorrect_current_password']
                );
                
                return ['success' => false, 'message' => 'Current password is incorrect'];
            }
        }
        
        return ['success' => false, 'message' => 'User not found'];
    }

    public function logout() {
        // Create security logger
        require_once 'SecurityLogger.php';
        $securityLogger = new SecurityLogger($this->conn);
        
        // Start the session if it's not already started
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Get user ID before destroying session
        $userId = $_SESSION['user_id'] ?? null;
        
        // Unset all session variables
        $_SESSION = [];
        
        // Destroy the session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        
        // Destroy the session
        session_destroy();
        
        // Log logout event if we had a user ID
        if ($userId) {
            $securityLogger->logEvent(
                SecurityLogger::EVENT_LOGOUT,
                $userId,
                []
            );
        }
        
        return true;
    }
}
?> 