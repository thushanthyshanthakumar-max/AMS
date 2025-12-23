<?php
$pageTitle = 'Generate Reports';
$baseUrl = '..';
require_once '../includes/header.php';
requireLecturer();

$lecturerId = getLecturerId($pdo, $_SESSION['user_id']);

if (!$lecturerId) {
    setFlashMessage('danger', 'Lecturer profile not found.');
    redirect('../logout.php');
}

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $groupId = (int)$_GET['group_id'];
    $startDate = $_GET['start_date'];
    $endDate = $_GET['end_date'];
    
    if (lecturerHasAccessToGroup($pdo, $lecturerId, $groupId)) {
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
        WHERE ga.lecturer_id = ?
        ORDER BY g.name
    ");
    $stmt->execute([$lecturerId]);
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
    
    if (lecturerHasAccessToGroup($pdo, $lecturerId, $groupId)) {
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

<div class="premium-banner">
    <div class="banner-content">
        <div class="banner-icon-wrapper">
            <i class="fas fa-chart-bar"></i>
        </div>
        <div class="banner-text">
            <h1>Analytics & Reports</h1>
            <p>Deep dive into student attendance metrics and batch performance</p>
        </div>
        <div class="banner-actions">
            <button onclick="window.print()" class="btn btn-secondary">
                <i class="fas fa-print"></i> Quick Print
            </button>
        </div>
    </div>
</div>

<div class="card" style="margin-bottom: 2rem;">
    <div class="card-body">
        <form method="GET">
            <input type="hidden" name="generate" value="1">
            <div style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 1.5rem; align-items: end;">
                <div class="form-group">
                    <label for="group_id" class="form-label" style="font-weight: 700; color: #475569;">Batch Selection</label>
                    <div style="position: relative;">
                        <i class="fas fa-users" style="position: absolute; left: 15px; top: 18px; color: #94a3b8; font-size: 0.9rem;"></i>
                        <select id="group_id" name="group_id" class="form-control" required style="padding-left: 45px; border-radius: 12px; height: 50px;">
                            <option value="">Select a group...</option>
                            <?php foreach ($assignedGroups as $group): ?>
                                <option value="<?php echo $group['id']; ?>" <?php echo (isset($_GET['group_id']) && $_GET['group_id'] == $group['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($group['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="start_date" class="form-label" style="font-weight: 700; color: #475569;">From</label>
                    <input type="date" id="start_date" name="start_date" class="form-control" value="<?php echo isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); ?>" required style="border-radius: 12px; height: 50px;">
                </div>
                
                <div class="form-group">
                    <label for="end_date" class="form-label" style="font-weight: 700; color: #475569;">To</label>
                    <input type="date" id="end_date" name="end_date" class="form-control" value="<?php echo isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d'); ?>" required style="border-radius: 12px; height: 50px;">
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary" style="height: 50px; padding: 0 1.5rem; border-radius: 12px; box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.3);">
                        <i class="fas fa-arrows-rotate"></i> Compile
                    </button>
                </div>
            </div>
            
            <div style="margin-top: 1.5rem; display: flex; gap: 0.75rem;">
                <button type="button" class="btn btn-sm btn-secondary" style="background: #f1f5f9; color: #475569; border: none; font-weight: 700; border-radius: 8px; padding: 0.5rem 1rem;" onclick="setDateRange('today')">Today</button>
                <button type="button" class="btn btn-sm btn-secondary" style="background: #f1f5f9; color: #475569; border: none; font-weight: 700; border-radius: 8px; padding: 0.5rem 1rem;" onclick="setDateRange('this_week')">This Week</button>
                <button type="button" class="btn btn-sm btn-secondary" style="background: #f1f5f9; color: #475569; border: none; font-weight: 700; border-radius: 8px; padding: 0.5rem 1rem;" onclick="setDateRange('last_week')">Last Week</button>
                <button type="button" class="btn btn-sm btn-secondary" style="background: #f1f5f9; color: #475569; border: none; font-weight: 700; border-radius: 8px; padding: 0.5rem 1rem;" onclick="setDateRange('this_month')">This Month</button>
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

    <!-- Report Analytics -->
    <div class="grid grid-cols-4" style="margin-bottom: 2rem;">
        <div class="stat-card" style="--bg-accent: #6366f1;">
            <div class="stat-icon"><i class="fas fa-user-group"></i></div>
            <div class="stat-value"><?php echo count($reportData['students']); ?></div>
            <div class="stat-label">Total Enrollment</div>
        </div>
        <div class="stat-card" style="--bg-accent: #10b981;">
            <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
            <div class="stat-value"><?php echo $reportData['overall']['present']; ?></div>
            <div class="stat-label">Total Present Sessions</div>
        </div>
        <div class="stat-card" style="--bg-accent: #ef4444;">
            <div class="stat-icon"><i class="fas fa-calendar-xmark"></i></div>
            <div class="stat-value"><?php echo $reportData['overall']['absent']; ?></div>
            <div class="stat-label">Total Absent Sessions</div>
        </div>
        <div class="stat-card" style="--bg-accent: #8b5cf6;">
            <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
            <div class="stat-value">
                <?php 
                $totalOverview = $reportData['overall']['total_records'];
                echo $totalOverview > 0 ? round(($reportData['overall']['present'] / $totalOverview) * 100) . '%' : '0%'; 
                ?>
            </div>
            <div class="stat-label">Batch Avg. Persistence</div>
        </div>
    </div>
    
    <!-- Detail Analytics Table -->
    <div class="card">
        <div class="card-body">
            <?php if (empty($reportData['students'])): ?>
                <div style="text-align: center; padding: 5rem 2rem;">
                    <i class="fas fa-folder-open" style="font-size: 3rem; color: #e2e8f0; margin-bottom: 1.5rem;"></i>
                    <p style="color: #94a3b8; font-weight: 600;">No attendance records compiled for this date range.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table id="reportTable">
                        <thead>
                            <tr>
                                <th>Student Identity</th>
                                <th class="text-center">Total Sessions</th>
                                <th class="text-center">Present</th>
                                <th class="text-center">Absent</th>
                                <th style="width: 250px;">Engagement Level</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reportData['students'] as $student): 
                                $attendanceRate = calculateAttendancePercentage($student['present'], $student['total_records']);
                                $barColor = $attendanceRate >= 75 ? '#10b981' : ($attendanceRate >= 50 ? '#f59e0b' : '#ef4444');
                            ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        <div style="width: 32px; height: 32px; background: #f1f5f9; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #64748b; font-size: 0.75rem; font-weight: 800;">
                                            <?php echo substr($student['reg_no'], -2); ?>
                                        </div>
                                        <div>
                                            <div style="font-weight: 700; color: #1e293b;"><?php echo htmlspecialchars($student['name']); ?></div>
                                            <div style="font-size: 0.75rem; color: #94a3b8; font-weight: 600;"><?php echo htmlspecialchars($student['reg_no']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center" style="font-weight: 700; color: #475569;"><?php echo $student['total_records']; ?></td>
                                <td class="text-center">
                                    <span class="badge" style="background: #ecfdf5; color: #059669;"><?php echo $student['present']; ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="badge" style="background: #fef2f2; color: #dc2626;"><?php echo $student['absent']; ?></span>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 1rem;">
                                        <div style="flex-grow: 1; height: 8px; background: #f1f5f9; border-radius: 10px; overflow: hidden;">
                                            <div style="height: 100%; width: <?php echo $attendanceRate; ?>%; background: <?php echo $barColor; ?>; box-shadow: 0 0 10px <?php echo $barColor; ?>40;"></div>
                                        </div>
                                        <div style="font-family: 'JetBrains Mono', monospace; font-size: 0.85rem; font-weight: 800; color: #1e293b; width: 45px; text-align: right;">
                                            <?php echo $attendanceRate; ?>%
                                        </div>
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
