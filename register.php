<?php
// Start session and include database configuration
session_start();

// Database configuration
$host = 'localhost';
$dbname = 'chat_app';
$username = 'root'; // Change this to your database username
$password = '';     // Change this to your database password

// Handle form submission
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get and sanitize input data
        $full_name = trim($_POST['full_name'] ?? '');
        $username_input = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password_input = trim($_POST['password'] ?? '');
        $confirm_password = trim($_POST['confirm_password'] ?? '');
        
        // Validation
        if (empty($full_name) || empty($username_input) || empty($email) || empty($password_input) || empty($confirm_password)) {
            throw new Exception('Please fill in all fields.');
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Please enter a valid email address.');
        }
        
        if (strlen($password_input) < 6) {
            throw new Exception('Password must be at least 6 characters long.');
        }
        
        if ($password_input !== $confirm_password) {
            throw new Exception('Passwords do not match.');
        }
        
        if (!preg_match('/^[a-zA-Z0-9_]{3,}$/', $username_input)) {
            throw new Exception('Username must be at least 3 characters long and contain only letters, numbers, and underscores.');
        }
        
        // Connect to database
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
        
        // Check if username already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username_input]);
        if ($stmt->fetch()) {
            throw new Exception('Username already exists. Please choose a different username.');
        }
        
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            throw new Exception('Email already exists. Please use a different email address.');
        }
        
        // Hash the password
        $password_hash = password_hash($password_input, PASSWORD_DEFAULT);
        
        // Insert new user into database
        $stmt = $pdo->prepare("
            INSERT INTO users (username, full_name, email, password_hash) 
            VALUES (?, ?, ?, ?)
        ");
        
        $stmt->execute([$username_input, $full_name, $email, $password_hash]);
        
        $response['success'] = true;
        $response['message'] = 'Account created successfully! You can now sign in.';
        
    } catch (PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }
    
    // Return JSON response for AJAX requests
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - ChatApp</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .auth-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .auth-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .auth-header h1 {
            color: #333;
            font-size: 2.5rem;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .auth-header p {
            color: #666;
            font-size: 1rem;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-group label {
            display: block;
            color: #555;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e1e5e9;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
            margin-top: 12px;
        }

        .btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .auth-links {
            text-align: center;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid #e1e5e9;
        }

        .auth-links a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .auth-links a:hover {
            color: #764ba2;
        }

        .alert {
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-weight: 500;
            display: none;
        }

        .alert-error {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.2);
        }

        .alert-success {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
            border: 1px solid rgba(40, 167, 69, 0.2);
        }

        .loading {
            display: none;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .spinner {
            width: 24px;
            height: 24px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .password-strength {
            margin-top: 5px;
            height: 4px;
            background: #e1e5e9;
            border-radius: 2px;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            transition: all 0.3s ease;
            width: 0%;
        }

        .strength-weak { background: #dc3545; width: 25%; }
        .strength-fair { background: #ffc107; width: 50%; }
        .strength-good { background: #17a2b8; width: 75%; }
        .strength-strong { background: #28a745; width: 100%; }

        @media (max-width: 480px) {
            .auth-container {
                padding: 30px 20px;
                margin: 10px;
            }
            
            .auth-header h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-header">
            <h1><i class="fas fa-user-plus"></i> Join ChatApp</h1>
            <p>Create your account to start chatting!</p>
        </div>

        <div id="alert" class="alert"><?php 
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($response['message'])) {
                echo '<div class="alert alert-' . ($response['success'] ? 'success' : 'error') . '" style="display: block;">' . htmlspecialchars($response['message']) . '</div>';
            }
        ?></div>

        <form id="registerForm" method="POST" action="">
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" required value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                <i class="fas fa-user"></i>
            </div>

            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required pattern="[a-zA-Z0-9_]{3,}" title="Username must be at least 3 characters long and contain only letters, numbers, and underscores" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                <i class="fas fa-at"></i>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                <i class="fas fa-envelope"></i>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required minlength="6">
                <i class="fas fa-lock"></i>
                <div class="password-strength">
                    <div class="password-strength-bar" id="strengthBar"></div>
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
                <i class="fas fa-lock"></i>
            </div>

            <button type="submit" class="btn" id="registerBtn">
                <span class="btn-text">Create Account</span>
                <div class="loading">
                    <div class="spinner"></div>
                </div>
            </button>
        </form>

        <div class="auth-links">
            <p>Already have an account? <a href="login.php?check=true">Sign in here</a></p>
        </div>
    </div>

    <script>
        // DOM elements
        const registerForm = document.getElementById('registerForm');
        const registerBtn = document.getElementById('registerBtn');
        const btnText = document.querySelector('.btn-text');
        const loading = document.querySelector('.loading');
        const alert = document.getElementById('alert');
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const strengthBar = document.getElementById('strengthBar');

        // Show alert function
        function showAlert(message, type = 'error') {
            alert.className = `alert alert-${type}`;
            alert.textContent = message;
            alert.style.display = 'block';
            
            // Auto hide after 5 seconds
            setTimeout(() => {
                alert.style.display = 'none';
            }, 5000);
        }

        // Password strength check
       passwordInput.addEventListener('input', () => {
    const val = passwordInput.value;
    let strength = 0;

    if (val.length >= 6) strength++;
    if (/[A-Z]/.test(val)) strength++;
    if (/[0-9]/.test(val)) strength++;
    if (/[^A-Za-z0-9]/.test(val)) strength++;

    // Remove all strength classes
    strengthBar.className = 'password-strength-bar';
    strengthBar.style.width = '0%';

    if (strength === 0) {
        strengthBar.style.width = '0%';
    } else if (strength === 1) {
        strengthBar.classList.add('strength-weak');
        strengthBar.style.width = '25%';
    } else if (strength === 2) {
        strengthBar.classList.add('strength-fair');
        strengthBar.style.width = '50%';
    } else if (strength === 3) {
        strengthBar.classList.add('strength-good');
        strengthBar.style.width = '75%';
    } else {
        strengthBar.classList.add('strength-strong');
        strengthBar.style.width = '100%';
    }
});
    

        // Form submit handler for AJAX (optional - form will work with regular POST too)
        registerForm.addEventListener('submit', function (e) {
            // Allow normal form submission for now
            // You can uncomment the AJAX code below if you prefer AJAX submission
            
            
            e.preventDefault();

            const formData = new FormData(registerForm);

            // Show loading state
            btnText.style.display = 'none';
            loading.style.display = 'block';
            registerBtn.disabled = true;

            fetch('', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                loading.style.display = 'none';
                btnText.style.display = 'inline';
                registerBtn.disabled = false;

                if (data.success) {
                    showAlert(data.message, 'success');
                    registerForm.reset();
                    strengthBar.className = 'password-strength-bar';
                    strengthBar.style.width = '0%';
                } else {
                    showAlert(data.message, 'error');
                }
            })
            .catch(error => {
                loading.style.display = 'none';
                btnText.style.display = 'inline';
                registerBtn.disabled = false;
                showAlert('An error occurred. Please try again.', 'error');
            });
            
        });
    </script>
</body>
</html>