<?php
class Analytics {
    private $conn;
    private $table_name = "analytics";
    private $events_table = "analytics_events";

    public function __construct($db) {
        $this->conn = $db;
        
        // Ensure the analytics_events table exists
        $this->createEventsTableIfNotExists();
        
        // Ensure the performance_metrics table exists
        $this->createPerformanceTableIfNotExists();
    }

    private function createEventsTableIfNotExists() {
        try {
            $query = "CREATE TABLE IF NOT EXISTS " . $this->events_table . " (
                id INT PRIMARY KEY AUTO_INCREMENT,
                event_type VARCHAR(50) NOT NULL,
                event_data TEXT,
                user_id INT NULL,
                ip_address VARCHAR(45),
                page_url VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )";
            
            $this->conn->exec($query);
            return true;
        } catch (PDOException $e) {
            error_log("Error creating analytics_events table: " . $e->getMessage());
            return false;
        }
    }
    
    private function createPerformanceTableIfNotExists() {
        try {
            $query = "CREATE TABLE IF NOT EXISTS performance_metrics (
                id INT PRIMARY KEY AUTO_INCREMENT,
                page_url VARCHAR(255) NOT NULL,
                load_time FLOAT NOT NULL,
                user_id INT NULL,
                ip_address VARCHAR(45),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )";
            
            $this->conn->exec($query);
            return true;
        } catch (PDOException $e) {
            error_log("Error creating performance_metrics table: " . $e->getMessage());
            return false;
        }
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
    
    // Track custom events like form submissions, button clicks, etc.
    public function trackEvent($event_type, $event_data = null, $page_url = null) {
        $query = "INSERT INTO " . $this->events_table . "
                SET event_type=:event_type,
                    event_data=:event_data,
                    user_id=:user_id,
                    ip_address=:ip_address,
                    page_url=:page_url";
        
        $stmt = $this->conn->prepare($query);
        
        $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $page_url = $page_url ?? $_SERVER['REQUEST_URI'];
        $event_data = $event_data ? json_encode($event_data) : null;
        
        $stmt->bindParam(":event_type", $event_type);
        $stmt->bindParam(":event_data", $event_data);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":ip_address", $ip_address);
        $stmt->bindParam(":page_url", $page_url);
        
        $result = $stmt->execute();
        
        // Log error if execution fails
        if (!$result) {
            error_log("Event tracking failed for event type: " . $event_type . ". Error: " . json_encode($stmt->errorInfo()));
        }
        
        return $result;
    }
    
    // Debug method to check if events table exists and has data
    public function getEventTableStatus() {
        // Check if table exists
        try {
            $query = "SELECT COUNT(*) as count FROM " . $this->events_table;
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get the latest 5 events for debugging
            $query = "SELECT * FROM " . $this->events_table . " ORDER BY id DESC LIMIT 5";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $latest = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'exists' => true,
                'count' => $result['count'],
                'latest' => $latest
            ];
        } catch (PDOException $e) {
            return [
                'exists' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function getPageViewsCount($days = 7) {
        try {
            $query = "SELECT DATE(created_at) as date, COUNT(*) as views 
                     FROM " . $this->table_name . " 
                     WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL :days DAY) 
                     GROUP BY DATE(created_at) 
                     ORDER BY date";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':days', $days, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // If no data is available, return a simple empty array
            if (empty($result)) {
                $result = [];
                $date = new DateTime();
                $date->modify("-$days days");
                
                for ($i = 0; $i < $days; $i++) {
                    $date->modify('+1 day');
                    $result[] = [
                        'date' => $date->format('Y-m-d'),
                        'views' => 0
                    ];
                }
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Page Views Count Error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getTopPosts($limit = 5) {
        try {
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
        } catch (PDOException $e) {
            error_log("Top Posts Error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getUserActivity($days = 7) {
        try {
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
            
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // If no data is available, return empty dataset with dates
            if (empty($result)) {
                $result = [];
                $date = new DateTime();
                $date->modify("-$days days");
                
                for ($i = 0; $i < $days; $i++) {
                    $date->modify('+1 day');
                    $result[] = [
                        'date' => $date->format('Y-m-d'),
                        'active_users' => 0
                    ];
                }
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("User Activity Error: " . $e->getMessage());
            return [];
        }
    }
    
    // New methods for custom events
    public function getEventCounts($event_type, $days = 7) {
        try {
            $query = "SELECT 
                        DATE(created_at) as date, 
                        COUNT(*) as count
                     FROM " . $this->events_table . " 
                     WHERE 
                        event_type = :event_type AND
                        created_at >= DATE_SUB(CURRENT_DATE, INTERVAL :days DAY) 
                     GROUP BY DATE(created_at) 
                     ORDER BY date";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':event_type', $event_type);
            $stmt->bindParam(':days', $days, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // If no data is available, return empty dataset with dates
            if (empty($result)) {
                $result = [];
                $date = new DateTime();
                $date->modify("-$days days");
                
                for ($i = 0; $i < $days; $i++) {
                    $date->modify('+1 day');
                    $result[] = [
                        'date' => $date->format('Y-m-d'),
                        'count' => 0
                    ];
                }
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Event Counts Error for type $event_type: " . $e->getMessage());
            return [];
        }
    }
    
    public function getGeoStats($days = 30) {
        try {
            // Create a geo_data table if it doesn't exist to cache IP to location mappings
            $this->createGeoTableIfNotExists();
            
            // Get unique IP addresses from analytics within the time range
            $query = "SELECT DISTINCT ip_address 
                     FROM " . $this->table_name . " 
                     WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL :days DAY)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':days', $days, PDO::PARAM_INT);
            $stmt->execute();
            
            $ips = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Process IP addresses to determine location
            foreach ($ips as $ip) {
                // Check if we already have this IP in our geo_data table
                $query = "SELECT country_code FROM geo_data WHERE ip_address = :ip";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':ip', $ip);
                $stmt->execute();
                
                if ($stmt->rowCount() == 0) {
                    // We don't have this IP in our cache, so determine its location
                    $country_code = $this->getCountryFromIP($ip);
                    
                    // Insert into geo_data cache
                    $query = "INSERT INTO geo_data (ip_address, country_code) VALUES (:ip, :country)";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(':ip', $ip);
                    $stmt->bindParam(':country', $country_code);
                    $stmt->execute();
                }
            }
            
            // Get country statistics
            $query = "SELECT 
                        g.country_code, 
                        COUNT(*) as count 
                     FROM " . $this->table_name . " a
                     INNER JOIN geo_data g ON a.ip_address = g.ip_address
                     WHERE a.created_at >= DATE_SUB(CURRENT_DATE, INTERVAL :days DAY)
                     GROUP BY g.country_code
                     ORDER BY count DESC";
            
            try {
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':days', $days, PDO::PARAM_INT);
                $stmt->execute();
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // If no results, try a more basic query that doesn't filter by date
                if (empty($result)) {
                    $query = "SELECT 
                                g.country_code, 
                                COUNT(*) as count 
                             FROM " . $this->table_name . " a
                             INNER JOIN geo_data g ON a.ip_address = g.ip_address
                             GROUP BY g.country_code
                             ORDER BY count DESC";
                    
                    $stmt = $this->conn->prepare($query);
                    $stmt->execute();
                    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
                
                return $result;
            } catch (PDOException $e) {
                error_log("Geo Query Error: " . $e->getMessage() . " - SQL: " . $query);
                return [];
            }
        } catch (PDOException $e) {
            error_log("Geo Stats Error: " . $e->getMessage());
            return [];
        }
    }
    
    private function createGeoTableIfNotExists() {
        try {
            $query = "CREATE TABLE IF NOT EXISTS geo_data (
                id INT PRIMARY KEY AUTO_INCREMENT,
                ip_address VARCHAR(45) NOT NULL,
                country_code VARCHAR(2) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY (ip_address)
            )";
            
            $this->conn->exec($query);
            return true;
        } catch (PDOException $e) {
            error_log("Error creating geo_data table: " . $e->getMessage());
            return false;
        }
    }
    
    public function getCountryFromIP($ip) {
        // Try to use PHP's GeoIP extension if available
        if (function_exists('geoip_country_code_by_name')) {
            $country_code = @geoip_country_code_by_name($ip);
            if ($country_code) {
                return $country_code;
            }
        }
        
        // Fall back to a simple approach for local/private IPs
        if (strpos($ip, '127.0.0.') === 0 || $ip === '::1') {
            return 'LH'; // localhost
        }
        
        if (strpos($ip, '192.168.') === 0 || strpos($ip, '10.') === 0 || strpos($ip, '172.16.') === 0) {
            return 'PV'; // private network
        }
        
        // Try to use ip-api.com (free service with some rate limits)
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            try {
                // First try HTTPS, then fall back to HTTP if that fails
                $url = "https://ip-api.com/json/" . $ip . "?fields=countryCode";
                $response = @file_get_contents($url);
                
                if ($response === false) {
                    // Try HTTP if HTTPS fails
                    $url = "http://ip-api.com/json/" . $ip . "?fields=countryCode";
                    $response = @file_get_contents($url);
                }
                
                if ($response !== false) {
                    $data = json_decode($response, true);
                    if (isset($data['countryCode']) && !empty($data['countryCode'])) {
                        return $data['countryCode'];
                    }
                } else {
                    // Log the error
                    error_log("IP API request failed for IP: " . $ip . " - Error: " . error_get_last()['message']);
                }
            } catch (Exception $e) {
                error_log("IP Geolocation Error: " . $e->getMessage());
            }
        }
        
        // Return unknown for other IPs instead of assigning random countries
        return 'UN'; // unknown
    }
    
    // Method to log performance metrics
    public function logPerformance($page_url, $load_time) {
        $query = "INSERT INTO performance_metrics
                SET page_url=:page_url,
                    load_time=:load_time,
                    user_id=:user_id,
                    ip_address=:ip_address";
        
        $stmt = $this->conn->prepare($query);
        
        $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        $ip_address = $_SERVER['REMOTE_ADDR'];
        
        $stmt->bindParam(":page_url", $page_url);
        $stmt->bindParam(":load_time", $load_time);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":ip_address", $ip_address);
        
        return $stmt->execute();
    }
    
    public function getPerformanceMetrics($days = 7) {
        try {
            $query = "SELECT 
                        DATE(created_at) as date, 
                        AVG(load_time) as load_time
                     FROM performance_metrics 
                     WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL :days DAY) 
                     GROUP BY DATE(created_at) 
                     ORDER BY date";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':days', $days, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // If no data is available, return empty dataset with dates
            if (empty($result)) {
                $result = [];
                $date = new DateTime();
                $date->modify("-$days days");
                
                for ($i = 0; $i < $days; $i++) {
                    $date->modify('+1 day');
                    $result[] = [
                        'date' => $date->format('Y-m-d'),
                        'load_time' => 0
                    ];
                }
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Performance Metrics Error: " . $e->getMessage());
            return [];
        }
    }
    
    // Method to check if we have meaningful geo data
    public function hasGeoData() {
        try {
            // Count records with country codes other than 'UN'
            $query = "SELECT COUNT(*) as count FROM geo_data WHERE country_code != 'UN'";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['count'] > 0;
        } catch (PDOException $e) {
            error_log("Geo Data Check Error: " . $e->getMessage());
            return false;
        }
    }
    
    // Simple method to get geo stats directly from geo_data table
    public function getSimpleGeoStats() {
        try {
            $query = "SELECT country_code, COUNT(*) as count 
                     FROM geo_data 
                     GROUP BY country_code 
                     ORDER BY count DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Simple Geo Stats Error: " . $e->getMessage());
            return [];
        }
    }

    public function getPageViewsForDateRange($startDate, $endDate) {
        try {
            $query = "SELECT DATE(created_at) as date, COUNT(*) as views 
                     FROM " . $this->table_name . " 
                     WHERE created_at >= :start_date AND created_at <= :end_date
                     GROUP BY DATE(created_at) 
                     ORDER BY date";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':start_date', $startDate);
            $stmt->bindParam(':end_date', $endDate);
            $stmt->execute();
            
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // If no data is available, return an empty array
            if (empty($result)) {
                $result = [];
                $start = new DateTime($startDate);
                $end = new DateTime($endDate);
                $interval = new DateInterval('P1D');
                $dateRange = new DatePeriod($start, $interval, $end);
                
                foreach ($dateRange as $date) {
                    $result[] = [
                        'date' => $date->format('Y-m-d'),
                        'views' => 0
                    ];
                }
                
                // Add the end date as well
                $result[] = [
                    'date' => $end->format('Y-m-d'),
                    'views' => 0
                ];
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Page Views Date Range Error: " . $e->getMessage());
            return [];
        }
    }

    public function getUserActivityForDateRange($startDate, $endDate) {
        try {
            $query = "SELECT 
                        DATE(created_at) as date, 
                        COUNT(DISTINCT user_id) as active_users
                     FROM " . $this->table_name . " 
                     WHERE 
                        created_at >= :start_date AND created_at <= :end_date
                        AND user_id IS NOT NULL
                     GROUP BY DATE(created_at) 
                     ORDER BY date";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':start_date', $startDate);
            $stmt->bindParam(':end_date', $endDate);
            $stmt->execute();
            
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // If no data is available, return empty dataset with dates
            if (empty($result)) {
                $result = [];
                $start = new DateTime($startDate);
                $end = new DateTime($endDate);
                $interval = new DateInterval('P1D');
                $dateRange = new DatePeriod($start, $interval, $end);
                
                foreach ($dateRange as $date) {
                    $result[] = [
                        'date' => $date->format('Y-m-d'),
                        'active_users' => 0
                    ];
                }
                
                // Add the end date as well
                $result[] = [
                    'date' => $end->format('Y-m-d'),
                    'active_users' => 0
                ];
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("User Activity Date Range Error: " . $e->getMessage());
            return [];
        }
    }

    public function getEventCountsForDateRange($event_type, $startDate, $endDate) {
        try {
            $query = "SELECT 
                        DATE(created_at) as date, 
                        COUNT(*) as count
                     FROM " . $this->events_table . " 
                     WHERE 
                        event_type = :event_type AND
                        created_at >= :start_date AND created_at <= :end_date
                     GROUP BY DATE(created_at) 
                     ORDER BY date";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':event_type', $event_type);
            $stmt->bindParam(':start_date', $startDate);
            $stmt->bindParam(':end_date', $endDate);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Event Counts Date Range Error: " . $e->getMessage());
            return [];
        }
    }

    public function getPerformanceMetricsForDateRange($startDate, $endDate) {
        try {
            $query = "SELECT 
                        DATE(created_at) as date, 
                        AVG(load_time) as load_time
                     FROM performance_metrics 
                     WHERE created_at >= :start_date AND created_at <= :end_date
                     GROUP BY DATE(created_at) 
                     ORDER BY date";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':start_date', $startDate);
            $stmt->bindParam(':end_date', $endDate);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Performance Metrics Date Range Error: " . $e->getMessage());
            return [];
        }
    }

    public function getGeoStatsForDateRange($startDate, $endDate) {
        try {
            $query = "SELECT 
                        g.country_code,
                        COUNT(*) as count
                     FROM geo_data g
                     JOIN analytics a ON g.analytics_id = a.id
                     WHERE 
                        a.created_at >= :start_date AND a.created_at <= :end_date
                        AND g.country_code != 'UN'
                     GROUP BY g.country_code
                     ORDER BY count DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':start_date', $startDate);
            $stmt->bindParam(':end_date', $endDate);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Geo Stats Date Range Error: " . $e->getMessage());
            return [];
        }
    }
}
?> 