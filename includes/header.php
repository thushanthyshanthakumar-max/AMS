<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' . APP_NAME : APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo isset($baseUrl) ? $baseUrl : ''; ?>/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php if (isLoggedIn()): ?>
    <nav class="navbar">
        <div class="navbar-brand">
            <i class="fas fa-graduation-cap"></i>
            <span><?php echo APP_NAME; ?></span>
        </div>
        <ul class="navbar-menu">
            <?php if (isAdmin()): ?>
                <li><a href="<?php echo isset($baseUrl) ? $baseUrl : ''; ?>/admin/dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="<?php echo isset($baseUrl) ? $baseUrl : ''; ?>/admin/groups.php"><i class="fas fa-users"></i> Groups</a></li>
                <li><a href="<?php echo isset($baseUrl) ? $baseUrl : ''; ?>/admin/teachers.php"><i class="fas fa-chalkboard-teacher"></i> Teachers</a></li>
                <li><a href="<?php echo isset($baseUrl) ? $baseUrl : ''; ?>/admin/students.php"><i class="fas fa-user-graduate"></i> Students</a></li>
                <li><a href="<?php echo isset($baseUrl) ? $baseUrl : ''; ?>/admin/partitions.php"><i class="fas fa-clock"></i> Partitions</a></li>
                <li><a href="<?php echo isset($baseUrl) ? $baseUrl : ''; ?>/admin/attendance.php"><i class="fas fa-clipboard-check"></i> Attendance</a></li>
            <?php elseif (isTeacher()): ?>
                <li><a href="<?php echo isset($baseUrl) ? $baseUrl : ''; ?>/teacher/dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="<?php echo isset($baseUrl) ? $baseUrl : ''; ?>/teacher/mark_attendance.php"><i class="fas fa-clipboard-check"></i> Mark Attendance</a></li>
                <li><a href="<?php echo isset($baseUrl) ? $baseUrl : ''; ?>/teacher/students.php"><i class="fas fa-user-graduate"></i> Students</a></li>
                <li><a href="<?php echo isset($baseUrl) ? $baseUrl : ''; ?>/teacher/reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
            <?php endif; ?>
        </ul>
        <div class="navbar-user">
            <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <a href="<?php echo isset($baseUrl) ? $baseUrl : ''; ?>/logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </nav>
    <?php endif; ?>
    
    <div class="container">
        <?php
        $flash = getFlashMessage();
        if ($flash):
        ?>
        <div class="alert alert-<?php echo $flash['type']; ?>">
            <?php echo htmlspecialchars($flash['message']); ?>
        </div>
        <?php endif; ?>
