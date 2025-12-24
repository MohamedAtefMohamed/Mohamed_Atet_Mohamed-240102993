<?php
require_once __DIR__ . '/includes/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movie Reviews - Browse Movies</title>
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="app.css">
    <style>
        .filters-section { background: #1a1a1a; padding: 20px; margin: 80px auto 20px; max-width: 1200px; border-radius: 10px; }
        .filters { display: flex; gap: 15px; flex-wrap: wrap; align-items: center; }
        .filters select, .filters input { padding: 10px; border: 1px solid #333; background: #2a2a2a; color: #fff; border-radius: 5px; }
        .movies-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; max-width: 1200px; margin: 20px auto; padding: 20px; }
        .movie-card { background: #1a1a1a; border-radius: 10px; overflow: hidden; transition: transform 0.3s; cursor: pointer; }
        .movie-card:hover { transform: translateY(-5px); }
        .movie-card img { width: 100%; height: 300px; object-fit: cover; }
        .movie-card-content { padding: 15px; }
        .movie-card h3 { margin: 0 0 10px; font-size: 1.1em; }
        .movie-rating { color: #ffc107; }
        .loading { text-align: center; padding: 40px; }
    </style>
</head>
<body>
    <?php include 'includes/nav.php'; ?>
    
    <div class="filters-section">
        <div class="filters">
            <select id="genreFilter">
                <option value="">All Genres</option>
            </select>
            <select id="ratingFilter">
                <option value="">All Ratings</option>
                <option value="9">9+ Stars</option>
                <option value="8">8+ Stars</option>
                <option value="7">7+ Stars</option>
                <option value="6">6+ Stars</option>
                <option value="5">5+ Stars</option>
            </select>
            <select id="typeFilter">
                <option value="">All Types</option>
                <option value="movie">Movies</option>
                <option value="series">Series</option>
                <option value="cartoon">Cartoons</option>
            </select>
            <input type="text" id="searchInput" placeholder="Search movies...">
        </div>
    </div>

    <div id="moviesContainer" class="movies-grid">
        <div class="loading">Loading movies...</div>
    </div>

    <script>
        let allMovies = [];
        let genres = [];

        // Load genres
        async function loadGenres() {
            try {
                const res = await fetch('api/genres.php');
                if (!res.ok) {
                    throw new Error('Failed to load genres');
                }
                const data = await res.json();
                if (data.success) {
                    genres = data.data || [];
                    const select = document.getElementById('genreFilter');
                    genres.forEach(genre => {
                        const option = document.createElement('option');
                        option.value = genre.id;
                        option.textContent = genre.name;
                        select.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Error loading genres:', error);
            }
        }

        // Load movies
        async function loadMovies(filters = {}) {
            try {
                const params = new URLSearchParams();
                if (filters.genre_id) params.append('genre_id', filters.genre_id);
                if (filters.type) params.append('type', filters.type);
                if (filters.search) params.append('search', filters.search);

                const res = await fetch(`api/movies.php?${params}`);
                const text = await res.text();
                
                if (!res.ok) {
                    throw new Error(`HTTP ${res.status}: ${res.statusText}`);
                }
                
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error('Invalid JSON response:', text);
                    throw new Error('Invalid response from server');
                }
                
                if (data.success) {
                    allMovies = data.data || [];
                    displayMovies(allMovies, filters.rating);
                } else {
                    console.error('API Error:', data.message || data.error);
                    document.getElementById('moviesContainer').innerHTML = 
                        `<div class="loading">Error: ${data.message || 'Failed to load movies'}</div>`;
                }
            } catch (error) {
                console.error('Error loading movies:', error);
                document.getElementById('moviesContainer').innerHTML = 
                    `<div class="loading">Error loading movies: ${error.message}. Please check the console for details.</div>`;
            }
        }

        // Display movies
        function displayMovies(movies, minRating = null) {
            const container = document.getElementById('moviesContainer');
            
            if (movies.length === 0) {
                container.innerHTML = '<div class="loading">No movies found</div>';
                return;
            }

            // Filter by rating if specified
            if (minRating) {
                movies = movies.filter(m => parseFloat(m.rating) >= parseFloat(minRating));
            }

            container.innerHTML = movies.map(movie => `
                <div class="movie-card" onclick="window.location.href='movie-details.php?id=${movie.id}'">
                    <img src="${movie.poster_url || './images/black-banner.png'}" alt="${movie.title}" onerror="this.src='./images/black-banner.png'">
                    <div class="movie-card-content">
                        <h3>${movie.title}</h3>
                        <div class="movie-rating">
                            <i class='bx bxs-star'></i> ${movie.rating || '0'}/10
                        </div>
                        ${movie.genre_name ? `<small>${movie.genre_name}</small>` : ''}
                    </div>
                </div>
            `).join('');
        }

        // Apply filters
        function applyFilters() {
            const filters = {
                genre_id: document.getElementById('genreFilter').value || null,
                type: document.getElementById('typeFilter').value || null,
                search: document.getElementById('searchInput').value.trim() || null,
                rating: document.getElementById('ratingFilter').value || null
            };
            loadMovies(filters);
        }

        // Event listeners
        document.getElementById('genreFilter').addEventListener('change', applyFilters);
        document.getElementById('typeFilter').addEventListener('change', applyFilters);
        document.getElementById('ratingFilter').addEventListener('change', () => {
            displayMovies(allMovies, document.getElementById('ratingFilter').value);
        });
        document.getElementById('searchInput').addEventListener('input', debounce(applyFilters, 300));

        // Debounce function
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        // Initialize
        loadGenres();
        loadMovies();
    </script>
</body>
</html>

