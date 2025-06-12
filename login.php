<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ChatApp</title>
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
            max-width: 400px;
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

        .btn-secondary {
            background: #6c757d;
            margin-top: 10px;
        }

        .btn-secondary:hover {
            background: #5a6268;
            box-shadow: 0 10px 30px rgba(108, 117, 125, 0.3);
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

        .alert-info {
            background: rgba(23, 162, 184, 0.1);
            color: #17a2b8;
            border: 1px solid rgba(23, 162, 184, 0.2);
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

        .session-info {
            background: rgba(255, 193, 7, 0.1);
            border: 1px solid rgba(255, 193, 7, 0.2);
            color: #856404;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: none;
        }

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
            <h1><i class="fas fa-comments"></i> ChatApp</h1>
            <p>Welcome back! Please sign in to your account.</p>
        </div>

        <div id="alert" class="alert"></div>
        <div id="sessionInfo" class="session-info">
            <p>You appear to be already logged in. <button type="button" id="continueToChat" class="btn btn-secondary">Continue to Chat</button> or login with different credentials below.</p>
        </div>

        <form id="loginForm">
            <div class="form-group">
                <label for="username">Username or Email</label>
                <input type="text" id="username" name="username" required>
                <i class="fas fa-user"></i>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
                <i class="fas fa-lock"></i>
            </div>

            <button type="submit" class="btn" id="loginBtn">
                <span class="btn-text">Sign In</span>
                <div class="loading">
                    <div class="spinner"></div>
                </div>
            </button>

            <button type="button" class="btn btn-secondary" id="logoutBtn" style="display: none;">
                <i class="fas fa-sign-out-alt"></i> Logout Current Session
            </button>
        </form>

        <div class="auth-links">
            <p>Don't have an account? <a href="register.php">Sign up here</a></p>
        </div>
    </div>

    <script>
        // DOM elements
        const loginForm = document.getElementById('loginForm');
        const loginBtn = document.getElementById('loginBtn');
        const logoutBtn = document.getElementById('logoutBtn');
        const continueBtn = document.getElementById('continueToChat');
        const btnText = document.querySelector('.btn-text');
        const loading = document.querySelector('.loading');
        const alert = document.getElementById('alert');
        const sessionInfo = document.getElementById('sessionInfo');

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

        // Set loading state
        function setLoading(isLoading) {
            if (isLoading) {
                loginBtn.disabled = true;
                btnText.style.opacity = '0';
                loading.style.display = 'block';
            } else {
                loginBtn.disabled = false;
                btnText.style.opacity = '1';
                loading.style.display = 'none';
            }
        }

        // Handle logout
        async function logout() {
            try {
                const response = await fetch('auth.php?action=logout', {
                    method: 'GET'
                });
                const result = await response.json();
                
                if (result.success) {
                    showAlert('Logged out successfully', 'success');
                    sessionInfo.style.display = 'none';
                    logoutBtn.style.display = 'none';
                    // Clear form
                    loginForm.reset();
                } else {
                    showAlert('Logout failed');
                }
            } catch (error) {
                showAlert('Network error during logout');
                console.error('Logout error:', error);
            }
        }

        // Handle form submission
        loginForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            setLoading(true);

            try {
                const response = await fetch('auth.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showAlert('Login successful! Redirecting...', 'success');
                    setTimeout(() => {
                        window.location.href = 'chat.php';
                    }, 1500);
                } else {
                    showAlert(result.message || 'Login failed. Please try again.');
                }
            } catch (error) {
                showAlert('Network error. Please check your connection.');
                console.error('Login error:', error);
            } finally {
                setLoading(false);
            }
        });

        // Event listeners
        logoutBtn.addEventListener('click', logout);
        
        continueBtn.addEventListener('click', function() {
            window.location.href = 'chat.php';
        });

        // Input focus effects
        document.querySelectorAll('.form-group input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.querySelector('i').style.color = '#667eea';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.querySelector('i').style.color = '#aaa';
            });
        });

        // Check if already logged in - but don't auto-redirect
        window.addEventListener('load', function() {
            // Only check if we're coming from a register page or explicitly want to check
            const urlParams = new URLSearchParams(window.location.search);
            const checkAuth = urlParams.get('check') === 'true';
            
            // Don't auto-check auth unless specifically requested
            if (!checkAuth) {
                return;
            }

            fetch('auth.php?action=check')
                .then(response => response.json())
                .then(result => {
                    if (result.logged_in) {
                        // Don't auto-redirect, just show the option
                        sessionInfo.style.display = 'block';
                        logoutBtn.style.display = 'block';
                        showAlert('You are already logged in. You can continue to chat or login with different credentials.', 'info');
                    }
                })
                .catch(error => console.error('Auth check error:', error));
        });
    </script>
</body>
</html>