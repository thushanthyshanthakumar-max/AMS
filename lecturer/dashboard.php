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

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-chart-line"></i> Dashboard Overview</h2>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-value"><?php echo count($assignedGroups); ?></div>
        <div class="stat-label">Assigned Groups</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-user-graduate"></i>
        </div>
        <div class="stat-value"><?php echo $totalStudents; ?></div>
        <div class="stat-label">Total Students</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-clipboard-check"></i>
        </div>
        <div class="stat-value"><?php echo $recentAttendance; ?></div>
        <div class="stat-label">Attendance (7 days)</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-value"><?php echo count($upcomingPartitions); ?></div>
        <div class="stat-label">Scheduled Partitions</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-users"></i> My Assigned Groups</h2>
    </div>
    <div class="card-body">
        <?php if (empty($assignedGroups)): ?>
            <p class="text-center" style="color: var(--text-secondary); padding: 2rem;">
                <i class="fas fa-info-circle"></i> No groups assigned yet. Please contact the administrator.
            </p>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1rem;">
                <?php foreach ($assignedGroups as $group): ?>
                    <div class="card" style="margin: 0;">
                        <div class="card-body">
                            <h3 style="margin-bottom: 1rem; color: var(--primary-color);">
                                <i class="fas fa-users"></i> <?php echo htmlspecialchars($group['name']); ?>
                            </h3>
                            <p style="color: var(--text-secondary); margin-bottom: 1rem;">
                                <i class="fas fa-user-graduate"></i> <?php echo $group['student_count']; ?> students
                            </p>
                            <a href="mark_attendance.php?group_id=<?php echo $group['id']; ?>" class="btn btn-primary btn-block">
                                <i class="fas fa-clipboard-check"></i> Mark Attendance
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-calendar-alt"></i> Scheduled Partitions</h2>
    </div>
    <div class="card-body">
        <?php if (empty($upcomingPartitions)): ?>
            <p class="text-center" style="color: var(--text-secondary); padding: 2rem;">
                <i class="fas fa-info-circle"></i> No partitions scheduled.
            </p>
        <?php else: ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Group</th>
                            <th>Partition Name</th>
                            <th>Day</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($upcomingPartitions as $partition): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($partition['group_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($partition['name']); ?></td>
                            <td><?php echo htmlspecialchars($partition['day_of_week']); ?></td>
                            <td><?php echo formatTime($partition['start_time']) . ' - ' . formatTime($partition['end_time']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-rocket"></i> Quick Actions</h2>
    </div>
    <div class="card-body">
        <div class="d-flex gap-2" style="flex-wrap: wrap;">
            <a href="mark_attendance.php" class="btn btn-primary">
                <i class="fas fa-clipboard-check"></i> Mark Attendance
            </a>
            <a href="students.php" class="btn btn-success">
                <i class="fas fa-user-graduate"></i> View Students
            </a>
            <a href="reports.php" class="btn btn-warning">
                <i class="fas fa-chart-bar"></i> Generate Reports
            </a>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
