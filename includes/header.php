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
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Styles -->
    <link rel="stylesheet" href="<?php echo isset($baseUrl) ? $baseUrl : ''; ?>/css/style.css">
    <style>
        .app-header {
            background: white;
            box-shadow: var(--shadow-sm);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo-area {
            display: flex;
            align-items: center;
            gap: 1rem;
            text-decoration: none;
            color: var(--primary-color);
        }

        .logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            box-shadow: var(--shadow-md);
        }

        .logo-text {
            display: flex;
            flex-direction: column;
        }

        .app-name {
            font-weight: 700;
            font-size: 1.25rem;
            line-height: 1.2;
            color: var(--text-primary);
        }

        .app-label {
            font-size: 0.75rem;
            color: var(--text-secondary);
            font-weight: 500;
            letter-spacing: 0.5px;
        }

        .main-nav ul {
            display: flex;
            list-style: none;
            gap: 0.5rem;
            margin: 0;
            padding: 0;
        }

        .main-nav a {
            text-decoration: none;
            color: var(--text-secondary);
            font-weight: 600;
            padding: 0.75rem 1rem;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: var(--transition);
            font-size: 0.95rem;
        }

        .main-nav a:hover {
            color: var(--primary-color);
            background: var(--light-color);
        }

        .main-nav a.active {
            color: var(--primary-color);
            background: rgba(99, 102, 241, 0.1);
        }

        .main-nav i {
            font-size: 1.1em;
        }

        .user-area {
            display: flex;
            align-items: center;
            gap: 1rem;
            position: relative;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: var(--radius-lg);
            transition: var(--transition);
        }

        .user-area:hover {
            background: var(--light-color);
        }

        .user-info {
            text-align: right;
            display: none;
        }

        @media (min-width: 768px) {
            .user-info {
                display: block;
            }
        }

        .user-name {
            display: block;
            font-weight: 700;
            color: var(--text-primary);
            font-size: 0.9rem;
        }

        .user-role {
            display: block;
            font-size: 0.75rem;
            color: var(--text-secondary);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: var(--dark-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.1rem;
            border: 2px solid white;
            box-shadow: 0 0 0 2px var(--border-color);
        }

        .user-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-lg);
            border-radius: var(--radius-lg);
            width: 200px;
            display: none;
            opacity: 0;
            transform: translateY(10px);
            transition: var(--transition);
            overflow: hidden;
            margin-top: 5px;
        }

        .user-area:hover .user-dropdown {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }

        .user-dropdown a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            text-decoration: none;
            color: var(--text-secondary);
            font-weight: 500;
            transition: var(--transition);
        }

        .user-dropdown a:hover {
            background: var(--light-color);
            color: var(--primary-color);
        }

        .user-dropdown .logout-link {
            border-top: 1px solid var(--border-color);
            color: var(--danger-color);
        }

        .user-dropdown .logout-link:hover {
            background: #fef2f2;
            color: #dc2626;
        }

        .alert {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.5rem;
            border-radius: var(--radius-md);
            margin-bottom: 2rem;
            border: 1px solid transparent;
            font-weight: 500;
            box-shadow: var(--shadow-sm);
        }

        .alert-success {
            background: #ecfdf5;
            color: #059669;
            border-color: #a7f3d0;
        }

        .alert-danger {
            background: #fef2f2;
            color: #dc2626;
            border-color: #fecaca;
        }

        .alert-icon {
            font-size: 1.25rem;
        }
    </style>
