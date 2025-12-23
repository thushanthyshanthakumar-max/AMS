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

<div class="premium-banner">
    <div class="banner-content">
        <div class="banner-icon-wrapper">
            <i class="fas fa-clipboard-user"></i>
        </div>
        <div class="banner-text">
            <h1>Attendance Desk</h1>
            <p>Mark and manage student attendance for your active sessions</p>
        </div>
        <div class="banner-actions">
            <div style="background: rgba(255,255,255,0.05); padding: 0.75rem 1.5rem; border-radius: 16px; border: 1px solid rgba(255,255,255,0.1); width: fit-content;">
                <div style="color: rgba(255,255,255,0.4); font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em;">Current System Time</div>
                <div style="color: white; font-weight: 800; font-size: 1.25rem; line-height: 1; margin-top: 0.2rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-clock" style="color: #818cf8; font-size: 1rem;"></i>
                    <?php echo date('g:i A'); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($isCurrentSession): ?>
<div class="card" style="background: linear-gradient(135deg, #059669 0%, #065f46 100%); border: none; margin-bottom: 2rem; box-shadow: 0 20px 25px -5px rgba(5, 150, 105, 0.3);">
    <div class="card-body" style="padding: 1.5rem 2rem; display: flex; align-items: center; gap: 2rem;">
        <div style="width: 60px; height: 60px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; animation: pulse 2s infinite;">
            <i class="fas fa-broadcast-tower" style="color: white; font-size: 1.5rem;"></i>
        </div>
        <div style="flex-grow: 1;">
            <div style="color: rgba(255,255,255,0.8); font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Active Session Detected</div>
            <h2 style="color: white; margin: 0; font-weight: 800; font-size: 1.5rem;">
                <?php echo htmlspecialchars($currentPartition['name']); ?> • 
                <span style="opacity: 0.9;"><?php echo htmlspecialchars($currentPartition['group_name']); ?></span>
            </h2>
            <div style="color: rgba(255,255,255,0.7); font-size: 0.9rem; margin-top: 4px; font-weight: 500;">
                <i class="fas fa-hourglass-start" style="margin-right: 5px;"></i>
                <?php echo date('g:i A', strtotime($currentPartition['start_time'])); ?> - <?php echo date('g:i A', strtotime($currentPartition['end_time'])); ?>
            </div>
        </div>
        <a href="?group_id=<?php echo $currentPartition['group_id']; ?>&partition_id=<?php echo $currentPartition['id']; ?>&date=<?php echo $selectedDate; ?>" class="btn" style="background: white; color: #059669; font-weight: 800; padding: 0.75rem 1.5rem; border-radius: 14px;">
            Open Session <i class="fas fa-arrow-right" style="margin-left: 8px;"></i>
        </a>
    </div>
