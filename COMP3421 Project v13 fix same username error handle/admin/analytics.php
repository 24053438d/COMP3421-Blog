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

// Set default values
$defaultDays = 30;
$days = $defaultDays;
$specificDateRange = false;
$startDate = '';
$endDate = '';

// Handle filter form submission
if (isset($_GET['filter_type'])) {
    if ($_GET['filter_type'] == 'days' && isset($_GET['days']) && is_numeric($_GET['days'])) {
        $days = intval($_GET['days']);
    } elseif ($_GET['filter_type'] == 'date_range' && isset($_GET['start_date']) && isset($_GET['end_date'])) {
        $startDate = $_GET['start_date'];
        $endDate = $_GET['end_date'];
        $specificDateRange = true;
    }
}

// Get analytics data based on filter
if ($specificDateRange) {
    $pageViews = $analytics->getPageViewsForDateRange($startDate, $endDate);
    $userActivity = $analytics->getUserActivityForDateRange($startDate, $endDate);
    $commentEvents = $analytics->getEventCountsForDateRange('comment_submit', $startDate, $endDate);
    $postEvents = $analytics->getEventCountsForDateRange('post_submit', $startDate, $endDate);
    $performanceData = $analytics->getPerformanceMetricsForDateRange($startDate, $endDate);
    
    if ($analytics->hasGeoData()) {
        $geoStats = $analytics->getGeoStatsForDateRange($startDate, $endDate);
        if (empty($geoStats)) {
            $geoStats = $analytics->getSimpleGeoStats();
        }
    } else {
        $geoStats = [];
    }
} else {
    $pageViews = $analytics->getPageViewsCount($days);
    $userActivity = $analytics->getUserActivity($days);
    $commentEvents = $analytics->getEventCounts('comment_submit', $days);
    $postEvents = $analytics->getEventCounts('post_submit', $days);
    $performanceData = $analytics->getPerformanceMetrics($days);
    
    // Try to get geo stats using different methods
    $hasGeoData = $analytics->hasGeoData();
    $geoStats = [];
    
    if ($hasGeoData) {
        // First try the normal method
        $geoStats = $analytics->getGeoStats($days);
        
        // If that returns no data, try the simple method
        if (empty($geoStats)) {
            $geoStats = $analytics->getSimpleGeoStats();
        }
    } else {
        // No meaningful geo data exists
        $geoStats = [];
    }
}

// Get top posts (no date filter for this)
$topPosts = $analytics->getTopPosts(10);

// Prepare data for charts
$dates = [];
$views = [];
$users = [];
$commentCounts = [];
$postCounts = [];
$performanceTimes = [];

// Process page views and user activity
foreach ($pageViews as $data) {
    $dates[] = $data['date'];
    $views[] = $data['views'];
}

foreach ($userActivity as $data) {
    $users[] = $data['active_users'];
}

// Process comment submissions
$commentDates = [];
foreach ($commentEvents as $data) {
    $commentDates[$data['date']] = $data['count'];
}

// Process post submissions
$postDates = [];
foreach ($postEvents as $data) {
    $postDates[$data['date']] = $data['count'];
}

// Fill in missing dates with zeros
foreach ($dates as $date) {
    $commentCounts[] = $commentDates[$date] ?? 0;
    $postCounts[] = $postDates[$date] ?? 0;
}

// Process performance data
foreach ($performanceData as $data) {
    $performanceTimes[] = $data['load_time'];
}

// Process geo data
$countryLabels = [];
$countryCounts = [];
foreach ($geoStats as $stat) {
    $countryLabels[] = $stat['country_code'];
    $countryCounts[] = $stat['count'];
}

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
    'PV' => 'Private Network',
    'UN' => 'Unknown'
];

// Convert country codes to full names for display
$countryLabelsFormatted = array_map(function($code) use ($countryNames) {
    return isset($countryNames[$code]) ? $countryNames[$code] : $code;
}, $countryLabels);

require_once '../includes/header.php';

// Calculate the default date values for the date range picker
$defaultEndDate = date('Y-m-d');
$defaultStartDate = date('Y-m-d', strtotime('-' . $defaultDays . ' days'));
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h2>Analytics Dashboard</h2>
        <p class="text-muted">View site performance and user engagement metrics</p>
    </div>
</div>

