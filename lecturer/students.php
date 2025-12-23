<?php
$pageTitle = 'My Students';
$baseUrl = '..';
require_once '../includes/header.php';
requireLecturer();

$lecturerId = getLecturerId($pdo, $_SESSION['user_id']);

if (!$lecturerId) {
    setFlashMessage('danger', 'Lecturer profile not found.');
    redirect('../logout.php');
}

// Get assigned groups
try {
    $stmt = $pdo->prepare("
        SELECT g.*
        FROM groups g
        JOIN group_assignments ga ON g.id = ga.group_id
        WHERE ga.lecturer_id = ?
        ORDER BY g.name
    ");
    $stmt->execute([$lecturerId]);
    $assignedGroups = $stmt->fetchAll();
} catch (PDOException $e) {
    $assignedGroups = [];
}

// Filter inputs
$selectedGroup = isset($_GET['group_id']) && $_GET['group_id'] !== '' ? (int)$_GET['group_id'] : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get students
$students = [];
$params = [];
$sqlConditions = "";

if ($search) {
    $sqlConditions .= " AND (s.name LIKE ? OR s.reg_no LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($selectedGroup && lecturerHasAccessToGroup($pdo, $lecturerId, $selectedGroup)) {
    try {
        $stmt = $pdo->prepare("
            SELECT s.*, g.name as group_name,
                   (SELECT COUNT(*) FROM attendance WHERE student_id = s.id AND status = 'present') as present_count,
                   (SELECT COUNT(*) FROM attendance WHERE student_id = s.id) as total_attendance
            FROM students s
            JOIN groups g ON s.group_id = g.id
            WHERE s.group_id = ? $sqlConditions
            ORDER BY s.reg_no
        ");
        
        $queryParams = array_merge([$selectedGroup], $params);
        $stmt->execute($queryParams);
        $students = $stmt->fetchAll();
    } catch (PDOException $e) {
        setFlashMessage('danger', 'Error loading students.');
    }
} elseif (empty($selectedGroup) && !empty($assignedGroups)) {
    // Show all students from all assigned groups
    try {
        $groupIds = array_column($assignedGroups, 'id');
        $placeholders = implode(',', array_fill(0, count($groupIds), '?'));
        
        $baseSql = "
            SELECT s.*, g.name as group_name,
                   (SELECT COUNT(*) FROM attendance WHERE student_id = s.id AND status = 'present') as present_count,
                   (SELECT COUNT(*) FROM attendance WHERE student_id = s.id) as total_attendance
            FROM students s
            JOIN groups g ON s.group_id = g.id
            WHERE s.group_id IN ($placeholders) $sqlConditions
            ORDER BY g.name, s.reg_no
        ";
        
        $queryParams = array_merge($groupIds, $params);
        
        $stmt = $pdo->prepare($baseSql);
        $stmt->execute($queryParams);
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
            WHERE s.id = ? AND ga.lecturer_id = ?
        ");
        $stmt->execute([$studentId, $lecturerId]);
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

<div class="premium-banner">
    <div class="banner-content">
        <div class="banner-icon-wrapper">
            <i class="fas fa-user-graduate"></i>
        </div>
        <div class="banner-text">
            <h1>Student Roster</h1>
            <p>Monitor individual student progress and engagement metrics</p>
        </div>
        <div class="banner-actions">
            <div style="display: flex; gap: 1rem; align-items: center; background: rgba(255,255,255,0.05); padding: 0.75rem 1.5rem; border-radius: 16px; border: 1px solid rgba(255,255,255,0.1); width: fit-content;">
                <div>
                    <div style="color: rgba(255,255,255,0.4); font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em;">Total Students</div>
                    <div style="color: white; font-weight: 800; font-size: 1.25rem; line-height: 1; margin-top: 0.2rem;"><?php echo count($students); ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($viewStudent): ?>
    <!-- View Student Details -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-user-graduate"></i> Student Profile</h2>
            <a href="students.php" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Back to Students
            </a>
        </div>
        <div class="card-body">
            <div style="display: flex; gap: 2rem; align-items: flex-start; margin-bottom: 2rem;">
                <div style="background: var(--light-color); padding: 2rem; border-radius: 50%; width: 100px; height: 100px; display: flex; align-items: center; justify-content: center; font-size: 3rem; color: var(--primary-color);">
                    <i class="fas fa-user"></i>
                </div>
                <div style="flex: 1;">
                    <div style="margin-bottom: 1.5rem;">
                        <span class="badge badge-<?php echo $viewStudent['title'] === 'MR' ? 'primary' : 'info'; ?>" style="font-size: 0.875rem; margin-bottom: 0.5rem;">
                            <?php echo htmlspecialchars($viewStudent['title']); ?>
                        </span>
                        <h1 style="color: var(--text-primary); margin: 0; font-size: 2rem;"><?php echo htmlspecialchars($viewStudent['name']); ?></h1>
                        <p style="color: var(--text-secondary); font-size: 1.25rem; margin-top: 0.25rem; font-weight: 500;">
                            <?php echo htmlspecialchars($viewStudent['reg_no']); ?>
                        </p>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                        <div style="background: var(--light-color); padding: 1rem; border-radius: var(--radius-md); border-left: 4px solid var(--primary-color);">
                            <small class="text-uppercase" style="color: var(--text-secondary); font-weight: 700; font-size: 0.75rem;">Group</small>
                            <div style="font-weight: 600; font-size: 1.1rem; margin-top: 0.25rem;">
                                <?php echo htmlspecialchars($viewStudent['group_name']); ?>
                            </div>
                        </div>
                        <div style="background: var(--light-color); padding: 1rem; border-radius: var(--radius-md); border-left: 4px solid var(--info-color);">
                            <small class="text-uppercase" style="color: var(--text-secondary); font-weight: 700; font-size: 0.75rem;">Joined Date</small>
                            <div style="font-weight: 600; font-size: 1.1rem; margin-top: 0.25rem;">
                                <?php echo date('F j, Y', strtotime($viewStudent['created_at'])); ?>
                            </div>
                        </div>
                    </div>
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
    <!-- Student List & Search -->
    <div class="card">
        <div class="card-body">
            <form method="GET" style="margin-bottom: 2rem;">
                <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1.5rem;">
                    <div class="form-group">
                        <label for="group_id" class="form-label" style="font-weight: 700; color: #475569;">Batch Navigation</label>
                        <select id="group_id" name="group_id" class="form-control" onchange="this.form.submit()" style="border-radius: 12px; height: 48px;">
                            <option value="">All Assigned Batches</option>
                            <?php foreach ($assignedGroups as $group): ?>
                                <option value="<?php echo $group['id']; ?>" <?php echo ($selectedGroup == $group['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($group['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="search" class="form-label" style="font-weight: 700; color: #475569;">Quick Student Search</label>
                        <div style="display: flex; gap: 0.75rem;">
                            <div style="position: relative; flex-grow: 1;">
                                <i class="fas fa-search" style="position: absolute; left: 15px; top: 16px; color: #94a3b8;"></i>
                                <input type="text" id="search" name="search" class="form-control" 
                                       placeholder="Find by name or registration number..." 
                                       value="<?php echo htmlspecialchars($search); ?>"
                                       style="padding-left: 45px; border-radius: 12px; height: 48px;">
                            </div>
                            <button type="submit" class="btn btn-primary" style="height: 48px; border-radius: 12px; padding: 0 1.5rem;">
                                Search
                            </button>
                            <?php if (!empty($search)): ?>
                                <a href="students.php" class="btn btn-secondary" style="height: 48px; width: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; background: #f1f5f9; border: none; color: #475569;">
                                    <i class="fas fa-times"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </form>
            
            <?php if (empty($students)): ?>
                <div style="text-align: center; padding: 5rem 2rem;">
                    <div style="width: 80px; height: 80px; background: #f8fafc; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                        <i class="fas fa-users-slash" style="font-size: 2rem; color: #cbd5e1;"></i>
                    </div>
                    <h3 style="color: #1e293b; font-weight: 800; margin-bottom: 0.5rem;">No Students Found</h3>
                    <p style="color: #64748b;">We couldn't find any students matching your criteria.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table id="studentsTable">
                        <thead>
                            <tr>
                                <th>Student Identity</th>
                                <?php if (!$selectedGroup): ?>
                                    <th>Assigned Batch</th>
                                <?php endif; ?>
                                <th class="text-center">Persistence Index</th>
                                <th class="text-right">Administration</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): 
                                $attendanceRate = calculateAttendancePercentage($student['present_count'], $student['total_attendance']);
                                $statusColor = $attendanceRate >= 75 ? '#10b981' : ($attendanceRate >= 50 ? '#f59e0b' : '#ef4444');
                            ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 1rem;">
                                        <div style="width: 44px; height: 44px; background: #f1f5f9; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #4338ca; font-weight: 800; font-size: 0.9rem;">
                                            <?php echo substr($student['reg_no'], -2); ?>
                                        </div>
                                        <div>
                                            <div style="font-weight: 700; color: #1e293b;"><?php echo htmlspecialchars($student['name']); ?></div>
                                            <div style="font-size: 0.75rem; color: #94a3b8; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;"><?php echo htmlspecialchars($student['reg_no']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <?php if (!$selectedGroup): ?>
                                    <td>
                                        <span style="font-weight: 700; color: #64748b; font-size: 0.9rem;">
                                            <i class="fas fa-layer-group" style="margin-right: 6px; color: #cbd5e1;"></i>
                                            <?php echo htmlspecialchars($student['group_name']); ?>
                                        </span>
                                    </td>
                                <?php endif; ?>
                                <td class="text-center">
                                    <div style="display: inline-flex; align-items: center; gap: 10px; background: <?php echo $statusColor; ?>10; padding: 6px 14px; border-radius: 20px; border: 1px solid <?php echo $statusColor; ?>20;">
                                        <div style="width: 8px; height: 8px; border-radius: 50%; background: <?php echo $statusColor; ?>;"></div>
                                        <span style="font-weight: 800; color: <?php echo $statusColor; ?>; font-size: 0.9rem;">
                                            <?php echo $attendanceRate; ?>%
                                        </span>
                                        <span style="color: #94a3b8; font-size: 0.75rem; font-weight: 600;">(<?php echo $student['present_count']; ?>/<?php echo $student['total_attendance']; ?>)</span>
                                    </div>
                                </td>
                                <td class="text-right">
                                    <a href="students.php?view=<?php echo $student['id']; ?>" class="btn" style="background: white; border: 1px solid #e2e8f0; color: #1e293b; font-weight: 700; font-size: 0.85rem; border-radius: 10px; padding: 0.6rem 1.2rem; transition: all 0.2s;">
                                        <i class="fas fa-id-card-clip" style="color: #6366f1; margin-right: 8px;"></i> View Profile
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
