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

// Prepare data for charts
$dates = [];
$views = [];
$users = [];

foreach ($pageViews as $data) {
    $dates[] = $data['date'];
    $views[] = $data['views'];
}

foreach ($userActivity as $data) {
    $users[] = $data['active_users'];
}

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
                <canvas id="pageViewsChart" height="100"></canvas>
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
                <ul class="list-group list-group-flush">
                    <?php foreach ($topPosts as $post): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <?php echo htmlspecialchars($post['title']); ?>
                            <span class="badge bg-primary rounded-pill"><?php echo $post['views']; ?> views</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Active Users (Last 30 Days)</h5>
            </div>
            <div class="card-body">
                <canvas id="userActivityChart" height="250"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5>Grafana Integration</h5>
            </div>
            <div class="card-body">
                <p>For more detailed analytics, view the <a href="/grafana/" target="_blank">Grafana Dashboard</a>.</p>
                
                <div class="alert alert-info">
                    <h6>Setting up Grafana Integration:</h6>
                    <ol>
                        <li>Install Grafana on your server or use Grafana Cloud</li>
                        <li>Configure a MySQL data source pointing to your blog database</li>
                        <li>Create dashboards for post views, user activity, and comment trends</li>
                        <li>Embed the dashboards using iframe or use direct links</li>
                    </ol>
                    <p class="mb-0">
                        Grafana configuration files and dashboard templates can be found in the 
                        <code>/grafana-config/</code> directory.
                    </p>
                </div>
                
                <!-- Example of embedding a Grafana dashboard -->
                <!-- <iframe src="http://grafana-server:3000/d-solo/abc123/blog-analytics?orgId=1&panelId=1" width="100%" height="300" frameborder="0"></iframe> -->
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
</script>

<?php require_once '../includes/footer.php'; ?> 