</head>
<body>
    <?php if (isLoggedIn()): 
        $currentScript = basename($_SERVER['PHP_SELF']);
    ?>
    <header class="app-header">
        <div class="header-container">
            <a href="#" class="logo-area">
                <div class="logo-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="logo-text">
                    <span class="app-name">AMS</span>
                    <span class="app-label">Attendance System</span>
                </div>
            </a>

            <nav class="main-nav">
                <ul>
                    <?php if (isAdmin()): ?>
                        <li><a href="<?php echo isset($baseUrl) ? $baseUrl : ''; ?>/admin/dashboard.php" class="<?php echo $currentScript == 'dashboard.php' ? 'active' : ''; ?>"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
                        <li><a href="<?php echo isset($baseUrl) ? $baseUrl : ''; ?>/admin/students.php" class="<?php echo $currentScript == 'students.php' ? 'active' : ''; ?>"><i class="fas fa-user-graduate"></i><span>Students</span></a></li>
                        <li><a href="<?php echo isset($baseUrl) ? $baseUrl : ''; ?>/admin/partitions.php" class="<?php echo $currentScript == 'partitions.php' ? 'active' : ''; ?>"><i class="fas fa-clock"></i><span>Schedule</span></a></li>
                        <li><a href="<?php echo isset($baseUrl) ? $baseUrl : ''; ?>/admin/teachers.php" class="<?php echo $currentScript == 'teachers.php' ? 'active' : ''; ?>"><i class="fas fa-chalkboard-teacher"></i><span>Teachers</span></a></li>
                        <li><a href="<?php echo isset($baseUrl) ? $baseUrl : ''; ?>/admin/groups.php" class="<?php echo $currentScript == 'groups.php' ? 'active' : ''; ?>"><i class="fas fa-users"></i><span>Groups</span></a></li>
                    <?php elseif (isTeacher()): ?>
                        <li><a href="<?php echo isset($baseUrl) ? $baseUrl : ''; ?>/teacher/dashboard.php" class="<?php echo $currentScript == 'dashboard.php' ? 'active' : ''; ?>"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
                        <li><a href="<?php echo isset($baseUrl) ? $baseUrl : ''; ?>/teacher/mark_attendance.php" class="<?php echo $currentScript == 'mark_attendance.php' ? 'active' : ''; ?>"><i class="fas fa-clipboard-check"></i><span>Mark</span></a></li>
                        <li><a href="<?php echo isset($baseUrl) ? $baseUrl : ''; ?>/teacher/students.php" class="<?php echo $currentScript == 'students.php' ? 'active' : ''; ?>"><i class="fas fa-user-graduate"></i><span>Students</span></a></li>
                        <li><a href="<?php echo isset($baseUrl) ? $baseUrl : ''; ?>/teacher/reports.php" class="<?php echo $currentScript == 'reports.php' ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i><span>Reports</span></a></li>
                        <li><a href="<?php echo isset($baseUrl) ? $baseUrl : ''; ?>/teacher/profile.php" class="<?php echo $currentScript == 'profile.php' ? 'active' : ''; ?>"><i class="fas fa-user-cog"></i><span>Profile</span></a></li>
                    <?php endif; ?>
                </ul>
            </nav>

            <div class="user-area">
                <div class="user-info">
                    <span class="user-role"><?php echo isAdmin() ? 'Administrator' : 'Teacher'; ?></span>
                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                </div>
                <div class="user-avatar">
                   <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                </div>
                <div class="user-dropdown">
                    <?php if (isTeacher()): ?>
                        <a href="<?php echo isset($baseUrl) ? $baseUrl : ''; ?>/teacher/profile.php"><i class="fas fa-user-cog"></i> Profile Settings</a>
                    <?php endif; ?>
                    <a href="<?php echo isset($baseUrl) ? $baseUrl : ''; ?>/logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
                </div>
            </div>
        </div>
    </header>
    <?php endif; ?>
    
    <div class="main-content">
        <div class="container">
            <?php
            $flash = getFlashMessage();
            if ($flash):
            ?>
            <div class="alert alert-<?php echo $flash['type']; ?>">
                <div class="alert-icon">
                    <?php if ($flash['type'] === 'success'): ?>
                        <i class="fas fa-check-circle"></i>
                    <?php elseif ($flash['type'] === 'danger'): ?>
                        <i class="fas fa-exclamation-circle"></i>
                    <?php else: ?>
                        <i class="fas fa-info-circle"></i>
                    <?php endif; ?>
                </div>
                <div class="alert-content"><?php echo htmlspecialchars($flash['message']); ?></div>
            </div>
            <?php endif; ?>