<!-- Filter Form -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5>Filter Options</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-center">
                    <div class="col-md-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="filter_type" id="filter_days" value="days" <?php echo (!$specificDateRange) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="filter_days">
                                Last X Days
                            </label>
                        </div>
                        <select class="form-select" name="days" id="days_select" <?php echo ($specificDateRange) ? 'disabled' : ''; ?>>
                            <option value="7" <?php echo ($days == 7) ? 'selected' : ''; ?>>7 days</option>
                            <option value="14" <?php echo ($days == 14) ? 'selected' : ''; ?>>14 days</option>
                            <option value="30" <?php echo ($days == 30 && !$specificDateRange) ? 'selected' : ''; ?>>30 days</option>
                            <option value="60" <?php echo ($days == 60) ? 'selected' : ''; ?>>60 days</option>
                            <option value="90" <?php echo ($days == 90) ? 'selected' : ''; ?>>90 days</option>
                        </select>
                    </div>
                    <div class="col-md-7">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="filter_type" id="filter_date_range" value="date_range" <?php echo ($specificDateRange) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="filter_date_range">
                                Specific Date Range
                            </label>
                        </div>
                        <div class="row">
                            <div class="col-md-5">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" 
                                       value="<?php echo $specificDateRange ? $startDate : $defaultStartDate; ?>"
                                       <?php echo (!$specificDateRange) ? 'disabled' : ''; ?>>
                            </div>
                            <div class="col-md-5">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" 
                                       value="<?php echo $specificDateRange ? $endDate : $defaultEndDate; ?>"
                                       <?php echo (!$specificDateRange) ? 'disabled' : ''; ?>>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5>Page Views <?php echo $specificDateRange ? "(" . $startDate . " to " . $endDate . ")" : "(Last " . $days . " Days)"; ?></h5>
            </div>
            <div class="card-body">
                <?php if (count($views) > 0): ?>
                    <canvas id="pageViewsChart" height="100"></canvas>
                <?php else: ?>
                    <div class="alert alert-info">No page view data available for the selected period.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Top Posts</h5>
            </div>
            <div class="card-body">
                <?php if (count($topPosts) > 0): ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($topPosts as $post): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?php echo htmlspecialchars($post['title']); ?>
                                <span class="badge bg-primary rounded-pill"><?php echo $post['views']; ?> views</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="alert alert-info">No post view data available yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Active Users <?php echo $specificDateRange ? "(" . $startDate . " to " . $endDate . ")" : "(Last " . $days . " Days)"; ?></h5>
            </div>
            <div class="card-body">
                <?php if (count($users) > 0): ?>
                    <canvas id="userActivityChart" height="250"></canvas>
                <?php else: ?>
                    <div class="alert alert-info">No user activity data available for the selected period.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>User Interactions <?php echo $specificDateRange ? "(" . $startDate . " to " . $endDate . ")" : "(Last " . $days . " Days)"; ?></h5>
            </div>
            <div class="card-body">
                <?php if (count($commentCounts) > 0 || count($postCounts) > 0): ?>
                    <canvas id="interactionsChart" height="250"></canvas>
                <?php else: ?>
                    <div class="alert alert-info">No user interaction data available for the selected period.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Page Load Performance <?php echo $specificDateRange ? "(" . $startDate . " to " . $endDate . ")" : "(Last " . $days . " Days)"; ?></h5>
            </div>
            <div class="card-body">
                <?php if (count($performanceTimes) > 0): ?>
                    <canvas id="performanceChart" height="250"></canvas>
                <?php else: ?>
                    <div class="alert alert-info">No performance data available for the selected period.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5>Geographical Distribution <?php echo $specificDateRange ? "(" . $startDate . " to " . $endDate . ")" : "(Last " . $days . " Days)"; ?></h5>
            </div>
            <div class="card-body">
                <?php if (count($countryLabels) > 0): ?>
                    <div style="max-width: 80%; margin: 0 auto;">
                        <canvas id="geoChart" height="200"></canvas>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <strong>No geographical data available for the selected period.</strong>
                        <hr>
                        <p><strong>Troubleshooting Steps:</strong></p>
                        <ol>
                            <li>Use the <a href="debug_analytics.php">Debug Tool</a> to reset and repopulate geo data</li>
                            <li>Check your Hostinger PHP Settings:
                                <ul>
                                    <li>Set <code>allow_url_fopen = On</code> to enable external API access</li>
                                    <li>Set <code>max_execution_time = 60</code> to allow time for IP lookups</li>
                                    <li>Set <code>default_socket_timeout = 30</code> for API connections</li>
                                </ul>
                            </li>
                            <li>Verify in phpMyAdmin that the geo_data table has records with country codes other than 'UN'</li>
                        </ol>
                        
                        <?php if (!$hasGeoData): ?>
                            <div class="alert alert-warning">
                                <strong>Issue detected:</strong> Your geo_data table has no records with meaningful country codes (non-'UN').
                            </div>
                        <?php endif; ?>
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
                <h5>Analytics Implementation</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <h6>About Our Analytics Implementation:</h6>
                    <p>This dashboard shows analytics data collected directly from our database and rendered using Chart.js.</p>
                    <p>While Grafana could be used for more advanced visualization, our hosting environment doesn't support external database connections. This built-in dashboard provides all the required analytics features:</p>
                </div>
                
                <h6 class="mt-4">Implemented Analytics Features:</h6>
                <ul>
                    <li><strong>Page Views & User Interactions</strong> - Tracking how users navigate through the app, which pages they visit, and which actions they take (button clicks, form submissions)</li>
                    <li><strong>Custom Events</strong> - Tracking important user interactions like comment submissions and post creations</li>
                    <li><strong>Performance Metrics</strong> - Monitoring page load times</li>
                    <li><strong>Geographical Metrics</strong> - Basic tracking of where the app is being used (based on country detection from IP addresses)</li>
                </ul>
                
                <a href="admin/debug_analytics.php" class="btn btn-outline-info mt-3">Analytics Debug Tool</a>
            </div>
        </div>
    </div>
