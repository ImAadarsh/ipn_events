<?php
session_start();

// Include utility functions
require_once 'includes/utils.php';

// Log logout if user was logged in
if (isset($_SESSION['username'])) {
    logUserActivity($_SESSION['username'], 'Logout', 'User logged out');
}

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: index.php");
exit();
?> 