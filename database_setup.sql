-- Student Attendance Management System Database Setup
-- Drop existing database if exists and create new one
DROP DATABASE IF EXISTS attendance_system;
CREATE DATABASE attendance_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE attendance_system;

-- Users table (for admins and teachers)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'teacher') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_role (role)
) ENGINE=InnoDB;

-- Teachers table (teacher details)
CREATE TABLE teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB;

-- Groups table (classes/groups)
CREATE TABLE groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB;

-- Students table (student details)
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    phone VARCHAR(20),
    group_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
    INDEX idx_group_id (group_id)
) ENGINE=InnoDB;

-- Group assignments (many-to-many for teachers to groups)
CREATE TABLE group_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    teacher_id INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    UNIQUE KEY unique_assignment (group_id, teacher_id),
    INDEX idx_group_id (group_id),
    INDEX idx_teacher_id (teacher_id)
) ENGINE=InnoDB;

-- Partitions table (dynamic class sessions)
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
) ENGINE=InnoDB;

-- Attendance table (attendance records)
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
) ENGINE=InnoDB;

-- Insert default admin user
-- Username: admin, Password: admin123 (hashed)
INSERT INTO users (username, password, role) VALUES 
('admin', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe6/6.N0GqvLxXLqCpJNPqLqKOTqWqmKu', 'admin');

-- Sample data for testing (optional)
-- Insert sample teacher
INSERT INTO users (username, password, role) VALUES 
('teacher1', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe6/6.N0GqvLxXLqCpJNPqLqKOTqWqmKu', 'teacher');

INSERT INTO teachers (user_id, name, email, phone) VALUES 
(2, 'John Smith', 'john.smith@school.com', '555-0101');

-- Insert sample group
INSERT INTO groups (name, created_by) VALUES 
('Class 10A', 1);

-- Insert sample students
INSERT INTO students (name, email, phone, group_id) VALUES 
('Alice Johnson', 'alice.j@student.com', '555-0201', 1),
('Bob Williams', 'bob.w@student.com', '555-0202', 1),
('Carol Davis', 'carol.d@student.com', '555-0203', 1);

-- Assign teacher to group
INSERT INTO group_assignments (group_id, teacher_id) VALUES 
(1, 1);

-- Insert sample partitions
INSERT INTO partitions (group_id, name, start_time, end_time, day_of_week) VALUES 
(1, 'Morning Class', '09:00:00', '12:00:00', 'Monday'),
(1, 'Morning Class', '09:00:00', '12:00:00', 'Wednesday'),
(1, 'Morning Class', '09:00:00', '12:00:00', 'Friday'),
(1, 'Evening Class', '14:00:00', '17:00:00', 'Tuesday'),
(1, 'Evening Class', '14:00:00', '17:00:00', 'Thursday');