</div>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Page Views Chart
    const pageViewsCtx = document.getElementById('pageViewsChart').getContext('2d');
    const pageViewsChart = new Chart(pageViewsCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($dates); ?>,
            datasets: [{
                label: 'Page Views',
                data: <?php echo json_encode($views); ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 2,
                tension: 0.1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            responsive: true
        }
    });
    
    // User Activity Chart
    const userActivityCtx = document.getElementById('userActivityChart').getContext('2d');
    const userActivityChart = new Chart(userActivityCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($dates); ?>,
            datasets: [{
                label: 'Active Users',
                data: <?php echo json_encode($users); ?>,
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
    
    // User Interactions Chart
    const interactionsCtx = document.getElementById('interactionsChart').getContext('2d');
    const interactionsChart = new Chart(interactionsCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($dates); ?>,
            datasets: [
                {
                    label: 'Comment Submissions',
                    data: <?php echo json_encode($commentCounts); ?>,
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 2,
                    tension: 0.1
                },
                {
                    label: 'Post Submissions',
                    data: <?php echo json_encode($postCounts); ?>,
                    backgroundColor: 'rgba(153, 102, 255, 0.2)',
                    borderColor: 'rgba(153, 102, 255, 1)',
                    borderWidth: 2,
                    tension: 0.1
                }
            ]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            },
            responsive: true
        }
    });
    
    // Performance Chart
    const performanceCtx = document.getElementById('performanceChart').getContext('2d');
    const performanceChart = new Chart(performanceCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($dates); ?>,
            datasets: [{
                label: 'Page Load Time (s)',
                data: <?php echo json_encode($performanceTimes); ?>,
                backgroundColor: 'rgba(255, 159, 64, 0.2)',
                borderColor: 'rgba(255, 159, 64, 1)',
                borderWidth: 2,
                tension: 0.1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            responsive: true
        }
    });
    
    <?php if (count($countryLabels) > 0): ?>
    // Geographical Distribution Chart
    const geoCtx = document.getElementById('geoChart').getContext('2d');
    const backgroundColors = [
        'rgba(54, 162, 235, 0.7)',
        'rgba(255, 99, 132, 0.7)',
        'rgba(75, 192, 192, 0.7)',
        'rgba(153, 102, 255, 0.7)',
        'rgba(255, 159, 64, 0.7)',
        'rgba(255, 205, 86, 0.7)',
        'rgba(201, 203, 207, 0.7)',
        'rgba(255, 99, 71, 0.7)',
        'rgba(50, 205, 50, 0.7)',
        'rgba(138, 43, 226, 0.7)'
    ];
    
    const geoChart = new Chart(geoCtx, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode($countryLabelsFormatted); ?>,
            datasets: [{
                data: <?php echo json_encode($countryCounts); ?>,
                backgroundColor: backgroundColors.slice(0, <?php echo count($countryLabels); ?>)
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            aspectRatio: 2,  // Increase this value to make the chart smaller
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        boxWidth: 15  // Smaller legend boxes
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.formattedValue;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((context.raw / total) * 100);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
    <?php endif; ?>
</script>

<!-- Add JavaScript for form interaction -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterDaysRadio = document.getElementById('filter_days');
    const filterDateRangeRadio = document.getElementById('filter_date_range');
    const daysSelect = document.getElementById('days_select');
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    
    // Function to update form field states
    function updateFormState() {
        if (filterDaysRadio.checked) {
            daysSelect.disabled = false;
            startDateInput.disabled = true;
            endDateInput.disabled = true;
        } else if (filterDateRangeRadio.checked) {
            daysSelect.disabled = true;
            startDateInput.disabled = false;
            endDateInput.disabled = false;
        }
    }
    
    // Add event listeners
    filterDaysRadio.addEventListener('change', updateFormState);
    filterDateRangeRadio.addEventListener('change', updateFormState);
    
    // Initialize form state
    updateFormState();
});
</script>

<?php require_once '../includes/footer.php'; ?> 