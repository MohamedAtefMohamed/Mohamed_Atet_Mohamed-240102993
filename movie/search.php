<?php
require_once 'config/database.php';
require_once 'models/Movie.php';

$query = trim($_GET['q'] ?? '');
$results = [];

if (!empty($query)) {
    $movieModel = new Movie();
    $results = $movieModel->search($query);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results<?php echo $query ? ' - ' . htmlspecialchars($query) : ''; ?> - Flix</title>
    <link rel="stylesheet" href="grid.css">
    <link rel="stylesheet" href="app.css">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
    <?php include 'includes/nav.php'; ?>
    
    <main class="section" style="margin-top: 60px;">
        <div class="container">
            <h2 class="section-header">
                Search Results<?php echo $query ? ' for "' . htmlspecialchars($query) . '"' : ''; ?>
            </h2>
            
            <?php if (empty($query)): ?>
                <p>Please enter a search term.</p>
            <?php elseif (empty($results)): ?>
                <p>No results found for "<?php echo htmlspecialchars($query); ?>".</p>
            <?php else: ?>
                <div class="movies-slide carousel-nav-center owl-carousel">
                    <?php foreach ($results as $movie): ?>
                        <article class="movie-item">
                            <a href="movie-details.php?id=<?php echo $movie['id']; ?>" aria-label="View <?php echo htmlspecialchars($movie['title']); ?> details">
                                <img src="<?php echo htmlspecialchars($movie['poster_url'] ?? './images/black-banner.png'); ?>" alt="<?php echo htmlspecialchars($movie['title']); ?> poster">
                                <div class="movie-item-content">
                                    <div class="movie-item-title">
                                        <?php echo htmlspecialchars($movie['title']); ?>
                                    </div>
                                    <div class="movie-infos">
                                        <div class="movie-info">
                                            <i class="bx bxs-star"></i>
                                            <span><?php echo $movie['rating']; ?></span>
                                        </div>
                                        <?php if ($movie['duration']): ?>
                                            <div class="movie-info">
                                                <i class="bx bxs-time"></i>
                                                <span><?php echo $movie['duration']; ?> mins</span>
                                            </div>
                                        <?php endif; ?>
                                        <div class="movie-info">
                                            <span><?php echo strtoupper($movie['type']); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>
    <script src="app.js"></script>
</body>
</html>

