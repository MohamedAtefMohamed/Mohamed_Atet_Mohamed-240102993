<?php
require_once __DIR__ . '/includes/auth.php';
requireAdmin();

require_once __DIR__ . '/config/database.php';
$db = getDB();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $review_id = (int)($_POST['review_id'] ?? 0);
    
    if ($review_id && in_array($action, ['approve', 'reject', 'delete'])) {
        try {
            if ($action === 'delete') {
                $stmt = $db->prepare("DELETE FROM reviews WHERE id = ?");
                $stmt->execute([$review_id]);
            } else {
                $status = $action === 'approve' ? 'approved' : 'rejected';
                $stmt = $db->prepare("UPDATE reviews SET status = ? WHERE id = ?");
                $stmt->execute([$status, $review_id]);
            }
            $message = "Review {$action}d successfully";
        } catch (PDOException $e) {
            $message = "Error: " . $e->getMessage();
        }
    }
}

// Get all reviews with movie and user info
$stmt = $db->prepare("SELECT r.*, m.title as movie_title, u.username 
                      FROM reviews r 
                      JOIN movies m ON r.movie_id = m.id 
                      JOIN users u ON r.user_id = u.id 
                      ORDER BY r.created_at DESC");
$stmt->execute();
$reviews = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Review Moderation</title>
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="app.css">
    <style>
        .admin-container { max-width: 1400px; margin: 80px auto 40px; padding: 20px; }
        .admin-header { margin-bottom: 30px; }
        .admin-header h1 { margin: 0 0 10px; }
        .status-badge { display: inline-block; padding: 5px 10px; border-radius: 5px; font-size: 0.9em; }
        .status-pending { background: #ffc107; color: #000; }
        .status-approved { background: #28a745; color: #fff; }
        .status-rejected { background: #dc3545; color: #fff; }
        .reviews-table { width: 100%; border-collapse: collapse; background: #1a1a1a; border-radius: 10px; overflow: hidden; }
        .reviews-table th, .reviews-table td { padding: 15px; text-align: left; border-bottom: 1px solid #333; }
        .reviews-table th { background: #2a2a2a; }
        .reviews-table tr:hover { background: #222; }
        .action-buttons { display: flex; gap: 10px; }
        .btn-sm { padding: 5px 10px; font-size: 0.9em; border: none; border-radius: 5px; cursor: pointer; }
        .btn-approve { background: #28a745; color: #fff; }
        .btn-reject { background: #dc3545; color: #fff; }
        .btn-delete { background: #6c757d; color: #fff; }
        .btn-sm:hover { opacity: 0.8; }
        .filter-tabs { display: flex; gap: 10px; margin-bottom: 20px; }
        .filter-tab { padding: 10px 20px; background: #2a2a2a; border: none; color: #fff; border-radius: 5px; cursor: pointer; }
        .filter-tab.active { background: #e50914; }
    </style>
</head>
<body>
    <?php include 'includes/nav.php'; ?>
    
    <div class="admin-container">
        <div class="admin-header">
            <h1>Review Moderation</h1>
            <p>Manage and moderate user reviews</p>
        </div>

        <div class="admin-tabs" style="display: flex; gap: 10px; margin-bottom: 30px; border-bottom: 2px solid #333;">
            <a href="admin.php" class="admin-tab active" style="padding: 15px 25px; background: transparent; border: none; color: #fff; cursor: pointer; border-bottom: 3px solid #e50914; margin-bottom: -2px; text-decoration: none;">Reviews</a>
            <a href="admin-movies.php" class="admin-tab" style="padding: 15px 25px; background: transparent; border: none; color: #fff; cursor: pointer; border-bottom: 3px solid transparent; margin-bottom: -2px; text-decoration: none;">Movies</a>
        </div>

        <div class="filter-tabs">
            <button class="filter-tab active" onclick="filterReviews('all')">All</button>
            <button class="filter-tab" onclick="filterReviews('pending')">Pending</button>
            <button class="filter-tab" onclick="filterReviews('approved')">Approved</button>
            <button class="filter-tab" onclick="filterReviews('rejected')">Rejected</button>
        </div>

        <?php if (isset($message)): ?>
            <div style="padding: 10px; background: #28a745; color: #fff; border-radius: 5px; margin-bottom: 20px;">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <table class="reviews-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Movie</th>
                    <th>User</th>
                    <th>Rating</th>
                    <th>Comment</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reviews as $review): ?>
                    <tr data-status="<?= $review['status'] ?>">
                        <td><?= $review['id'] ?></td>
                        <td><a href="movie-details.php?id=<?= $review['movie_id'] ?>" style="color: #e50914;"><?= htmlspecialchars($review['movie_title']) ?></a></td>
                        <td><?= htmlspecialchars($review['username']) ?></td>
                        <td><strong><?= $review['rating'] ?>/10</strong></td>
                        <td><?= htmlspecialchars(substr($review['comment'] ?: 'No comment', 0, 100)) ?><?= strlen($review['comment'] ?? '') > 100 ? '...' : '' ?></td>
                        <td>
                            <span class="status-badge status-<?= $review['status'] ?>">
                                <?= ucfirst($review['status']) ?>
                            </span>
                        </td>
                        <td><?= date('M d, Y', strtotime($review['created_at'])) ?></td>
                        <td>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure?');">
                                <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                                <?php if ($review['status'] !== 'approved'): ?>
                                    <button type="submit" name="action" value="approve" class="btn-sm btn-approve">Approve</button>
                                <?php endif; ?>
                                <?php if ($review['status'] !== 'rejected'): ?>
                                    <button type="submit" name="action" value="reject" class="btn-sm btn-reject">Reject</button>
                                <?php endif; ?>
                                <button type="submit" name="action" value="delete" class="btn-sm btn-delete">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
        function filterReviews(status) {
            const rows = document.querySelectorAll('.reviews-table tbody tr');
            const tabs = document.querySelectorAll('.filter-tab');
            
            tabs.forEach(tab => tab.classList.remove('active'));
            event.target.classList.add('active');
            
            rows.forEach(row => {
                if (status === 'all' || row.dataset.status === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>

