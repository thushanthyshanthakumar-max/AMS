<?php
/**
 * Database Setup Helper Script
 * Run this file ONCE to set up the database with correct password hashes
 */

// Database configuration
$host = 'localhost';
$dbname = 'attendance_system';
$username = 'root';
$password = ''; // Change if your MySQL has a password

echo "<h2>Student Attendance Management System - Database Setup</h2>";
echo "<hr>";

try {
    // Connect to MySQL (without database)
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✓ Connected to MySQL server</p>";
    
    // Drop and create database
    $pdo->exec("DROP DATABASE IF EXISTS $dbname");
    $pdo->exec("CREATE DATABASE $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE $dbname");
    
    echo "<p>✓ Database '$dbname' created</p>";
    
    // Create tables
    $pdo->exec("
        CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'lecturer') NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_username (username),
            INDEX idx_role (role)
        ) ENGINE=InnoDB
    ");
    
    $pdo->exec("
        CREATE TABLE lecturers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            phone VARCHAR(20),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id)
        ) ENGINE=InnoDB
    ");
    
    $pdo->exec("
        CREATE TABLE groups (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_created_by (created_by)
        ) ENGINE=InnoDB
    ");
    
    $pdo->exec("
        CREATE TABLE students (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE,
            phone VARCHAR(20),
            group_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
            INDEX idx_group_id (group_id)
        ) ENGINE=InnoDB
    ");
    
    $pdo->exec("
        CREATE TABLE group_assignments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            group_id INT NOT NULL,
            lecturer_id INT NOT NULL,
            assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
            FOREIGN KEY (lecturer_id) REFERENCES lecturers(id) ON DELETE CASCADE,
            UNIQUE KEY unique_assignment (group_id, lecturer_id),
            INDEX idx_group_id (group_id),
            INDEX idx_lecturer_id (lecturer_id)
        ) ENGINE=InnoDB
    ");
    
    $pdo->exec("
        CREATE TABLE partitions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            group_id INT NOT NULL,
            name VARCHAR(100) NOT NULL,
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            day_of_week VARCHAR(20) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
            INDEX idx_group_id (group_id),
            INDEX idx_day_of_week (day_of_week)
        ) ENGINE=InnoDB
    ");
    
    $pdo->exec("
        CREATE TABLE attendance (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            partition_id INT NOT NULL,
            date DATE NOT NULL,
            status ENUM('present', 'absent', 'late') NOT NULL,
            marked_by INT NOT NULL,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
            FOREIGN KEY (partition_id) REFERENCES partitions(id) ON DELETE CASCADE,
            FOREIGN KEY (marked_by) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_attendance (student_id, partition_id, date),
            INDEX idx_student_id (student_id),
            INDEX idx_partition_id (partition_id),
            INDEX idx_date (date),
            INDEX idx_marked_by (marked_by)
        ) ENGINE=InnoDB
    ");
    
    echo "<p>✓ All tables created</p>";
    
    // Hash the password properly
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
    
    // Insert admin user
    $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'admin')");
    $stmt->execute(['admin', $adminPassword]);
    
    echo "<p>✓ Admin user created (username: admin, password: admin123)</p>";
    
    // Insert lecturer user
    $lecturerPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'lecturer')");
    $stmt->execute(['lecturer1', $lecturerPassword]);
    
    echo "<p>✓ Lecturer user created (username: lecturer1, password: admin123)</p>";
    
    // Insert lecturer details
    $pdo->exec("INSERT INTO lecturers (user_id, name, email, phone) VALUES (2, 'John Smith', 'john.smith@school.com', '555-0101')");
    
    // Insert sample group
    $pdo->exec("INSERT INTO groups (name, created_by) VALUES ('Class 10A', 1)");
    
    echo "<p>✓ Sample group created</p>";
    
    // Insert sample students
    $pdo->exec("
        INSERT INTO students (name, email, phone, group_id) VALUES 
        ('Alice Johnson', 'alice.j@student.com', '555-0201', 1),
        ('Bob Williams', 'bob.w@student.com', '555-0202', 1),
        ('Carol Davis', 'carol.d@student.com', '555-0203', 1)
    ");
    
    echo "<p>✓ Sample students added</p>";
    
    // Assign lecturer to group
    $pdo->exec("INSERT INTO group_assignments (group_id, lecturer_id) VALUES (1, 1)");
    
    // Insert sample partitions
    $pdo->exec("
        INSERT INTO partitions (group_id, name, start_time, end_time, day_of_week) VALUES 
        (1, 'Morning Class', '09:00:00', '12:00:00', 'Monday'),
        (1, 'Morning Class', '09:00:00', '12:00:00', 'Wednesday'),
        (1, 'Morning Class', '09:00:00', '12:00:00', 'Friday'),
        (1, 'Evening Class', '14:00:00', '17:00:00', 'Tuesday'),
        (1, 'Evening Class', '14:00:00', '17:00:00', 'Thursday')
    ");
    
    echo "<p>✓ Sample partitions created</p>";
    
    echo "<hr>";
    echo "<h3 style='color: green;'>✓ Setup Complete!</h3>";
    echo "<p><strong>You can now login with:</strong></p>";
    echo "<ul>";
    echo "<li>Admin: <code>admin</code> / <code>admin123</code></li>";
    echo "<li>Lecturer: <code>lecturer1</code> / <code>admin123</code></li>";
    echo "</ul>";
    echo "<p><a href='login.php' style='display: inline-block; padding: 10px 20px; background: #6366f1; color: white; text-decoration: none; border-radius: 5px;'>Go to Login Page</a></p>";
    echo "<hr>";
    echo "<p style='color: red;'><strong>IMPORTANT:</strong> Delete this file (setup.php) after setup is complete for security!</p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    echo "<hr>";
    echo "<h3>Troubleshooting:</h3>";
    echo "<ul>";
    echo "<li>Make sure MySQL/XAMPP is running</li>";
    echo "<li>Check database credentials at the top of this file</li>";
    echo "<li>Ensure MySQL user has permission to create databases</li>";
    echo "</ul>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Database Setup</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        code {
            background: #e0e0e0;
            padding: 2px 6px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
</body>
</html>
