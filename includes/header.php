<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Include database connection
require_once 'config/database.php';

// Get current page name
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IPN Foundation - Events Admin Dashboard</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.3/css/buttons.dataTables.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="includes/style.css">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --sidebar-width: 280px;
            --sidebar-bg: #1e293b;
            --primary-color: #4f46e5;
            --primary-light: #818cf8;
            --primary-dark: #3730a3;
            --secondary-color: #f97316;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #06b6d4;
            --light-color: #f8fafc;
            --dark-color: #0f172a;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --gray-900: #0f172a;
            --border-radius: 0.5rem;
            --card-border-radius: 1rem;
            --box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--gray-100);
            color: var(--gray-800);
            overflow-x: hidden;
            line-height: 1.6;
        }
        
        /* Scrollbar styling */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: var(--gray-200);
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--gray-400);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: var(--gray-500);
        }
        
        /* Sidebar styling */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: var(--sidebar-width);
            background: linear-gradient(to bottom, var(--sidebar-bg), var(--dark-color));
            color: var(--light-color);
            z-index: 100;
            transition: var(--transition);
            box-shadow: var(--box-shadow);
            padding-top: 1rem;
            overflow-y: auto;
        }
        
        .sidebar-brand {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 1rem;
        }
        
        .sidebar-brand h2 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            background: linear-gradient(to right, var(--primary-light), var(--info-color));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .sidebar-brand p {
            font-size: 0.875rem;
            opacity: 0.8;
            margin-top: 0.25rem;
        }
        
        .sidebar-nav {
            padding: 0;
            list-style: none;
            margin: 1.5rem 0;
        }
        
        .sidebar-nav li {
            margin-bottom: 0.25rem;
        }
        
        .sidebar-nav li a {
            display: flex;
            align-items: center;
            padding: 0.875rem 1.5rem;
            color: var(--gray-300);
            text-decoration: none;
            transition: var(--transition);
            border-radius: 0 2rem 2rem 0;
            margin-right: 1rem;
            font-weight: 500;
        }
        
        .sidebar-nav li a i {
            margin-right: 0.875rem;
            font-size: 1.125rem;
            width: 1.5rem;
            text-align: center;
            transition: var(--transition);
        }
        
        .sidebar-nav li a:hover {
            color: white;
            background-color: rgba(255, 255, 255, 0.08);
        }
        
        .sidebar-nav li a:hover i {
            transform: translateX(2px);
        }
        
        .sidebar-nav li a.active {
            background: linear-gradient(90deg, rgba(79, 70, 229, 0.2) 0%, rgba(79, 70, 229, 0) 100%);
            color: white;
            font-weight: 600;
            border-left: 4px solid var(--primary-color);
        }
        
        .sidebar-nav li a.active i {
            color: var(--primary-light);
        }
        
        /* Main content styling */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 1.5rem 2rem;
            min-height: 100vh;
            transition: var(--transition);
        }
        
        /* Navbar styling */
        .navbar {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            padding: 0.75rem 1.5rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 1rem;
            z-index: 99;
        }
        
        .navbar .page-title {
            font-weight: 600;
            font-size: 1.25rem;
            margin: 0;
            color: var(--gray-800);
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            position: relative;
        }
        
        .user-profile .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--info-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: var(--transition);
            margin-right: 0.75rem;
        }
        
        .user-profile .avatar:hover {
            transform: scale(1.05);
        }
        
        .user-profile span {
            font-weight: 500;
            color: var(--gray-700);
        }
        
        /* Card styling */
        .card {
            background-color: white;
            border-radius: var(--card-border-radius);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: var(--transition);
            border: none;
            overflow: hidden;
            height: 100%;
        }
        
        .card:hover {
            box-shadow: var(--box-shadow);
            transform: translateY(-3px);
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid var(--gray-200);
            font-weight: 600;
            color: var(--gray-800);
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .card-header h5 {
            margin: 0;
            font-size: 1.125rem;
            color: white
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        /* Stats cards */
        .stats-card {
            background-color: white;
            border-radius: var(--card-border-radius);
            padding: 1.5rem;
            height: 100%;
            position: relative;
            overflow: hidden;
            transition: var(--transition);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        
        .stats-card::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(to right, var(--primary-color), var(--info-color));
            border-radius: 0 0 var(--card-border-radius) var(--card-border-radius);
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--box-shadow);
        }
        
        .stats-card i {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, var(--primary-color), var(--info-color));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .stats-card h3 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
            color: var(--gray-800);
        }
        
        .stats-card p {
            color: var(--gray-500);
            font-size: 0.875rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        /* Table styling */
        .table {
            width: 100%;
            margin-bottom: 1rem;
            color: var(--gray-700);
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .table thead th {
            background-color: var(--gray-100);
            color: var(--gray-600);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 1px;
            padding: 1rem;
            border-bottom: 2px solid var(--gray-200);
        }
        
        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid var(--gray-200);
            font-size: 0.875rem;
        }
        
        .table tbody tr:last-child td {
            border-bottom: none;
        }
        
        .table-hover tbody tr {
            transition: var(--transition);
        }
        
        .table-hover tbody tr:hover {
            background-color: var(--gray-100);
        }
        
        .table .badge {
            padding: 0.5rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 2rem;
        }
        
        .table .badge.bg-success {
            background-color: var(--success-color) !important;
        }
        
        .table .badge.bg-warning {
            background-color: var(--warning-color) !important;
            color: var(--dark-color) !important;
        }
        
        .table .badge.bg-info {
            background-color: var(--info-color) !important;
        }
        
        .table .badge.bg-danger {
            background-color: var(--danger-color) !important;
        }
        
        /* Form styling */
        .form-label {
            color: var(--gray-700);
            font-weight: 500;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }
        
        .form-control, .form-select {
            padding: 0.625rem 1rem;
            border: 1px solid var(--gray-300);
            border-radius: 0.5rem;
            transition: var(--transition);
            font-size: 0.875rem;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2);
            outline: none;
        }
        
        /* Button styling */
        .btn {
            padding: 0.625rem 1.25rem;
            border-radius: 0.5rem;
            font-weight: 500;
            font-size: 0.875rem;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            line-height: 1.5;
        }
        
        .btn i {
            margin-right: 0.5rem;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
            color: white;
        }
        
        .btn-secondary {
            background-color: var(--gray-500);
            border-color: var(--gray-500);
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: var(--gray-600);
            border-color: var(--gray-600);
            color: white;
        }
        
        .btn-outline-primary {
            border-color: var(--primary-color);
            color: var(--primary-color);
            background-color: transparent;
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.75rem;
        }
        
        /* Responsive design */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .navbar {
                position: static;
                margin-bottom: 1rem;
            }
            
            .stats-card {
                margin-bottom: 1rem;
            }
            
            .stats-card i {
                font-size: 2rem;
            }
            
            .stats-card h3 {
                font-size: 1.5rem;
            }
        }
        
        /* Chart styling */
        canvas {
            max-width: 100%;
        }
        
        /* Datatable styling */
        .dataTables_wrapper .dataTables_length, 
        .dataTables_wrapper .dataTables_filter {
            margin-bottom: 1rem;
        }
        
        .dataTables_wrapper .dataTables_length select {
            padding: 0.375rem 1.5rem 0.375rem 0.75rem;
            border-radius: 0.25rem;
            border: 1px solid var(--gray-300);
        }
        
        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid var(--gray-300);
            border-radius: 0.25rem;
            padding: 0.375rem 0.75rem;
            margin-left: 0.5rem;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 0.375rem 0.75rem;
            border-radius: 0.25rem;
            border: 1px solid var(--gray-300);
            background-color: white;
            color: var(--gray-700) !important;
            margin: 0 0.25rem;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
            color: white !important;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: var(--gray-100) !important;
            border-color: var(--gray-400) !important;
            color: var(--gray-800) !important;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <h2>IPN Foundation</h2>
            <p class="mb-0">Events Admin</p>
        </div>
        
        <!-- Sidebar Menu -->
        <ul class="sidebar-nav">
            <li>
                <a href="dashboard.php" class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="conclaves.php" class="<?php echo ($current_page == 'conclaves.php') ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i> IPN Conclaves
                </a>
            </li>
            <li>
                <a href="yuva.php" class="<?php echo ($current_page == 'yuva.php') ? 'active' : ''; ?>">
                    <i class="fas fa-graduation-cap"></i> Yuva Summit
                </a>
            </li>
            <li>
                <a href="leaderssummit.php" class="<?php echo ($current_page == 'leaderssummit.php') ? 'active' : ''; ?>">
                    <i class="fas fa-globe-asia"></i> Leaders Summit
                </a>
            </li>
            <li>
                <a href="misb.php" class="<?php echo ($current_page == 'misb.php') ? 'active' : ''; ?>">
                    <i class="fas fa-award"></i> Impactful Schools
                </a>
            </li>
            <li>
                <a href="ils.php" class="<?php echo ($current_page == 'ils.php') ? 'active' : ''; ?>">
                    <i class="fas fa-landmark"></i> IPN Leadership
                </a>
            </li>
            <li>
                <a href="quest.php" class="<?php echo ($current_page == 'quest.php') ? 'active' : ''; ?>">
                    <i class="fas fa-trophy"></i> Quest 2025
                </a>
            </li>
            <li>
                <a href="analytics.php" class="<?php echo ($current_page == 'analytics.php') ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar"></i> Analytics
                </a>
            </li>
        </ul>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Navbar -->
        <nav class="navbar">
            <h1 class="page-title">
                <?php
                switch($current_page) {
                    case 'dashboard.php':
                        echo '<i class="fas fa-tachometer-alt text-primary me-2"></i> Dashboard';
                        break;
                    case 'conclaves.php':
                        echo '<i class="fas fa-users text-primary me-2"></i> IPN Conclaves';
                        break;
                    case 'yuva.php':
                        echo '<i class="fas fa-graduation-cap text-primary me-2"></i> Yuva Summit';
                        break;
                    case 'leaderssummit.php':
                        echo '<i class="fas fa-globe-asia text-primary me-2"></i> Leaders Summit';
                        break;
                    case 'misb.php':
                        echo '<i class="fas fa-award text-primary me-2"></i> Impactful Schools';
                        break;
                    case 'ils.php':
                        echo '<i class="fas fa-landmark text-primary me-2"></i> IPN Leadership';
                        break;
                    case 'quest.php':
                        echo '<i class="fas fa-trophy text-primary me-2"></i> Quest 2025';
                        break;
                    case 'analytics.php':
                        echo '<i class="fas fa-chart-bar text-primary me-2"></i> Analytics';
                        break;
                    default:
                        echo 'IPN Foundation Events';
                }
                ?>
            </h1>
            
            <div class="user-profile dropdown">
                <?php
                $username = $_SESSION['username'] ?? '';
                $initials = '';
                if (!empty($username)) {
                    $parts = explode('.', $username);
                    if (count($parts) > 1) {
                        $initials = strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
                    } else {
                        $initials = strtoupper(substr($username, 0, 2));
                    }
                }
                ?>
                <div class="avatar"><?php echo $initials; ?></div>
                <span><?php echo $username; ?></span>
                <a href="logout.php" class="btn btn-sm btn-outline-danger ms-3">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </nav>
        
        <!-- Page Content Starts Here -->
