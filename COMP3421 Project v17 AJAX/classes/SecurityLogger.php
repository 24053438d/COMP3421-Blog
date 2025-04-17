<?php
class SecurityLogger {
    private $conn;
    private $table_name = "security_logs";
    
    const EVENT_LOGIN_SUCCESS = 'login_success';
    const EVENT_LOGIN_FAILED = 'login_failed';
    const EVENT_LOGOUT = 'logout';
    const EVENT_PASSWORD_RESET_REQUEST = 'password_reset_request';
    const EVENT_PASSWORD_RESET_SUCCESS = 'password_reset_success';
    const EVENT_PASSWORD_CHANGE = 'password_change';
    const EVENT_ACCOUNT_LOCKED = 'account_locked';
    const EVENT_SUSPICIOUS_ACTIVITY = 'suspicious_activity';
    const EVENT_ADMIN_ACTION = 'admin_action';
    const EVENT_REGISTRATION = 'registration';
    
    public function __construct($db) {
        $this->conn = $db;
        $this->ensureTableExists();
    }
    
    /**
     * Ensure the security logs table exists
     */
    private function ensureTableExists() {
        $query = "CREATE TABLE IF NOT EXISTS " . $this->table_name . " (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_type VARCHAR(50) NOT NULL,
            user_id INT,
            ip_address VARCHAR(45) NOT NULL,
            user_agent VARCHAR(255),
            details TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $this->conn->exec($query);
    }
    
    /**
     * Log a security event
     * 
     * @param string $eventType Type of security event
     * @param int|null $userId ID of the user (null if not logged in)
     * @param array $details Additional details about the event
     * @return bool Whether the log was created successfully
     */
    public function logEvent($eventType, $userId = null, $details = []) {
        $query = "INSERT INTO " . $this->table_name . "
                SET event_type = :event_type,
                    user_id = :user_id,
                    ip_address = :ip_address,
                    user_agent = :user_agent,
                    details = :details";
        
        $stmt = $this->conn->prepare($query);
        
        // Get client IP address and user agent
        $ipAddress = $this->getClientIP();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        // Convert details array to JSON
        $detailsJson = json_encode($details);
        
        // Bind parameters
        $stmt->bindParam(":event_type", $eventType);
        $stmt->bindParam(":user_id", $userId);
        $stmt->bindParam(":ip_address", $ipAddress);
        $stmt->bindParam(":user_agent", $userAgent);
        $stmt->bindParam(":details", $detailsJson);
        
        return $stmt->execute();
    }
    
