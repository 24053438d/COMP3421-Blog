<?php
require_once '../config/database.php';
require_once '../classes/User.php';
require_once '../classes/Post.php';
require_once '../classes/Comment.php';
require_once '../middleware/AuthMiddleware.php';

// Ensure the user is an admin
AuthMiddleware::requireAdmin();

$database = new Database();
$db = $database->getConnection();

$post = new Post($db);
$comment = new Comment($db);
$user = new User($db);

// Get posts for management
$stmt = $db->prepare("SELECT p.*, u.username FROM posts p 
                     LEFT JOIN users u ON p.user_id = u.id 
                     ORDER BY p.created_at DESC");
$stmt->execute();
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get pending comments for moderation
$stmt = $db->prepare("SELECT c.*, u.username, p.title as post_title 
                     FROM comments c 
                     LEFT JOIN users u ON c.user_id = u.id 
                     LEFT JOIN posts p ON c.post_id = p.id
                     WHERE c.status = 'pending'
                     ORDER BY c.created_at DESC");
$stmt->execute();
$pending_comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process comment approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_action'])) {
    $comment_id = $_POST['comment_id'];
    $action = $_POST['comment_action'];
    
    if ($action === 'approve') {
        $comment->approve($comment_id);
    } elseif ($action === 'reject') {
        $comment->delete($comment_id);
    }
    
    header("Location: dashboard.php");
    exit;
}

// Process post deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post'])) {
    $post->id = $_POST['post_id'];
    if ($post->delete()) {
        header("Location: dashboard.php?deleted=1");
        exit;
    }
}

require_once '../includes/header.php';
?>

<h2>Admin Dashboard</h2>

<?php if (isset($_GET['deleted']) && $_GET['deleted'] == 1): ?>
    <div class="alert alert-success">Post deleted successfully!</div>
<?php endif; ?>

<ul class="nav nav-tabs mb-4" id="adminTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="posts-tab" data-bs-toggle="tab" data-bs-target="#posts" type="button" role="tab">
            Manage Posts
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="comments-tab" data-bs-toggle="tab" data-bs-target="#comments" type="button" role="tab">
            Pending Comments (<?php echo count($pending_comments); ?>)
        </button>
    </li>
</ul>

<div class="tab-content" id="adminTabsContent">
    <div class="tab-pane fade show active" id="posts" role="tabpanel">
        <h3>Manage Posts</h3>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($posts as $post_item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($post_item['title']); ?></td>
                        <td><?php echo htmlspecialchars($post_item['username']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($post_item['created_at'])); ?></td>
                        <td>
                            <span class="badge bg-<?php echo $post_item['status'] == 'published' ? 'success' : ($post_item['status'] == 'draft' ? 'warning' : 'secondary'); ?>">
                                <?php echo ucfirst($post_item['status']); ?>
                            </span>
                        </td>
                        <td>
                            <a href="../view_post.php?id=<?php echo $post_item['id']; ?>" class="btn btn-sm btn-info">View</a>
                            <a href="../edit_post.php?id=<?php echo $post_item['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                            <form method="POST" style="display: inline-block;">
                                <input type="hidden" name="post_id" value="<?php echo $post_item['id']; ?>">
                                <button type="submit" name="delete_post" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="tab-pane fade" id="comments" role="tabpanel">
        <h3>Comments Pending Approval</h3>
        <?php if (empty($pending_comments)): ?>
            <p>No comments pending approval.</p>
        <?php else: ?>
            <div class="list-group">
                <?php foreach ($pending_comments as $comment): ?>
                    <div class="list-group-item">
                        <div class="d-flex w-100 justify-content-between">
                            <h5 class="mb-1">Comment on: <?php echo htmlspecialchars($comment['post_title']); ?></h5>
                            <small><?php echo date('M d, Y g:i a', strtotime($comment['created_at'])); ?></small>
                        </div>
                        <p class="mb-1"><?php echo htmlspecialchars($comment['content']); ?></p>
                        <small>By: <?php echo htmlspecialchars($comment['username']); ?></small>
                        <div class="mt-2">
                            <form method="POST" style="display:inline-block;">
                                <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                <button type="submit" name="comment_action" value="approve" class="btn btn-sm btn-success">Approve</button>
                                <button type="submit" name="comment_action" value="reject" class="btn btn-sm btn-danger">Reject</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 