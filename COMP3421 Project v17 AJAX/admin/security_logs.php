<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';
require_once '../classes/User.php';
require_once '../classes/SecurityLogger.php';
require_once '../middleware/AuthMiddleware.php';

// Ensure user is logged in and is an admin
AuthMiddleware::requireLogin();
AuthMiddleware::requireAdmin();

$database = new Database();
$db = $database->getConnection();
$securityLogger = new SecurityLogger($db);

// Pagination settings
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$logsPerPage = 20;
$offset = ($page - 1) * $logsPerPage;

// Filter settings
$eventType = isset($_GET['event_type']) ? $_GET['event_type'] : null;
$username = isset($_GET['username']) ? $_GET['username'] : null;
$fromDate = isset($_GET['from_date']) ? $_GET['from_date'] : null;
$toDate = isset($_GET['to_date']) ? $_GET['to_date'] : null;

// For AJAX requests, only return the filtered data
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    // Ensure we return JSON even if there's an error
    header('Content-Type: application/json');
    
    try {
        // Get logs based on filters
        $logs = $securityLogger->getFilteredLogs($eventType, $username, $fromDate, $toDate, $logsPerPage, $offset);
        $totalLogs = $securityLogger->countFilteredLogs($eventType, $username, $fromDate, $toDate);
        $totalPages = ceil($totalLogs / $logsPerPage);
        
        // Process logs to ensure proper JSON encoding
        $processedLogs = [];
        foreach ($logs as $log) {
            // Handle the details field to ensure it's valid JSON
            if (isset($log['details']) && !empty($log['details'])) {
                // If details is already a JSON string, decode it to ensure it's valid
                if (is_string($log['details'])) {
                    $decoded = json_decode($log['details'], true);
                    if ($decoded !== null || $log['details'] === 'null') {
                        // If valid JSON, keep the string version
                        $log['details'] = $log['details'];
                    } else {
                        // If not valid JSON, encode it as a plain string
                        $log['details'] = json_encode($log['details']);
                    }
                } else {
                    // If not a string, encode it
                    $log['details'] = json_encode($log['details']);
                }
            } else {
                // Ensure empty details are represented as empty object
                $log['details'] = "{}";
            }
            
            $processedLogs[] = $log;
        }
        
        // Prepare response
        $response = [
            'logs' => $processedLogs,
            'totalLogs' => $totalLogs,
            'totalPages' => $totalPages,
            'currentPage' => $page
        ];
        
        // Return JSON response
        echo json_encode($response);
    } catch (Exception $e) {
        // Log the error
        error_log("Security logs AJAX error: " . $e->getMessage());
        
        // Return error response
        echo json_encode([
            'error' => true,
            'message' => 'An error occurred while fetching security logs: ' . $e->getMessage()
        ]);
    }
    exit;
}

// Get logs based on filters
$logs = $securityLogger->getFilteredLogs($eventType, $username, $fromDate, $toDate, $logsPerPage, $offset);
$totalLogs = $securityLogger->countFilteredLogs($eventType, $username, $fromDate, $toDate);
$totalPages = ceil($totalLogs / $logsPerPage);

// Get available event types for filter dropdown
$eventTypes = $securityLogger->getEventTypes();

