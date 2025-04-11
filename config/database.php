<?php
// Load environment variables
require_once __DIR__ . '/env.php';

// Database connection details from environment
$host = DB_HOST;
$user = DB_USER;
$password = DB_PASSWORD;
$dbname = DB_NAME;

// Create connection
$conn = new mysqli($host, $user, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set character set to utf8mb4
$conn->set_charset("utf8mb4");

// Function to connect to Quest database
function connectQuestDB() {
    $host = QUEST_DB_HOST;
    $user = QUEST_DB_USER;
    $password = QUEST_DB_PASSWORD;
    $dbname = QUEST_DB_NAME;
    
    // Create connection
    $questConn = new mysqli($host, $user, $password, $dbname);
    
    // Check connection
    if ($questConn->connect_error) {
        die("Quest DB Connection failed: " . $questConn->connect_error);
    }
    
    // Set character set to utf8mb4
    $questConn->set_charset("utf8mb4");
    
    return $questConn;
}

// Function to escape and sanitize input
function clean($conn, $data) {
    return $conn->real_escape_string(trim($data));
}

// Function to execute queries and return result
function query($conn, $sql) {
    $result = $conn->query($sql);
    if (!$result) {
        die("Query failed: " . $conn->error);
    }
    return $result;
}

// Function to get a single row
function fetch_row($conn, $sql) {
    $result = query($conn, $sql);
    return $result->fetch_assoc();
}

// Function to get multiple rows
function fetch_all($conn, $sql) {
    $result = query($conn, $sql);
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

// Function to count rows
function count_rows($conn, $sql) {
    $result = query($conn, $sql);
    return $result->num_rows;
}

// Function to insert data
function insert($conn, $table, $data) {
    $columns = implode(", ", array_keys($data));
    $values = "'" . implode("', '", array_map(function($value) use ($conn) {
        return clean($conn, $value);
    }, array_values($data))) . "'";
    
    $sql = "INSERT INTO $table ($columns) VALUES ($values)";
    
    if ($conn->query($sql) === TRUE) {
        // Log the insert operation if utils.php is loaded
        if (function_exists('logUserActivity') && isset($_SESSION['username'])) {
            $details = "Added new record to $table (ID: {$conn->insert_id})";
            logUserActivity($_SESSION['username'], 'Database Insert', $details);
        }
        return $conn->insert_id;
    } else {
        // Log the error if utils.php is loaded
        if (function_exists('logUserActivity') && isset($_SESSION['username'])) {
            $details = "Error inserting into $table: " . $conn->error;
            logUserActivity($_SESSION['username'], 'Database Insert', $details, 'failure');
        }
        die("Error: " . $sql . "<br>" . $conn->error);
    }
}

// Function to update data
function update($conn, $table, $data, $where) {
    $sets = [];
    foreach ($data as $column => $value) {
        $sets[] = "$column = '" . clean($conn, $value) . "'";
    }
    
    $sql = "UPDATE $table SET " . implode(", ", $sets) . " WHERE $where";
    
    if ($conn->query($sql) === TRUE) {
        // Log the update operation if utils.php is loaded
        if (function_exists('logUserActivity') && isset($_SESSION['username'])) {
            $details = "Updated record in $table where $where";
            logUserActivity($_SESSION['username'], 'Database Update', $details);
        }
        return true;
    } else {
        // Log the error if utils.php is loaded
        if (function_exists('logUserActivity') && isset($_SESSION['username'])) {
            $details = "Error updating $table: " . $conn->error;
            logUserActivity($_SESSION['username'], 'Database Update', $details, 'failure');
        }
        die("Error: " . $sql . "<br>" . $conn->error);
    }
}

// Function to delete data
function delete($conn, $table, $where) {
    $sql = "DELETE FROM $table WHERE $where";
    
    if ($conn->query($sql) === TRUE) {
        // Log the delete operation if utils.php is loaded
        if (function_exists('logUserActivity') && isset($_SESSION['username'])) {
            $details = "Deleted record from $table where $where";
            logUserActivity($_SESSION['username'], 'Database Delete', $details);
        }
        return true;
    } else {
        // Log the error if utils.php is loaded
        if (function_exists('logUserActivity') && isset($_SESSION['username'])) {
            $details = "Error deleting from $table: " . $conn->error;
            logUserActivity($_SESSION['username'], 'Database Delete', $details, 'failure');
        }
        die("Error: " . $sql . "<br>" . $conn->error);
    }
}
?> 