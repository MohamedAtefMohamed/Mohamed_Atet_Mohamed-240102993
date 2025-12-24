<?php
require_once __DIR__ . '/../config/database.php';

class Movie {
    private $db;

    public function __construct() {
        $this->db = getDB();
    }

    public function getAll($filters = [], $limit = 50, $offset = 0) {
        try {
            $sql = "SELECT m.*, g.name as genre_name,
                    COALESCE(AVG(r.rating), m.rating) as rating
                    FROM movies m 
                    LEFT JOIN genres g ON m.genre_id = g.id 
                    LEFT JOIN reviews r ON m.id = r.movie_id AND r.status = 'approved'
                    WHERE 1=1";
            $params = [];

            if (!empty($filters['type'])) {
                $sql .= " AND m.type = ?";
                $params[] = $filters['type'];
            }

            if (!empty($filters['genre_id'])) {
                $sql .= " AND m.genre_id = ?";
                $params[] = $filters['genre_id'];
            }

            if (!empty($filters['search'])) {
                $sql .= " AND (m.title LIKE ? OR m.description LIKE ?)";
                $searchTerm = "%{$filters['search']}%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }

            $sql .= " GROUP BY m.id ORDER BY m.created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $movies = $stmt->fetchAll();
            
            foreach ($movies as &$movie) {
                $movie['rating'] = round($movie['rating'], 1);
            }
            
            return $movies;
        } catch (PDOException $e) {
            error_log("Error fetching movies: " . $e->getMessage());
            return [];
        }
    }

    public function getById($id) {
        try {
            $stmt = $this->db->prepare("SELECT m.*, g.name as genre_name,
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
            }
            return $movie ?: null;
        } catch (PDOException $e) {
            error_log("Error fetching movie: " . $e->getMessage());
            return null;
        }
    }

    public function create($data) {
        try {
            $required = ['title', 'type'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return ['success' => false, 'message' => "Field '$field' is required"];
                }
            }

            $sql = "INSERT INTO movies (title, description, release_year, duration, rating, poster_url, banner_url, type, genre_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['title'],
                $data['description'] ?? null,
                $data['release_year'] ?? null,
                $data['duration'] ?? null,
                $data['rating'] ?? 0.0,
                $data['poster_url'] ?? null,
                $data['banner_url'] ?? null,
                $data['type'],
                $data['genre_id'] ?? null
            ]);

            $id = $this->db->lastInsertId();
            return ['success' => true, 'message' => 'Movie created successfully', 'id' => $id];
        } catch (PDOException $e) {
            error_log("Error creating movie: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create movie'];
        }
    }

    public function update($id, $data) {
        try {
            $fields = [];
            $params = [];

            $allowedFields = ['title', 'description', 'release_year', 'duration', 'rating', 'poster_url', 'banner_url', 'type', 'genre_id'];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $fields[] = "$field = ?";
                    $params[] = $data[$field];
                }
            }

            if (empty($fields)) {
                return ['success' => false, 'message' => 'No fields to update'];
            }

            $params[] = $id;
            $sql = "UPDATE movies SET " . implode(', ', $fields) . " WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return ['success' => true, 'message' => 'Movie updated successfully'];
        } catch (PDOException $e) {
            error_log("Error updating movie: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update movie'];
        }
    }

    public function delete($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM movies WHERE id = ?");
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Movie deleted successfully'];
            } else {
                return ['success' => false, 'message' => 'Movie not found'];
            }
        } catch (PDOException $e) {
            error_log("Error deleting movie: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to delete movie'];
        }
    }

    public function getByGenre($genreId) {
        return $this->getAll(['genre_id' => $genreId]);
    }

    public function search($query) {
        return $this->getAll(['search' => $query]);
    }
}
?>

