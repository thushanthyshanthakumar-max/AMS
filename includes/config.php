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
    //define('DB_HOST', 'localhost');
    //define('DB_NAME', 'attendance_system');
    //define('DB_USER', 'root');
    //define('DB_PASS', '');
     define('DB_HOST', 'sql204.ezyro.com');
     define('DB_NAME', 'ezyro_40750460_attendance_system');
     define('DB_USER', 'ezyro_40750460');
     define('DB_PASS', 'e9ed2ee5c143');
    
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


<?php
/**
 * Database Configuration File
 * Automatically detects environment and uses appropriate config
 * Supports: Local (XAMPP), Railway, InfinityFree
 */

// Check if running on Railway
if (getenv('MYSQLHOST') || getenv('RAILWAY_ENVIRONMENT')) {
    // Use Railway configuration
    require_once __DIR__ . '/config.railway.php';
} 
// Check if running on InfinityFree (check for InfinityFree-specific environment)
elseif (file_exists(__DIR__ . '/config.infinityfree.php')) {
    // Use InfinityFree configuration
    require_once __DIR__ . '/config.infinityfree.php';
} 
// Default to local configuration

