<?php
/**
 * Utility Functions
 * Contains helper functions used throughout the application
 */

/**
 * Log user activity to a text file
 * 
 * @param string $username The username of the user
 * @param string $action The action performed
 * @param string $details Additional details about the action
 * @param string $status Success or failure status
 * @return bool True if log was successfully written
 */
function logUserActivity($username, $action, $details = '', $status = 'success') {
    // Create logs directory if it doesn't exist
    $logDir = __DIR__ . '/logs';
    if (!file_exists($logDir)) {
        mkdir($logDir, 0777, true);
    }
    
    // Current date for filename (YYYY-MM-DD format)
    $today = date('Y-m-d');
    $logFile = $logDir . '/activity_' . $today . '.txt';
    
    // Format timestamp for log entry
    $timestamp = date('Y-m-d H:i:s');
    
    // Format the log entry
    $logEntry = sprintf(
        "[%s] %s | %s | %s | %s | %s\n",
        $timestamp,
        $username,
        $_SERVER['REMOTE_ADDR'],
        $action,
        $details,
        $status
    );
    
    // Write to log file
    return file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX) !== false;
}

/**
 * Format date for display
 * 
 * @param string $dateString MySQL datetime string
 * @param string $format PHP date format string
 * @return string Formatted date
 */
function formatDate($dateString, $format = 'M d, Y h:i A') {
    if (empty($dateString)) return '';
    $date = new DateTime($dateString);
    return $date->format($format);
}

/**
 * Generate a random password
 * 
 * @param int $length Password length
 * @return string Random password
 */
function generateRandomPassword($length = 10) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
    $password = '';
    $charLength = strlen($chars) - 1;
    
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, $charLength)];
    }
    
    return $password;
}

/**
 * Sanitize and validate input
 * 
 * @param string $input Input string to sanitize
 * @return string Sanitized input
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Get current page name
 * 
 * @return string Current page name
 */
function getCurrentPage() {
    return basename($_SERVER['PHP_SELF']);
}

/**
 * Check if user has admin privileges
 * 
 * @return bool True if user is admin
 */
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
} 