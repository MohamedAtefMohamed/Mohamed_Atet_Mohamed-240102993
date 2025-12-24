<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Movie.php';
require_once __DIR__ . '/../includes/auth.php';

$method = $_SERVER['REQUEST_METHOD'];
$movieModel = new Movie();

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));
$id = isset($pathParts[2]) ? (int)$pathParts[2] : null;

switch ($method) {
    case 'GET':
        $db = getDB();
        if ($id) {
            $stmt = $db->prepare("SELECT m.*, g.name as genre_name,
                COALESCE(AVG(r.rating), m.rating) as rating,
                COUNT(r.id) as review_count
                FROM movies m 
                LEFT JOIN genres g ON m.genre_id = g.id 
                LEFT JOIN reviews r ON m.id = r.movie_id AND r.status = 'approved'
                WHERE m.id = ?
                GROUP BY m.id");
            $stmt->execute([$id]);
            $movie = $stmt->fetch();
            if ($movie) {
                $movie['rating'] = round($movie['rating'], 1);
                echo json_encode(['success' => true, 'data' => $movie]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Movie not found']);
            }
        } else {
            try {
                $filters = [
                    'type' => $_GET['type'] ?? '',
                    'genre_id' => isset($_GET['genre_id']) ? (int)$_GET['genre_id'] : null,
                    'search' => $_GET['search'] ?? ''
                ];
                
                $checkTable = $db->query("SHOW TABLES LIKE 'reviews'");
                $reviewsTableExists = $checkTable->rowCount() > 0;
                
                if ($reviewsTableExists) {
                    $sql = "SELECT m.*, g.name as genre_name,
                        COALESCE(AVG(r.rating), m.rating) as rating,
                        COUNT(r.id) as review_count
                        FROM movies m 
                        LEFT JOIN genres g ON m.genre_id = g.id 
                        LEFT JOIN reviews r ON m.id = r.movie_id AND r.status = 'approved'
                        WHERE 1=1";
                } else {
                    $sql = "SELECT m.*, g.name as genre_name,
                        m.rating as rating,
                        0 as review_count
                        FROM movies m 
                        LEFT JOIN genres g ON m.genre_id = g.id 
                        WHERE 1=1";
                }
                
                $params = [];
                
                if ($filters['type']) {
                    $sql .= " AND m.type = ?";
                    $params[] = $filters['type'];
                }
                if ($filters['genre_id']) {
                    $sql .= " AND m.genre_id = ?";
                    $params[] = $filters['genre_id'];
                }
                if ($filters['search']) {
                    $sql .= " AND (m.title LIKE ? OR m.description LIKE ?)";
                    $search = "%{$filters['search']}%";
                    $params[] = $search;
                    $params[] = $search;
                }
                
                if ($reviewsTableExists) {
                    $sql .= " GROUP BY m.id ORDER BY m.created_at DESC";
                } else {
                    $sql .= " ORDER BY m.created_at DESC";
                }
                
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $movies = $stmt->fetchAll();
                
                foreach ($movies as &$movie) {
                    $movie['rating'] = round((float)$movie['rating'], 1);
                }
                
                echo json_encode(['success' => true, 'data' => $movies, 'count' => count($movies)]);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Database error: ' . $e->getMessage(),
                    'error' => $e->getMessage()
                ]);
                error_log("Movies API Error: " . $e->getMessage());
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Server error: ' . $e->getMessage(),
                    'error' => $e->getMessage()
                ]);
                error_log("Movies API Error: " . $e->getMessage());
            }
        }
        break;

    case 'POST':
        requireAdmin();
        
        $data = json_decode(file_get_contents('php://input'), true);
        $result = $movieModel->create($data);
        
        if ($result['success']) {
            http_response_code(201);
            echo json_encode($result);
        } else {
            http_response_code(400);
            echo json_encode($result);
        }
        break;

    case 'PUT':
    case 'PATCH':
        requireAdmin();
        
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Movie ID required']);
            break;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $result = $movieModel->update($id, $data);
        
        if ($result['success']) {
            echo json_encode($result);
        } else {
            http_response_code(400);
            echo json_encode($result);
        }
        break;

    case 'DELETE':
        requireAdmin();
        
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Movie ID required']);
            break;
        }
        
        $result = $movieModel->delete($id);
        
        if ($result['success']) {
            echo json_encode($result);
        } else {
            http_response_code(404);
            echo json_encode($result);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}
?>

