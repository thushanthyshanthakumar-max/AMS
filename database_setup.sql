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
    reg_no VARCHAR(50) UNIQUE NOT NULL,
    title ENUM('MR', 'MISS', 'MRS') NOT NULL,
    name VARCHAR(100) NOT NULL,
    group_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
    INDEX idx_group_id (group_id),
    INDEX idx_reg_no (reg_no)
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
('admin', '$2y$10$XcyYRW92Ov4TRwrKwzYmwOa3.HtK7Jh1GMlEfduOtbV4E2HC21Ogm', 'admin');

-- Teachers for each group (10 lecturers)
INSERT INTO users (username, password, role) VALUES 
('teacher1', '$2y$10$FvU/MXpJkUzAyK9EveKrC.y4/13rRka8oIFOaTutVgcrfWRm3HlOG', 'teacher'),
('teacher2', '$2y$10$FvU/MXpJkUzAyK9EveKrC.y4/13rRka8oIFOaTutVgcrfWRm3HlOG', 'teacher'),
('teacher3', '$2y$10$FvU/MXpJkUzAyK9EveKrC.y4/13rRka8oIFOaTutVgcrfWRm3HlOG', 'teacher'),
('teacher4', '$2y$10$FvU/MXpJkUzAyK9EveKrC.y4/13rRka8oIFOaTutVgcrfWRm3HlOG', 'teacher'),
('teacher5', '$2y$10$FvU/MXpJkUzAyK9EveKrC.y4/13rRka8oIFOaTutVgcrfWRm3HlOG', 'teacher'),
('teacher6', '$2y$10$FvU/MXpJkUzAyK9EveKrC.y4/13rRka8oIFOaTutVgcrfWRm3HlOG', 'teacher'),
('teacher7', '$2y$10$FvU/MXpJkUzAyK9EveKrC.y4/13rRka8oIFOaTutVgcrfWRm3HlOG', 'teacher'),
('teacher8', '$2y$10$FvU/MXpJkUzAyK9EveKrC.y4/13rRka8oIFOaTutVgcrfWRm3HlOG', 'teacher'),
('teacher9', '$2y$10$FvU/MXpJkUzAyK9EveKrC.y4/13rRka8oIFOaTutVgcrfWRm3HlOG', 'teacher'),
('teacher10', '$2y$10$FvU/MXpJkUzAyK9EveKrC.y4/13rRka8oIFOaTutVgcrfWRm3HlOG', 'teacher');

INSERT INTO teachers (user_id, name, email, phone) VALUES 
(2, 'Lecturer Group 01', 'lecturer01@univ.jfn.ac.lk', '021-2218000'),
(3, 'Lecturer Group 02', 'lecturer02@univ.jfn.ac.lk', '021-2218001'),
(4, 'Lecturer Group 03', 'lecturer03@univ.jfn.ac.lk', '021-2218002'),
(5, 'Lecturer Group 04', 'lecturer04@univ.jfn.ac.lk', '021-2218003'),
(6, 'Lecturer Group 05', 'lecturer05@univ.jfn.ac.lk', '021-2218004'),
(7, 'Lecturer Group 06', 'lecturer06@univ.jfn.ac.lk', '021-2218005'),
(8, 'Lecturer Group 07', 'lecturer07@univ.jfn.ac.lk', '021-2218006'),
(9, 'Lecturer Group 08', 'lecturer08@univ.jfn.ac.lk', '021-2218007'),
(10, 'Lecturer Group 09', 'lecturer09@univ.jfn.ac.lk', '021-2218008'),
(11, 'Lecturer Group 10', 'lecturer10@univ.jfn.ac.lk', '021-2218009');

-- Insert groups (10 groups from PDF)
INSERT INTO groups (name, created_by) VALUES 
('GROUP-01 (1P Physics)', 1),
('GROUP-02 (2P Physics)', 1),
('GROUP-03 (3P Physics)', 1),
('GROUP-04 (4P Physics)', 1),
('GROUP-05 (SWC)', 1),
('GROUP-06 (1B Botany)', 1),
('GROUP-07 (2B Botany)', 1),
('GROUP-08 (4M Mathematics)', 1),
('GROUP-09 (FSL Fisheries)', 1),
('GROUP-10 (CSH Computer Science)', 1);

-- Assign teachers to groups
INSERT INTO group_assignments (group_id, teacher_id) VALUES 
(1, 1), (2, 2), (3, 3), (4, 4), (5, 5), (6, 6), (7, 7), (8, 8), (9, 9), (10, 10);
