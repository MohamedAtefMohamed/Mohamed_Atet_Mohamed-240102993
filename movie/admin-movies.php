<?php
require_once __DIR__ . '/includes/auth.php';
requireAdmin();

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/Movie.php';

$db = getDB();
$movieModel = new Movie();
$message = '';
$error = '';

// Handle movie actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $data = [
            'title' => trim($_POST['title'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'release_year' => !empty($_POST['release_year']) ? (int)$_POST['release_year'] : null,
            'duration' => !empty($_POST['duration']) ? (int)$_POST['duration'] : null,
            'rating' => !empty($_POST['rating']) ? (float)$_POST['rating'] : 0.0,
            'poster_url' => trim($_POST['poster_url'] ?? ''),
            'banner_url' => trim($_POST['banner_url'] ?? ''),
            'type' => $_POST['type'] ?? 'movie',
            'genre_id' => !empty($_POST['genre_id']) ? (int)$_POST['genre_id'] : null   
        ];
        
        $result = $movieModel->create($data);
        if ($result['success']) {
            $message = 'Movie added successfully!';
        } else {
            $error = $result['message'];
        }
    } elseif ($action === 'delete') {
        $movie_id = (int)($_POST['movie_id'] ?? 0);
        if ($movie_id) {
            $result = $movieModel->delete($movie_id);
            if ($result['success']) {
                $message = 'Movie deleted successfully!';
            } else {
                $error = $result['message'];
            }
        }
    }
}

// Get all movies
$movies = $movieModel->getAll([], 100, 0);

// Get all genres
$genreStmt = $db->query("SELECT * FROM genres ORDER BY name");
$genres = $genreStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Movie Management</title>
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="app.css">
    <style>
        .admin-container { max-width: 1400px; margin: 80px auto 40px; padding: 20px; }
        .admin-header { margin-bottom: 30px; }
        .admin-header h1 { margin: 0 0 10px; }
        .admin-tabs { display: flex; gap: 10px; margin-bottom: 30px; border-bottom: 2px solid #333; }
        .admin-tab { padding: 15px 25px; background: transparent; border: none; color: #fff; cursor: pointer; border-bottom: 3px solid transparent; margin-bottom: -2px; }
        .admin-tab.active { border-bottom-color: #e50914; color: #e50914; }
        .admin-tab:hover { color: #e50914; }
        .movie-form { background: #1a1a1a; padding: 25px; border-radius: 10px; margin-bottom: 30px; }
        .movie-form h3 { margin-top: 0; margin-bottom: 20px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 15px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; color: #fff; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #333; background: #2a2a2a; color: #fff; border-radius: 5px; }
        .form-group textarea { min-height: 100px; resize: vertical; }
        .form-group.full-width { grid-column: 1 / -1; }
        .btn { padding: 10px 20px; background: #e50914; color: #fff; border: none; border-radius: 5px; cursor: pointer; }
        .btn:hover { background: #f40612; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        .movies-table { width: 100%; border-collapse: collapse; background: #1a1a1a; border-radius: 10px; overflow: hidden; margin-top: 20px; }
        .movies-table th, .movies-table td { padding: 15px; text-align: left; border-bottom: 1px solid #333; }
        .movies-table th { background: #2a2a2a; }
        .movies-table tr:hover { background: #222; }
        .message { padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .message.success { background: #28a745; color: #fff; }
        .message.error { background: #dc3545; color: #fff; }
        .btn-sm { padding: 5px 10px; font-size: 0.9em; }
    </style>
</head>
<body>
    <?php include 'includes/nav.php'; ?>
    
    <div class="admin-container">
        <div class="admin-header">
            <h1>Movie Management</h1>
            <p>Add, edit, and manage movies for review</p>
        </div>

        <div class="admin-tabs">
            <a href="admin-movies.php" class="admin-tab active">Movies</a>
            <a href="admin.php" class="admin-tab">Reviews</a>
        </div>

        <?php if ($message): ?>
            <div class="message success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Add Movie Form -->
        <div class="movie-form">
            <h3>Add New Movie</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-row">
                    <div class="form-group">
                        <label for="title">Movie Title *</label>
                        <input type="text" id="title" name="title" required>
                    </div>
                    <div class="form-group">
                        <label for="type">Type *</label>
                        <select id="type" name="type" required>
                            <option value="movie">Movie</option>
                            <option value="series">Series</option>
                            <option value="cartoon">Cartoon</option>
                        </select>
                    </div>
                </div>
                <div class="form-group full-width">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" placeholder="Enter movie description..."></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="release_year">Release Year</label>
                        <input type="number" id="release_year" name="release_year" min="1900" max="2099">
                    </div>
                    <div class="form-group">
                        <label for="duration">Duration (minutes)</label>
                        <input type="number" id="duration" name="duration" min="1">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="genre_id">Genre</label>
                        <select id="genre_id" name="genre_id">
                            <option value="">Select Genre</option>
                            <?php foreach ($genres as $genre): ?>
                                <option value="<?= $genre['id'] ?>"><?= htmlspecialchars($genre['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="rating">Initial Rating (0-10)</label>
                        <input type="number" id="rating" name="rating" min="0" max="10" step="0.1" value="0">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="poster_url">Poster Image URL</label>
                        <input type="text" id="poster_url" name="poster_url" placeholder="./images/movies/movie.jpg">
                    </div>
                    <div class="form-group">
                        <label for="banner_url">Banner Image URL</label>
                        <input type="text" id="banner_url" name="banner_url" placeholder="./images/movies/banner.jpg">
                    </div>
                </div>
                <button type="submit" class="btn">Add Movie</button>
            </form>
        </div>

        <!-- Movies List -->
        <h3>All Movies (<?= count($movies) ?>)</h3>
        <table class="movies-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Type</th>
                    <th>Genre</th>
                    <th>Rating</th>
                    <th>Year</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($movies)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px;">No movies found. Add your first movie above!</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($movies as $movie): ?>
                        <tr>
                            <td><?= $movie['id'] ?></td>
                            <td>
                                <a href="movie-details.php?id=<?= $movie['id'] ?>" style="color: #e50914;">
                                    <?= htmlspecialchars($movie['title']) ?>
                                </a>
                            </td>
                            <td><?= ucfirst($movie['type']) ?></td>
                            <td><?= htmlspecialchars($movie['genre_name'] ?? 'N/A') ?></td>
                            <td><?= $movie['rating'] ?>/10</td>
                            <td><?= $movie['release_year'] ?? 'N/A' ?></td>
                            <td>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this movie? This will also delete all reviews.');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="movie_id" value="<?= $movie['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

