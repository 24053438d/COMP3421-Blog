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
        // Get the comment's post_id to check ownership
        $query = "SELECT post_id FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        $comment_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$comment_data) {
            return false;
        }
        
        $is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
        $is_post_author = false;
        
        // Check if the current user is the post author
        if (isset($_SESSION['user_id']) && isset($comment_data['post_id'])) {
            $post_query = "SELECT user_id FROM posts WHERE id = ?";
            $post_stmt = $this->conn->prepare($post_query);
            $post_stmt->bindParam(1, $comment_data['post_id']);
            $post_stmt->execute();
            $post_data = $post_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($post_data && $post_data['user_id'] == $_SESSION['user_id']) {
                $is_post_author = true;
            }
        }
        
        // Only allow admin or post author to approve comments
        if (!$is_admin && !$is_post_author) {
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
        // Get the comment's post_id to check ownership
        $query = "SELECT post_id FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        $comment_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$comment_data) {
            return false;
        }
        
        $is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
        $is_post_author = false;
        
        // Check if the current user is the post author
        if (isset($_SESSION['user_id']) && isset($comment_data['post_id'])) {
            $post_query = "SELECT user_id FROM posts WHERE id = ?";
            $post_stmt = $this->conn->prepare($post_query);
            $post_stmt->bindParam(1, $comment_data['post_id']);
            $post_stmt->execute();
            $post_data = $post_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($post_data && $post_data['user_id'] == $_SESSION['user_id']) {
                $is_post_author = true;
            }
        }
        
        // Only allow admin or post author to delete comments
        if (!$is_admin && !$is_post_author) {
            return false;
        }
        
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        
        return $stmt->execute();
    }
}
?> 