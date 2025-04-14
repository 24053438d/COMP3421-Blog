<?php
class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $username;
    public $email;
    public $password;
    public $role;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                SET username=:username, 
                    email=:email, 
                    password=:password";
        
        $stmt = $this->conn->prepare($query);
        
        $this->password = password_hash($this->password, PASSWORD_DEFAULT);
        
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", $this->password);
        
        return $stmt->execute();
    }

    public function login($email, $password) {
        $query = "SELECT id, username, password, role 
                FROM " . $this->table_name . "
                WHERE email = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $email);
        $stmt->execute();
        
        if($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if(password_verify($password, $row['password'])) {
                session_start();
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['role'] = $row['role'];
                return true;
            }
        }
        return false;
    }

    public function isAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }

    public function requestPasswordReset($email) {
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
                return [
                    'success' => true,
                    'token' => $token,
                    'email' => $email
                ];
            }
        }
        
        return ['success' => false];
    }

    public function resetPassword($token, $new_password) {
        $query = "SELECT id FROM " . $this->table_name . "
                WHERE reset_token = ? AND reset_token_expiry > NOW()";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $token);
        $stmt->execute();
        
        if($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            $query = "UPDATE " . $this->table_name . "
                    SET password = ?, reset_token = NULL, reset_token_expiry = NULL
                    WHERE id = ?";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $hashed_password);
            $stmt->bindParam(2, $row['id']);
            
            return $stmt->execute();
        }
        
        return false;
    }

    public function logout() {
        // Start the session if it's not already started
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
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
        
        return true;
    }
}
?> 