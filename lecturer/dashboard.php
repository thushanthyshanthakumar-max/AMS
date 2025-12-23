<?php
$pageTitle = 'Lecturer Dashboard';
$baseUrl = '..';
require_once '../includes/header.php';
requireLecturer();

$lecturerId = getLecturerId($pdo, $_SESSION['user_id']);

if (!$lecturerId) {
    setFlashMessage('danger', 'Lecturer profile not found.');
    redirect('../logout.php');
}

try {
    // Get assigned groups
    $stmt = $pdo->prepare("
        SELECT g.*, 
               (SELECT COUNT(*) FROM students WHERE group_id = g.id) as student_count
        FROM groups g
        JOIN group_assignments ga ON g.id = ga.group_id
        WHERE ga.lecturer_id = ?
        ORDER BY g.name
    ");
    $stmt->execute([$lecturerId]);
    $assignedGroups = $stmt->fetchAll();
    
    // Get total students in assigned groups
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT s.id) as total
        FROM students s
        JOIN group_assignments ga ON s.group_id = ga.group_id
        WHERE ga.lecturer_id = ?
    ");
    $stmt->execute([$lecturerId]);
    $totalStudents = $stmt->fetchColumn();
    
    // Get upcoming partitions (today and next 7 days)
    $stmt = $pdo->prepare("
        SELECT p.*, g.name as group_name
        FROM partitions p
        JOIN groups g ON p.group_id = g.id
        JOIN group_assignments ga ON g.id = ga.group_id
        WHERE ga.lecturer_id = ?
        ORDER BY 
            CASE p.day_of_week
                WHEN 'Monday' THEN 1
                WHEN 'Tuesday' THEN 2
                WHEN 'Wednesday' THEN 3
                WHEN 'Thursday' THEN 4
                WHEN 'Friday' THEN 5
                WHEN 'Saturday' THEN 6
                WHEN 'Sunday' THEN 7
            END,
            p.start_time
        LIMIT 10
    ");
    $stmt->execute([$lecturerId]);
    $upcomingPartitions = $stmt->fetchAll();
    
    // Get recent attendance marked by this lecturer
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total
        FROM attendance
        WHERE marked_by = ? AND date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $recentAttendance = $stmt->fetchColumn();
    
} catch (PDOException $e) {
    setFlashMessage('danger', 'Error loading dashboard data.');
    $assignedGroups = [];
    $totalStudents = 0;
    $upcomingPartitions = [];
    $recentAttendance = 0;
}
?>

<div class="premium-banner">
    <div class="banner-content">
        <div class="banner-icon-wrapper" style="width: 80px; height: 80px; border-radius: 24px; margin: 0; background: rgba(255,255,255,0.1); border: 2px solid rgba(255,255,255,0.1); backdrop-filter: blur(10px); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
            <span style="font-weight: 800; font-size: 2rem; color: white;"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></span>
        </div>
        <div class="banner-text" style="flex-grow: 1;">
            <h1 style="color: white; margin-bottom: 4px; font-size: 2.25rem;">Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?></h1>
            <div class="banner-meta" style="margin: 0; opacity: 0.6;">
                <span><i class="fas fa-calendar-day"></i> It's <?php echo date('l, F j, Y'); ?></span>
            </div>
        </div>
        <div class="banner-actions">
            <a href="mark_attendance.php" class="btn btn-primary" style="background: white; color: #4f46e5; border: none; padding: 1rem 2rem; border-radius: 16px; font-weight: 800; box-shadow: 0 15px 30px -5px rgba(0,0,0,0.2); white-space: nowrap;">
                <i class="fas fa-bolt"></i> Mark Attendance
            </a>
        </div>
    </div>
</div>

<div class="grid grid-cols-4" style="margin-bottom: 3rem; display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem;">
    <div class="mini-stat-card" style="--bg-accent: #6366f1; --bg-soft: #f5f3ff;">
        <div class="mini-stat-icon"><i class="fas fa-layer-group"></i></div>
        <div class="mini-stat-info">
            <h2><?php echo count($assignedGroups); ?></h2>
            <p>Batches Managed</p>
        </div>
    </div>
    
    <div class="mini-stat-card" style="--bg-accent: #10b981; --bg-soft: #ecfdf5;">
        <div class="mini-stat-icon"><i class="fas fa-user-graduate"></i></div>
        <div class="mini-stat-info">
            <h2><?php echo $totalStudents; ?></h2>
            <p>Total Enrollment</p>
        </div>
    </div>
    
    <div class="mini-stat-card" style="--bg-accent: #f59e0b; --bg-soft: #fffbeb;">
        <div class="mini-stat-icon"><i class="fas fa-clipboard-check"></i></div>
        <div class="mini-stat-info">
            <h2><?php echo $recentAttendance; ?></h2>
            <p>Records (Last 7D)</p>
        </div>
    </div>
    
    <div class="mini-stat-card" style="--bg-accent: #8b5cf6; --bg-soft: #f5f3ff;">
        <div class="mini-stat-icon"><i class="fas fa-calendar-week"></i></div>
        <div class="mini-stat-info">
            <h2><?php echo count($upcomingPartitions); ?></h2>
            <p>Weekly Sessions</p>
        </div>
    </div>
</div>

<div class="card" style="margin-bottom: 2.5rem;">
    <div class="card-body">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
            <h3 style="margin: 0; font-weight: 800; color: #1e293b;"><i class="fas fa-users-line" style="color: #6366f1; margin-right: 10px;"></i> Active Academic Batches</h3>
            <a href="mark_attendance.php" style="color: #6366f1; font-weight: 700; text-decoration: none; font-size: 0.9rem;">View All Sessions <i class="fas fa-arrow-right" style="margin-left: 5px;"></i></a>
        </div>
        
        <?php if (empty($assignedGroups)): ?>
            <div style="text-align: center; padding: 4rem;">
                <i class="fas fa-user-slash" style="font-size: 3rem; color: #e2e8f0; margin-bottom: 1rem;"></i>
                <p style="color: #94a3b8; font-weight: 600;">No academic groups assigned to your profile yet.</p>
            </div>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem;">
                <?php foreach ($assignedGroups as $group): ?>
                    <a href="mark_attendance.php?group_id=<?php echo $group['id']; ?>" style="text-decoration: none; display: block; background: white; border-radius: 20px; border: 1px solid #f1f5f9; padding: 1.5rem; transition: all 0.3s; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem;">
                            <div style="width: 40px; height: 40px; border-radius: 10px; background: #f5f3ff; color: #7c3aed; display: flex; align-items: center; justify-content: center; font-size: 1.1rem;">
                                <i class="fas fa-users"></i>
                            </div>
                            <span class="badge" style="background: #ecfdf5; color: #059669; font-weight: 800; font-size: 0.65rem;">ACTIVE BATCH</span>
                        </div>
                        <h4 style="margin: 0 0 4px 0; color: #1e293b; font-weight: 800; font-size: 1.15rem;"><?php echo htmlspecialchars($group['name']); ?></h4>
                        <div style="color: #64748b; font-size: 0.85rem; font-weight: 600; margin-bottom: 1.5rem;">
                            <i class="fas fa-graduation-cap" style="margin-right: 6px; color: #cbd5e1;"></i>
                            <?php echo $group['student_count']; ?> Enrolled
                        </div>
                        <div style="display: flex; align-items: center; color: #6366f1; font-weight: 700; font-size: 0.85rem;">
                            Mark Attendance <i class="fas fa-chevron-right" style="margin-left:auto; font-size: 0.75rem;"></i>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="grid grid-cols-2">
    <div class="card">
        <div class="card-body">
            <h3 style="margin-bottom: 1.5rem; font-weight: 800; color: #1e293b;"><i class="fas fa-calendar-check" style="color: #6366f1; margin-right: 10px;"></i> Weekly Schedule</h3>
            <?php if (empty($upcomingPartitions)): ?>
                <p style="color: #94a3b8; font-weight: 600;">No sessions scheduled for this week.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table id="scheduleDashboard">
                        <thead>
                            <tr>
                                <th>Session Detail</th>
                                <th>Timeline</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($upcomingPartitions as $partition): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 700; color: #1e293b;"><?php echo htmlspecialchars($partition['group_name']); ?></div>
                                    <div style="font-size: 0.75rem; color: #94a3b8; font-weight: 600;"><?php echo htmlspecialchars($partition['name']); ?></div>
                                </td>
                                <td>
                                    <div style="font-weight: 800; color: #475569; font-size: 0.85rem;"><?php echo htmlspecialchars($partition['day_of_week']); ?></div>
                                    <div style="font-size: 0.75rem; color: #6366f1; font-weight: 700;"><?php echo formatTime($partition['start_time']); ?></div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h3 style="margin-bottom: 1.5rem; font-weight: 800; color: #1e293b;"><i class="fas fa-rocket" style="color: #6366f1; margin-right: 10px;"></i> Rapid Navigation</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <a href="mark_attendance.php" style="text-decoration: none; background: #f8fafc; padding: 1.5rem; border-radius: 16px; border: 1px solid #f1f5f9; display: flex; flex-direction: column; gap: 10px; transition: all 0.2s;">
                    <i class="fas fa-clipboard-user" style="color: #6366f1; font-size: 1.5rem;"></i>
                    <span style="font-weight: 800; color: #1e293b; font-size: 0.9rem;">Marking Desk</span>
                </a>
                <a href="students.php" style="text-decoration: none; background: #f8fafc; padding: 1.5rem; border-radius: 16px; border: 1px solid #f1f5f9; display: flex; flex-direction: column; gap: 10px; transition: all 0.2s;">
                    <i class="fas fa-user-graduate" style="color: #10b981; font-size: 1.5rem;"></i>
                    <span style="font-weight: 800; color: #1e293b; font-size: 0.9rem;">Student Roster</span>
                </a>
                <a href="reports.php" style="text-decoration: none; background: #f8fafc; padding: 1.5rem; border-radius: 16px; border: 1px solid #f1f5f9; display: flex; flex-direction: column; gap: 10px; transition: all 0.2s;">
                    <i class="fas fa-chart-bar" style="color: #f59e0b; font-size: 1.5rem;"></i>
                    <span style="font-weight: 800; color: #1e293b; font-size: 0.9rem;">Reports Vault</span>
                </a>
                <a href="profile.php" style="text-decoration: none; background: #f8fafc; padding: 1.5rem; border-radius: 16px; border: 1px solid #f1f5f9; display: flex; flex-direction: column; gap: 10px; transition: all 0.2s;">
                    <i class="fas fa-fingerprint" style="color: #3b82f6; font-size: 1.5rem;"></i>
                    <span style="font-weight: 800; color: #1e293b; font-size: 0.9rem;">Identity Settings</span>
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
