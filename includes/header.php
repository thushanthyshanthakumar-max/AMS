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
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Styles -->
    <link rel="stylesheet" href="<?php echo isset($baseUrl) ? $baseUrl : ''; ?>/css/style.css">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
        }

        .app-header {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            position: sticky;
            top: 0;
            z-index: 1000;
            border-bottom: 1px solid rgba(255, 255, 255, 0.3);
        }

        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo-area {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            color: var(--primary-color);
        }

        .logo-icon {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
            color: white;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.3);
        }

        .logo-text {
            display: flex;
            flex-direction: column;
        }

        .app-name {
            font-weight: 800;
            font-size: 1.4rem;
            letter-spacing: -0.03em;
            color: #0f172a;
        }

        .app-label {
            font-size: 0.7rem;
            color: #64748b;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-top: -2px;
        }

        .main-nav ul {
            display: flex;
            list-style: none;
            gap: 0.25rem;
            margin: 0;
            padding: 0;
        }

        .main-nav a {
            text-decoration: none;
            color: #64748b;
            font-weight: 600;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            transition: all 0.2s;
            font-size: 0.95rem;
        }

        .main-nav a:hover {
            color: var(--primary-color);
            background: rgba(99, 102, 241, 0.05);
            transform: translateY(-1px);
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
            border-radius: 16px;
            transition: all 0.2s;
            border: 1px solid transparent;
        }

        .user-area:hover {
            background: white;
            border-color: #f1f5f9;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        .user-info {
            text-align: right;
            display: none;
            line-height: 1.2;
        }

        @media (min-width: 768px) {
            .user-info {
                display: block;
            }
        }

        .user-name {
            display: block;
            font-weight: 700;
            font-size: 0.9rem;
            color: #0f172a;
        }

        .user-role {
            display: block;
            font-size: 0.7rem;
            color: #64748b;
            font-weight: 600;
        }

        .user-avatar {
            width: 42px;
            height: 42px;
            background: #1e293b;
            color: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.1rem;
            /* Removed border and box-shadow for a cleaner look */
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

        /* Mobile Hamburger Menu Button */
        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #1e293b;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .mobile-menu-toggle:hover {
            background: rgba(99, 102, 241, 0.1);
            color: #6366f1;
        }

        /* Mobile Drawer Overlay */
        .mobile-drawer-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9998;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .mobile-drawer-overlay.active {
            display: block;
            opacity: 1;
        }

        /* Mobile Drawer */
        .mobile-drawer {
            display: none;
            position: fixed;
            top: 0;
            left: -280px;
            width: 280px;
            height: 100vh;
            background: white;
            z-index: 9999;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            transition: left 0.3s ease;
            overflow-y: auto;
        }

        .mobile-drawer.active {
            left: 0;
        }

        .mobile-drawer-header {
            padding: 1.5rem;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .mobile-drawer-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #64748b;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .mobile-drawer-close:hover {
            background: #f8fafc;
            color: #1e293b;
        }

        .mobile-drawer-nav {
            padding: 1rem;
        }

        .mobile-drawer-nav a {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            text-decoration: none;
            color: #64748b;
            font-weight: 600;
            border-radius: 12px;
            margin-bottom: 0.5rem;
            transition: all 0.2s;
        }

        .mobile-drawer-nav a:hover {
            background: rgba(99, 102, 241, 0.05);
            color: #6366f1;
        }

        .mobile-drawer-nav a.active {
            background: rgba(99, 102, 241, 0.1);
            color: #6366f1;
        }

        .mobile-drawer-nav a i {
            font-size: 1.25rem;
            width: 24px;
            text-align: center;
        }

        .mobile-drawer-footer {
            padding: 1rem;
            border-top: 1px solid #f1f5f9;
            margin-top: auto;
        }

        /* Mobile Navigation Styles */
        @media (max-width: 768px) {
            .header-container {
                height: 70px;
                padding: 0 1rem;
            }

            .logo-area h1 {
                font-size: 1.125rem;
            }

            .logo-icon {
                width: 35px;
                height: 35px;
                font-size: 1rem;
            }

            /* Hide desktop navigation */
            .main-nav {
                display: none;
            }

            /* Show mobile menu toggle */
            .mobile-menu-toggle {
                display: block;
            }

            /* Show mobile drawer */
            .mobile-drawer {
                display: block;
            }

            .user-area {
                padding: 0.375rem;
            }

            .user-avatar {
                width: 35px;
                height: 35px;
                font-size: 0.9rem;
            }

            .user-info {
                display: none !important;
            }
        }

        @media (max-width: 640px) {
            .logo-area h1 {
                font-size: 1rem;
            }

            .app-label {
                display: none;
            }
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

        .header-right {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .logout-btn-direct {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: #fef2f2;
            color: #dc2626;
            padding: 0.6rem 1rem;
            border-radius: 12px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.2s;
            font-size: 0.9rem;
            border: 1px solid #fee2e2;
        }

        .logout-btn-direct:hover {
            background: #fee2e2;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(220, 38, 38, 0.1);
        }

        @media (max-width: 768px) {
            .logout-btn-direct span {
                display: none;
            }
            .logout-btn-direct {
                padding: 0;
                height: 35px;
                width: 35px;
                justify-content: center;
                border-radius: 10px;
            }
            .header-right {
                gap: 0.5rem;
            }
        }

        /* Modal Base Styles (Global) */
        .modal {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 100% !important;
            background: rgba(15, 23, 42, 0.75) !important;
            backdrop-filter: blur(8px) !important;
            -webkit-backdrop-filter: blur(8px) !important;
            z-index: 99999 !important;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            opacity: 0;
            transition: all 0.3s ease;
        }

        .modal.active {
            display: flex !important;
            opacity: 1 !important;
        }

        .modal-content {
            background: white !important;
            width: 100% !important;
            max-width: 550px !important;
            border-radius: 24px !important;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5) !important;
            overflow: hidden !important;
            transform: translateY(30px);
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .modal.active .modal-content {
            transform: translateY(0) !important;
        }

        .modal-header {
            padding: 1.5rem 2rem !important;
            background: #f8fafc !important;
            border-bottom: 1px solid #f1f5f9 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
        }

        .modal-header h3 {
            font-size: 1.25rem;
            font-weight: 800;
            color: #1e293b;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .modal-body {
            padding: 2rem !important;
        }

        .modal-footer {
            padding: 1.5rem 2rem !important;
            background: #f8fafc !important;
            border-top: 1px solid #f1f5f9 !important;
            display: flex !important;
            justify-content: flex-end !important;
            gap: 1rem !important;
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
                        <li><a href="<?php echo isset($baseUrl) ? $baseUrl : ''; ?>/admin/lecturers.php" class="<?php echo $currentScript == 'lecturers.php' ? 'active' : ''; ?>"><i class="fas fa-chalkboard-teacher"></i><span>Lecturers</span></a></li>
                        <li><a href="<?php echo isset($baseUrl) ? $baseUrl : ''; ?>/admin/groups.php" class="<?php echo $currentScript == 'groups.php' ? 'active' : ''; ?>"><i class="fas fa-users"></i><span>Groups</span></a></li>
                    <?php elseif (isLecturer()): ?>
                        <li><a href="<?php echo isset($baseUrl) ? $baseUrl : ''; ?>/lecturer/dashboard.php" class="<?php echo $currentScript == 'dashboard.php' ? 'active' : ''; ?>"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
                        <li><a href="<?php echo isset($baseUrl) ? $baseUrl : ''; ?>/lecturer/mark_attendance.php" class="<?php echo $currentScript == 'mark_attendance.php' ? 'active' : ''; ?>"><i class="fas fa-clipboard-check"></i><span>Mark</span></a></li>
                        <li><a href="<?php echo isset($baseUrl) ? $baseUrl : ''; ?>/lecturer/students.php" class="<?php echo $currentScript == 'students.php' ? 'active' : ''; ?>"><i class="fas fa-user-graduate"></i><span>Students</span></a></li>
                        <li><a href="<?php echo isset($baseUrl) ? $baseUrl : ''; ?>/lecturer/reports.php" class="<?php echo $currentScript == 'reports.php' ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i><span>Reports</span></a></li>
                        <li><a href="<?php echo isset($baseUrl) ? $baseUrl : ''; ?>/lecturer/profile.php" class="<?php echo $currentScript == 'profile.php' ? 'active' : ''; ?>"><i class="fas fa-user-cog"></i><span>Profile</span></a></li>
                    <?php endif; ?>
                </ul>
            </nav>

            <div class="header-right">
                <!-- Mobile Menu Toggle Button -->
                <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Toggle menu">
                    <i class="fas fa-bars"></i>
                </button>

                <?php 
                    $profileUrl = isLecturer() ? (isset($baseUrl) ? $baseUrl : '') . '/lecturer/profile.php' : '#';
                ?>
                <a href="<?php echo $profileUrl; ?>" class="user-area" style="text-decoration: none;">
                    <div class="user-info">
                        <span class="user-role"><?php echo isAdmin() ? 'Administrator' : 'Lecturer'; ?></span>
                        <span class="user-name"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    </div>
                    <div class="user-avatar">
                       <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                    </div>
                </a>

                <a href="<?php echo isset($baseUrl) ? $baseUrl : ''; ?>/logout.php" class="logout-btn-direct" title="Sign Out">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Mobile Drawer Overlay -->
    <div class="mobile-drawer-overlay" id="mobileDrawerOverlay"></div>

    <!-- Mobile Drawer Navigation -->
    <div class="mobile-drawer" id="mobileDrawer">
        <div class="mobile-drawer-header">
            <div class="logo-area" style="text-decoration: none;">
                <div class="logo-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="logo-text">
                    <span class="app-name">AMS</span>
                    <span class="app-label">Attendance System</span>
                </div>
            </div>
            <button class="mobile-drawer-close" id="mobileDrawerClose" aria-label="Close menu">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="mobile-drawer-nav">
            <?php if (isAdmin()): ?>
                <a href="<?php echo isset($baseUrl) ? $baseUrl : ''; ?>/admin/dashboard.php" class="<?php echo $currentScript == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="<?php echo isset($baseUrl) ? $baseUrl : ''; ?>/admin/students.php" class="<?php echo $currentScript == 'students.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-graduate"></i>
                    <span>Students</span>
                </a>
                <a href="<?php echo isset($baseUrl) ? $baseUrl : ''; ?>/admin/partitions.php" class="<?php echo $currentScript == 'partitions.php' ? 'active' : ''; ?>">
                    <i class="fas fa-clock"></i>
                    <span>Schedule</span>
                </a>
                <a href="<?php echo isset($baseUrl) ? $baseUrl : ''; ?>/admin/lecturers.php" class="<?php echo $currentScript == 'lecturers.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <span>Lecturers</span>
                </a>
                <a href="<?php echo isset($baseUrl) ? $baseUrl : ''; ?>/admin/groups.php" class="<?php echo $currentScript == 'groups.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span>Groups</span>
                </a>
                <a href="<?php echo isset($baseUrl) ? $baseUrl : ''; ?>/admin/attendance.php" class="<?php echo $currentScript == 'attendance.php' ? 'active' : ''; ?>">
                    <i class="fas fa-clipboard-list"></i>
                    <span>Attendance Logs</span>
                </a>
            <?php elseif (isLecturer()): ?>
                <a href="<?php echo isset($baseUrl) ? $baseUrl : ''; ?>/lecturer/dashboard.php" class="<?php echo $currentScript == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="<?php echo isset($baseUrl) ? $baseUrl : ''; ?>/lecturer/mark_attendance.php" class="<?php echo $currentScript == 'mark_attendance.php' ? 'active' : ''; ?>">
                    <i class="fas fa-clipboard-check"></i>
                    <span>Mark Attendance</span>
                </a>
                <a href="<?php echo isset($baseUrl) ? $baseUrl : ''; ?>/lecturer/students.php" class="<?php echo $currentScript == 'students.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-graduate"></i>
                    <span>Students</span>
                </a>
                <a href="<?php echo isset($baseUrl) ? $baseUrl : ''; ?>/lecturer/reports.php" class="<?php echo $currentScript == 'reports.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line"></i>
                    <span>Reports</span>
                </a>
                <a href="<?php echo isset($baseUrl) ? $baseUrl : ''; ?>/lecturer/profile.php" class="<?php echo $currentScript == 'profile.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-cog"></i>
                    <span>Profile</span>
                </a>
            <?php endif; ?>
        </div>

        <div class="mobile-drawer-footer">
            <a href="<?php echo isset($baseUrl) ? $baseUrl : ''; ?>/logout.php" style="display: flex; align-items: center; gap: 0.75rem; padding: 1rem; text-decoration: none; color: #dc2626; font-weight: 600; background: #fef2f2; border-radius: 12px;">
                <i class="fas fa-sign-out-alt"></i>
                <span>Sign Out</span>
            </a>
        </div>
    </div>

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
