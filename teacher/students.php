<?php
$pageTitle = 'My Students';
$baseUrl = '..';
require_once '../includes/header.php';
requireTeacher();

$teacherId = getTeacherId($pdo, $_SESSION['user_id']);

if (!$teacherId) {
    setFlashMessage('danger', 'Teacher profile not found.');
    redirect('../logout.php');
}

// Get assigned groups
try {
    $stmt = $pdo->prepare("
        SELECT g.*
        FROM groups g
        JOIN group_assignments ga ON g.id = ga.group_id
        WHERE ga.teacher_id = ?
        ORDER BY g.name
    ");
    $stmt->execute([$teacherId]);
    $assignedGroups = $stmt->fetchAll();
} catch (PDOException $e) {
    $assignedGroups = [];
}

// Filter by group
$selectedGroup = isset($_GET['group_id']) ? (int)$_GET['group_id'] : null;

// Get students
$students = [];
if ($selectedGroup && teacherHasAccessToGroup($pdo, $teacherId, $selectedGroup)) {
    try {
        $stmt = $pdo->prepare("
            SELECT s.*,
                   (SELECT COUNT(*) FROM attendance WHERE student_id = s.id AND status = 'present') as present_count,
                   (SELECT COUNT(*) FROM attendance WHERE student_id = s.id) as total_attendance
            FROM students s
            WHERE s.group_id = ?
            ORDER BY s.name
        ");
        $stmt->execute([$selectedGroup]);
        $students = $stmt->fetchAll();
    } catch (PDOException $e) {
        setFlashMessage('danger', 'Error loading students.');
    }
} elseif (!$selectedGroup && !empty($assignedGroups)) {
    // Show all students from all assigned groups
    try {
        $groupIds = array_column($assignedGroups, 'id');
        $placeholders = implode(',', array_fill(0, count($groupIds), '?'));
        
        $stmt = $pdo->prepare("
            SELECT s.*, g.name as group_name,
                   (SELECT COUNT(*) FROM attendance WHERE student_id = s.id AND status = 'present') as present_count,
                   (SELECT COUNT(*) FROM attendance WHERE student_id = s.id) as total_attendance
            FROM students s
            JOIN groups g ON s.group_id = g.id
            WHERE s.group_id IN ($placeholders)
            ORDER BY g.name, s.name
        ");
        $stmt->execute($groupIds);
        $students = $stmt->fetchAll();
    } catch (PDOException $e) {
        setFlashMessage('danger', 'Error loading students.');
    }
}

// View specific student
$viewStudent = null;
if (isset($_GET['view'])) {
    $studentId = (int)$_GET['view'];
    try {
        $stmt = $pdo->prepare("
            SELECT s.*, g.name as group_name
            FROM students s
            JOIN groups g ON s.group_id = g.id
            JOIN group_assignments ga ON g.id = ga.group_id
            WHERE s.id = ? AND ga.teacher_id = ?
        ");
        $stmt->execute([$studentId, $teacherId]);
        $viewStudent = $stmt->fetch();
        
        if ($viewStudent) {
            // Get attendance history
            $stmt = $pdo->prepare("
                SELECT a.*, p.name as partition_name, p.day_of_week
                FROM attendance a
                JOIN partitions p ON a.partition_id = p.id
                WHERE a.student_id = ?
                ORDER BY a.date DESC
                LIMIT 50
            ");
            $stmt->execute([$studentId]);
            $attendanceHistory = $stmt->fetchAll();
            
            // Get statistics
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
                    SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late
                FROM attendance
                WHERE student_id = ?
            ");
            $stmt->execute([$studentId]);
            $studentStats = $stmt->fetch();
        }
    } catch (PDOException $e) {
        setFlashMessage('danger', 'Error loading student details.');
    }
}
?>

<?php if ($viewStudent): ?>
    <!-- View Student Details -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-user-graduate"></i> <?php echo htmlspecialchars($viewStudent['name']); ?></h2>
            <a href="students.php" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Back to Students
            </a>
        </div>
        <div class="card-body">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                <div>
                    <strong>Email:</strong><br>
                    <?php echo htmlspecialchars($viewStudent['email']); ?>
                </div>
                <div>
                    <strong>Phone:</strong><br>
                    <?php echo htmlspecialchars($viewStudent['phone']); ?>
                </div>
                <div>
                    <strong>Group:</strong><br>
                    <?php echo htmlspecialchars($viewStudent['group_name']); ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Attendance Statistics -->
    <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr);">
        <div class="stat-card" style="background: linear-gradient(135deg, var(--info-color), #2563eb);">
            <div class="stat-icon"><i class="fas fa-clipboard-list"></i></div>
            <div class="stat-value"><?php echo $studentStats['total']; ?></div>
            <div class="stat-label">Total Records</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-value"><?php echo $studentStats['present']; ?></div>
            <div class="stat-label">Present</div>
        </div>
        <div class="stat-card" style="background: linear-gradient(135deg, var(--danger-color), #dc2626);">
            <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
            <div class="stat-value"><?php echo $studentStats['absent']; ?></div>
            <div class="stat-label">Absent</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-percentage"></i></div>
            <div class="stat-value"><?php echo calculateAttendancePercentage($studentStats['present'], $studentStats['total']); ?>%</div>
            <div class="stat-label">Attendance Rate</div>
        </div>
    </div>
    
    <!-- Attendance History -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-history"></i> Attendance History</h2>
        </div>
        <div class="card-body">
            <?php if (empty($attendanceHistory)): ?>
                <p class="text-center" style="color: var(--text-secondary); padding: 2rem;">
                    No attendance records found.
                </p>
            <?php else: ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Partition</th>
                                <th>Day</th>
                                <th>Status</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attendanceHistory as $record): ?>
                            <tr>
                                <td><?php echo formatDate($record['date']); ?></td>
                                <td><?php echo htmlspecialchars($record['partition_name']); ?></td>
                                <td><?php echo htmlspecialchars($record['day_of_week']); ?></td>
                                <td><?php echo getStatusBadge($record['status']); ?></td>
                                <td><?php echo htmlspecialchars($record['notes']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php else: ?>
    <!-- List All Students -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-user-graduate"></i> My Students</h2>
        </div>
        <div class="card-body">
            <form method="GET" class="mb-3">
                <div class="form-group">
                    <label for="group_id" class="form-label">Filter by Group</label>
                    <select id="group_id" name="group_id" class="form-control" onchange="this.form.submit()">
                        <option value="">All Groups</option>
                        <?php foreach ($assignedGroups as $group): ?>
                            <option value="<?php echo $group['id']; ?>" <?php echo ($selectedGroup == $group['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($group['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
            
            <?php if (empty($students)): ?>
                <p class="text-center" style="color: var(--text-secondary); padding: 2rem;">
                    <i class="fas fa-info-circle"></i> No students found.
                </p>
            <?php else: ?>
                <div class="table-responsive">
                    <table id="studentsTable">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <?php if (!$selectedGroup): ?>
                                    <th>Group</th>
                                <?php endif; ?>
                                <th>Attendance Rate</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): 
                                $attendanceRate = calculateAttendancePercentage($student['present_count'], $student['total_attendance']);
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($student['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                                <td><?php echo htmlspecialchars($student['phone']); ?></td>
                                <?php if (!$selectedGroup): ?>
                                    <td><?php echo htmlspecialchars($student['group_name']); ?></td>
                                <?php endif; ?>
                                <td>
                                    <span class="badge <?php echo $attendanceRate >= 75 ? 'badge-success' : ($attendanceRate >= 50 ? 'badge-warning' : 'badge-danger'); ?>">
                                        <?php echo $attendanceRate; ?>%
                                    </span>
                                    (<?php echo $student['present_count']; ?>/<?php echo $student['total_attendance']; ?>)
                                </td>
                                <td>
                                    <a href="students.php?view=<?php echo $student['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> View Details
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
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
