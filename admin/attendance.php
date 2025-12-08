<?php
$pageTitle = 'View Attendance';
$baseUrl = '..';
require_once '../includes/header.php';
requireAdmin();

// Get all groups for filter
try {
    $stmt = $pdo->query("SELECT id, name FROM groups ORDER BY name");
    $groups = $stmt->fetchAll();
} catch (PDOException $e) {
    $groups = [];
}

// Filter parameters
$selectedGroup = isset($_GET['group_id']) ? (int)$_GET['group_id'] : null;
$selectedDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Get attendance records
$attendanceRecords = [];
if ($selectedGroup) {
    try {
        $stmt = $pdo->prepare("
            SELECT a.*, s.name as student_name, p.name as partition_name,
                   p.start_time, p.end_time, p.day_of_week,
                   u.username as marked_by_name
            FROM attendance a
            JOIN students s ON a.student_id = s.id
            JOIN partitions p ON a.partition_id = p.id
            JOIN users u ON a.marked_by = u.id
            WHERE s.group_id = ? AND a.date = ?
            ORDER BY p.start_time, s.name
        ");
        $stmt->execute([$selectedGroup, $selectedDate]);
        $attendanceRecords = $stmt->fetchAll();
        
        // Get statistics
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late
            FROM attendance a
            JOIN students s ON a.student_id = s.id
            WHERE s.group_id = ? AND a.date = ?
        ");
        $stmt->execute([$selectedGroup, $selectedDate]);
        $stats = $stmt->fetch();
    } catch (PDOException $e) {
        setFlashMessage('danger', 'Error loading attendance records.');
        $attendanceRecords = [];
        $stats = ['total' => 0, 'present' => 0, 'absent' => 0, 'late' => 0];
    }
}
?>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-clipboard-check"></i> View Attendance Records</h2>
    </div>
    <div class="card-body">
        <form method="GET" class="mb-3">
            <div style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 1rem; align-items: end;">
                <div class="form-group">
                    <label for="group_id" class="form-label">Select Group</label>
                    <select id="group_id" name="group_id" class="form-control" required>
                        <option value="">Select a group...</option>
                        <?php foreach ($groups as $group): ?>
                            <option value="<?php echo $group['id']; ?>" <?php echo ($selectedGroup == $group['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($group['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="date" class="form-label">Select Date</label>
                    <input type="date" id="date" name="date" class="form-control" value="<?php echo $selectedDate; ?>" required>
                </div>
                
                <div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> View Attendance
                    </button>
                </div>
            </div>
        </form>
        
        <?php if ($selectedGroup && !empty($attendanceRecords)): ?>
            <!-- Statistics -->
            <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 2rem;">
                <div class="stat-card" style="background: linear-gradient(135deg, var(--info-color), #2563eb);">
                    <div class="stat-icon"><i class="fas fa-clipboard-list"></i></div>
                    <div class="stat-value"><?php echo $stats['total']; ?></div>
                    <div class="stat-label">Total Records</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-value"><?php echo $stats['present']; ?></div>
                    <div class="stat-label">Present</div>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, var(--danger-color), #dc2626);">
                    <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
                    <div class="stat-value"><?php echo $stats['absent']; ?></div>
                    <div class="stat-label">Absent</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                    <div class="stat-value"><?php echo $stats['late']; ?></div>
                    <div class="stat-label">Late</div>
                </div>
            </div>
            
            <!-- Attendance Records -->
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Partition</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th>Marked By</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attendanceRecords as $record): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($record['student_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($record['partition_name']); ?></td>
                            <td><?php echo formatTime($record['start_time']) . ' - ' . formatTime($record['end_time']); ?></td>
                            <td><?php echo getStatusBadge($record['status']); ?></td>
                            <td><?php echo htmlspecialchars($record['marked_by_name']); ?></td>
                            <td><?php echo htmlspecialchars($record['notes']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif ($selectedGroup): ?>
            <p class="text-center" style="color: var(--text-secondary); padding: 2rem;">
                <i class="fas fa-info-circle"></i> No attendance records found for the selected date.
            </p>
        <?php else: ?>
            <p class="text-center" style="color: var(--text-secondary); padding: 2rem;">
                <i class="fas fa-arrow-up"></i> Please select a group and date to view attendance records.
            </p>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
