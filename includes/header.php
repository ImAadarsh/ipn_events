<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Include database connection and utilities
require_once 'config/database.php';
require_once 'includes/utils.php';

// Get current page name
$current_page = basename($_SERVER['PHP_SELF']);

// Log page access
logUserActivity($_SESSION['username'], 'Page Access', $current_page);
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
        
        /* Prevent scrolling when sidebar is open on mobile */
        body.sidebar-open {
            overflow: hidden;
            position: fixed;
            width: 100%;
            height: 100%;
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
            display: flex;
            justify-content: space-between;
            align-items: center;
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

        .sidebar-close {
            display: none;
            color: var(--gray-300);
            font-size: 1.25rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .sidebar-close:hover {
            color: white;
            transform: scale(1.1);
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
                z-index: 1050;
                width: 280px;
                height: 100%;
                padding-bottom: 70px; /* Ensures the sidebar scrolls properly on mobile */
            }
            
            .main-content {
                margin-left: 0;
                padding: 1rem;
                width: 100%;
                padding-top: 60px; /* Add space for fixed navbar */
                padding-bottom: 70px; /* Add space for fixed bottom menu */
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .navbar {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                z-index: 1030;
                margin-bottom: 0;
                padding: 0.75rem 1rem;
                flex-wrap: nowrap;
                border-radius: 0;
                background-color: white;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
                transition: transform 0.3s ease;
            }
            
            /* Hide navbar when scrolling down on mobile */
            .navbar.nav-up {
                transform: translateY(-100%);
            }
            
            /* Show navbar when scrolling up */
            .navbar.nav-down {
                transform: translateY(0);
            }
            
            .navbar .page-title {
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                max-width: 85%;
                font-size: 1.15rem;
            }
            
            .navbar .page-title span {
                display: inline-flex;
                align-items: center;
            }
            
            .navbar .page-title button {
                padding: 0.25rem 0.5rem;
                margin-right: 0.5rem;
            }
            
            .navbar .mobile-only {
                display: flex;
            }
            
            .navbar .user-profile {
                display: none;
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
            
            .overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: rgba(0, 0, 0, 0.5);
                z-index: 1040;
            }
            
            .overlay.active {
                display: block;
            }
            
            .sidebar-close {
                display: block;
            }
            
            .user-menu-container {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                background: white;
                padding: 0.75rem 1rem;
                box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
                display: flex;
                justify-content: space-between;
                align-items: center;
                z-index: 1010;
                height: 60px;
            }
        }
        
        @media (max-width: 768px) {
            .navbar {
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
                gap: 0.75rem;
                padding: 0.5rem 1rem;
            }
            
            .navbar .page-title {
                font-size: 1.1rem;
                width: auto;
                margin: 0;
            }
            
            .user-menu-container {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                background: white;
                padding: 0.75rem 1rem;
                box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
                display: flex;
                justify-content: space-between;
                align-items: center;
                z-index: 1010;
            }
            
            .user-profile {
                width: 100%;
                justify-content: space-between;
            }
            
            .user-profile .avatar {
                width: 35px;
                height: 35px;
                font-size: 0.875rem;
            }
            
            .dataTables_wrapper .dataTables_length, 
            .dataTables_wrapper .dataTables_filter {
                text-align: left;
                width: 100%;
                margin-bottom: 0.5rem;
            }
            
            .dataTables_wrapper .dataTables_filter input {
                width: 100%;
                margin-left: 0;
                margin-top: 0.5rem;
            }
            
            .dataTables_wrapper .dataTables_paginate {
                white-space: nowrap;
                overflow-x: auto;
                width: 100%;
                text-align: center;
            }
            
            .card-body {
                padding: 1rem;
            }
            
            .card-header {
                padding: 1rem;
            }
        }
        
        @media (max-width: 576px) {
            .main-content {
                padding: 0.75rem;
                padding-top: 55px; /* Slightly reduced header padding */
                padding-bottom: 65px; /* Slightly reduced footer padding */
            }
            
            .navbar {
                padding: 0.5rem 0.75rem;
                height: 55px;
            }
            
            .user-menu-container {
                padding: 0.625rem 0.75rem;
                height: 55px;
            }
            
            .table thead th, .table tbody td {
                padding: 0.75rem 0.5rem;
                font-size: 0.8rem;
            }

            /* Make tables responsive on very small screens */
            .table-responsive {
                display: block;
                width: 100%;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            /* Optimize cards for mobile */
            .card-header {
                padding: 0.75rem;
            }
            
            .card-body {
                padding: 0.75rem;
            }
            
            /* Reduce font sizes */
            h1, .h1 { font-size: 1.5rem; }
            h2, .h2 { font-size: 1.25rem; }
            h3, .h3 { font-size: 1.125rem; }
            h4, .h4 { font-size: 1rem; }
            h5, .h5 { font-size: 0.875rem; }
            
            /* Optimize forms for mobile */
            .form-group {
                margin-bottom: 0.75rem;
            }
            
            /* Make buttons more touch-friendly */
            .btn {
                padding: 0.5rem 1rem;
                font-size: 0.875rem;
            }
            
            .btn-sm {
                padding: 0.25rem 0.5rem;
                font-size: 0.75rem;
            }
        }
        
        /* Chart styling */
        canvas {
            max-width: 100%;
        }
        
        /* Chart container for mobile */
        .chart-container {
            position: relative;
            height: 350px; /* Default height */
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        @media (max-width: 768px) {
            .chart-container {
                height: 300px;
                display: none; /* Hidden by default on mobile */
            }
            
            .chart-container.d-block {
                display: block !important; /* Show when toggled */
            }
            
            /* Simplified chart styles for mobile */
            .chart-container canvas {
                margin-top: 0.5rem;
            }
            
            /* More space for legends at bottom on mobile */
            .chart-container.with-legend {
                padding-bottom: 100px;
            }
        }
        
        @media (max-width: 576px) {
            .chart-container {
                height: 250px;
            }
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
            <div>
                <h2>IPN Foundation</h2>
                <p class="mb-0">Events Admin</p>
            </div>
            <div class="sidebar-close" id="sidebar-close">
                <i class="fas fa-times"></i>
            </div>
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
            <?php if (isAdmin()): ?>
            <li>
                <a href="logs.php" class="<?php echo ($current_page == 'logs.php') ? 'active' : ''; ?>">
                    <i class="fas fa-history"></i> User Logs
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Navbar -->
        <nav class="navbar">
            <h1 class="page-title">
                <button id="sidebar-toggle" class="btn btn-sm btn-outline-primary d-lg-none">
                    <i class="fas fa-bars"></i>
                </button>
                <span>
                <?php
                switch($current_page) {
                    case 'dashboard.php':
                        echo '<i class="fas fa-tachometer-alt text-primary me-1"></i> <span class="d-none d-sm-inline">Dashboard</span>';
                        break;
                    case 'conclaves.php':
                        echo '<i class="fas fa-users text-primary me-1"></i> <span class="d-none d-sm-inline">IPN </span>Conclaves';
                        break;
                    case 'yuva.php':
                        echo '<i class="fas fa-graduation-cap text-primary me-1"></i> Yuva Summit';
                        break;
                    case 'leaderssummit.php':
                        echo '<i class="fas fa-globe-asia text-primary me-1"></i> <span class="d-none d-sm-inline">Leaders </span>Summit';
                        break;
                    case 'misb.php':
                        echo '<i class="fas fa-award text-primary me-1"></i> <span class="d-none d-sm-inline">Impactful </span>Schools';
                        break;
                    case 'ils.php':
                        echo '<i class="fas fa-landmark text-primary me-1"></i> IPN Leadership';
                        break;
                    case 'quest.php':
                        echo '<i class="fas fa-trophy text-primary me-1"></i> Quest 2025';
                        break;
                    case 'analytics.php':
                        echo '<i class="fas fa-chart-bar text-primary me-1"></i> Analytics';
                        break;
                    default:
                        echo 'IPN Foundation';
                }
                ?>
                </span>
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
                <div class="d-flex align-items-center">
                    <div class="avatar"><?php echo $initials; ?></div>
                    <span class="d-none d-sm-inline ms-2"><?php echo $username; ?></span>
                </div>
                <a href="logout.php" class="btn btn-sm btn-outline-danger ms-3">
                    <i class="fas fa-sign-out-alt"></i> <span class="d-none d-sm-inline">Logout</span>
                </a>
            </div>
        </nav>
        
        <!-- Mobile User Menu (Fixed at Bottom) -->
        <div class="user-menu-container d-md-none">
            <div class="d-flex align-items-center">
                <div class="avatar"><?php echo $initials; ?></div>
                <span class="ms-2"><?php echo $username; ?></span>
            </div>
            <a href="logout.php" class="btn btn-sm btn-outline-danger">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
        
        <!-- Page Content Starts Here -->


    
    <!-- Mobile overlay for sidebar -->
    <div class="overlay" id="sidebar-overlay"></div>
    
    <!-- jQuery -->
