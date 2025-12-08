<?php
/**
 * Database Configuration File
 * Automatically detects Railway environment and uses appropriate config
 */

// Check if running on Railway (Railway sets these environment variables)
if (getenv('MYSQLHOST') || getenv('RAILWAY_ENVIRONMENT')) {
    // Use Railway configuration
    require_once __DIR__ . '/config.railway.php';
} else {
    // Use local configuration
    
    // Database configuration
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'attendance_system');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    
    // Application configuration
    define('APP_NAME', 'Student Attendance Management System');
    define('SESSION_TIMEOUT', 1800); // 30 minutes in seconds
    
    // Timezone
    date_default_timezone_set('Asia/Kolkata');
    
    // Database connection using PDO
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}
?>
