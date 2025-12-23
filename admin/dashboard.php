<?php
$pageTitle = 'Admin Dashboard';
$baseUrl = '..';
require_once '../includes/header.php';
requireAdmin();

// Get statistics
try {
    // Total groups
    $stmt = $pdo->query("SELECT COUNT(*) FROM groups");
    $totalGroups = $stmt->fetchColumn();
    
    // Total lecturers
    $stmt = $pdo->query("SELECT COUNT(*) FROM lecturers");
    $totalLecturers = $stmt->fetchColumn();
    
    // Total students
    $stmt = $pdo->query("SELECT COUNT(*) FROM students");
    $totalStudents = $stmt->fetchColumn();
    
    // Total partitions
    $stmt = $pdo->query("SELECT COUNT(*) FROM partitions");
    $totalPartitions = $stmt->fetchColumn();
    
    // Recent attendance (last 7 days)
    $stmt = $pdo->query("
        SELECT COUNT(*) FROM attendance 
        WHERE date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ");
    $recentAttendance = $stmt->fetchColumn();
    
    // Recent groups
    $stmt = $pdo->query("
        SELECT g.*, u.username as created_by_name, 
               (SELECT COUNT(*) FROM students WHERE group_id = g.id) as student_count
        FROM groups g
        JOIN users u ON g.created_by = u.id
        ORDER BY g.created_at DESC
        LIMIT 5
    ");
    $recentGroups = $stmt->fetchAll();
    
} catch (PDOException $e) {
    setFlashMessage('danger', 'Error loading dashboard data.');
    $totalGroups = $totalLecturers = $totalStudents = $totalPartitions = $recentAttendance = 0;
    $recentGroups = [];
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
        <div class="stat-value"><?php echo $totalGroups; ?></div>
        <div class="stat-label">Total Groups</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-chalkboard-teacher"></i>
        </div>
        <div class="stat-value"><?php echo $totalLecturers; ?></div>
        <div class="stat-label">Total Lecturers</div>
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
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-value"><?php echo $totalPartitions; ?></div>
        <div class="stat-label">Total Partitions</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-history"></i> Recent Groups</h2>
        <a href="groups.php" class="btn btn-primary btn-sm">
            <i class="fas fa-eye"></i> View All
        </a>
    </div>
    <div class="card-body">
        <?php if (empty($recentGroups)): ?>
            <p class="text-center" style="color: var(--text-secondary); padding: 2rem;">
                <i class="fas fa-info-circle"></i> No groups created yet.
            </p>
        <?php else: ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Group Name</th>
                            <th>Students</th>
                            <th>Created By</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentGroups as $group): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($group['name']); ?></strong></td>
                            <td><?php echo $group['student_count']; ?> students</td>
                            <td><?php echo htmlspecialchars($group['created_by_name']); ?></td>
                            <td><?php echo formatDate($group['created_at']); ?></td>
                            <td>
                                <a href="groups.php?view=<?php echo $group['id']; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye"></i> View
                                </a>
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
    <div class="card-header">
        <h2><i class="fas fa-clipboard-check"></i> Attendance Summary</h2>
    </div>
    <div class="card-body">
        <p style="font-size: 1.125rem; color: var(--text-secondary);">
            <i class="fas fa-calendar-week"></i> 
            <strong><?php echo $recentAttendance; ?></strong> attendance records marked in the last 7 days
        </p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-rocket"></i> Quick Actions</h2>
    </div>
    <div class="card-body">
        <div class="d-flex gap-2" style="flex-wrap: wrap;">
            <a href="groups.php?action=create" class="btn btn-primary">
                <i class="fas fa-plus"></i> Create Group
            </a>
            <a href="lecturers.php?action=create" class="btn btn-success">
                <i class="fas fa-plus"></i> Add Lecturer
            </a>
            <a href="students.php?action=create" class="btn btn-warning">
                <i class="fas fa-plus"></i> Add Student
            </a>
            <a href="partitions.php" class="btn btn-secondary">
                <i class="fas fa-clock"></i> Manage Partitions
            </a>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
