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

<div class="premium-banner">
    <div class="banner-content">
        <div class="banner-icon-wrapper">
            <i class="fas fa-history"></i>
        </div>
        <div class="banner-text">
            <h1>Attendance Logs</h1>
            <p>Review historical attendance records and session statistics</p>
        </div>
        <div class="banner-actions">
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-chart-pie"></i> View Overview
            </a>
        </div>
    </div>
</div>

<div class="card" style="margin-bottom: 2rem;">
    <div class="card-body">
        <form method="GET">
            <div style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 1.5rem; align-items: end;">
                <div class="form-group">
                    <label for="group_id" class="form-label" style="font-weight: 700; color: #475569;">Target Batch</label>
                    <select id="group_id" name="group_id" class="form-control" required style="border-radius: 12px; height: 50px;">
                        <option value="">Choose a group...</option>
                        <?php foreach ($groups as $group): ?>
                            <option value="<?php echo $group['id']; ?>" <?php echo ($selectedGroup == $group['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($group['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="date" class="form-label" style="font-weight: 700; color: #475569;">Specific Date</label>
                    <input type="date" id="date" name="date" class="form-control" value="<?php echo $selectedDate; ?>" required style="border-radius: 12px; height: 50px;">
                </div>
                
                <div>
                    <button type="submit" class="btn btn-primary" style="height: 50px; padding: 0 2rem; border-radius: 12px;">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if ($selectedGroup && !empty($attendanceRecords)): ?>
    <!-- Statistics Overview -->
    <div class="grid grid-cols-4" style="margin-bottom: 2rem;">
        <div class="mini-stat-card" style="--bg-accent: #6366f1;">
            <div class="mini-stat-icon"><i class="fas fa-users-viewfinder"></i></div>
            <div class="mini-stat-info">
                <h2><?php echo $stats['total']; ?></h2>
                <p>Total Roll Call</p>
            </div>
        </div>
        <div class="mini-stat-card" style="--bg-accent: #10b981;">
            <div class="mini-stat-icon"><i class="fas fa-user-check"></i></div>
            <div class="mini-stat-info">
                <h2><?php echo $stats['present']; ?></h2>
                <p>Present</p>
            </div>
        </div>
        <div class="mini-stat-card" style="--bg-accent: #f59e0b;">
            <div class="mini-stat-icon"><i class="fas fa-clock"></i></div>
            <div class="mini-stat-info">
                <h2><?php echo $stats['late']; ?></h2>
                <p>Late Arrival</p>
            </div>
        </div>
        <div class="mini-stat-card" style="--bg-accent: #ef4444;">
            <div class="mini-stat-icon"><i class="fas fa-user-xmark"></i></div>
            <div class="mini-stat-info">
                <h2><?php echo $stats['absent']; ?></h2>
                <p>Absent</p>
            </div>
        </div>
    </div>
    
    <!-- Attendance Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Student Identity</th>
                            <th>Class Session</th>
                            <th>Time Window</th>
                            <th>Status Badge</th>
                            <th>Registrar</th>
                            <th class="text-right">Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attendanceRecords as $record): ?>
                        <tr>
                            <td>
                                <div style="font-weight: 700; color: #0f172a;"><?php echo htmlspecialchars($record['student_name']); ?></div>
                            </td>
                            <td>
                                <span class="badge" style="background: #f1f5f9; color: #475569; letter-spacing: 0;">
                                    <?php echo htmlspecialchars($record['partition_name']); ?>
                                </span>
                            </td>
                            <td style="font-family: 'JetBrains Mono', monospace; font-size: 0.85rem; color: #64748b;">
                                <?php echo formatTime($record['start_time']) . ' - ' . formatTime($record['end_time']); ?>
                            </td>
                            <td><?php echo getStatusBadge($record['status']); ?></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.9rem; font-weight: 600; color: #1e293b;">
                                    <i class="fas fa-user-pen" style="color: #94a3b8; font-size: 0.8rem;"></i>
                                    <?php echo htmlspecialchars($record['marked_by_name']); ?>
                                </div>
                            </td>
                            <td class="text-right">
                                <?php if ($record['notes']): ?>
                                    <span style="font-size: 0.85rem; color: #94a3b8; font-style: italic;">"<?php echo htmlspecialchars($record['notes']); ?>"</span>
                                <?php else: ?>
                                    <span style="color: #e2e8f0;">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php elseif ($selectedGroup): ?>
    <div class="card" style="background: #f8fafc; border: 2px dashed #e2e8f0; border-radius: 24px;">
        <div class="card-body" style="text-align: center; padding: 5rem 2rem;">
            <i class="fas fa-calendar-xmark" style="font-size: 4rem; color: #cbd5e1; margin-bottom: 1.5rem;"></i>
            <h2 style="color: #64748b; font-weight: 800;">No Records Found</h2>
            <p style="color: #94a3b8;">There are no attendance logs for the selected group on this date.</p>
        </div>
    </div>
<?php else: ?>
    <div class="card" style="background: #f8fafc; border: 2px dashed #e2e8f0; border-radius: 24px;">
        <div class="card-body" style="text-align: center; padding: 5rem 2rem;">
            <i class="fas fa-search" style="font-size: 4rem; color: #cbd5e1; margin-bottom: 1.5rem;"></i>
            <h2 style="color: #64748b; font-weight: 800;">Ready to Search</h2>
            <p style="color: #94a3b8;">Select a batch and date above to pull up historical attendance logs.</p>
        </div>
    </div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
