<?php
class Analytics {
    private $conn;
    private $table_name = "analytics";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function logPageView($page_url) {
        $query = "INSERT INTO " . $this->table_name . "
                SET page_url=:page_url,
                    user_id=:user_id,
                    ip_address=:ip_address";
        
        $stmt = $this->conn->prepare($query);
        
        $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        $ip_address = $_SERVER['REMOTE_ADDR'];
        
        $stmt->bindParam(":page_url", $page_url);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":ip_address", $ip_address);
        
        return $stmt->execute();
    }
    
    public function getPageViewsCount($days = 7) {
        $query = "SELECT DATE(created_at) as date, COUNT(*) as views 
                 FROM " . $this->table_name . " 
                 WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL :days DAY) 
                 GROUP BY DATE(created_at) 
                 ORDER BY date";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':days', $days, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getTopPosts($limit = 5) {
        $query = "SELECT a.page_url, COUNT(*) as views, p.title 
                 FROM " . $this->table_name . " a
                 JOIN posts p ON a.page_url LIKE CONCAT('%id=', p.id, '%')
                 WHERE a.page_url LIKE 'view_post.php?id=%'
                 GROUP BY a.page_url
                 ORDER BY views DESC
                 LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getUserActivity($days = 7) {
        $query = "SELECT 
                    DATE(created_at) as date, 
                    COUNT(DISTINCT user_id) as active_users
                 FROM " . $this->table_name . " 
                 WHERE 
                    created_at >= DATE_SUB(CURRENT_DATE, INTERVAL :days DAY) 
                    AND user_id IS NOT NULL
                 GROUP BY DATE(created_at) 
                 ORDER BY date";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':days', $days, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?> 