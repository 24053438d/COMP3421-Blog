<?php
require_once '../config/database.php';
require_once '../classes/User.php';
require_once '../classes/Analytics.php';
require_once '../middleware/AuthMiddleware.php';

// Ensure the user is an admin
AuthMiddleware::requireAdmin();

$database = new Database();
$db = $database->getConnection();
$analytics = new Analytics($db);

// Check event table status
$eventStatus = $analytics->getEventTableStatus();

// Get performance metrics data for debugging
$performanceData = [];
try {
    $query = "SELECT * FROM performance_metrics ORDER BY id DESC LIMIT 10";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $performanceData = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $performanceError = $e->getMessage();
}

// Get geo data for debugging
$geoData = [];
try {
    $query = "SELECT * FROM geo_data ORDER BY id DESC LIMIT 10";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $geoData = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $geoError = $e->getMessage();
}

// Test event tracking
$testEvent = false;
if (isset($_POST['test_event'])) {
    $testResult = $analytics->trackEvent('test_event', ['source' => 'debug_page', 'time' => time()]);
    $testEvent = $testResult ? 'success' : 'failed';
    // Refresh the page to show new data
    header("Location: debug_analytics.php?test=" . $testEvent);
    exit;
}

// Test performance tracking
$testPerformance = false;
if (isset($_POST['test_performance'])) {
    $testResult = $analytics->logPerformance('debug_analytics.php', rand(100, 500) / 100);
    $testPerformance = $testResult ? 'success' : 'failed';
    // Refresh the page to show new data
    header("Location: debug_analytics.php?perf=" . $testPerformance);
    exit;
}

// Reset geo data
if (isset($_POST['reset_geo'])) {
    try {
        // First delete all existing geo data
        $query = "TRUNCATE TABLE geo_data";
        $db->exec($query);
        
        // Then repopulate from analytics data
        $query = "SELECT DISTINCT ip_address FROM analytics WHERE ip_address IS NOT NULL";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $ips = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($ips)) {
            // No IP addresses found in analytics table
            header("Location: debug_analytics.php?geo_reset=warning&message=" . urlencode("No IP addresses found in analytics table."));
            exit;
        }
        
        $count = 0;
        foreach ($ips as $ip) {
            $country_code = $analytics->getCountryFromIP($ip);
            $query = "INSERT INTO geo_data (ip_address, country_code) VALUES (:ip, :country)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':ip', $ip);
            $stmt->bindParam(':country', $country_code);
            if ($stmt->execute()) {
                $count++;
            }
        }
        
        // Refresh the page with status message
        header("Location: debug_analytics.php?geo_reset=success&count=" . $count);
        exit;
    } catch (PDOException $e) {
        header("Location: debug_analytics.php?geo_reset=failed&error=" . urlencode($e->getMessage()));
        exit;
    }
}

if (isset($_GET['test'])) {
    $testEvent = $_GET['test'];
}

if (isset($_GET['perf'])) {
    $testPerformance = $_GET['perf'];
}

if (isset($_GET['geo'])) {
    $testGeo = $_GET['geo'];
}

