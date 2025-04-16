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
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
$fromDate = isset($_GET['from_date']) ? $_GET['from_date'] : null;
$toDate = isset($_GET['to_date']) ? $_GET['to_date'] : null;

// For AJAX requests, only return the filtered data
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    // Get logs based on filters
    $logs = $securityLogger->getFilteredLogs($eventType, $userId, $fromDate, $toDate, $logsPerPage, $offset);
    $totalLogs = $securityLogger->countFilteredLogs($eventType, $userId, $fromDate, $toDate);
    $totalPages = ceil($totalLogs / $logsPerPage);
    
    // Prepare response
    $response = [
        'logs' => $logs,
        'totalLogs' => $totalLogs,
        'totalPages' => $totalPages,
        'currentPage' => $page
    ];
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Get logs based on filters
$logs = $securityLogger->getFilteredLogs($eventType, $userId, $fromDate, $toDate, $logsPerPage, $offset);
$totalLogs = $securityLogger->countFilteredLogs($eventType, $userId, $fromDate, $toDate);
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
        <p>User ID: <?php echo $userId ? htmlspecialchars($userId) : 'null'; ?></p>
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
            <form id="filter-form" method="GET" class="row g-3">
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
                    <label for="user_id" class="form-label">User ID</label>
                    <input type="number" class="form-control" id="user_id" name="user_id" value="<?php echo $userId ? htmlspecialchars($userId) : ''; ?>">
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
                                    <th>User ID</th>
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
                                            <?php if ($log['user_id']): ?>
                                                <a href="user_details.php?id=<?php echo htmlspecialchars($log['user_id']); ?>">
                                                    <?php echo htmlspecialchars($log['user_id']); ?>
                                                </a>
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
    
    // JavaScript to handle the detail view modal
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM content loaded');
        
        // Check if Bootstrap is loaded correctly
        if (typeof bootstrap === 'undefined') {
            console.error('Bootstrap JavaScript is not loaded');
            alert('Error: Bootstrap JavaScript is not loaded correctly. Please check your internet connection.');
        } else {
            console.log('Bootstrap JavaScript is loaded correctly');
        }
        
        // Initialize modal detail view handlers
        initializeDetailViewHandlers();
        
        // Initialize filter form
        const filterForm = document.getElementById('filter-form');
        const applyFilterBtn = document.getElementById('apply-filters');
        
        // Handle apply filters button click
        applyFilterBtn.addEventListener('click', function() {
            loadFilteredData(1);
        });
        
        // Handle pagination clicks
        document.addEventListener('click', function(e) {
            if (e.target.matches('.page-link') || e.target.closest('.page-link')) {
                const pageLink = e.target.matches('.page-link') ? e.target : e.target.closest('.page-link');
                const page = pageLink.getAttribute('data-page');
                if (page) {
                    loadFilteredData(page);
                    e.preventDefault();
                }
            }
        });
        
        // Function to load filtered data via AJAX
        function loadFilteredData(page) {
            // Show loading state
            document.getElementById('logs-container').innerHTML = '<div class="text-center p-5"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';
            
            // Get form data
            const formData = new FormData(filterForm);
            formData.append('ajax', '1');
            formData.append('page', page);
            
            // Convert FormData to URL parameters
            const params = new URLSearchParams(formData);
            
            // Fetch filtered data
            fetch(`/admin/security_logs.php?${params.toString()}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    updateLogsTable(data);
                })
                .catch(error => {
                    console.error('Error fetching data:', error);
                    document.getElementById('logs-container').innerHTML = '<div class="alert alert-danger">Error loading data. Please try again.</div>';
                });
        }
        
        // Function to update the logs table with new data
        function updateLogsTable(data) {
            const { logs, totalLogs, totalPages, currentPage } = data;
            
            // Update logs count
            document.getElementById('logs-count').textContent = `${totalLogs} events found`;
            
            let html = '';
            
            if (logs.length > 0) {
                html += `
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Event Type</th>
                                <th>User ID</th>
                                <th>IP Address</th>
                                <th>Date/Time</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>`;
                
                logs.forEach(log => {
                    let badgeClass = 'bg-info';
                    switch (log.event_type) {
                        case 'login_success':
                            badgeClass = 'bg-success';
                            break;
                        case 'login_failed':
                            badgeClass = 'bg-danger';
                            break;
                        case 'account_locked':
                            badgeClass = 'bg-warning';
                            break;
                        case 'suspicious_activity':
                            badgeClass = 'bg-danger';
                            break;
                    }
                    
                    const eventTypeDisplay = log.event_type.replace(/_/g, ' ');
                    const capitalizedEventType = eventTypeDisplay.charAt(0).toUpperCase() + eventTypeDisplay.slice(1);
                    
                    html += `
                    <tr>
                        <td>${log.id}</td>
                        <td>
                            <span class="badge ${badgeClass}">
                                ${capitalizedEventType}
                            </span>
                        </td>
                        <td>`;
                    
                    if (log.user_id) {
                        html += `<a href="user_details.php?id=${log.user_id}">${log.user_id}</a>`;
                    } else {
                        html += `<span class="text-muted">N/A</span>`;
                    }
                    
                    html += `
                        </td>
                        <td>${log.ip_address}</td>
                        <td>${log.created_at}</td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-secondary view-details" 
                                   data-bs-toggle="modal" data-bs-target="#detailsModal"
                                   data-details='${log.details}'>
                                View Details
                            </button>
                        </td>
                    </tr>`;
                });
                
                html += `
                        </tbody>
                    </table>
                </div>`;
                
                // Pagination
                if (totalPages > 1) {
                    html += `
                    <nav aria-label="Security logs pagination">
                        <ul class="pagination justify-content-center">
                            <li class="page-item ${currentPage <= 1 ? 'disabled' : ''}">
                                <a class="page-link" href="javascript:void(0);" data-page="${parseInt(currentPage) - 1}">
                                    Previous
                                </a>
                            </li>`;
                    
                    for (let i = Math.max(1, currentPage - 2); i <= Math.min(totalPages, parseInt(currentPage) + 2); i++) {
                        html += `
                            <li class="page-item ${currentPage == i ? 'active' : ''}">
                                <a class="page-link" href="javascript:void(0);" data-page="${i}">
                                    ${i}
                                </a>
                            </li>`;
                    }
                    
                    html += `
                            <li class="page-item ${currentPage >= totalPages ? 'disabled' : ''}">
                                <a class="page-link" href="javascript:void(0);" data-page="${parseInt(currentPage) + 1}">
                                    Next
                                </a>
                            </li>
                        </ul>
                    </nav>`;
                }
            } else {
                html = '<div class="alert alert-info">No security logs found matching your criteria.</div>';
            }
            
            document.getElementById('logs-container').innerHTML = html;
            
            // Re-initialize modal handlers for the new buttons
            initializeDetailViewHandlers();
        }
        
        // Function to initialize detail view handlers
        function initializeDetailViewHandlers() {
            const detailsButtons = document.querySelectorAll('.view-details');
            detailsButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const details = this.getAttribute('data-details');
                    try {
                        // Try to parse and pretty-print JSON
                        const parsedDetails = JSON.parse(details);
                        document.getElementById('eventDetails').textContent = JSON.stringify(parsedDetails, null, 2);
                    } catch (e) {
                        // If not valid JSON, just display as is
                        document.getElementById('eventDetails').textContent = details;
                    }
                });
            });
        }
    });
</script>

<!-- Make sure Bootstrap JS is loaded -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>

<?php require_once '../includes/footer.php'; ?> 