<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $fullName = trim($_POST['full_name'] ?? '');

    if ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } else {
        $result = registerUser($username, $email, $password, $fullName);
        
        if ($result['success']) {
            $success = 'Registration successful! Please login.';
            $loginResult = loginUser($username, $password);
            if ($loginResult['success']) {
                header('Location: index.php');
                exit;
            }
        } else {
            $error = $result['message'];
        }
    }
}

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Flix</title>
    <link rel="stylesheet" href="app.css">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <style>
        .register-container {
            min-height: calc(100vh - 60px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            margin-top: 60px;
        }
        .register-box {
            background-color: var(--box-bg);
            padding: 40px;
            border-radius: 10px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
        }
        .register-box h2 {
            text-align: center;
            margin-bottom: 30px;
            color: var(--text-color);
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-color);
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            background-color: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 5px;
            color: var(--text-color);
            font-size: 1rem;
        }
        .form-group input:focus {
            outline: none;
            border-color: var(--main-color);
        }
        .form-group input.error {
            border-color: #e74c3c;
        }
        .error-message {
            color: #e74c3c;
            margin-bottom: 15px;
            padding: 10px;
            background-color: rgba(231, 76, 60, 0.1);
            border-radius: 5px;
        }
        .success-message {
            color: #27ae60;
            margin-bottom: 15px;
            padding: 10px;
            background-color: rgba(39, 174, 96, 0.1);
            border-radius: 5px;
        }
        .register-box .btn {
            width: 100%;
            justify-content: center;
            margin-top: 10px;
        }
        .login-link {
            text-align: center;
            margin-top: 20px;
            color: var(--text-color);
        }
        .login-link a {
            color: var(--main-color);
        }
    </style>
</head>
<body>
    <?php include 'includes/nav.php'; ?>
    
    <div class="register-container">
        <div class="register-box">
            <h2>Create Account</h2>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form method="POST" id="register-form" onsubmit="return validateRegisterForm(event)">
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" autocomplete="name">
                </div>
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required autocomplete="username">
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required autocomplete="email">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required autocomplete="new-password" minlength="8">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password" minlength="8">
                </div>
                
                <button type="submit" class="btn btn-hover">
                    <span>Register</span>
                </button>
            </form>
            
            <div class="login-link">
                Already have an account? <a href="login.php">Login here</a>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="app.js"></script>
    <script>
        function validateRegisterForm(e) {
            const form = document.getElementById('register-form');
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const username = document.getElementById('username').value;
            const email = document.getElementById('email').value;
            
            if (!username || !email || !password || !confirmPassword) {
                alert('Please fill in all required fields');
                return false;
            }
            
            if (password.length < 8) {
                alert('Password must be at least 8 characters long');
                return false;
            }
            
            if (password !== confirmPassword) {
                alert('Passwords do not match');
                return false;
            }
            
            return true;
        }
        
        document.getElementById('confirm_password')?.addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>

