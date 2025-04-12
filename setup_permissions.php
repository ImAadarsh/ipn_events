<?php
// Include header with admin check
session_start();
require_once 'config/database.php';
require_once 'includes/utils.php';

// Ensure only admins can access this page
if (!isAdmin()) {
    header("Location: index.php");
    exit();
}

$success_message = "";
$error_message = "";

// Process setup
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['setup'])) {
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // Step 1: Create users table if it doesn't exist
        $conn->query("
        CREATE TABLE IF NOT EXISTS `ipn_events_dash_users` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `username` varchar(50) NOT NULL,
            `password` varchar(255) NOT NULL,
            `email` varchar(100) DEFAULT NULL,
            `role` enum('admin', 'user') NOT NULL DEFAULT 'user',
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `username` (`username`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        
        // Step 2: Create permissions table if it doesn't exist
        $conn->query("
        CREATE TABLE IF NOT EXISTS `user_permissions` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` varchar(50) NOT NULL,
            `event_type` varchar(50) NOT NULL,
            `can_view` tinyint(1) NOT NULL DEFAULT 0,
            `can_export` tinyint(1) NOT NULL DEFAULT 0,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `user_event_unique` (`user_id`, `event_type`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        
        // Step 3: Import existing users from .env file
        // This assumes the users are defined in .env and accessed through the $_SESSION mechanism
        // Get all existing usernames from the login system
        $users = [
            'imkaadarsh' => ['name' => 'Adarsh', 'role' => 'admin'],
            'gaurava.ipn' => ['name' => 'Gaurav', 'role' => 'admin'],
            'pooja.ipn' => ['name' => 'Pooja', 'role' => 'user'],
            'vijeta.ipn' => ['name' => 'Vijeta', 'role' => 'user'],
            'shvetambri.ipn' => ['name' => 'Shvetambri', 'role' => 'user'],
            'aarti.endeavour' => ['name' => 'Aarti', 'role' => 'user']
        ];
        
        // Insert users if they don't exist
        foreach ($users as $username => $data) {
            // Check if user already exists
            $result = $conn->query("SELECT id FROM ipn_events_dash_users WHERE username = '$username'");
            if ($result->num_rows == 0) {
                // Insert the user (use dummy password since actual authentication is via .env)
                $role = $data['role'];
                $dummy_password = password_hash('temp_password_'.time(), PASSWORD_DEFAULT);
                $conn->query("INSERT INTO ipn_events_dash_users (username, password, role) VALUES ('$username', '$dummy_password', '$role')");
            }
        }
        
        // Step 4: Set default permissions for admins (all permissions)
        $admin_users = ['imkaadarsh', 'gaurava.ipn'];
        $event_types = ['conclaves', 'yuva', 'leaderssummit', 'misb', 'ils', 'quest'];
        
        foreach ($admin_users as $admin) {
            foreach ($event_types as $event) {
                // Check if permission exists
                $result = $conn->query("SELECT id FROM user_permissions WHERE user_id = '$admin' AND event_type = '$event'");
                if ($result->num_rows == 0) {
                    $conn->query("INSERT INTO user_permissions (user_id, event_type, can_view, can_export) 
                                VALUES ('$admin', '$event', 1, 1)");
                }
            }
        }
        
        // Commit all changes
        $conn->commit();
        
        $success_message = "Permission system has been set up successfully!";
        
        // Log the setup
        logUserActivity($_SESSION['username'], 'System Setup', 'Permission system setup completed');
        
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        $error_message = "Error setting up permission system: " . $e->getMessage();
        
        // Log the error
        logUserActivity($_SESSION['username'], 'System Setup', 'Permission system setup failed: '.$e->getMessage(), 'failure');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Permission System - IPN Foundation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #f1f5f9;
            padding: 2rem;
        }
        .setup-container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 1rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            padding: 2rem;
        }
        .step {
            margin-bottom: 1.5rem;
            padding: 1.25rem;
            background-color: #f8fafc;
            border-radius: 0.5rem;
            border-left: 4px solid #4f46e5;
        }
        .step h3 {
            margin-top: 0;
            font-size: 1.25rem;
            color: #1e293b;
        }
        .step-content {
            color: #334155;
        }
        .code-block {
            background: #0f172a;
            color: #e2e8f0;
            padding: 1rem;
            border-radius: 0.5rem;
            margin: 1rem 0;
            overflow-x: auto;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Permission System Setup</h1>
            <a href="dashboard.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                <hr>
                <p class="mb-0">You can now manage user permissions through the <a href="user_permissions.php" class="alert-link">User Permissions</a> page.</p>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Setup Process</h5>
            </div>
            <div class="card-body">
                <p>This utility will set up the permission system for your IPN Events application. The following steps will be performed:</p>
                
                <div class="step">
                    <h3>Step 1: Create Users Table</h3>
                    <div class="step-content">
                        <p>A new <code>ipn_events_dash_users</code> table will be created to store user information.</p>
                        <div class="code-block">
                            <pre>CREATE TABLE IF NOT EXISTS `ipn_events_dash_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` enum('admin', 'user') NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;</pre>
                        </div>
                    </div>
                </div>
                
                <div class="step">
                    <h3>Step 2: Create Permissions Table</h3>
                    <div class="step-content">
                        <p>A new <code>user_permissions</code> table will be created to store permission settings for each user and event type.</p>
                        <div class="code-block">
                            <pre>CREATE TABLE IF NOT EXISTS `user_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(50) NOT NULL,
  `event_type` varchar(50) NOT NULL,
  `can_view` tinyint(1) NOT NULL DEFAULT 0,
  `can_export` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_event_unique` (`user_id`, `event_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;</pre>
                        </div>
                    </div>
                </div>
                
                <div class="step">
                    <h3>Step 3: Import Existing Users</h3>
                    <div class="step-content">
                        <p>Existing users from your environment configuration will be imported into the users table.</p>
                        <ul>
                            <li>Admin roles will be assigned to: <code>imkaadarsh, gaurava.ipn</code></li>
                            <li>User roles will be assigned to: <code>pooja.ipn, vijeta.ipn, shvetambri.ipn, aarti.endeavour</code></li>
                        </ul>
                    </div>
                </div>
                
                <div class="step">
                    <h3>Step 4: Set Default Permissions</h3>
                    <div class="step-content">
                        <p>Default permissions will be set up:</p>
                        <ul>
                            <li>Admin users will be granted full access to all event types</li>
                            <li>Regular users will need permissions to be assigned through the User Permissions page</li>
                        </ul>
                    </div>
                </div>
                
                <form method="POST" action="" class="mt-4">
                    <div class="d-grid">
                        <button type="submit" name="setup" class="btn btn-primary btn-lg">
                            <i class="fas fa-cog me-2"></i>Run Setup
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Next Steps</h5>
            </div>
            <div class="card-body">
                <p>After running the setup, you should:</p>
                <ol>
                    <li>Go to the <a href="user_permissions.php">User Permissions</a> page to assign specific permissions to each user</li>
                    <li>Test the system by logging in with different user accounts to ensure they can only access their permitted event data</li>
                    <li>Set up additional users as needed through the admin interface</li>
                </ol>
                <p>The permission system adds two main functions to control access:</p>
                <ul>
                    <li><code>canViewEvent($event_type)</code> - Checks if a user can view a specific event type</li>
                    <li><code>canExportEvent($event_type)</code> - Checks if a user can export data for a specific event type</li>
                </ul>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 