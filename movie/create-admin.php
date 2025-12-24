<?php
/**
 * Create Admin User Script
 * Run this once to create/reset the admin user
 * Access: http://localhost/movie/create-admin.php
 */

require_once __DIR__ . '/config/database.php';

$username = 'admin';
$email = 'admin@movie.com';
$password = 'admin123';
$fullName = 'Admin User';

try {
    $db = getDB();
    
    // Generate correct password hash
    $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    
    // Check if admin exists
    $stmt = $db->prepare("SELECT id, username FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Update existing admin
        $stmt = $db->prepare("UPDATE users SET password_hash = ?, is_admin = 1, full_name = ?, email = ? WHERE id = ?");
        $stmt->execute([$passwordHash, $fullName, $email, $existing['id']]);
        echo "<h2>✓ Admin user updated successfully!</h2>";
        echo "<p><strong>Username:</strong> $username</p>";
        echo "<p><strong>Password:</strong> $password</p>";
        echo "<p><strong>Email:</strong> $email</p>";
        echo "<p><strong>Status:</strong> Admin privileges enabled</p>";
    } else {
        // Create new admin
        $stmt = $db->prepare("INSERT INTO users (username, email, password_hash, full_name, is_admin) VALUES (?, ?, ?, ?, 1)");
        $stmt->execute([$username, $email, $passwordHash, $fullName]);
        echo "<h2>✓ Admin user created successfully!</h2>";
        echo "<p><strong>Username:</strong> $username</p>";
        echo "<p><strong>Password:</strong> $password</p>";
        echo "<p><strong>Email:</strong> $email</p>";
        echo "<p><strong>Status:</strong> Admin privileges enabled</p>";
    }
    
    // Verify the password works
    $verifyStmt = $db->prepare("SELECT password_hash FROM users WHERE username = ?");
    $verifyStmt->execute([$username]);
    $user = $verifyStmt->fetch();
    
    if ($user && password_verify($password, $user['password_hash'])) {
        echo "<p style='color: green;'><strong>✓ Password verification successful!</strong></p>";
    } else {
        echo "<p style='color: red;'><strong>✗ Password verification failed! Please run this script again.</strong></p>";
    }
    
    echo "<br><hr><br>";
    echo "<a href='login.php' style='padding: 10px 20px; background: #e50914; color: white; text-decoration: none; border-radius: 5px;'>Go to Login Page</a>";
    
} catch (PDOException $e) {
    echo "<h2 style='color: red;'>Error!</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Make sure:</strong></p>";
    echo "<ul>";
    echo "<li>The database 'flix_movies' exists</li>";
    echo "<li>The 'users' table exists</li>";
    echo "<li>You've run config/init.sql first</li>";
    echo "</ul>";
}

