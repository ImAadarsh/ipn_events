<?php
/**
 * Environment Configuration
 * This file loads environment variables from .env file
 */

// Define the function to load environment variables
function loadEnvironmentVariables($path) {
    if (!file_exists($path)) {
        throw new Exception(".env file not found. Please create one in the root directory.");
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if (!array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
        }
    }
}

// Define constants for database connections
function defineDBConstants() {
    // Main database
    define('DB_HOST', getenv('DB_HOST'));
    define('DB_USER', getenv('DB_USER'));
    define('DB_PASSWORD', getenv('DB_PASSWORD'));
    define('DB_NAME', getenv('DB_NAME'));
    
    // Quest database
    define('QUEST_DB_HOST', getenv('QUEST_DB_HOST'));
    define('QUEST_DB_USER', getenv('QUEST_DB_USER'));
    define('QUEST_DB_PASSWORD', getenv('QUEST_DB_PASSWORD'));
    define('QUEST_DB_NAME', getenv('QUEST_DB_NAME'));
}

// Define constants for user credentials
function defineUserCredentials() {
    define('USER_IMKAADARSH', getenv('USER_IMKAADARSH'));
    define('USER_GAURAVA', getenv('USER_GAURAVA'));
    define('USER_POOJA', getenv('USER_POOJA'));
    define('USER_VIJETA', getenv('USER_VIJETA'));
    define('USER_SHVETAMBRI', getenv('USER_SHVETAMBRI'));
    define('USER_AARTI', getenv('USER_AARTI'));
}

// Load variables from .env file
$envPath = dirname(__DIR__) . '/.env';
loadEnvironmentVariables($envPath);
defineDBConstants(); 
defineUserCredentials(); 