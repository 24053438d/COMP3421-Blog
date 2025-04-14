<?php
class Comment {
    private $conn;
    private $table_name = "comments";

    public $id;
    public $content;
    public $post_id;
    public $user_id;
    public $status;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                SET content=:content, 
                    post_id=:post_id, 
                    user_id=:user_id,
                    status=:status";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":content", $this->content);
        $stmt->bindParam(":post_id", $this->post_id);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":status", $this->status);
        
        return $stmt->execute();
    }

    public function getCommentsByPost($post_id) {
        $query = "SELECT c.*, u.username 
                FROM " . $this->table_name . " c
                LEFT JOIN users u ON c.user_id = u.id 
                WHERE c.post_id = ? AND c.status = 'approved'
                ORDER BY c.created_at ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $post_id);
        $stmt->execute();
        
        return $stmt;
    }
    
    public function approve($id) {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            return false;
        }
        
        $query = "UPDATE " . $this->table_name . "
                SET status = 'approved'
                WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        
        return $stmt->execute();
    }
    
    public function delete($id) {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            return false;
        }
        
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        
        return $stmt->execute();
    }
}
?> 