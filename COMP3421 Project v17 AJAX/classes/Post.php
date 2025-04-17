<?php
class Post {
    private $conn;
    private $table_name = "posts";

    public $id;
    public $title;
    public $content;
    public $user_id;
    public $status;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                SET title=:title, 
                    content=:content, 
                    user_id=:user_id,
                    status=:status";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":title", $this->title);
        $stmt->bindParam(":content", $this->content);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":status", $this->status);
        
        return $stmt->execute();
    }

    public function read($id = null) {
        $query = "SELECT p.*, u.username 
                FROM " . $this->table_name . " p
                LEFT JOIN users u ON p.user_id = u.id ";
        
        if($id) {
            $query .= "WHERE p.id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $id);
        } else {
            $query .= "WHERE p.status = 'published' ORDER BY p.created_at DESC";
            $stmt = $this->conn->prepare($query);
        }
        
        $stmt->execute();
        return $stmt;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . "
                SET title=:title, 
                    content=:content,
                    status=:status
                WHERE id=:id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":title", $this->title);
        $stmt->bindParam(":content", $this->content);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":id", $this->id);
        
        return $stmt->execute();
    }

    public function delete() {
        // First delete related comments
        $query = "DELETE FROM comments WHERE post_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        
        // Then delete the post
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        
        return $stmt->execute();
    }

    public function getPostsByUser($user_id) {
        $query = "SELECT p.*, u.username 
                FROM " . $this->table_name . " p
                LEFT JOIN users u ON p.user_id = u.id 
                WHERE p.user_id = ?
                ORDER BY p.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        
        return $stmt;
    }

    public function canEdit() {
        // User can edit if they are the author or an admin
        return isset($_SESSION['user_id']) && 
               ($_SESSION['user_id'] == $this->user_id || 
                (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'));
    }
}
?> 