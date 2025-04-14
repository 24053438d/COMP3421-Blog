<?php
require_once 'config/database.php';
require_once 'classes/User.php';
require_once 'classes/Post.php';
require_once 'classes/Comment.php';
require_once 'middleware/AuthMiddleware.php';
require_once 'classes/Analytics.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$database = new Database();
$db = $database->getConnection();
$post = new Post($db);
$comment = new Comment($db);
$analytics = new Analytics($db);

$errors = [];
$comment_success = false;

// Get post ID from URL
$post_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$post_id) {
    header("Location: index.php");
    exit;
}

// Get post data
$stmt = $post->read($post_id);
$post_data = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if post exists
if (!$post_data) {
    header("Location: index.php");
    exit;
}

// If post is not published, only let the author or admin view it
if ($post_data['status'] !== 'published') {
    if (!isset($_SESSION['user_id']) || 
        ($_SESSION['user_id'] != $post_data['user_id'] && 
         (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'))) {
        header("Location: index.php");
        exit;
    }
}

// Handle new comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    // CSRF protection
    if (!AuthMiddleware::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Invalid form submission";
    } else {
        if (empty($_POST['content'])) {
            $errors[] = "Comment cannot be empty";
        } else {
            $comment->content = htmlspecialchars(strip_tags($_POST['content']));
            $comment->post_id = $post_id;
            $comment->user_id = $_SESSION['user_id'];
            
            // Auto-approve comments for admins, otherwise set to pending
            $comment->status = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') ? 'approved' : 'pending';
            
            if ($comment->create()) {
                $comment_success = true;
                
                // Track comment submission event
                $analytics->trackEvent('comment_submit', [
                    'post_id' => $post_id,
                    'user_id' => $_SESSION['user_id']
                ]);
            } else {
                $errors[] = "Failed to add comment";
            }
        }
    }
}

// Get comments
$comments = $comment->getCommentsByPost($post_id);

// Log this view for analytics
$analytics->logPageView("view_post.php?id=$post_id");

require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-md-8 offset-md-2">
        <!-- Post status badge (if not published) -->
        <?php if ($post_data['status'] !== 'published'): ?>
            <div class="alert alert-warning">
                This post is currently in <strong><?php echo ucfirst($post_data['status']); ?></strong> status and is not visible to the public.
            </div>
        <?php endif; ?>
        
        <!-- Post content -->
        <article class="blog-post">
            <h1 class="display-5"><?php echo htmlspecialchars($post_data['title']); ?></h1>
            <p class="text-muted">
                By <?php echo htmlspecialchars($post_data['username']); ?> | 
                <?php echo date('F j, Y', strtotime($post_data['created_at'])); ?>
                <?php if ($post_data['updated_at'] != $post_data['created_at']): ?>
                    | Updated: <?php echo date('F j, Y', strtotime($post_data['updated_at'])); ?>
                <?php endif; ?>
            </p>
            
            <div class="mb-4">
                <?php 
                // Simple formatting: Convert double newlines to paragraphs
                $paragraphs = explode("\n\n", $post_data['content']);
                foreach ($paragraphs as $paragraph) {
                    if (trim($paragraph)) {
                        echo '<p>' . nl2br(htmlspecialchars($paragraph)) . '</p>';
                    }
                }
                ?>
            </div>
            
            <!-- Edit/Delete buttons for author or admin -->
            <?php if (isset($_SESSION['user_id']) && 
                     ($_SESSION['user_id'] == $post_data['user_id'] || 
                     (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'))): ?>
                <div class="mb-4">
                    <a href="edit_post.php?id=<?php echo $post_id; ?>" class="btn btn-primary">Edit Post</a>
                    
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <form method="POST" action="admin/dashboard.php" style="display: inline-block;">
                            <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                            <button type="submit" name="delete_post" class="btn btn-danger" onclick="return confirm('Are you sure?')">Delete Post</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </article>
        
        <hr class="my-5">
        
        <!-- Comments section -->
        <section class="comments-section">
            <h3>Comments</h3>
            
            <!-- Comment form for logged-in users -->
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <?php if ($comment_success): ?>
                            <div class="alert alert-success">
                                Comment added successfully! 
                                <?php if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'): ?>
                                    It will be visible after approval.
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo AuthMiddleware::generateCSRFToken(); ?>">
                            
                            <div class="mb-3">
                                <label for="content" class="form-label">Add a comment</label>
                                <textarea class="form-control" id="content" name="content" rows="3" required></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Submit Comment</button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info mb-4">
                    <a href="login.php">Log in</a> to leave a comment.
                </div>
            <?php endif; ?>
            
            <!-- Display comments -->
            <div class="comments-list">
                <?php if ($comments->rowCount() > 0): ?>
                    <?php while ($row = $comments->fetch(PDO::FETCH_ASSOC)): ?>
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <strong><?php echo htmlspecialchars($row['username']); ?></strong>
                                    <small class="text-muted"><?php echo date('M d, Y g:i a', strtotime($row['created_at'])); ?></small>
                                </div>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($row['content'])); ?></p>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-muted">No comments yet.</p>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 