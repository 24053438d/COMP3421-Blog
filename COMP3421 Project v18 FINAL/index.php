<?php
require_once 'config/database.php';
require_once 'classes/Post.php';
require_once 'classes/Analytics.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$database = new Database();
$db = $database->getConnection();
$post = new Post($db);

// Get published posts
$stmt = $post->read();

// Log this page view for analytics
$analytics = new Analytics($db);
$analytics->logPageView("index.php");

require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-md-8">
        <h1 class="mb-4">Latest Posts</h1>
        
        <?php if ($stmt->rowCount() > 0): ?>
            <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                <article class="card mb-4">
                    <div class="card-body">
                        <h2 class="card-title">
                            <a href="view_post.php?id=<?php echo $row['id']; ?>" class="text-decoration-none">
                                <?php echo htmlspecialchars($row['title']); ?>
                            </a>
                        </h2>
                        <p class="card-text text-muted">
                            By <?php echo htmlspecialchars($row['username']); ?> | 
                            <?php echo date('F j, Y', strtotime($row['created_at'])); ?>
                        </p>
                        <p class="card-text">
                            <?php 
                            // Display a preview (first 200 characters)
                            $preview = substr($row['content'], 0, 200);
                            if (strlen($row['content']) > 200) {
                                $preview .= '...';
                            }
                            echo htmlspecialchars($preview);
                            ?>
                        </p>
                        <a href="view_post.php?id=<?php echo $row['id']; ?>" class="btn btn-primary">Read More</a>
                    </div>
                </article>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="alert alert-info">No posts available yet.</div>
        <?php endif; ?>
    </div>
    
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">About</div>
            <div class="card-body">
                <p>Welcome to our Blog Platform! This is a simple CMS where users can create and comment on posts.</p>
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <a href="register.php" class="btn btn-primary">Sign Up</a>
                    <a href="login.php" class="btn btn-outline-primary">Log In</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 