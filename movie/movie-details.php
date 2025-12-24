<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

$movie_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$db = getDB();

// Get movie details
$stmt = $db->prepare("SELECT m.*, g.name as genre_name FROM movies m LEFT JOIN genres g ON m.genre_id = g.id WHERE m.id = ?");
$stmt->execute([$movie_id]);
$movie = $stmt->fetch();

if (!$movie) {
    header('Location: index.php');
    exit;
}

// Check if reviews table exists
$checkTable = $db->query("SHOW TABLES LIKE 'reviews'");
$reviewsTableExists = $checkTable->rowCount() > 0;

// Get reviews and average rating
if ($reviewsTableExists) {
    try {
        $reviewStmt = $db->prepare("SELECT r.*, u.username FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.movie_id = ? AND r.status = 'approved' ORDER BY r.created_at DESC");
        $reviewStmt->execute([$movie_id]);
        $reviews = $reviewStmt->fetchAll();

        $avgStmt = $db->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as count FROM reviews WHERE movie_id = ? AND status = 'approved'");
        $avgStmt->execute([$movie_id]);
        $avg = $avgStmt->fetch();
        $average_rating = round($avg['avg_rating'] ?? 0, 1);
        $total_reviews = $avg['count'] ?? 0;
    } catch (PDOException $e) {
        // If there's an error, fall back to empty reviews
        $reviews = [];
        $average_rating = round($movie['rating'] ?? 0, 1);
        $total_reviews = 0;
    }
} else {
    // Reviews table doesn't exist, use movie's default rating
    $reviews = [];
    $average_rating = round($movie['rating'] ?? 0, 1);
    $total_reviews = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($movie['title']) ?> - Movie Reviews</title>
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="app.css">
    <style>
        .movie-detail { max-width: 1200px; margin: 80px auto 40px; padding: 20px; }
        .movie-header { display: grid; grid-template-columns: 300px 1fr; gap: 30px; margin-bottom: 40px; }
        .movie-poster img { width: 100%; border-radius: 10px; }
        .movie-info h1 { font-size: 2.5em; margin: 0 0 20px; }
        .movie-meta { display: flex; gap: 20px; margin: 20px 0; flex-wrap: wrap; }
        .rating-display { font-size: 1.5em; color: #ffc107; }
        .reviews-section { margin-top: 40px; }
        .review-form { background: #1a1a1a; padding: 20px; border-radius: 10px; margin-bottom: 30px; }
        .review-form h3 { margin-top: 0; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; }
        .form-group input, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #333; background: #2a2a2a; color: #fff; border-radius: 5px; }
        .rating-input { display: flex; gap: 10px; align-items: center; }
        .rating-input input { width: 80px; }
        .btn { padding: 10px 20px; background: #e50914; color: #fff; border: none; border-radius: 5px; cursor: pointer; }
        .btn:hover { background: #f40612; }
        .review-item { background: #1a1a1a; padding: 20px; border-radius: 10px; margin-bottom: 15px; }
        .review-header { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .review-rating { color: #ffc107; }
        .filters { display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap; }
        .filters select, .filters input { padding: 10px; border: 1px solid #333; background: #2a2a2a; color: #fff; border-radius: 5px; }
        @media (max-width: 768px) {
            .movie-header { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <?php include 'includes/nav.php'; ?>
    
    <div class="movie-detail">
        <div class="movie-header">
            <div class="movie-poster">
                <img src="<?= htmlspecialchars($movie['poster_url'] ?: './images/black-banner.png') ?>" alt="<?= htmlspecialchars($movie['title']) ?>">
            </div>
            <div class="movie-info">
                <h1><?= htmlspecialchars($movie['title']) ?></h1>
                <div class="movie-meta">
                    <span><i class='bx bxs-star'></i> <span class="rating-display" id="avg-rating"><?= $average_rating ?></span>/10</span>
                    <span><i class='bx bx-user'></i> <?= $total_reviews ?> reviews</span>
                    <span><i class='bx bx-time'></i> <?= $movie['duration'] ?> mins</span>
                    <?php if ($movie['genre_name']): ?>
                        <span><i class='bx bx-tag'></i> <?= htmlspecialchars($movie['genre_name']) ?></span>
                    <?php endif; ?>
                </div>
                <p><?= htmlspecialchars($movie['description'] ?: 'No description available') ?></p>
            </div>
        </div>

        <div class="reviews-section">
            <h2>Reviews</h2>
            
            <?php if (isLoggedIn()): ?>
                <div class="review-form">
                    <h3>Write a Review</h3>
                    <form id="reviewForm">
                        <input type="hidden" name="movie_id" value="<?= $movie_id ?>">
                        <div class="form-group">
                            <label>Rating (1-10)</label>
                            <div class="rating-input">
                                <input type="number" name="rating" min="1" max="10" step="0.1" required>
                                <span>/10</span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Comment</label>
                            <textarea name="comment" rows="4" placeholder="Share your thoughts..."></textarea>
                        </div>
                        <button type="submit" class="btn">Submit Review</button>
                    </form>
                    <div id="reviewMessage" style="margin-top: 10px;"></div>
                </div>
            <?php else: ?>
                <p><a href="login.php">Login</a> to write a review</p>
            <?php endif; ?>

            <div class="filters">
                <select id="ratingFilter">
                    <option value="">All Ratings</option>
                    <option value="9">9+ Stars</option>
                    <option value="8">8+ Stars</option>
                    <option value="7">7+ Stars</option>
                    <option value="6">6+ Stars</option>
                    <option value="5">5+ Stars</option>
                </select>
            </div>

            <div id="reviewsList">
                <?php foreach ($reviews as $review): ?>
                    <div class="review-item" data-rating="<?= $review['rating'] ?>">
                        <div class="review-header">
                            <strong><?= htmlspecialchars($review['username']) ?></strong>
                            <span class="review-rating"><i class='bx bxs-star'></i> <?= $review['rating'] ?>/10</span>
                        </div>
                        <p><?= htmlspecialchars($review['comment'] ?: 'No comment') ?></p>
                        <small><?= date('M d, Y', strtotime($review['created_at'])) ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
        // Submit review
        document.getElementById('reviewForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData);
            data.rating = parseFloat(data.rating);

            const messageEl = document.getElementById('reviewMessage');
            messageEl.textContent = 'Submitting...';
            messageEl.style.color = '#fff';
            
            try {
                const res = await fetch('api/reviews.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                
                if (!res.ok) {
                    throw new Error('Network error');
                }
                
                const result = await res.json();
                messageEl.textContent = result.message || 'Review submitted';
                messageEl.style.color = result.success ? '#28a745' : '#dc3545';
                
                if (result.success) {
                    e.target.reset();
                    setTimeout(() => location.reload(), 2000);
                }
            } catch (error) {
                messageEl.textContent = 'Error submitting review. Please try again.';
                messageEl.style.color = '#dc3545';
                console.error('Review submission error:', error);
            }
        });

        // Filter by rating
        document.getElementById('ratingFilter')?.addEventListener('change', (e) => {
            const minRating = parseFloat(e.target.value);
            const reviews = document.querySelectorAll('.review-item');
            reviews.forEach(review => {
                const rating = parseFloat(review.dataset.rating);
                review.style.display = (!minRating || rating >= minRating) ? 'block' : 'none';
            });
        });
    </script>
</body>
</html>

