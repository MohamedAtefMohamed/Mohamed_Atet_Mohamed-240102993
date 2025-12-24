<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
$method = $_SERVER['REQUEST_METHOD'];
$db = getDB();

$movie_id = isset($_GET['movie_id']) ? (int)$_GET['movie_id'] : null;
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

switch ($method) {
    case 'GET':
        if ($id) {
            $stmt = $db->prepare("SELECT r.*, u.username FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.id = ?");
            $stmt->execute([$id]);
            $review = $stmt->fetch();
            echo json_encode(['success' => true, 'data' => $review ?: null]);
        } elseif ($movie_id) {
            $stmt = $db->prepare("SELECT r.*, u.username FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.movie_id = ? AND r.status = 'approved' ORDER BY r.created_at DESC");
            $stmt->execute([$movie_id]);
            $reviews = $stmt->fetchAll();
            
            $avgStmt = $db->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as count FROM reviews WHERE movie_id = ? AND status = 'approved'");
            $avgStmt->execute([$movie_id]);
            $avg = $avgStmt->fetch();
            
            echo json_encode([
                'success' => true,
                'reviews' => $reviews,
                'average_rating' => round($avg['avg_rating'] ?? 0, 1),
                'total_reviews' => $avg['count'] ?? 0
            ]);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'movie_id required']);
        }
        break;

    case 'POST':
        if (!isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Login required']);
            break;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $movie_id = (int)($data['movie_id'] ?? 0);
        $rating = (float)($data['rating'] ?? 0);
        $comment = trim($data['comment'] ?? '');

        if (!$movie_id || $rating < 1 || $rating > 10) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid rating (1-10)']);
            break;
        }

        $user_id = getCurrentUserId();

        try {
            $checkStmt = $db->prepare("SELECT id FROM reviews WHERE user_id = ? AND movie_id = ?");
            $checkStmt->execute([$user_id, $movie_id]);
            if ($checkStmt->fetch()) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'You already reviewed this movie']);
                break;
            }

            $stmt = $db->prepare("INSERT INTO reviews (movie_id, user_id, rating, comment, status) VALUES (?, ?, ?, ?, 'pending')");
            $stmt->execute([$movie_id, $user_id, $rating, $comment]);

            echo json_encode(['success' => true, 'message' => 'Review submitted for moderation']);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to submit review']);
        }
        break;

    case 'PUT':
    case 'PATCH':
        if (!isLoggedIn() || !isAdmin()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Admin access required']);
            break;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $id = (int)($data['id'] ?? 0);
        $status = $data['status'] ?? '';

        if (!$id || !in_array($status, ['pending', 'approved', 'rejected'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid data']);
            break;
        }

        try {
            $stmt = $db->prepare("UPDATE reviews SET status = ? WHERE id = ?");
            $stmt->execute([$status, $id]);
            echo json_encode(['success' => true, 'message' => 'Review updated']);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Update failed']);
        }
        break;

    case 'DELETE':
        if (!isLoggedIn() || !isAdmin()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Admin access required']);
            break;
        }

        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Review ID required']);
            break;
        }

        try {
            $stmt = $db->prepare("DELETE FROM reviews WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Review deleted']);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Delete failed']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>

