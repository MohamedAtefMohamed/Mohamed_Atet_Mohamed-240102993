-- Flix Movies Database Schema
-- Creates database and tables for the movie streaming platform

-- Create database
CREATE DATABASE IF NOT EXISTS flix_movies CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE flix_movies;

-- Users table for authentication
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    is_admin TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Genres table
CREATE TABLE IF NOT EXISTS genres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Movies table
CREATE TABLE IF NOT EXISTS movies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    release_year YEAR,
    duration INT COMMENT 'Duration in minutes',
    rating DECIMAL(3,1) DEFAULT 0.0 COMMENT 'Rating out of 10',
    poster_url VARCHAR(255),
    banner_url VARCHAR(255),
    type ENUM('movie', 'series', 'cartoon') DEFAULT 'movie',
    genre_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (genre_id) REFERENCES genres(id) ON DELETE SET NULL,
    INDEX idx_title (title),
    INDEX idx_type (type),
    INDEX idx_genre (genre_id),
    INDEX idx_rating (rating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User favorites table
CREATE TABLE IF NOT EXISTS user_favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    movie_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
    UNIQUE KEY unique_favorite (user_id, movie_id),
    INDEX idx_user (user_id),
    INDEX idx_movie (movie_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Watch history table
CREATE TABLE IF NOT EXISTS watch_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    movie_id INT NOT NULL,
    watched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    progress INT DEFAULT 0 COMMENT 'Progress percentage',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_movie (movie_id),
    INDEX idx_watched_at (watched_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reviews table
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    movie_id INT NOT NULL,
    user_id INT NOT NULL,
    rating DECIMAL(2,1) NOT NULL CHECK (rating >= 1 AND rating <= 10),
    comment TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_movie (movie_id),
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    INDEX idx_rating (rating),
    UNIQUE KEY unique_user_movie_review (user_id, movie_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample genres
INSERT INTO genres (name, description) VALUES
('Action', 'High-energy films with thrilling sequences'),
('Comedy', 'Light-hearted and humorous content'),
('Drama', 'Serious, plot-driven presentations'),
('Horror', 'Scary and suspenseful content'),
('Sci-Fi', 'Science fiction and futuristic themes'),
('Animation', 'Animated movies and cartoons'),
('Thriller', 'Suspenseful and exciting content')
ON DUPLICATE KEY UPDATE name=name;

-- Insert sample movies
INSERT INTO movies (title, description, release_year, duration, rating, poster_url, banner_url, type, genre_id) VALUES
('Black Panther', 'T''Challa returns home to Wakanda to become king, but finds his sovereignty challenged by a powerful adversary.', 2018, 134, 9.5, './images/black-banner.png', './images/black-banner.png', 'movie', 1),
('Supergirl', 'The adventures of Superman''s cousin in her own superhero series.', 2015, 45, 8.5, './images/series/supergirl.jpg', './images/supergirl-banner.jpg', 'series', 1),
('Wanda Vision', 'Wanda Maximoff and Vision live an idyllic suburban life, but begin to suspect things are not as they seem.', 2021, 50, 9.0, './images/series/wanda.png', './images/wanda-banner.jpg', 'series', 3),
('Transformer', 'An ancient struggle between two Cybertronian races, the heroic Autobots and the evil Decepticons, comes to Earth.', 2007, 144, 8.0, './images/movies/transformer.jpg', './images/transformer-banner.jpg', 'movie', 1),
('Demon Slayer', 'A boy becomes a demon slayer after his family is slaughtered and his sister is turned into a demon.', 2019, 24, 9.2, './images/cartoons/demon-slayer.jpg', './images/cartoons/demon-slayer.jpg', 'cartoon', 6),
('Captain Marvel', 'Carol Danvers becomes one of the universe''s most powerful heroes when Earth is caught in the middle of a galactic war.', 2019, 123, 8.8, './images/movies/captain-marvel.png', './images/movies/captain-marvel.png', 'movie', 1),
('Stranger Things', 'When a young boy vanishes, a small town uncovers a mystery involving secret experiments and supernatural forces.', 2016, 50, 9.5, './images/series/stranger-thing.jpg', './images/series/stranger-thing.jpg', 'series', 4)
ON DUPLICATE KEY UPDATE title=title;

-- Create default admin user (password: admin123)
-- Note: The password hash below is for 'admin123'
-- If you need to reset it, use: php create-admin.php or run: UPDATE users SET password_hash = '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyYq5Y5Y5Y5O' WHERE username = 'admin';
INSERT INTO users (username, email, password_hash, full_name, is_admin) VALUES
('admin', 'admin@movie.com', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyYq5Y5Y5Y5O', 'Admin User', 1)
ON DUPLICATE KEY UPDATE username=username;

