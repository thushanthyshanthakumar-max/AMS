<?php
// public_attendance_view.php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$teacherId = $_GET['teacher_id'] ?? null;

if (!$teacherId) {
    redirect('login.php');
}

// Get Teacher Details
$stmt = $pdo->prepare("SELECT name FROM teachers WHERE id = ?");
$stmt->execute([$teacherId]);
$teacherName = $stmt->fetchColumn();

if (!$teacherName) {
    redirect('login.php');
}

// Get current day and time
$currentDay = date('l'); 
$currentTime = date('H:i:s');
$selectedDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Get assigned groups for this teacher
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

// Auto-detect current partition
$currentPartition = null;
$autoSelectedGroup = null;

if (!isset($_GET['group_id']) && !isset($_GET['partition_id'])) {
    try {
        $stmt = $pdo->prepare("
            SELECT p.*, g.id as group_id, g.name as group_name
            FROM partitions p
            JOIN groups g ON p.group_id = g.id
            JOIN group_assignments ga ON g.id = ga.group_id
            WHERE ga.teacher_id = ?
            AND p.day_of_week = ?
            AND p.start_time <= ?
            AND p.end_time >= ?
            ORDER BY p.start_time
            LIMIT 1
        ");
        $stmt->execute([$teacherId, $currentDay, $currentTime, $currentTime]);
        $currentPartition = $stmt->fetch();
        
        if ($currentPartition) {
            $autoSelectedGroup = $currentPartition['group_id'];
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

if ($selectedGroup) {
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
            
            // Get existing attendance
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
        $error = "Error loading data.";
    }
}

$isCurrentSession = false;
if ($selectedPartition && $currentPartition && $selectedPartition == $currentPartition['id']) {
    $isCurrentSession = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance View - <?php echo htmlspecialchars($teacherName); ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f3f4f6;
            padding: 2rem;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .header-card {
            background: white;
            padding: 1.5rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        /* Reusing styles from mark_attendance.php but embedded since header.php is not used */
        .badge {
            padding: 0.25rem 0.5rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-primary { background: var(--primary-light); color: var(--primary-color); }
        .badge-success { background: #d1fae5; color: #059669; }
        .badge-danger { background: #fee2e2; color: #dc2626; }
        .badge-warning { background: #fef3c7; color: #d97706; }
        .badge-info { background: #e0f2fe; color: #0284c7; }

        .attendance-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1rem;
        }
        
        .attendance-item {
            background: white;
            padding: 1rem;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .calendar-view-container {
            display: flex;
            flex-direction: row;
            overflow-x: auto;
            gap: 1.5rem;
            padding-bottom: 1.5rem;
        }
        
        .day-column {
            min-width: 240px;
            flex: 0 0 auto;
            background: white;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 1rem;
        }

        .partition-quick-card {
            display: block;
            background: white;
            border: 2px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 1rem;
            margin-bottom: 1rem;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            transition: all 0.2s;
        }
        
        .partition-quick-card:hover {
            border-color: var(--primary-color);
            transform: translateY(-2px);
        }

        .partition-quick-card.active {
            border-color: var(--primary-color);
            background: var(--primary-light);
            color: var(--primary-color);
        }

        .partition-quick-card.current {
            border-color: #059669;
            background: #d1fae5;
            color: #059669;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header-card">
        <div>
            <h1 style="margin: 0; color: var(--primary-color);">
                <i class="fas fa-chalkboard-teacher"></i> <?php echo htmlspecialchars($teacherName); ?>
            </h1>
            <p style="margin: 0.5rem 0 0; color: var(--text-secondary);">attendance Record View</p>
        </div>
        <a href="login.php" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left"></i> Back to Login
        </a>
    </div>

    <div class="card" style="margin-bottom: 2rem;">
        <div class="card-body">
            <h3><i class="fas fa-calendar-alt"></i> Select Class & Date</h3>
            
            <form method="GET" style="margin-top: 1rem; display: flex; gap: 1rem; flex-wrap: wrap; align-items: end;">
                <input type="hidden" name="teacher_id" value="<?php echo $teacherId; ?>">
                
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Date</label>
                    <input type="date" name="date" class="form-control" value="<?php echo $selectedDate; ?>" onchange="this.form.submit()">
                </div>
            </form>

            <div style="margin-top: 2rem;">
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
                    } catch (PDOException $e) {}
                }
                $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                ?>
                
                <div class="calendar-view-container">
                <?php foreach ($days as $day): 
                    if (empty($partitionsByDay[$day])) continue;
                ?>
                    <div class="day-column">
                        <strong style="display: block; margin-bottom: 1rem; border-bottom: 2px solid #eee; padding-bottom: 0.5rem;">
                            <?php echo $day; ?>
                            <?php if ($day === $currentDay): ?>
                                <span style="font-size: 0.7rem; background: var(--primary-color); color: white; padding: 2px 6px; border-radius: 4px; margin-left: 0.5rem;">TODAY</span>
                            <?php endif; ?>
                        </strong>
                        
                        <?php foreach ($partitionsByDay[$day] as $partition): 
                             $isCurrent = ($day === $currentDay && 
                                          $currentTime >= $partition['start_time'] && 
                                          $currentTime <= $partition['end_time']);
                            $isActive = ($selectedPartition == $partition['id']);
                            $cardClass = $isCurrent ? 'current' : ($isActive ? 'active' : '');
                        ?>
                            <a href="?teacher_id=<?php echo $teacherId; ?>&group_id=<?php echo $partition['group_id']; ?>&partition_id=<?php echo $partition['id']; ?>&date=<?php echo $selectedDate; ?>" 
                               class="partition-quick-card <?php echo $cardClass; ?>">
                                <div style="font-weight: bold; margin-bottom: 0.25rem;"><?php echo htmlspecialchars($partition['name']); ?></div>
                                <div style="font-size: 0.85rem; margin-bottom: 0.25rem;">
                                    <?php echo date('g:i A', strtotime($partition['start_time'])); ?> - <?php echo date('g:i A', strtotime($partition['end_time'])); ?>
                                </div>
                                <div style="font-size: 0.85rem; color: #666;">
                                    <i class="fas fa-users"></i> <?php echo htmlspecialchars($partition['group_name']); ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if ($selectedGroup && $selectedPartition && !empty($students)): ?>
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-users"></i> Student Attendance Status</h2>
            <span class="badge badge-info"><?php echo formatDate($selectedDate); ?></span>
        </div>
        <div class="card-body">
            <div class="attendance-grid">
                <?php foreach ($students as $student): 
                    $existingStatus = $existingAttendance[$student['id']]['status'] ?? 'pending';
                    $existingNotes = $existingAttendance[$student['id']]['notes'] ?? '';
                    
                    $statusColor = 'secondary';
                    $statusIcon = 'question';
                    $statusText = 'Not Marked';
                    
                    if ($existingStatus === 'present') {
                        $statusColor = 'success';
                        $statusIcon = 'check';
                        $statusText = 'Present';
                    } elseif ($existingStatus === 'absent') {
                        $statusColor = 'danger';
                        $statusIcon = 'times';
                        $statusText = 'Absent';
                    } elseif ($existingStatus === 'late') {
                        $statusColor = 'warning';
                        $statusIcon = 'clock';
                        $statusText = 'Late';
                    }
                ?>
                <div class="attendance-item" style="border-left: 5px solid var(--<?php echo $statusColor === 'secondary' ? 'gray' : $statusColor; ?>-color);">
                    <div>
                        <strong><?php echo htmlspecialchars($student['reg_no']); ?></strong>
                        <br>
                        <span><?php echo htmlspecialchars($student['name']); ?></span>
                    </div>
                    
                    <div style="text-align: right;">
                        <span class="badge badge-<?php echo $statusColor; ?>" style="font-size: 1rem; padding: 0.5rem 1rem;">
                            <i class="fas fa-<?php echo $statusIcon; ?>"></i> <?php echo $statusText; ?>
                        </span>
                        <?php if ($existingNotes): ?>
                            <div style="font-size: 0.8rem; color: #666; margin-top: 0.5rem;">
                                <?php echo htmlspecialchars($existingNotes); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

</body>
</html>
