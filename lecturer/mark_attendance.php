<?php
$pageTitle = 'Mark Attendance';
$baseUrl = '..';
require_once '../includes/header.php';
requireLecturer();

$lecturerId = getLecturerId($pdo, $_SESSION['user_id']);

if (!$lecturerId) {
    setFlashMessage('danger', 'Lecturer profile not found.');
    redirect('../logout.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_attendance') {
    try {
        $groupId = (int)$_POST['group_id'];
        $partitionId = (int)$_POST['partition_id'];
        $date = $_POST['date'];
        $attendanceData = $_POST['attendance'] ?? [];
        
        // Verify lecturer has access to this group
        if (!lecturerHasAccessToGroup($pdo, $lecturerId, $groupId)) {
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

// Get current day and time
$currentDay = date('l'); // Monday, Tuesday, etc.
$currentTime = date('H:i:s');
$selectedDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

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

// Auto-detect current partition
$currentPartition = null;
$autoSelectedGroup = null;

if (!isset($_GET['group_id']) && !isset($_GET['partition_id'])) {
    // Try to find current active partition
    try {
        $stmt = $pdo->prepare("
            SELECT p.*, g.id as group_id, g.name as group_name
            FROM partitions p
            JOIN groups g ON p.group_id = g.id
            JOIN group_assignments ga ON g.id = ga.group_id
            WHERE ga.lecturer_id = ?
            AND p.day_of_week = ?
            AND p.start_time <= ?
            AND p.end_time >= ?
            ORDER BY p.start_time
            LIMIT 1
        ");
        $stmt->execute([$lecturerId, $currentDay, $currentTime, $currentTime]);
        $currentPartition = $stmt->fetch();
        
        if ($currentPartition) {
            $autoSelectedGroup = $currentPartition['group_id'];
            $selectedGroup = $currentPartition['group_id'];
            $selectedPartition = $currentPartition['id'];
        }
    } catch (PDOException $e) {
        // Ignore error
    }
}

// Get partitions for selected group
$partitions = [];
$students = [];
$existingAttendance = [];
$selectedGroup = isset($_GET['group_id']) ? (int)$_GET['group_id'] : ($autoSelectedGroup ?? null);
$selectedPartition = isset($_GET['partition_id']) ? (int)$_GET['partition_id'] : ($currentPartition['id'] ?? null);

if ($selectedGroup && lecturerHasAccessToGroup($pdo, $lecturerId, $selectedGroup)) {
    try {
        // Get partitions for this group
        $stmt = $pdo->prepare("SELECT * FROM partitions WHERE group_id = ? ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), start_time");
        $stmt->execute([$selectedGroup]);
        $partitions = $stmt->fetchAll();
        
        if ($selectedPartition) {
            // Get students in this group
            $stmt = $pdo->prepare("SELECT * FROM students WHERE group_id = ? ORDER BY reg_no");
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

// Check if there's a current active session
$isCurrentSession = false;
if ($selectedPartition && $currentPartition && $selectedPartition == $currentPartition['id']) {
    $isCurrentSession = true;
}
?>

<style>
.current-session-banner {
    background: linear-gradient(135deg, var(--success-color), #059669);
    color: white;
    padding: 1rem 1.5rem;
    border-radius: var(--radius-lg);
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: var(--shadow-lg);
    animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.02); }
}

.no-session-banner {
    background: linear-gradient(135deg, var(--warning-color), #d97706);
    color: white;
    padding: 1rem 1.5rem;
    border-radius: var(--radius-lg);
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: var(--shadow-md);
}

.session-info {
    flex: 1;
}

.session-time {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
}

.session-details {
    opacity: 0.9;
    font-size: 0.875rem;
}

.calendar-view-container {
    display: flex;
    flex-direction: row;
    overflow-x: auto;
    gap: 1.5rem;
    padding-bottom: 1.5rem; /* Allow space for scrollbar */
}

.calendar-view-container::-webkit-scrollbar {
    height: 8px;
}

.calendar-view-container::-webkit-scrollbar-track {
    background: var(--light-color);
    border-radius: 4px;
}

.calendar-view-container::-webkit-scrollbar-thumb {
    background: var(--primary-light);
    border-radius: 4px;
}

.day-column {
    min-width: 240px;
    flex: 0 0 auto;
    background: #f8fafc;
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    padding: 1rem;
    display: flex;
    flex-direction: column;
}

.day-column-header {
    font-weight: 700;
    font-size: 1.1rem;
    color: var(--text-primary);
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.partition-list-stack {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    height: 100%;
}

.partition-quick-card {
    width: 100%;
    aspect-ratio: 1/1; 
    flex: 0 0 auto;
    background: white;
    border: 3px solid var(--border-color);
    border-radius: var(--radius-lg);
    padding: 1rem;
    cursor: pointer;
    transition: var(--transition);
    text-decoration: none;
    color: var(--text-primary);
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.partition-quick-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--primary-color);
    transform: scaleX(0);
    transition: var(--transition);
}

.partition-quick-card:hover::before {
    transform: scaleX(1);
}

.partition-quick-card:hover {
    border-color: var(--primary-color);
    box-shadow: var(--shadow-lg);
    transform: translateY(-5px);
}

.partition-quick-card.active {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    color: white;
    border-color: var(--primary-dark);
    box-shadow: var(--shadow-xl);
}

.partition-quick-card.active::before {
    background: white;
    transform: scaleX(1);
}

.partition-quick-card.current {
    background: linear-gradient(135deg, var(--success-color), #059669);
    color: white;
    border-color: var(--success-color);
    box-shadow: var(--shadow-xl);
    animation: pulse-card 2s ease-in-out infinite;
}

.partition-quick-card.current::before {
    background: white;
    transform: scaleX(1);
}

@keyframes pulse-card {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.card-session-name {
    font-size: 1.125rem;
    font-weight: 700;
    margin-bottom: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.card-time {
    font-size: 0.875rem;
    opacity: 0.9;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.25rem;
}

.card-group {
    font-size: 0.75rem;
    opacity: 0.85;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.25rem;
    margin-top: auto;
}
</style>

<?php if ($isCurrentSession): ?>
<div class="current-session-banner">
    <i class="fas fa-clock" style="font-size: 2rem;"></i>
    <div class="session-info">
        <div class="session-time">
            <i class="fas fa-circle" style="font-size: 0.5rem; animation: blink 1s infinite;"></i>
            LIVE SESSION
        </div>
        <div class="session-details">
            <?php echo htmlspecialchars($currentPartition['name']); ?> • 
            <?php echo htmlspecialchars($currentPartition['group_name']); ?> • 
            <?php echo date('g:i A', strtotime($currentPartition['start_time'])); ?> - <?php echo date('g:i A', strtotime($currentPartition['end_time'])); ?>
        </div>
    </div>
</div>
<?php elseif (!$selectedPartition && !$currentPartition): ?>
<div class="no-session-banner">
    <i class="fas fa-info-circle" style="font-size: 2rem;"></i>
    <div class="session-info">
        <div class="session-time">No Active Session</div>
        <div class="session-details">
            There is no class scheduled for <?php echo $currentDay; ?> at <?php echo date('g:i A'); ?>. 
            Please select a partition below to mark attendance.
        </div>
    </div>
</div>
<?php endif; ?>

<style>
@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}
</style>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-clipboard-check"></i> Mark Attendance</h2>
    </div>
    <div class="card-body">
        <?php if (!empty($assignedGroups)): ?>
            <h3 style="margin-bottom: 1rem;"><i class="fas fa-calendar-alt"></i> Your Class Schedule</h3>
            
            <?php
            // Group partitions by day
            $partitionsByDay = [];
            foreach ($assignedGroups as $group) {
                try {
                    $stmt = $pdo->prepare("SELECT * FROM partitions WHERE group_id = ? ORDER BY start_time");
                    $stmt->execute([$group['id']]);
                    $groupPartitions = $stmt->fetchAll();
                    
                    foreach ($groupPartitions as $partition) {
                        $partition['group_name'] = $group['name'];
                        $partition['group_id'] = $group['id'];
                        $partitionsByDay[$partition['day_of_week']][] = $partition;
                    }
                } catch (PDOException $e) {
                    // Ignore
                }
            }
            
            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            ?>
            
            <div class="calendar-view-container">
            <?php foreach ($days as $day): 
                if (empty($partitionsByDay[$day])) continue;
            ?>
                <div class="day-column">
                    <div class="day-column-header">
                        <?php echo $day; ?>
                        <?php if ($day === $currentDay): ?>
                            <span class="badge badge-primary">Today</span>
                        <?php endif; ?>
                    </div>
                    <div class="partition-list-stack">
                        <?php foreach ($partitionsByDay[$day] as $partition): 
                            $isCurrent = ($day === $currentDay && 
                                         $currentTime >= $partition['start_time'] && 
                                         $currentTime <= $partition['end_time']);
                            $isActive = ($selectedPartition == $partition['id']);
                            $cardClass = $isCurrent ? 'current' : ($isActive ? 'active' : '');
                        ?>
                            <a href="?group_id=<?php echo $partition['group_id']; ?>&partition_id=<?php echo $partition['id']; ?>&date=<?php echo $selectedDate; ?>" 
                               class="partition-quick-card <?php echo $cardClass; ?>">
                                <div class="card-session-name">
                                    <?php if ($isCurrent): ?>
                                        <i class="fas fa-circle" style="font-size: 0.5rem; animation: blink 1s infinite;"></i>
                                    <?php else: ?>
                                        <i class="fas fa-clock"></i>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($partition['name']); ?>
                                </div>
                                <div class="card-time">
                                    <?php echo date('g:i A', strtotime($partition['start_time'])); ?>
                                    <span>-</span>
                                    <?php echo date('g:i A', strtotime($partition['end_time'])); ?>
                                </div>
                                <div class="card-group">
                                    <i class="fas fa-users"></i>
                                    <?php echo htmlspecialchars($partition['group_name']); ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                You are not assigned to any groups yet. Please contact the administrator.
            </div>
        <?php endif; ?>
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
                        <strong><?php echo htmlspecialchars($student['reg_no']); ?></strong>
                        <br>
                        <span><?php echo htmlspecialchars($student['name']); ?></span>
                        <br>
                        <small style="color: var(--text-secondary);">
                            <span class="badge badge-<?php echo $student['title'] === 'MR' ? 'primary' : 'info'; ?>">
                                <?php echo htmlspecialchars($student['title']); ?>
                            </span>
                        </small>
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

<script>
function markAll(status) {
    const radios = document.querySelectorAll(`input[type="radio"][value="${status}"]`);
    radios.forEach(radio => radio.checked = true);
}
</script>

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
