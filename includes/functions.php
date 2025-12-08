<?php
/**
 * Common Functions File
 * Contains utility functions used throughout the application
 */

/**
 * Sanitize input data
 */
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

/**
 * Check if user is teacher
 */
function isTeacher() {
    return isLoggedIn() && $_SESSION['role'] === 'teacher';
}

/**
 * Redirect to a page
 */
function redirect($page) {
    header("Location: $page");
    exit();
}

/**
 * Check session timeout
 */
function checkSessionTimeout() {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        session_unset();
        session_destroy();
        redirect('login.php?timeout=1');
    }
    $_SESSION['last_activity'] = time();
}

/**
 * Require login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
    checkSessionTimeout();
}

/**
 * Require admin role
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        redirect('index.php');
    }
}

/**
 * Require teacher role
 */
function requireTeacher() {
    requireLogin();
    if (!isTeacher()) {
        redirect('index.php');
    }
}

/**
 * Generate random password
 */
function generatePassword($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    return substr(str_shuffle($chars), 0, $length);
}

/**
 * Format date for display
 */
function formatDate($date) {
    return date('d M Y', strtotime($date));
}

/**
 * Format time for display
 */
function formatTime($time) {
    return date('h:i A', strtotime($time));
}

/**
 * Get day name from date
 */
function getDayName($date) {
    return date('l', strtotime($date));
}

/**
 * Calculate attendance percentage
 */
function calculateAttendancePercentage($present, $total) {
    if ($total == 0) return 0;
    return round(($present / $total) * 100, 2);
}

/**
 * Get status badge HTML
 */
function getStatusBadge($status) {
    $badges = [
        'present' => '<span class="badge badge-success">Present</span>',
        'absent' => '<span class="badge badge-danger">Absent</span>',
        'late' => '<span class="badge badge-warning">Late</span>'
    ];
    return $badges[$status] ?? '<span class="badge badge-secondary">Unknown</span>';
}

/**
 * Display flash message
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_type'] = $type;
    $_SESSION['flash_message'] = $message;
}

/**
 * Get and clear flash message
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $type = $_SESSION['flash_type'];
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_type']);
        unset($_SESSION['flash_message']);
        return ['type' => $type, 'message' => $message];
    }
    return null;
}

/**
 * Validate email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validate phone
 */
function validatePhone($phone) {
    return preg_match('/^[0-9\-\+\(\)\s]+$/', $phone);
}

/**
 * Get teacher ID from user ID
 */
function getTeacherId($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch();
    return $result ? $result['id'] : null;
}

/**
 * Check if teacher has access to group
 */
function teacherHasAccessToGroup($pdo, $teacherId, $groupId) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM group_assignments WHERE teacher_id = ? AND group_id = ?");
    $stmt->execute([$teacherId, $groupId]);
    return $stmt->fetchColumn() > 0;
}

/**
 * Export array to CSV
 */
function exportToCSV($filename, $data, $headers = []) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    if (!empty($headers)) {
        fputcsv($output, $headers);
    }
    
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit();
}
?>
