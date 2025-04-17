<?php
require_once 'config/database.php';
require_once 'classes/User.php';
require_once 'classes/Post.php';
require_once 'middleware/AuthMiddleware.php';

// Ensure user is logged in
AuthMiddleware::requireLogin();

$database = new Database();
$db = $database->getConnection();
$post = new Post($db);

$errors = [];
$success = false;
$post_data = null;

// Get post ID from URL
$post_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$post_id) {
    header("Location: index.php");
    exit;
}

// Get post data
$stmt = $post->read($post_id);
$post_data = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if post exists and user has permission to edit
if (!$post_data || ($post_data['user_id'] != $_SESSION['user_id'] && $_SESSION['role'] !== 'admin')) {
    header("Location: index.php");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!AuthMiddleware::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Invalid form submission";
    } else {
        // Validate title
        if (empty($_POST['title'])) {
            $errors[] = "Title is required";
        } elseif (strlen($_POST['title']) < 5) {
            $errors[] = "Title must be at least 5 characters";
        } else {
            $post->title = htmlspecialchars(strip_tags($_POST['title']));
        }
        
        // Validate content
        if (empty($_POST['content'])) {
            $errors[] = "Content is required";
        } elseif (strlen($_POST['content']) < 10) {
            $errors[] = "Content must be at least 10 characters";
        } else {
            $post->content = htmlspecialchars(strip_tags($_POST['content']));
        }
        
        // Set post ID and status
        $post->id = $post_id;
        $post->status = $_POST['status'];
        
        // If no errors, update post
        if (empty($errors)) {
            if ($post->update()) {
                $success = true;
                // Refresh post data
                $stmt = $post->read($post_id);
                $post_data = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $errors[] = "Failed to update post";
            }
        }
    }
}

require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <h4>Edit Post</h4>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        Post updated successfully! <a href="view_post.php?id=<?php echo $post_id; ?>">View post</a>
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
                        <label for="title" class="form-label">Title</label>
                        <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($post_data['title']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="content" class="form-label">Content</label>
                        <textarea class="form-control" id="content" name="content" rows="10" required><?php echo htmlspecialchars($post_data['content']); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-control" id="status" name="status">
                            <option value="draft" <?php echo $post_data['status'] == 'draft' ? 'selected' : ''; ?>>Draft</option>
                            <option value="published" <?php echo $post_data['status'] == 'published' ? 'selected' : ''; ?>>Published</option>
                            <option value="archived" <?php echo $post_data['status'] == 'archived' ? 'selected' : ''; ?>>Archived</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Update Post</button>
                    <a href="view_post.php?id=<?php echo $post_id; ?>" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 