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
        
        // Set user ID and status
        $post->user_id = $_SESSION['user_id'];
        $post->status = $_POST['status'];
        
        // If no errors, create post
        if (empty($errors)) {
            if ($post->create()) {
                $success = true;
            } else {
                $errors[] = "Failed to create post";
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
                <h4>Create New Post</h4>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        Post created successfully! <a href="index.php">View all posts</a>
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
                
                <?php if (!$success): ?>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo AuthMiddleware::generateCSRFToken(); ?>">
                        
                        <div class="mb-3">
                            <label for="title" class="form-label">Title</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="content" class="form-label">Content</label>
                            <textarea class="form-control" id="content" name="content" rows="10" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-control" id="status" name="status">
                                <option value="draft">Draft</option>
                                <option value="published">Publish Now</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Create Post</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 