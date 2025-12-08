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
                SELECT s.reg_no, s.title, s.name, p.name as partition_name,
                       a.date, a.status, a.notes
                FROM attendance a
                JOIN students s ON a.student_id = s.id
                JOIN partitions p ON a.partition_id = p.id
                WHERE s.group_id = ? AND a.date BETWEEN ? AND ?
                ORDER BY a.date DESC, s.reg_no
            ");
            $stmt->execute([$groupId, $startDate, $endDate]);
            $records = $stmt->fetchAll();
            
            $data = [];
            foreach ($records as $record) {
                $data[] = [
                    $record['reg_no'],
                    $record['title'],
                    $record['name'],
                    $record['partition_name'],
                    formatDate($record['date']),
                    ucfirst($record['status']),
                    $record['notes']
                ];
            }
            
            $headers = ['Reg No', 'Title', 'Student Name', 'Partition', 'Date', 'Status', 'Notes'];
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
                SELECT s.id, s.reg_no, s.title, s.name,
                       COUNT(a.id) as total_records,
                       SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present,
                       SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent,
                       SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late
                FROM students s
                LEFT JOIN attendance a ON s.id = a.student_id AND a.date BETWEEN ? AND ?
                WHERE s.group_id = ?
                GROUP BY s.id
                ORDER BY s.reg_no
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
        <h2><i class="fas fa-chart-line"></i> Generate Attendance Report</h2>
    </div>
    <div class="card-body">
        <form method="GET" class="report-filters">
            <input type="hidden" name="generate" value="1">
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; align-items: end;">
                <div class="form-group">
                    <label for="group_id" class="form-label font-weight-bold">Select Group</label>
                    <div style="position: relative;">
                        <i class="fas fa-users" style="position: absolute; left: 10px; top: 12px; color: var(--text-secondary);"></i>
                        <select id="group_id" name="group_id" class="form-control" style="padding-left: 35px;" required>
                            <option value="">Choose a class group...</option>
                            <?php foreach ($assignedGroups as $group): ?>
                                <option value="<?php echo $group['id']; ?>" <?php echo (isset($_GET['group_id']) && $_GET['group_id'] == $group['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($group['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="start_date" class="form-label font-weight-bold">From Date</label>
                    <input type="date" id="start_date" name="start_date" class="form-control" value="<?php echo isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="end_date" class="form-label font-weight-bold">To Date</label>
                    <input type="date" id="end_date" name="end_date" class="form-control" value="<?php echo isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d'); ?>" required>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-block" style="height: 45px;">
                        <i class="fas fa-search"></i> Generate Report
                    </button>
                </div>
            </div>
            
            <div style="margin-top: 1rem; display: flex; gap: 0.5rem; justify-content: center;">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setDateRange('today')">Today</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setDateRange('this_week')">This Week</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setDateRange('last_week')">Last Week</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setDateRange('this_month')">This Month</button>
            </div>
        </form>
    </div>
</div>

<script>
function setDateRange(range) {
    const today = new Date();
    let start = new Date();
    let end = new Date();
    
    if (range === 'today') {
        // start and end are already today
    } else if (range === 'this_week') {
        const day = today.getDay() || 7; // Get current day number, converting Sun (0) to 7
        if (day !== 1) start.setHours(-24 * (day - 1)); // Set to Monday
    } else if (range === 'last_week') {
        const day = today.getDay() || 7;
        start.setDate(today.getDate() - day - 6);
        end.setDate(today.getDate() - day);
    } else if (range === 'this_month') {
        start.setDate(1);
    }
    
    document.getElementById('start_date').value = start.toISOString().split('T')[0];
    document.getElementById('end_date').value = end.toISOString().split('T')[0];
}
</script>

<?php if ($reportData): ?>
    <!-- Report Actions -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <div>
            <h2 style="margin: 0; color: var(--text-primary);"><i class="fas fa-file-alt"></i> Attendance Report</h2>
            <p style="margin: 0.5rem 0 0; color: var(--text-secondary);">
                <strong><?php echo htmlspecialchars($reportData['group_name']); ?></strong> | 
                <?php echo formatDate($reportData['start_date']); ?> to <?php echo formatDate($reportData['end_date']); ?>
            </p>
        </div>
        <div style="display: flex; gap: 1rem;">
            <button onclick="window.print()" class="btn btn-secondary">
                <i class="fas fa-print"></i> Print
            </button>
            <a href="reports.php?export=csv&group_id=<?php echo $reportData['group_id']; ?>&start_date=<?php echo $reportData['start_date']; ?>&end_date=<?php echo $reportData['end_date']; ?>" class="btn btn-success">
                <i class="fas fa-file-csv"></i> Export CSV
            </a>
        </div>
    </div>

    <!-- Overall Statistics -->
    <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 2rem;">
        <div class="stat-card" style="background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); color: white;">
            <div class="stat-icon" style="opacity: 0.8;"><i class="fas fa-users"></i></div>
            <div class="stat-value"><?php echo count($reportData['students']); ?></div>
            <div class="stat-label">Total Students</div>
        </div>
        <div class="stat-card" style="background: white; border-top: 4px solid var(--success-color);">
            <div class="stat-icon" style="color: var(--success-color);"><i class="fas fa-check-circle"></i></div>
            <div class="stat-value" style="color: var(--success-color);"><?php echo $reportData['overall']['present']; ?></div>
            <div class="stat-label">Total Present</div>
        </div>
        <div class="stat-card" style="background: white; border-top: 4px solid var(--danger-color);">
            <div class="stat-icon" style="color: var(--danger-color);"><i class="fas fa-times-circle"></i></div>
            <div class="stat-value" style="color: var(--danger-color);"><?php echo $reportData['overall']['absent']; ?></div>
            <div class="stat-label">Total Absent</div>
        </div>
        <div class="stat-card" style="background: white; border-top: 4px solid var(--warning-color);">
            <div class="stat-icon" style="color: var(--warning-color);"><i class="fas fa-percentage"></i></div>
            <div class="stat-value" style="color: var(--text-primary);">
                <?php 
                $totalOverview = $reportData['overall']['total_records'];
                echo $totalOverview > 0 ? round(($reportData['overall']['present'] / $totalOverview) * 100) . '%' : '0%'; 
                ?>
            </div>
            <div class="stat-label">Avg Attendance</div>
        </div>
    </div>
    
    <!-- Student-wise Report -->
    <div class="card">
        <div class="card-body">
            <?php if (empty($reportData['students'])): ?>
                <div style="text-align: center; padding: 3rem;">
                    <i class="fas fa-chart-pie" style="font-size: 3rem; color: var(--border-color); margin-bottom: 1rem;"></i>
                    <p style="color: var(--text-secondary); font-size: 1.1rem;">No attendance records found for this period.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table id="reportTable" style="width: 100%; border-collapse: separate; border-spacing: 0;">
                        <thead>
                            <tr style="background-color: var(--light-color);">
                                <th style="padding: 1rem; border-bottom: 2px solid var(--border-color);">Reg. No.</th>
                                <th style="padding: 1rem; border-bottom: 2px solid var(--border-color);">Title</th>
                                <th style="padding: 1rem; border-bottom: 2px solid var(--border-color);">Student Name</th>
                                <th style="padding: 1rem; border-bottom: 2px solid var(--border-color); text-align: center;">Total Sessions</th>
                                <th style="padding: 1rem; border-bottom: 2px solid var(--border-color); text-align: center;">Present</th>
                                <th style="padding: 1rem; border-bottom: 2px solid var(--border-color); text-align: center;">Absent</th>
                                <th style="padding: 1rem; border-bottom: 2px solid var(--border-color);">Attendance %</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reportData['students'] as $student): 
                                $attendanceRate = calculateAttendancePercentage($student['present'], $student['total_records']);
                                $barColor = $attendanceRate >= 75 ? 'var(--success-color)' : ($attendanceRate >= 50 ? 'var(--warning-color)' : 'var(--danger-color)');
                            ?>
                            <tr>
                                <td style="padding: 1rem; border-bottom: 1px solid var(--border-color);">
                                    <strong><?php echo htmlspecialchars($student['reg_no']); ?></strong>
                                </td>
                                <td style="padding: 1rem; border-bottom: 1px solid var(--border-color);">
                                    <span class="badge badge-<?php echo $student['title'] === 'MR' ? 'primary' : 'info'; ?>">
                                        <?php echo htmlspecialchars($student['title']); ?>
                                    </span>
                                </td>
                                <td style="padding: 1rem; border-bottom: 1px solid var(--border-color);">
                                    <?php echo htmlspecialchars($student['name']); ?>
                                </td>
                                <td style="padding: 1rem; border-bottom: 1px solid var(--border-color); text-align: center;">
                                    <?php echo $student['total_records']; ?>
                                </td>
                                <td style="padding: 1rem; border-bottom: 1px solid var(--border-color); text-align: center;">
                                    <span style="color: var(--success-color); font-weight: 700;"><?php echo $student['present']; ?></span>
                                </td>
                                <td style="padding: 1rem; border-bottom: 1px solid var(--border-color); text-align: center;">
                                    <span style="color: var(--danger-color); font-weight: 700;"><?php echo $student['absent']; ?></span>
                                </td>
                                <td style="padding: 1rem; border-bottom: 1px solid var(--border-color); width: 200px;">
                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        <div style="flex: 1; height: 8px; background: var(--border-color); border-radius: 4px; overflow: hidden;">
                                            <div style="height: 100%; width: <?php echo $attendanceRate; ?>%; background: <?php echo $barColor; ?>;"></div>
                                        </div>
                                        <span style="font-weight: 600; color: var(--text-primary); min-width: 40px; text-align: right;">
                                            <?php echo $attendanceRate; ?>%
                                        </span>
                                    </div>
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

<style>
@media print {
    .report-filters, .btn, .sidebar, header {
        display: none !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    .main-content {
        margin: 0 !important;
        padding: 0 !important;
    }
}
</style>

<?php require_once '../includes/footer.php'; ?>
