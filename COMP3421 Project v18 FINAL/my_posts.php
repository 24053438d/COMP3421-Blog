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

// Get user posts
$stmt = $post->getPostsByUser($_SESSION['user_id']);

require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-md-10 offset-md-1">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>My Posts</h1>
            <a href="create_post.php" class="btn btn-primary">Create New Post</a>
        </div>
        
        <?php if ($stmt->rowCount() > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                        <tr>
                            <td>
                                <a href="view_post.php?id=<?php echo $row['id']; ?>">
                                    <?php echo htmlspecialchars($row['title']); ?>
                                </a>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $row['status'] == 'published' ? 'success' : ($row['status'] == 'draft' ? 'warning' : 'secondary'); ?>">
                                    <?php echo ucfirst($row['status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="view_post.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info">View</a>
                                <a href="edit_post.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                You haven't created any posts yet. <a href="create_post.php">Create your first post</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 