require_once '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2>Security Logs</h2>
            <p class="text-muted">View and filter security events in the system</p>
        </div>
    </div>
    
    <!-- Debug info (only visible during development) -->
    <?php if (false): // Set to false in production ?>
    <div class="alert alert-info">
        <h5>Debug Info:</h5>
        <p>Event Type: <?php echo $eventType ? htmlspecialchars($eventType) : 'null'; ?></p>
        <p>Username: <?php echo $username ? htmlspecialchars($username) : 'null'; ?></p>
        <p>From Date: <?php echo $fromDate ? htmlspecialchars($fromDate) : 'null'; ?></p>
        <p>To Date: <?php echo $toDate ? htmlspecialchars($toDate) : 'null'; ?></p>
        <p>Total Logs: <?php echo $totalLogs; ?></p>
    </div>
    <?php endif; ?>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header">
            <h5>Filters</h5>
        </div>
        <div class="card-body">
            <form id="filter-form" action="/admin/security_logs.php" class="row g-3">
                <div class="col-md-3">
                    <label for="event_type" class="form-label">Event Type</label>
                    <select class="form-select" id="event_type" name="event_type">
                        <option value="">All Event Types</option>
                        <?php foreach ($eventTypes as $type): ?>
                            <option value="<?php echo htmlspecialchars($type); ?>" <?php echo $eventType === $type ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(str_replace('_', ' ', ucfirst($type))); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" value="<?php echo $username ? htmlspecialchars($username) : ''; ?>">
                </div>
                <div class="col-md-3">
                    <label for="from_date" class="form-label">From Date</label>
                    <input type="date" class="form-control" id="from_date" name="from_date" value="<?php echo $fromDate ? htmlspecialchars($fromDate) : ''; ?>">
                </div>
                <div class="col-md-3">
                    <label for="to_date" class="form-label">To Date</label>
                    <input type="date" class="form-control" id="to_date" name="to_date" value="<?php echo $toDate ? htmlspecialchars($toDate) : ''; ?>">
                </div>
                <div class="col-12">
                    <button type="button" id="apply-filters" class="btn btn-primary">Apply Filters</button>
                    <a href="/admin/security_logs.php" class="btn btn-secondary">Reset Filters</a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Logs Table -->
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5>Security Events</h5>
                <span class="badge bg-secondary" id="logs-count"><?php echo $totalLogs; ?> events found</span>
            </div>
        </div>
        <div class="card-body">
            <div id="logs-container">
                <?php if (count($logs) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Event Type</th>
                                    <th>Username</th>
                                    <th>IP Address</th>
                                    <th>Date/Time</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody id="logs-tbody">
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($log['id']); ?></td>
                                        <td>
                                            <span class="badge 
                                                <?php 
                                                switch ($log['event_type']) {
                                                    case SecurityLogger::EVENT_LOGIN_SUCCESS:
                                                        echo 'bg-success';
                                                        break;
                                                    case SecurityLogger::EVENT_LOGIN_FAILED:
                                                        echo 'bg-danger';
                                                        break;
                                                    case SecurityLogger::EVENT_ACCOUNT_LOCKED:
                                                        echo 'bg-warning';
                                                        break;
                                                    case SecurityLogger::EVENT_SUSPICIOUS_ACTIVITY:
                                                        echo 'bg-danger';
                                                        break;
                                                    default:
                                                        echo 'bg-info';
                                                        break;
                                                }
                                                ?>">
                                                <?php echo htmlspecialchars(str_replace('_', ' ', ucfirst($log['event_type']))); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (isset($log['username']) && $log['username']): ?>
                                                <?php echo htmlspecialchars($log['username']); ?>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                                        <td><?php echo htmlspecialchars($log['created_at']); ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-secondary view-details" 
                                                    data-bs-toggle="modal" data-bs-target="#detailsModal"
                                                    data-details='<?php echo htmlspecialchars($log['details']); ?>'>
                                                View Details
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div id="pagination-container">
                        <?php if ($totalPages > 1): ?>
                            <nav aria-label="Security logs pagination">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="javascript:void(0);" data-page="<?php echo $page - 1; ?>">
                                            Previous
                                        </a>
                                    </li>
                                    
                                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                        <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                            <a class="page-link" href="javascript:void(0);" data-page="<?php echo $i; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="javascript:void(0);" data-page="<?php echo $page + 1; ?>">
                                            Next
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">No security logs found matching your criteria.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Log Details -->
<div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailsModalLabel">Event Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <pre id="eventDetails" class="bg-light p-3 rounded"></pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    console.log('Security logs page loaded');
    
    // Function to load logs via AJAX
    function loadLogs(page = 1) {
        // Get filter values
        const eventType = document.getElementById('event_type').value;
        const username = document.getElementById('username').value;
        const fromDate = document.getElementById('from_date').value;
        const toDate = document.getElementById('to_date').value;
        
        // Build query string
        let queryString = `ajax=1&page=${page}`;
        if (eventType) queryString += `&event_type=${encodeURIComponent(eventType)}`;
        if (username) queryString += `&username=${encodeURIComponent(username)}`;
        if (fromDate) queryString += `&from_date=${encodeURIComponent(fromDate)}`;
        if (toDate) queryString += `&to_date=${encodeURIComponent(toDate)}`;
        
        // Update pagination in URL without reloading
        if (page > 1) {
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('page', page);
            history.replaceState(null, '', currentUrl.toString());
        } else {
            // Remove page parameter if page is 1
            const currentUrl = new URL(window.location.href);
            if (currentUrl.searchParams.has('page')) {
                currentUrl.searchParams.delete('page');
                history.replaceState(null, '', currentUrl.toString());
            }
        }
        
        console.log('AJAX request URL:', `/admin/security_logs.php?${queryString}`);
        
        // Show loading indicator
        document.getElementById('logs-container').innerHTML = '<div class="text-center p-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
        
        // Fetch logs
        fetch(`/admin/security_logs.php?${queryString}`)
            .then(response => {
                console.log('AJAX response status:', response.status);
                if (!response.ok) {
                    throw new Error(`Server responded with status ${response.status}`);
                }
                return response.text().then(text => {
                    console.log('AJAX raw response:', text.substring(0, 500) + (text.length > 500 ? '...' : ''));
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('JSON parse error:', e);
                        console.error('Response text:', text);
                        throw new Error('Invalid JSON in response');
                    }
                });
            })
            .then(data => {
                console.log('AJAX parsed data:', data);
                
                // Check if the response contains an error
                if (data.error) {
                    throw new Error(data.message || 'Unknown server error');
                }
                
                // Update logs count
                document.getElementById('logs-count').textContent = `${data.totalLogs} events found`;
                
                // If no logs found
                if (!data.logs || data.logs.length === 0) {
                    document.getElementById('logs-container').innerHTML = '<div class="alert alert-info">No security logs found matching your criteria.</div>';
                    return;
                }
                
                // Build table HTML
                let html = `
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Event Type</th>
                                    <th>Username</th>
                                    <th>IP Address</th>
                                    <th>Date/Time</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody id="logs-tbody">`;
                
                // Add rows
                data.logs.forEach(log => {
                    html += `<tr>
                        <td>${log.id}</td>
                        <td>
                            <span class="badge ${getEventBadgeClass(log.event_type)}">
                                ${formatEventType(log.event_type)}
                            </span>
                        </td>
                        <td>`;
                    
                    if (log.username) {
                        html += `${log.username}`;
                    } else {
                        html += `<span class="text-muted">N/A</span>`;
                    }
                    
                    html += `</td>
                        <td>${log.ip_address}</td>
                        <td>${log.created_at}</td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-secondary view-details" 
                                    data-bs-toggle="modal" data-bs-target="#detailsModal"
                                    data-details='${escapeDetailsJson(log.details)}'>
                                View Details
                            </button>
                        </td>
                    </tr>`;
                });
                
                html += `</tbody>
                        </table>
                    </div>`;
                
                // Add pagination if needed
                if (data.totalPages > 1) {
                    html += `
                    <nav aria-label="Security logs pagination">
                        <ul class="pagination justify-content-center">
                            <li class="page-item ${data.currentPage <= 1 ? 'disabled' : ''}">
                                <a class="page-link" href="javascript:void(0);" data-page="${data.currentPage - 1}">
                                    Previous
                                </a>
                            </li>`;
                    
                    for (let i = Math.max(1, data.currentPage - 2); i <= Math.min(data.totalPages, data.currentPage + 2); i++) {
                        html += `
                            <li class="page-item ${data.currentPage == i ? 'active' : ''}">
                                <a class="page-link" href="javascript:void(0);" data-page="${i}">
                                    ${i}
                                </a>
                            </li>`;
                    }
                    
                    html += `
                            <li class="page-item ${data.currentPage >= data.totalPages ? 'disabled' : ''}">
                                <a class="page-link" href="javascript:void(0);" data-page="${data.currentPage + 1}">
                                    Next
                                </a>
                            </li>
                        </ul>
                    </nav>`;
                }
                
                // Update container with new content
                document.getElementById('logs-container').innerHTML = html;
                
                // Add event listeners to pagination links
                document.querySelectorAll('.pagination .page-link').forEach(link => {
                    link.addEventListener('click', function() {
                        const page = parseInt(this.getAttribute('data-page'));
                        if (!isNaN(page)) {
                            loadLogs(page);
                        }
                    });
                });
                
                // Add event listeners to view details buttons
                initializeDetailViewHandlers();
            })
            .catch(error => {
                console.error('Error fetching security logs:', error);
                document.getElementById('logs-container').innerHTML = `<div class="alert alert-danger">
                    <strong>Error loading security logs:</strong> ${error.message}<br>
                    Please try refreshing the page or contact an administrator.
                </div>`;
            });
    }
    
    // Helper function to format event type
    function formatEventType(eventType) {
        return eventType.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    }
    
    // Helper function to get badge class for event type
    function getEventBadgeClass(eventType) {
        switch (eventType) {
            case 'login_success':
                return 'bg-success';
            case 'login_failed':
                return 'bg-danger';
            case 'account_locked':
                return 'bg-warning';
            case 'suspicious_activity':
                return 'bg-danger';
            default:
                return 'bg-info';
        }
    }
    
    // Helper function to safely escape details JSON for HTML attribute
    function escapeDetailsJson(details) {
        if (!details) return '';
        
        // If details is already a string, escape it
        if (typeof details === 'string') {
            return details.replace(/'/g, '&apos;').replace(/"/g, '&quot;');
        }
        
        // If it's an object, stringify it and escape
        try {
            return JSON.stringify(details).replace(/'/g, '&apos;').replace(/"/g, '&quot;');
        } catch (e) {
            console.error('Error stringifying details:', e);
            return '';
        }
    }
    
    // Initialize modal details view handlers
    function initializeDetailViewHandlers() {
        document.querySelectorAll('.view-details').forEach(button => {
            button.addEventListener('click', function() {
                const detailsAttr = this.getAttribute('data-details');
                try {
                    // First unescape the HTML entities
                    const unescaped = detailsAttr
                        .replace(/&quot;/g, '"')
                        .replace(/&apos;/g, "'");
                        
                    // Then parse the JSON
                    let detailsObj;
                    if (unescaped.startsWith('{') || unescaped.startsWith('[')) {
                        detailsObj = JSON.parse(unescaped);
                    } else {
                        // Try to parse it as a JSON string that might be doubly encoded
                        try {
                            detailsObj = JSON.parse(JSON.parse(unescaped));
                        } catch (e) {
                            detailsObj = unescaped;
                        }
                    }
                    
                    // Format the output
                    if (typeof detailsObj === 'object') {
                        document.getElementById('eventDetails').textContent = JSON.stringify(detailsObj, null, 2);
                    } else {
                        document.getElementById('eventDetails').textContent = detailsObj;
                    }
                } catch (e) {
                    console.error('Error parsing details:', e, detailsAttr);
                    document.getElementById('eventDetails').textContent = detailsAttr || 'No details available';
                }
            });
        });
    }
    
    // Document ready
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize details view handlers for the initial page load
        initializeDetailViewHandlers();
        
        // Add event listener to Apply Filters button
        document.getElementById('apply-filters').addEventListener('click', function(e) {
            e.preventDefault();
            
            // Get filter values from the form
            const eventType = document.getElementById('event_type').value;
            const username = document.getElementById('username').value;
            const fromDate = document.getElementById('from_date').value;
            const toDate = document.getElementById('to_date').value;
            
            // Update URL in browser history to reflect current filters (without actually navigating)
            const url = new URL(window.location.origin + '/admin/security_logs.php');
            if (eventType) url.searchParams.set('event_type', eventType);
            if (username) url.searchParams.set('username', username);
            if (fromDate) url.searchParams.set('from_date', fromDate);
            if (toDate) url.searchParams.set('to_date', toDate);
            
            // Update browser history
            history.pushState(null, '', url.toString());
            
            // Load via AJAX
            loadLogs(1);
        });
        
        // Add event listeners to pagination links for the initial page load
        document.querySelectorAll('.pagination .page-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const page = parseInt(this.getAttribute('data-page'));
                if (!isNaN(page)) {
                    loadLogs(page);
                }
            });
        });
        
        // Handle direct URL modification (e.g., when clicking browser back button)
        window.addEventListener('popstate', function(event) {
            // Extract current URL parameters
            const urlParams = new URLSearchParams(window.location.search);
            
            // Update form fields with URL parameters
            document.getElementById('event_type').value = urlParams.get('event_type') || '';
            document.getElementById('username').value = urlParams.get('username') || '';
            document.getElementById('from_date').value = urlParams.get('from_date') || '';
            document.getElementById('to_date').value = urlParams.get('to_date') || '';
            
            // Reload with current parameters
            loadLogs(parseInt(urlParams.get('page')) || 1);
        });
    });
</script>

<!-- Make sure Bootstrap JS is loaded -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>

<?php require_once '../includes/footer.php'; ?> 