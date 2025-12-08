<?php
$pageTitle = 'Generate Reports';
$baseUrl = '..';
require_once '../includes/header.php';
requireTeacher();

$teacherId = getTeacherId($pdo, $_SESSION['user_id']);

if (!$teacherId) {
    setFlashMessage('danger', 'Teacher profile not found.');
    redirect('../logout.php');
}

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $groupId = (int)$_GET['group_id'];
    $startDate = $_GET['start_date'];
    $endDate = $_GET['end_date'];
    
    if (teacherHasAccessToGroup($pdo, $teacherId, $groupId)) {
        try {
            $stmt = $pdo->prepare("
                SELECT s.name as student_name, s.email, p.name as partition_name,
                       a.date, a.status, a.notes
                FROM attendance a
                JOIN students s ON a.student_id = s.id
                JOIN partitions p ON a.partition_id = p.id
                WHERE s.group_id = ? AND a.date BETWEEN ? AND ?
                ORDER BY a.date DESC, s.name
            ");
            $stmt->execute([$groupId, $startDate, $endDate]);
            $records = $stmt->fetchAll();
            
            $data = [];
            foreach ($records as $record) {
                $data[] = [
                    $record['student_name'],
                    $record['email'],
                    $record['partition_name'],
                    formatDate($record['date']),
                    ucfirst($record['status']),
                    $record['notes']
                ];
            }
            
            $headers = ['Student Name', 'Email', 'Partition', 'Date', 'Status', 'Notes'];
            $filename = 'attendance_report_' . date('Y-m-d') . '.csv';
            
            exportToCSV($filename, $data, $headers);
        } catch (PDOException $e) {
            setFlashMessage('danger', 'Error generating report.');
            redirect('reports.php');
        }
    }
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

// Generate report
$reportData = null;
if (isset($_GET['generate'])) {
    $groupId = (int)$_GET['group_id'];
    $startDate = $_GET['start_date'];
    $endDate = $_GET['end_date'];
    
    if (teacherHasAccessToGroup($pdo, $teacherId, $groupId)) {
        try {
            // Get group name
            $stmt = $pdo->prepare("SELECT name FROM groups WHERE id = ?");
            $stmt->execute([$groupId]);
            $groupName = $stmt->fetchColumn();
            
            // Get students and their attendance
            $stmt = $pdo->prepare("
                SELECT s.id, s.name, s.email,
                       COUNT(a.id) as total_records,
                       SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present,
                       SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent,
                       SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late
                FROM students s
                LEFT JOIN attendance a ON s.id = a.student_id AND a.date BETWEEN ? AND ?
                WHERE s.group_id = ?
                GROUP BY s.id
                ORDER BY s.name
            ");
            $stmt->execute([$startDate, $endDate, $groupId]);
            $studentStats = $stmt->fetchAll();
            
            // Get overall statistics
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(*) as total_records,
                    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
                    SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late
                FROM attendance a
                JOIN students s ON a.student_id = s.id
                WHERE s.group_id = ? AND a.date BETWEEN ? AND ?
            ");
            $stmt->execute([$groupId, $startDate, $endDate]);
            $overallStats = $stmt->fetch();
            
            $reportData = [
                'group_id' => $groupId,
                'group_name' => $groupName,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'students' => $studentStats,
                'overall' => $overallStats
            ];
        } catch (PDOException $e) {
            setFlashMessage('danger', 'Error generating report.');
        }
    }
}
?>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-chart-bar"></i> Generate Attendance Report</h2>
    </div>
    <div class="card-body">
        <form method="GET">
            <input type="hidden" name="generate" value="1">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 1rem; align-items: end;">
                <div class="form-group">
                    <label for="group_id" class="form-label">Select Group</label>
                    <select id="group_id" name="group_id" class="form-control" required>
                        <option value="">Select a group...</option>
                        <?php foreach ($assignedGroups as $group): ?>
                            <option value="<?php echo $group['id']; ?>" <?php echo (isset($_GET['group_id']) && $_GET['group_id'] == $group['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($group['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" id="start_date" name="start_date" class="form-control" value="<?php echo isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" id="end_date" name="end_date" class="form-control" value="<?php echo isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d'); ?>" required>
                </div>
                
                <div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-chart-bar"></i> Generate Report
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if ($reportData): ?>
    <!-- Report Header -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-file-alt"></i> Attendance Report: <?php echo htmlspecialchars($reportData['group_name']); ?></h2>
            <a href="reports.php?export=csv&group_id=<?php echo $reportData['group_id']; ?>&start_date=<?php echo $reportData['start_date']; ?>&end_date=<?php echo $reportData['end_date']; ?>" class="btn btn-success btn-sm">
                <i class="fas fa-download"></i> Export to CSV
            </a>
        </div>
        <div class="card-body">
            <p><strong>Period:</strong> <?php echo formatDate($reportData['start_date']); ?> to <?php echo formatDate($reportData['end_date']); ?></p>
        </div>
    </div>
    
    <!-- Overall Statistics -->
    <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr);">
        <div class="stat-card" style="background: linear-gradient(135deg, var(--info-color), #2563eb);">
            <div class="stat-icon"><i class="fas fa-clipboard-list"></i></div>
            <div class="stat-value"><?php echo $reportData['overall']['total_records']; ?></div>
            <div class="stat-label">Total Records</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-value"><?php echo $reportData['overall']['present']; ?></div>
            <div class="stat-label">Present</div>
        </div>
        <div class="stat-card" style="background: linear-gradient(135deg, var(--danger-color), #dc2626);">
            <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
            <div class="stat-value"><?php echo $reportData['overall']['absent']; ?></div>
            <div class="stat-label">Absent</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-clock"></i></div>
            <div class="stat-value"><?php echo $reportData['overall']['late']; ?></div>
            <div class="stat-label">Late</div>
        </div>
    </div>
    
    <!-- Student-wise Report -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-users"></i> Student-wise Attendance</h2>
        </div>
        <div class="card-body">
            <?php if (empty($reportData['students'])): ?>
                <p class="text-center" style="color: var(--text-secondary); padding: 2rem;">
                    No data available for the selected period.
                </p>
            <?php else: ?>
                <div class="table-responsive">
                    <table id="reportTable">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Email</th>
                                <th>Total Records</th>
                                <th>Present</th>
                                <th>Absent</th>
                                <th>Late</th>
                                <th>Attendance %</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reportData['students'] as $student): 
                                $attendanceRate = calculateAttendancePercentage($student['present'], $student['total_records']);
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($student['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                                <td><?php echo $student['total_records']; ?></td>
                                <td><span class="badge badge-success"><?php echo $student['present']; ?></span></td>
                                <td><span class="badge badge-danger"><?php echo $student['absent']; ?></span></td>
                                <td><span class="badge badge-warning"><?php echo $student['late']; ?></span></td>
                                <td>
                                    <span class="badge <?php echo $attendanceRate >= 75 ? 'badge-success' : ($attendanceRate >= 50 ? 'badge-warning' : 'badge-danger'); ?>">
                                        <?php echo $attendanceRate; ?>%
                                    </span>
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
