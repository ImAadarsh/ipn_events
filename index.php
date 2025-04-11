<?php
session_start();

// Include environment configuration
require_once 'config/env.php';

// Redirect to dashboard if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Process login form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // Use credentials from .env file
    $users = [
        'imkaadarsh' => USER_IMKAADARSH,
        'gaurava.ipn' => USER_GAURAVA,
        'pooja.ipn' => USER_POOJA,
        'vijeta.ipn' => USER_VIJETA,
        'shvetambri.ipn' => USER_SHVETAMBRI,
        'aarti.endeavour' => USER_AARTI
    ];
    
    if (array_key_exists($username, $users) && $users[$username] === $password) {
        $_SESSION['user_id'] = $username;
        $_SESSION['username'] = $username;
        
        // Set user role (can be expanded later if needed)
        if ($username === 'gaurava.ipn') {
            $_SESSION['role'] = 'admin';
        } else {
            $_SESSION['role'] = 'user';
        }
        
        header("Location: dashboard.php");
        exit();
    } else {
        $error_message = "Invalid username or password!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IPN Foundation - Admin Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4f46e5;
            --primary-light: #818cf8;
            --primary-dark: #3730a3;
            --secondary-color: #f97316;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #06b6d4;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --gray-900: #0f172a;
            --box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --card-border-radius: 1rem;
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, var(--gray-800), var(--gray-900));
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray-700);
            padding: 2rem 1rem;
        }
        
        .login-container {
            display: flex;
            max-width: 1000px;
            background: white;
            border-radius: var(--card-border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            position: relative;
            width: 100%;
        }
        
        .login-left {
            flex: 1;
            padding: 3.5rem;
            z-index: 1;
        }
        
        .login-right {
            flex: 1;
            background: linear-gradient(135deg, var(--primary-color), var(--info-color));
            padding: 3.5rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .login-right::before {
            content: "";
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 60%);
            opacity: 0.4;
            animation: pulse 15s infinite;
        }
        
        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 0.4;
            }
            50% {
                transform: scale(1.1);
                opacity: 0.2;
            }
            100% {
                transform: scale(1);
                opacity: 0.4;
            }
        }
        
        .login-logo {
            margin-bottom: 2.5rem;
            text-align: left;
        }
        
        .login-logo h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
            background: linear-gradient(to right, var(--primary-color), var(--info-color));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .login-logo p {
            font-size: 1rem;
            color: var(--gray-500);
            font-weight: 500;
        }
        
        .login-form-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: var(--gray-800);
        }
        
        .form-label {
            font-weight: 500;
            color: var(--gray-700);
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }
        
        .form-control {
            padding: 0.75rem 1rem;
            border: 1px solid var(--gray-300);
            border-radius: 0.5rem;
            font-size: 0.875rem;
            transition: var(--transition);
            background-color: white;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
            outline: none;
        }
        
        .input-group-text {
            background-color: var(--gray-100);
            border: 1px solid var(--gray-300);
            color: var(--gray-600);
            padding: 0.75rem 1rem;
            border-radius: 0.5rem 0 0 0.5rem;
            width: 2.75rem;
            justify-content: center;
        }
        
        .form-control {
            border-radius: 0 0.5rem 0.5rem 0;
        }
        
        .btn-login {
            background: linear-gradient(to right, var(--primary-color), var(--primary-dark));
            color: white;
            font-weight: 600;
            border: none;
            padding: 0.875rem 1.25rem;
            border-radius: 0.5rem;
            width: 100%;
            margin-top: 1.5rem;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            z-index: 1;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, rgba(255,255,255,0) 0%, rgba(255,255,255,0.2) 50%, rgba(255,255,255,0) 100%);
            transition: all 0.8s;
            z-index: -1;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }
        
        .btn-login:hover::before {
            left: 100%;
        }
        
        .features-list {
            margin-top: 2rem;
            list-style: none;
            padding: 0;
        }
        
        .features-list li {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
        }
        
        .features-list li i {
            margin-right: 0.75rem;
            font-size: 1.25rem;
            color: #f8fafc;
            background: rgba(255, 255, 255, 0.2);
            width: 2.5rem;
            height: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        
        .welcome-text {
            margin-bottom: 2rem;
        }
        
        .welcome-text h2 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1rem;
            line-height: 1.2;
        }
        
        .welcome-text p {
            font-size: 1rem;
            opacity: 0.9;
            line-height: 1.6;
        }
        
        .password-toggle {
            cursor: pointer;
            color: var(--gray-600);
            transition: var(--transition);
        }
        
        .password-toggle:hover {
            color: var(--primary-color);
        }
        
        .alert {
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border: none;
            font-weight: 500;
        }
        
        .alert-danger {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
        }
        
        .alert-danger .btn-close {
            color: var(--danger-color);
            filter: none;
            font-size: 0.75rem;
        }
        
        .footer {
            margin-top: 2rem;
            text-align: center;
            font-size: 0.875rem;
            color: var(--gray-500);
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-fade-in {
            animation: fadeIn 0.6s ease-out forwards;
        }
        
        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.2s; }
        .delay-3 { animation-delay: 0.3s; }
        
        /* Responsive */
        @media (max-width: 992px) {
            .login-container {
                flex-direction: column;
                max-width: 500px;
            }
            
            .login-right {
                display: none;
            }
            
            .login-left {
                padding: 2.5rem;
            }
        }
        
        @media (max-width: 576px) {
            .login-left {
                padding: 2rem 1.5rem;
            }
            
            .login-logo h1 {
                font-size: 1.75rem;
            }
            
            .login-form-title {
                font-size: 1.25rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-left">
            <div class="login-logo animate-fade-in">
                <h1>IPN Foundation</h1>
                <p>Events Administration Portal</p>
            </div>
            
            <h2 class="login-form-title animate-fade-in delay-1">Sign In to Dashboard</h2>
            
            <?php if(isset($error_message)): ?>
            <div class="alert alert-danger d-flex align-items-center animate-fade-in" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo $error_message; ?>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <form method="post" action="" class="animate-fade-in delay-2">
                <div class="mb-4">
                    <label for="username" class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control" id="username" name="username" required placeholder="Enter your username" autofocus>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" required placeholder="Enter your password">
                        <span class="input-group-text password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </span>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i> Sign In
                </button>
            </form>
            
            <div class="footer animate-fade-in delay-3">
                <p> © <?php echo date('Y'); ?> IPN Foundation. Crafted By Endeavour Digital.</p>
            </div>
        </div>
        
        <div class="login-right">
            <div class="welcome-text animate-fade-in">
                <h2>Welcome to IPN Foundation Events Dashboard</h2>
                <p>Manage all your event registrations and analytics in one place. Access data from multiple events and gain valuable insights.</p>
            </div>
            
            <ul class="features-list">
                <li class="animate-fade-in delay-1">
                    <i class="fas fa-chart-line"></i>
                    <span>Comprehensive event analytics</span>
                </li>
                <li class="animate-fade-in delay-2">
                    <i class="fas fa-users"></i>
                    <span>Registration management for all events</span>
                </li>
                <li class="animate-fade-in delay-3">
                    <i class="fas fa-file-export"></i>
                    <span>Export data in multiple formats</span>
                </li>
            </ul>
        </div>
    </div>
    
    <script>
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
        
        // Add animation classes on load
        document.addEventListener('DOMContentLoaded', function() {
            const animatedElements = document.querySelectorAll('.animate-fade-in');
            animatedElements.forEach(element => {
                element.style.opacity = '0';
            });
            
            setTimeout(() => {
                animatedElements.forEach(element => {
                    element.style.opacity = '';
                });
            }, 100);
        });
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
