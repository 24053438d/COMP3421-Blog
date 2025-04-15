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

// Get analytics data
$pageViews = $analytics->getPageViewsCount(30);
$topPosts = $analytics->getTopPosts(10);
$userActivity = $analytics->getUserActivity(30);
$commentEvents = $analytics->getEventCounts('comment_submit', 30);
$postEvents = $analytics->getEventCounts('post_submit', 30);
$performanceData = $analytics->getPerformanceMetrics(30);

// Try to get geo stats using different methods
$hasGeoData = $analytics->hasGeoData();
$geoStats = [];

if ($hasGeoData) {
    // First try the normal method
    $geoStats = $analytics->getGeoStats(30);
    
    // If that returns no data, try the simple method
    if (empty($geoStats)) {
        $geoStats = $analytics->getSimpleGeoStats();
    }
} else {
    // No meaningful geo data exists
    $geoStats = [];
}

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
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h2>Analytics Dashboard</h2>
        <p class="text-muted">View site performance and user engagement metrics</p>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5>Page Views (Last 30 Days)</h5>
            </div>
            <div class="card-body">
                <?php if (count($views) > 0): ?>
                    <canvas id="pageViewsChart" height="100"></canvas>
                <?php else: ?>
                    <div class="alert alert-info">No page view data available yet.</div>
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
                <h5>Active Users (Last 30 Days)</h5>
            </div>
            <div class="card-body">
                <?php if (count($users) > 0): ?>
                    <canvas id="userActivityChart" height="250"></canvas>
                <?php else: ?>
                    <div class="alert alert-info">No user activity data available yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>User Interactions (Last 30 Days)</h5>
            </div>
            <div class="card-body">
                <?php if (count($commentCounts) > 0 || count($postCounts) > 0): ?>
                    <canvas id="interactionsChart" height="250"></canvas>
                <?php else: ?>
                    <div class="alert alert-info">No user interaction data available yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Page Load Performance</h5>
            </div>
            <div class="card-body">
                <?php if (count($performanceTimes) > 0): ?>
                    <canvas id="performanceChart" height="250"></canvas>
                <?php else: ?>
                    <div class="alert alert-info">No performance data available yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5>Geographical Distribution</h5>
            </div>
            <div class="card-body">
                <?php if (count($countryLabels) > 0): ?>
                    <div style="max-width: 80%; margin: 0 auto;">
                        <canvas id="geoChart" height="200"></canvas>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <strong>No geographical data available yet.</strong>
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

<?php require_once '../includes/footer.php'; ?> 