</div>
<?php elseif (!$selectedPartition && !$currentPartition): ?>
<div class="card" style="background: white; border-radius: 20px; border: 1px solid #e2e8f0; margin-bottom: 2rem;">
    <div class="card-body" style="padding: 1.5rem 2rem; display: flex; align-items: center; gap: 1.5rem;">
        <div style="width: 50px; height: 50px; background: #fff7ed; border-radius: 16px; display: flex; align-items: center; justify-content: center;">
            <i class="fas fa-calendar-day" style="color: #f59e0b; font-size: 1.25rem;"></i>
        </div>
        <div>
            <h4 style="margin: 0; font-weight: 800; color: #1e293b;">No Live Classes</h4>
            <p style="margin: 2px 0 0 0; color: #64748b; font-size: 0.9rem;">It's currently <?php echo $currentDay; ?> at <?php echo date('g:i A'); ?>. Select a batch below to view schedule details.</p>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card" style="margin-bottom: 2rem;">
    <div class="card-body">
        <?php if (!empty($assignedGroups)): ?>
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
                <h3 style="margin: 0; font-weight: 800; color: #1e293b;"><i class="fas fa-calendar-week" style="color: #6366f1; margin-right: 10px;"></i> Academic Schedule</h3>
                <div class="form-group" style="margin: 0; min-width: 200px;">
                    <input type="date" id="dateFilter" class="form-control" value="<?php echo $selectedDate; ?>" style="height: 42px; border-radius: 10px;">
                </div>
            </div>
            
            <?php
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
                } catch (PDOException $e) {}
            }
            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            ?>
            
            <div class="calendar-view-container" style="display: flex; gap: 1.25rem; overflow-x: auto; padding-bottom: 1rem;">
            <?php foreach ($days as $day): 
                if (empty($partitionsByDay[$day])) continue;
            ?>
                <div class="day-column" style="min-width: 280px; background: #f8fafc; padding: 1.25rem; border-radius: 20px; border: 1px solid #f1f5f9;">
                    <div style="font-weight: 800; font-size: 1rem; color: #1e293b; margin-bottom: 1.25rem; display: flex; align-items: center; justify-content: space-between;">
                        <?php echo $day; ?>
                        <?php if ($day === $currentDay): ?>
                            <span class="badge" style="background: #e0e7ff; color: #4338ca; font-size: 0.7rem;">TODAY</span>
                        <?php endif; ?>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <?php foreach ($partitionsByDay[$day] as $partition): 
                            $isCurrent = ($day === $currentDay && $currentTime >= $partition['start_time'] && $currentTime <= $partition['end_time']);
                            $isActive = ($selectedPartition == $partition['id']);
                        ?>
                            <a href="?group_id=<?php echo $partition['group_id']; ?>&partition_id=<?php echo $partition['id']; ?>&date=<?php echo $selectedDate; ?>" 
                               class="partition-card <?php echo $isActive ? 'active' : ''; ?> <?php echo $isCurrent ? 'current' : ''; ?>"
                               style="text-decoration: none; display: block; background: <?php echo $isActive ? 'linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%)' : 'white'; ?>; padding: 1.25rem; border-radius: 16px; border: 2px solid <?php echo $isActive ? '#6366f1' : '#e2e8f0'; ?>; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: <?php echo $isActive ? '0 10px 15px -3px rgba(99, 102, 241, 0.3)' : '0 1px 3px rgba(0,0,0,0.05)'; ?>; cursor: pointer; position: relative; overflow: hidden;">
                                <?php if ($isCurrent): ?>
                                <div style="position: absolute; top: 8px; right: 8px; width: 10px; height: 10px; background: #10b981; border-radius: 50%; box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2); animation: pulse 2s infinite;"></div>
                                <?php endif; ?>
                                <div style="color: <?php echo $isActive ? 'rgba(255,255,255,0.8)' : '#64748b'; ?>; font-size: 0.75rem; font-weight: 700; margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
                                    <i class="fas fa-clock"></i>
                                    <?php echo date('g:i A', strtotime($partition['start_time'])); ?> - <?php echo date('g:i A', strtotime($partition['end_time'])); ?>
                                </div>
                                <div style="color: <?php echo $isActive ? 'white' : '#1e293b'; ?>; font-weight: 800; font-size: 1.05rem; margin-bottom: 6px; line-height: 1.3;">
                                    <?php echo htmlspecialchars($partition['name']); ?>
                                </div>
                                <div style="color: <?php echo $isActive ? 'rgba(255,255,255,0.9)' : '#6366f1'; ?>; font-size: 0.8rem; font-weight: 600; display: flex; align-items: center; gap: 4px;">
                                    <i class="fas fa-users" style="font-size: 0.75rem;"></i>
                                    <?php echo htmlspecialchars($partition['group_name']); ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-warning" style="border-radius: 16px;">
                <i class="fas fa-exclamation-triangle"></i>
                You are not currently assigned to any classroom batches.
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
        <div class="card-header" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
            <h2 style="margin: 0;"><i class="fas fa-users"></i> Students (<?php echo count($students); ?>)</h2>
            <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                <button type="button" class="btn attendance-action-btn" onclick="markAll('present')" style="background: #10b981; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 12px; font-weight: 700; box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.3); transition: all 0.2s; white-space: nowrap;">
                    <i class="fas fa-check"></i> All Present
                </button>
                <button type="button" class="btn attendance-action-btn" onclick="markAll('absent')" style="background: #ef4444; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 12px; font-weight: 700; box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.3); transition: all 0.2s; white-space: nowrap;">
                    <i class="fas fa-times"></i> All Absent
                </button>
                <button type="submit" class="btn attendance-action-btn" style="background: #6366f1; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 12px; font-weight: 700; box-shadow: 0 4px 6px -1px rgba(99, 102, 241, 0.3); transition: all 0.2s; white-space: nowrap;">
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