require_once '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h2>Analytics Debug</h2>
        <p class="text-muted">Debug information for analytics event tracking</p>
        
        <?php if ($testEvent === 'success'): ?>
            <div class="alert alert-success">
                Test event was successfully added!
            </div>
        <?php elseif ($testEvent === 'failed'): ?>
            <div class="alert alert-danger">
                Failed to add test event.
            </div>
        <?php endif; ?>
        
        <?php if ($testPerformance === 'success'): ?>
            <div class="alert alert-success">
                Test performance metric was successfully added!
            </div>
        <?php elseif ($testPerformance === 'failed'): ?>
            <div class="alert alert-danger">
                Failed to add test performance metric.
            </div>
        <?php endif; ?>
        
        <?php if ($testGeo === 'success'): ?>
            <div class="alert alert-success">
                Test geo data was successfully added!
            </div>
        <?php elseif ($testGeo === 'failed'): ?>
            <div class="alert alert-danger">
                Failed to add test geo data.
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5>Events Table Status</h5>
            </div>
            <div class="card-body">
                <?php if ($eventStatus['exists']): ?>
                    <div class="alert alert-info">
                        <p><strong>Table exists:</strong> Yes</p>
                        <p><strong>Event count:</strong> <?php echo $eventStatus['count']; ?></p>
                    </div>
                    
                    <h6>Latest Events:</h6>
                    <?php if (count($eventStatus['latest']) > 0): ?>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Event Type</th>
                                    <th>User ID</th>
                                    <th>Page URL</th>
                                    <th>Data</th>
                                    <th>Created At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($eventStatus['latest'] as $event): ?>
                                    <tr>
                                        <td><?php echo $event['id']; ?></td>
                                        <td><?php echo htmlspecialchars($event['event_type']); ?></td>
                                        <td><?php echo $event['user_id'] ? $event['user_id'] : 'None'; ?></td>
                                        <td><?php echo htmlspecialchars($event['page_url']); ?></td>
                                        <td>
                                            <pre style="max-width: 300px; white-space: pre-wrap;"><?php echo htmlspecialchars($event['event_data']); ?></pre>
                                        </td>
                                        <td><?php echo $event['created_at']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No events recorded yet.</p>
                    <?php endif; ?>
                    
                    <form method="POST" class="mt-4">
                        <button type="submit" name="test_event" class="btn btn-primary">Add Test Event</button>
                    </form>
                    
                <?php else: ?>
                    <div class="alert alert-danger">
                        <p><strong>Table exists:</strong> No</p>
                        <p><strong>Error:</strong> <?php echo htmlspecialchars($eventStatus['error']); ?></p>
                    </div>
                    
                    <div class="mt-4">
                        <h6>Troubleshooting:</h6>
                        <ol>
                            <li>Check that the <code>analytics_events</code> table exists in your database</li>
                            <li>Make sure the table schema matches what's defined in <code>database/schema.sql</code></li>
                            <li>Verify that the createEventsTableIfNotExists method in <code>Analytics.php</code> is working properly</li>
                        </ol>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5>Performance Metrics</h5>
            </div>
            <div class="card-body">
                <?php if (isset($performanceError)): ?>
                    <div class="alert alert-danger">
                        <p><strong>Error:</strong> <?php echo htmlspecialchars($performanceError); ?></p>
                    </div>
                    
                    <div class="mt-4">
                        <h6>Troubleshooting:</h6>
                        <ol>
                            <li>Check that the <code>performance_metrics</code> table exists in your database</li>
                            <li>Make sure the table schema is correct</li>
                            <li>Verify that the createPerformanceTableIfNotExists method in <code>Analytics.php</code> is working properly</li>
                        </ol>
                    </div>
                <?php else: ?>
                    <h6>Latest Performance Metrics:</h6>
                    <?php if (count($performanceData) > 0): ?>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Page URL</th>
                                    <th>Load Time (s)</th>
                                    <th>User ID</th>
                                    <th>IP Address</th>
                                    <th>Created At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($performanceData as $data): ?>
                                    <tr>
                                        <td><?php echo $data['id']; ?></td>
                                        <td><?php echo htmlspecialchars($data['page_url']); ?></td>
                                        <td><?php echo number_format($data['load_time'], 2); ?></td>
                                        <td><?php echo $data['user_id'] ? $data['user_id'] : 'None'; ?></td>
                                        <td><?php echo htmlspecialchars($data['ip_address']); ?></td>
                                        <td><?php echo $data['created_at']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No performance metrics recorded yet.</p>
                    <?php endif; ?>
                    
                    <form method="POST" class="mt-4">
                        <button type="submit" name="test_performance" class="btn btn-primary">Add Test Performance Metric</button>
                    </form>
                    
                    <div class="mt-4">
                        <div class="alert alert-info">
                            <h6>How Performance Tracking Works:</h6>
                            <p>Page load times are measured using the JavaScript Performance API. The timing data is collected when a page fully loads, and then sent to the server via an AJAX request to <code>/api/log_performance.php</code>.</p>
                            <p>This data is stored in the <code>performance_metrics</code> table and displayed on the analytics dashboard as average load times per day.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5>Geo Data</h5>
            </div>
            <div class="card-body">
                <?php if (isset($geoError)): ?>
                    <div class="alert alert-danger">
                        <p><strong>Error:</strong> <?php echo htmlspecialchars($geoError); ?></p>
                    </div>
                    
                    <div class="mt-4">
                        <h6>Troubleshooting:</h6>
                        <ol>
                            <li>Check that the <code>geo_data</code> table exists in your database</li>
                            <li>Make sure the table schema is correct</li>
                            <li>Verify that the createGeoTableIfNotExists method in <code>Analytics.php</code> is working properly</li>
                        </ol>
                    </div>
                <?php else: ?>
                    <h6>Latest Geo Data:</h6>
                    <?php if (count($geoData) > 0): ?>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>IP Address</th>
                                    <th>Country Code</th>
                                    <th>Country Name</th>
                                    <th>Created At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // Map of country codes to country names
                                $countryNames = [
                                    'US' => 'United States',
                                    'CA' => 'Canada',
                                    'GB' => 'United Kingdom',
                                    'DE' => 'Germany',
                                    'FR' => 'France',
                                    'JP' => 'Japan',
                                    'AU' => 'Australia',
                                    'BR' => 'Brazil',
                                    'IN' => 'India',
                                    'CN' => 'China',
                                    'LH' => 'Localhost',
                                    'PV' => 'Private Network'
                                ];
                                
                                foreach ($geoData as $data): 
                                    $countryName = isset($countryNames[$data['country_code']]) ? 
                                        $countryNames[$data['country_code']] : $data['country_code'];
                                ?>
                                    <tr>
                                        <td><?php echo $data['id']; ?></td>
                                        <td><?php echo htmlspecialchars($data['ip_address']); ?></td>
                                        <td><?php echo htmlspecialchars($data['country_code']); ?></td>
                                        <td><?php echo htmlspecialchars($countryName); ?></td>
                                        <td><?php echo $data['created_at']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No geo data recorded yet.</p>
                    <?php endif; ?>
                    
                    <form method="POST" class="mt-4">
                        <button type="submit" name="test_geo" class="btn btn-primary">Add Test Geo Data</button>
                        <button type="submit" name="reset_geo" class="btn btn-warning">Reset & Repopulate Geo Data</button>
                    </form>
                    
                    <?php if (isset($_GET['geo_reset'])): ?>
                        <?php if ($_GET['geo_reset'] === 'success'): ?>
                            <div class="alert alert-success mt-3">
                                Geo data successfully reset and repopulated with <?php echo $_GET['count']; ?> records.
                            </div>
                        <?php elseif ($_GET['geo_reset'] === 'warning'): ?>
                            <div class="alert alert-warning mt-3">
                                Warning: <?php echo htmlspecialchars($_GET['message'] ?? 'No message provided'); ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-danger mt-3">
                                Error resetting geo data: <?php echo htmlspecialchars($_GET['error'] ?? 'Unknown error'); ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <div class="mt-4">
                        <div class="alert alert-info">
                            <h6>How Geo Tracking Works:</h6>
                            <p>When users visit the site, their IP addresses are recorded. The system attempts to determine their country using IP geolocation.</p>
                            <p>Country codes are cached in the <code>geo_data</code> table to avoid repeated lookups for the same IP address.</p>
                            <p>This data is used to generate the geographical distribution chart on the analytics dashboard.</p>
                        </div>
                    </div>
                    
                    <!-- API Test Section -->
                    <h5 class="mt-4">IP Geolocation API Test</h5>
                    <form method="POST" action="debug_analytics.php">
                        <div class="mb-3">
                            <label for="test_ip" class="form-label">IP Address to Test:</label>
                            <input type="text" class="form-control" id="test_ip" name="test_ip" value="8.8.8.8" placeholder="Enter an IP to test">
                            <div class="form-text">Default: 8.8.8.8 (Google DNS)</div>
                        </div>
                        <button type="submit" name="test_api" class="btn btn-info">Test API Connection</button>
                    </form>
                    
                    <?php
                    if (isset($_POST['test_api'])) {
                        $test_ip = $_POST['test_ip'] ?? '8.8.8.8';
                        echo '<div class="mt-3 p-3 border rounded bg-light">';
                        echo '<h6>API Test Results:</h6>';
                        
                        // Test if allow_url_fopen is enabled
                        echo '<p><strong>allow_url_fopen:</strong> ' . (ini_get('allow_url_fopen') ? 'Enabled ✅' : 'Disabled ❌') . '</p>';
                        
                        // Try HTTPS first
                        $url = "https://ip-api.com/json/" . $test_ip . "?fields=countryCode,status,message";
                        $response = @file_get_contents($url);
                        echo '<p><strong>HTTPS Test:</strong> ' . ($response !== false ? 'Success ✅' : 'Failed ❌ (' . error_get_last()['message'] . ')') . '</p>';
                        
                        // Try HTTP if HTTPS fails
                        if ($response === false) {
                            $url = "http://ip-api.com/json/" . $test_ip . "?fields=countryCode,status,message";
                            $response = @file_get_contents($url);
                            echo '<p><strong>HTTP Test:</strong> ' . ($response !== false ? 'Success ✅' : 'Failed ❌ (' . error_get_last()['message'] . ')') . '</p>';
                        }
                        
                        // Show the response
                        if ($response !== false) {
                            $data = json_decode($response, true);
                            echo '<p><strong>API Response:</strong></p>';
                            echo '<pre>' . print_r($data, true) . '</pre>';
                            
                            if (isset($data['countryCode']) && !empty($data['countryCode'])) {
                                echo '<div class="alert alert-success">API is working correctly! Country code: ' . $data['countryCode'] . '</div>';
                                
                                // Update this IP in the geo_data table
                                $analytics->getCountryFromIP($test_ip); // This will update the geo_data table
                                echo '<p>Updated geo_data table with this IP and country code.</p>';
                            } else {
                                echo '<div class="alert alert-danger">API response does not contain a country code. Check the API response above.</div>';
                            }
                        } else {
                            echo '<div class="alert alert-danger">
                                Failed to connect to IP API. Enable allow_url_fopen in your PHP settings.
                                <ol>
                                    <li>Go to Hostinger Control Panel > Advanced > PHP Configuration</li>
                                    <li>Set allow_url_fopen = On</li>
                                    <li>Save changes</li>
                                </ol>
                            </div>';
                        }
                        
                        echo '</div>';
                    }
                    ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5>Expected Event Points</h5>
            </div>
            <div class="card-body">
                <p>Events should be tracked at the following points in the application:</p>
                <ul>
                    <li><strong>Comment submissions</strong> - Tracked in <code>view_post.php</code> when a comment is successfully created</li>
                    <li><strong>Post submissions</strong> - Tracked in <code>create_post.php</code> when a post is successfully created</li>
                    <li><strong>Page load performance</strong> - Tracked via JavaScript in <code>footer.php</code> on every page load</li>
                </ul>
                
                <h6 class="mt-4">Common Issues:</h6>
                <ol>
                    <li>Analytics object not initialized before tracking events</li>
                    <li>Missing session_start() in files where $_SESSION is used</li>
                    <li>Database connection issues</li>
                    <li>Table permissions issues</li>
                    <li>JavaScript errors preventing performance tracking</li>
                    <li>CORS issues with the performance API endpoint</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 