    /**
     * Get client IP address
     * 
     * @return string Client IP address
     */
    private function getClientIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        }
        return $ip;
    }
    
    /**
     * Get login attempts for a given user within a time period
     * 
     * @param string $email User email
     * @param int $minutes Time period in minutes
     * @return int Number of failed login attempts
     */
    public function getFailedLoginAttempts($email, $minutes = 30) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . "
                WHERE event_type = :event_type
                AND created_at > DATE_SUB(NOW(), INTERVAL :minutes MINUTE)
                AND JSON_EXTRACT(details, '$.email') = :email";
        
        $stmt = $this->conn->prepare($query);
        
        $eventType = self::EVENT_LOGIN_FAILED;
        $email = json_encode($email);  // Need to json_encode to match the stored JSON format
        
        $stmt->bindParam(":event_type", $eventType);
        $stmt->bindParam(":minutes", $minutes, PDO::PARAM_INT);
        $stmt->bindParam(":email", $email);
        
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return (int)$row['count'];
    }
    
    /**
     * Check if an IP address is suspicious (too many failed attempts)
     * 
     * @param int $threshold Maximum number of failed attempts
     * @param int $minutes Time period in minutes
     * @return bool True if IP is suspicious, false otherwise
     */
    public function isSuspiciousIP($threshold = 10, $minutes = 30) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . "
                WHERE event_type = :event_type
                AND ip_address = :ip_address
                AND created_at > DATE_SUB(NOW(), INTERVAL :minutes MINUTE)";
        
        $stmt = $this->conn->prepare($query);
        
        $eventType = self::EVENT_LOGIN_FAILED;
        $ipAddress = $this->getClientIP();
        
        $stmt->bindParam(":event_type", $eventType);
        $stmt->bindParam(":ip_address", $ipAddress);
        $stmt->bindParam(":minutes", $minutes, PDO::PARAM_INT);
        
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return (int)$row['count'] >= $threshold;
    }
    
    /**
     * Get security logs for a specific user
     * 
     * @param int $userId User ID
     * @param int $limit Maximum number of logs to retrieve
     * @return array Array of security logs
     */
    public function getLogsForUser($userId, $limit = 100) {
        $query = "SELECT * FROM " . $this->table_name . "
                WHERE user_id = :user_id
                ORDER BY created_at DESC
                LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $userId);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get recent security logs (for admin viewing)
     * 
     * @param int $limit Maximum number of logs to retrieve
     * @return array Array of security logs
     */
    public function getRecentLogs($limit = 100) {
        $query = "SELECT * FROM " . $this->table_name . "
                ORDER BY created_at DESC
                LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get filtered security logs
     * 
     * @param string|null $eventType Filter by event type
     * @param string|null $username Filter by username
     * @param string|null $fromDate Filter by start date (YYYY-MM-DD)
     * @param string|null $toDate Filter by end date (YYYY-MM-DD)
     * @param int $limit Maximum number of logs to retrieve
     * @param int $offset Offset for pagination
     * @return array Array of security logs
     */
    public function getFilteredLogs($eventType = null, $username = null, $fromDate = null, $toDate = null, $limit = 100, $offset = 0) {
        $query = "SELECT sl.*, u.username 
                 FROM " . $this->table_name . " sl
                 LEFT JOIN users u ON sl.user_id = u.id
                 WHERE 1=1";
        $params = [];
        
        // Add filters
        if ($eventType !== null && $eventType !== '') {
            $query .= " AND sl.event_type = :event_type";
            $params[":event_type"] = $eventType;
        }
        
        if ($username !== null && $username !== '') {
            $query .= " AND u.username LIKE :username";
            $params[":username"] = '%' . $username . '%';
        }
        
        if ($fromDate !== null && $fromDate !== '') {
            $query .= " AND sl.created_at >= :from_date";
            $params[":from_date"] = $fromDate . ' 00:00:00';
        }
        
        if ($toDate !== null && $toDate !== '') {
            $query .= " AND sl.created_at <= :to_date";
            $params[":to_date"] = $toDate . ' 23:59:59';
        }
        
        // Add order and limit
        $query .= " ORDER BY sl.created_at DESC LIMIT :limit OFFSET :offset";
        $params[":limit"] = (int)$limit;
        $params[":offset"] = (int)$offset;
        
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        foreach ($params as $key => $value) {
            if ($key === ':limit' || $key === ':offset') {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value);
            }
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Count filtered security logs
     * 
     * @param string|null $eventType Filter by event type
     * @param string|null $username Filter by username
     * @param string|null $fromDate Filter by start date (YYYY-MM-DD)
     * @param string|null $toDate Filter by end date (YYYY-MM-DD)
     * @return int Total count of matching logs
     */
    public function countFilteredLogs($eventType = null, $username = null, $fromDate = null, $toDate = null) {
        $query = "SELECT COUNT(*) as count 
                 FROM " . $this->table_name . " sl
                 LEFT JOIN users u ON sl.user_id = u.id
                 WHERE 1=1";
        $params = [];
        
        // Add filters
        if ($eventType !== null && $eventType !== '') {
            $query .= " AND sl.event_type = :event_type";
            $params[":event_type"] = $eventType;
        }
        
        if ($username !== null && $username !== '') {
            $query .= " AND u.username LIKE :username";
            $params[":username"] = '%' . $username . '%';
        }
        
        if ($fromDate !== null && $fromDate !== '') {
            $query .= " AND sl.created_at >= :from_date";
            $params[":from_date"] = $fromDate . ' 00:00:00';
        }
        
        if ($toDate !== null && $toDate !== '') {
            $query .= " AND sl.created_at <= :to_date";
            $params[":to_date"] = $toDate . ' 23:59:59';
        }
        
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return (int)$row['count'];
    }
    
    /**
     * Get all unique event types in the logs
     * 
     * @return array Array of event types
     */
    public function getEventTypes() {
        $query = "SELECT DISTINCT event_type FROM " . $this->table_name . " ORDER BY event_type";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $types = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $types[] = $row['event_type'];
        }
        
        return $types;
    }
    
    /**
     * Get a summary of security events for dashboard
     * 
     * @param int $days Number of days to include in summary
     * @return array Summary statistics
     */
    public function getSecuritySummary($days = 7) {
        // Get total login attempts (successful and failed)
        $query = "SELECT 
                    SUM(CASE WHEN event_type = :login_success THEN 1 ELSE 0 END) as successful_logins,
                    SUM(CASE WHEN event_type = :login_failed THEN 1 ELSE 0 END) as failed_logins,
                    SUM(CASE WHEN event_type = :account_locked THEN 1 ELSE 0 END) as account_locks,
                    SUM(CASE WHEN event_type = :suspicious_activity THEN 1 ELSE 0 END) as suspicious_activities,
                    COUNT(*) as total_events
                FROM " . $this->table_name . "
                WHERE created_at > DATE_SUB(NOW(), INTERVAL :days DAY)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindValue(":login_success", self::EVENT_LOGIN_SUCCESS);
        $stmt->bindValue(":login_failed", self::EVENT_LOGIN_FAILED);
        $stmt->bindValue(":account_locked", self::EVENT_ACCOUNT_LOCKED);
        $stmt->bindValue(":suspicious_activity", self::EVENT_SUSPICIOUS_ACTIVITY);
        $stmt->bindValue(":days", $days, PDO::PARAM_INT);
        
        $stmt->execute();
        $summary = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get most common IP addresses with failed logins
        $query = "SELECT ip_address, COUNT(*) as count
                FROM " . $this->table_name . "
                WHERE event_type = :login_failed
                AND created_at > DATE_SUB(NOW(), INTERVAL :days DAY)
                GROUP BY ip_address
                ORDER BY count DESC
                LIMIT 5";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":login_failed", self::EVENT_LOGIN_FAILED);
        $stmt->bindValue(":days", $days, PDO::PARAM_INT);
        $stmt->execute();
        
        $summary['top_failed_ips'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get daily statistics for chart
        $query = "SELECT 
                    DATE(created_at) as date,
                    SUM(CASE WHEN event_type = :login_success THEN 1 ELSE 0 END) as successful_logins,
                    SUM(CASE WHEN event_type = :login_failed THEN 1 ELSE 0 END) as failed_logins
                FROM " . $this->table_name . "
                WHERE created_at > DATE_SUB(NOW(), INTERVAL :days DAY)
                GROUP BY DATE(created_at)
                ORDER BY date";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":login_success", self::EVENT_LOGIN_SUCCESS);
        $stmt->bindValue(":login_failed", self::EVENT_LOGIN_FAILED);
        $stmt->bindValue(":days", $days, PDO::PARAM_INT);
        $stmt->execute();
        
        $summary['daily_stats'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $summary;
    }
}
?> 