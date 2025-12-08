<?php
$pageTitle = 'Mark Attendance';
$baseUrl = '..';
$additionalJS = 'attendance.js';
require_once '../includes/header.php';
requireTeacher();

$teacherId = getTeacherId($pdo, $_SESSION['user_id']);

if (!$teacherId) {
    setFlashMessage('danger', 'Teacher profile not found.');
    redirect('../logout.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_attendance') {
    try {
        $groupId = (int)$_POST['group_id'];
        $partitionId = (int)$_POST['partition_id'];
        $date = $_POST['date'];
        $attendanceData = $_POST['attendance'] ?? [];
        
        // Verify teacher has access to this group
        if (!teacherHasAccessToGroup($pdo, $teacherId, $groupId)) {
            setFlashMessage('danger', 'You do not have access to this group.');
            redirect('mark_attendance.php');
        }
        
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("
            INSERT INTO attendance (student_id, partition_id, date, status, marked_by, notes)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE status = VALUES(status), notes = VALUES(notes), marked_by = VALUES(marked_by)
        ");
        
        foreach ($attendanceData as $studentId => $data) {
            $status = $data['status'];
            $notes = sanitize($data['notes'] ?? '');
            
            $stmt->execute([
                $studentId,
                $partitionId,
                $date,
                $status,
                $_SESSION['user_id'],
                $notes
            ]);
        }
        
        $pdo->commit();
        setFlashMessage('success', 'Attendance marked successfully.');
        redirect('mark_attendance.php?group_id=' . $groupId . '&partition_id=' . $partitionId . '&date=' . $date);
        
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        setFlashMessage('danger', 'Error marking attendance: ' . $e->getMessage());
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

// Get partitions for selected group
$partitions = [];
$students = [];
$existingAttendance = [];
$selectedGroup = isset($_GET['group_id']) ? (int)$_GET['group_id'] : null;
$selectedPartition = isset($_GET['partition_id']) ? (int)$_GET['partition_id'] : null;
$selectedDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

if ($selectedGroup && teacherHasAccessToGroup($pdo, $teacherId, $selectedGroup)) {
    try {
        // Get partitions for this group
        $stmt = $pdo->prepare("SELECT * FROM partitions WHERE group_id = ? ORDER BY day_of_week, start_time");
        $stmt->execute([$selectedGroup]);
        $partitions = $stmt->fetchAll();
        
        if ($selectedPartition) {
            // Get students in this group
            $stmt = $pdo->prepare("SELECT * FROM students WHERE group_id = ? ORDER BY name");
            $stmt->execute([$selectedGroup]);
            $students = $stmt->fetchAll();
            
            // Get existing attendance for this date and partition
            $stmt = $pdo->prepare("
                SELECT student_id, status, notes
                FROM attendance
                WHERE partition_id = ? AND date = ?
            ");
            $stmt->execute([$selectedPartition, $selectedDate]);
            $existing = $stmt->fetchAll();
            
            foreach ($existing as $record) {
                $existingAttendance[$record['student_id']] = [
                    'status' => $record['status'],
                    'notes' => $record['notes']
                ];
            }
        }
    } catch (PDOException $e) {
        setFlashMessage('danger', 'Error loading data.');
    }
}
?>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-clipboard-check"></i> Mark Attendance</h2>
    </div>
    <div class="card-body">
        <form method="GET" id="filterForm">
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 1rem; align-items: end;">
                <div class="form-group">
                    <label for="group_id" class="form-label">Select Group</label>
                    <select id="group_id" name="group_id" class="form-control" required onchange="document.getElementById('filterForm').submit()">
                        <option value="">Select a group...</option>
                        <?php foreach ($assignedGroups as $group): ?>
                            <option value="<?php echo $group['id']; ?>" <?php echo ($selectedGroup == $group['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($group['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="partition_id" class="form-label">Select Partition</label>
                    <select id="partition_id" name="partition_id" class="form-control" <?php echo empty($partitions) ? 'disabled' : ''; ?> required onchange="document.getElementById('filterForm').submit()">
                        <option value="">Select a partition...</option>
                        <?php foreach ($partitions as $partition): ?>
                            <option value="<?php echo $partition['id']; ?>" <?php echo ($selectedPartition == $partition['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($partition['name']); ?> - <?php echo htmlspecialchars($partition['day_of_week']); ?> (<?php echo formatTime($partition['start_time']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="date" class="form-label">Select Date</label>
                    <input type="date" id="date" name="date" class="form-control" value="<?php echo $selectedDate; ?>" max="<?php echo date('Y-m-d'); ?>" required onchange="document.getElementById('filterForm').submit()">
                </div>
                
                <div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Load
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if ($selectedGroup && $selectedPartition && !empty($students)): ?>
<form method="POST" id="attendanceForm">
    <input type="hidden" name="action" value="mark_attendance">
    <input type="hidden" name="group_id" value="<?php echo $selectedGroup; ?>">
    <input type="hidden" name="partition_id" value="<?php echo $selectedPartition; ?>">
    <input type="hidden" name="date" value="<?php echo $selectedDate; ?>">
    
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-users"></i> Students (<?php echo count($students); ?>)</h2>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-success" onclick="markAll('present')">
                    <i class="fas fa-check"></i> All Present
                </button>
                <button type="button" class="btn btn-sm btn-danger" onclick="markAll('absent')">
                    <i class="fas fa-times"></i> All Absent
                </button>
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="fas fa-save"></i> Save Attendance
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="attendance-grid">
                <?php foreach ($students as $student): 
                    $existingStatus = $existingAttendance[$student['id']]['status'] ?? 'present';
                    $existingNotes = $existingAttendance[$student['id']]['notes'] ?? '';
                ?>
                <div class="attendance-item">
                    <div>
                        <strong><?php echo htmlspecialchars($student['name']); ?></strong>
                        <br>
                        <small style="color: var(--text-secondary);"><?php echo htmlspecialchars($student['email']); ?></small>
                    </div>
                    
                    <div class="attendance-status">
                        <label class="status-radio">
                            <input type="radio" name="attendance[<?php echo $student['id']; ?>][status]" value="present" <?php echo ($existingStatus === 'present') ? 'checked' : ''; ?>>
                            <span style="color: var(--success-color); font-weight: 600;">Present</span>
                        </label>
                        <label class="status-radio">
                            <input type="radio" name="attendance[<?php echo $student['id']; ?>][status]" value="absent" <?php echo ($existingStatus === 'absent') ? 'checked' : ''; ?>>
                            <span style="color: var(--danger-color); font-weight: 600;">Absent</span>
                        </label>
                        <label class="status-radio">
                            <input type="radio" name="attendance[<?php echo $student['id']; ?>][status]" value="late" <?php echo ($existingStatus === 'late') ? 'checked' : ''; ?>>
                            <span style="color: var(--warning-color); font-weight: 600;">Late</span>
                        </label>
                    </div>
                    
                    <div>
                        <input type="text" name="attendance[<?php echo $student['id']; ?>][notes]" class="form-control" placeholder="Notes (optional)" value="<?php echo htmlspecialchars($existingNotes); ?>">
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</form>
<?php elseif ($selectedGroup && $selectedPartition): ?>
<div class="card">
    <div class="card-body">
        <p class="text-center" style="color: var(--text-secondary); padding: 2rem;">
            <i class="fas fa-info-circle"></i> No students found in this group.
        </p>
    </div>
</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
