<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Get lecturer ID
$lecturerId = isset($_GET['lecturer_id']) ? (int)$_GET['lecturer_id'] : 0;

if (!$lecturerId) {
    redirect('login.php');
}

// Fetch lecturer details
try {
    $stmt = $pdo->prepare("SELECT name FROM lecturers WHERE id = ?");
    $stmt->execute([$lecturerId]);
    $lecturer = $stmt->fetch();

    if (!$lecturer) {
        die("Lecturer not found.");
    }
} catch (PDOException $e) {
    die("Error fetching lecturer details.");
}

$selectedDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$dayOfWeek = date('l', strtotime($selectedDate));

// Fetch partitions/classes for this lecturer on the selected date
$partitions = [];
try {
    $stmt = $pdo->prepare("
        SELECT p.*, g.name as group_name
        FROM partitions p
        JOIN groups g ON p.group_id = g.id
        JOIN group_assignments ga ON g.id = ga.group_id
        WHERE ga.lecturer_id = ? AND p.day_of_week = ?
        ORDER BY p.start_time ASC
    ");
    $stmt->execute([$lecturerId, $dayOfWeek]);
    $partitions = $stmt->fetchAll();
} catch (PDOException $e) {
    // Handle error
}

// Get selected partition to show students
$selectedPartitionId = isset($_GET['partition_id']) ? (int)$_GET['partition_id'] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance View - <?php echo htmlspecialchars($lecturer['name']); ?></title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --glass-bg: rgba(255, 255, 255, 0.9);
            --glass-border: rgba(255, 255, 255, 0.2);
            --accent-gradient: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: #f8fafc;
            color: #1e293b;
            min-height: 100vh;
        }

        .page-header {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            padding: 1rem 1.5rem;
            position: sticky;
            top: 0;
            z-index: 100;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e2e8f0;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 1.5rem 1rem;
        }

        /* Lecturer Banner */
        .premium-banner {
            background: var(--accent-gradient);
            padding: 2.5rem 2rem;
            border-radius: 24px;
            color: white;
            position: relative;
            overflow: hidden;
            margin-bottom: 2rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .premium-banner::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 70%);
            border-radius: 50%;
        }

        .banner-content {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .lecturer-avatar-large {
            width: 70px;
            height: 70px;
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.4);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            backdrop-filter: blur(5px);
        }

        .banner-text h1 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        /* Controls Section */
        .control-panel {
            background: white;
            padding: 1.5rem;
            border-radius: 20px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        @media (min-width: 768px) {
            .control-panel {
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
            }
        }

        .date-picker-wrap {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .custom-input {
            border: 2px solid #f1f5f9;
            background: #f8fafc;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            font-family: inherit;
            font-weight: 500;
            transition: all 0.2s;
            outline: none;
        }

        .custom-input:focus {
            border-color: #6366f1;
            background: white;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }

        /* Search Section */
        .search-container {
            position: relative;
            flex: 1;
            max-width: 400px;
        }

        .search-container i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }

        .search-input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.75rem;
            border: 2px solid #f1f5f9;
            background: #f8fafc;
            border-radius: 12px;
            outline: none;
            transition: all 0.2s;
        }

        /* Class Selection */
        .class-pills {
            display: flex;
            gap: 0.75rem;
            overflow-x: auto;
            padding-bottom: 0.5rem;
            margin-bottom: 2rem;
            scrollbar-width: none;
        }

        .class-pills::-webkit-scrollbar { display: none; }

        .pill {
            background: white;
            padding: 0.8rem 1.25rem;
            border-radius: 14px;
            white-space: nowrap;
            text-decoration: none;
            color: #64748b;
            font-weight: 600;
            font-size: 0.95rem;
            border: 2px solid transparent;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .pill:hover {
            transform: translateY(-2px);
            color: #4f46e5;
        }

        .pill.active {
            background: #4f46e5;
            color: white;
            box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.3);
        }

        /* Student Record Rendering */
        .student-grid {
            display: grid;
            gap: 1rem;
        }

        .student-card {
            background: white;
            padding: 1.25rem;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.2s;
            border: 1px solid #f1f5f9;
        }

        .student-card:hover {
            border-color: #e2e8f0;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
        }

        .student-main-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .reg-badge {
            background: #f1f5f9;
            color: #475569;
            padding: 0.4rem 0.75rem;
            border-radius: 10px;
            font-family: monospace;
            font-weight: 700;
            font-size: 0.85rem;
        }

        .student-details h4 {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 600;
            color: #1e293b;
        }

        .student-details p {
            margin: 0;
            font-size: 0.8rem;
            color: #94a3b8;
            font-weight: 500;
        }

        .status-pill {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.5rem 1rem;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 700;
        }

        .status-pill.p { background: #dcfce7; color: #166534; }
        .status-pill.a { background: #fee2e2; color: #991b1b; }
        .status-pill.l { background: #fef3c7; color: #92400e; }
        .status-pill.u { background: #f1f5f9; color: #64748b; }

        /* Stats in Card */
        .mini-stats {
            display: flex;
            gap: 0.4rem;
            margin-top: 0.5rem;
        }

        .mini-badge {
            font-size: 0.7rem;
            padding: 0.2rem 0.5rem;
            background: #f8fafc;
            border-radius: 6px;
            color: #64748b;
            font-weight: 600;
            border: 1px solid #f1f5f9;
        }

        /* Percentage indicator on card */
        .pct-circle {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: 700;
            border: 3px solid #e2e8f0;
            position: relative;
        }

        .pct-circle::after {
            content: '';
            position: absolute;
            top: -3px; left: -3px; bottom: -3px; right: -3px;
            border-radius: 50%;
            border: 3px solid transparent;
            border-top-color: #6366f1;
            transform: rotate(calc(var(--pct) * 3.6deg));
            display: none; /* Dynamic would need SVG usually */
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 24px;
            color: #94a3b8;
        }

        .empty-state i {
            display: block;
            font-size: 4rem;
            margin-bottom: 1rem;
            background: var(--accent-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>
<body>

    <header class="page-header">
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <i class="fas fa-graduation-cap" style="font-size: 1.5rem; color: #6366f1;"></i>
            <span style="font-weight: 800; font-size: 1.2rem; letter-spacing: -0.02em;"><?php echo APP_NAME; ?></span>
        </div>
        <a href="login.php" style="text-decoration: none; color: #64748b; font-weight: 600; display: flex; align-items: center; gap: 0.5rem; font-size: 0.9rem;">
            <span>Staff Portal</span>
            <i class="fas fa-arrow-right"></i>
        </a>
    </header>

    <div class="container">
        <!-- Premium Banner -->
        <div class="premium-banner">
            <div class="banner-content">
                <div class="lecturer-avatar-large">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="banner-text">
                    <p style="text-transform: uppercase; font-size: 0.7rem; font-weight: 800; letter-spacing: 0.1em; opacity: 0.8; margin-bottom: 0.2rem;">Attendance Hub</p>
                    <h1><?php echo htmlspecialchars($lecturer['name']); ?></h1>
                    <div style="display: flex; align-items: center; gap: 1rem; margin-top: 0.5rem; font-size: 0.9rem; opacity: 0.9;">
                        <span><i class="fas fa-calendar-day"></i> <?php echo date('D, M d', strtotime($selectedDate)); ?></span>
                        <span><i class="fas fa-clock"></i> <?php echo date('h:i A'); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Control Panel -->
        <div class="control-panel">
            <form method="GET" class="date-picker-wrap">
                <input type="hidden" name="lecturer_id" value="<?php echo $lecturerId; ?>">
                <input type="date" name="date" class="custom-input" value="<?php echo $selectedDate; ?>" onchange="this.form.submit()">
            </form>

            <?php if ($selectedPartitionId): ?>
            <div class="search-container">
                <i class="fas fa-search"></i>
                <input type="text" id="studentSearch" class="search-input" placeholder="Find by ID or Name..." onkeyup="filterStudents()">
            </div>
            <?php endif; ?>
        </div>

        <!-- Class Selection Pills -->
        <?php if (!empty($partitions)): ?>
            <div class="class-pills">
                <?php foreach ($partitions as $p): 
                    $isActive = ($selectedPartitionId == $p['id']);
                ?>
                    <a href="?lecturer_id=<?php echo $lecturerId; ?>&date=<?php echo $selectedDate; ?>&partition_id=<?php echo $p['id']; ?>" class="pill <?php echo $isActive ? 'active' : ''; ?>">
                        <i class="fas fa-chalkboard"></i>
                        <span><?php echo htmlspecialchars($p['name']); ?></span>
                        <span style="font-size: 0.7rem; opacity: 0.7; font-weight: 400;">
                            <?php echo date('g:i A', strtotime($p['start_time'])); ?>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-calendar-times"></i>
                <h3>No Classes Today</h3>
                <p>Enjoy your break! No sessions are scheduled for this date.</p>
            </div>
        <?php endif; ?>

        <!-- Student Attendance List -->
        <?php if ($selectedPartitionId): 
            // Data logic (duplicated/refined from previous version)
            $students = [];
            $summaryMap = [];
            $totalClassesHeld = 0;
            try {
                $stmt = $pdo->prepare("SELECT group_id FROM partitions WHERE id = ?");
                $stmt->execute([$selectedPartitionId]);
                $partitionInfo = $stmt->fetch();
                
                if ($partitionInfo) {
                    $groupId = $partitionInfo['group_id'];
                    $stmt = $pdo->prepare("SELECT * FROM students WHERE group_id = ? ORDER BY reg_no");
                    $stmt->execute([$groupId]);
                    $students = $stmt->fetchAll();

                    $stmt = $pdo->prepare("SELECT student_id, status FROM attendance WHERE partition_id = ? AND date = ?");
                    $stmt->execute([$selectedPartitionId, $selectedDate]);
                    $attendanceMap = [];
                    while ($row = $stmt->fetch()) $attendanceMap[$row['student_id']] = $row['status'];

                    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT date) FROM attendance WHERE partition_id = ?");
                    $stmt->execute([$selectedPartitionId]);
                    $totalClassesHeld = (int)$stmt->fetchColumn();

                    $stmt = $pdo->prepare("SELECT student_id, SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as p, SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as l, SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as a FROM attendance WHERE partition_id = ? GROUP BY student_id");
                    $stmt->execute([$selectedPartitionId]);
                    while ($row = $stmt->fetch()) $summaryMap[$row['student_id']] = $row;
                }
            } catch (PDOException $e) {}
        ?>
            
            <?php if (!empty($students)): ?>
                <div class="student-grid" id="studentGrid">
                    <?php foreach ($students as $student): 
                        $status = $attendanceMap[$student['id']] ?? 'unknown';
                        $summary = $summaryMap[$student['id']] ?? ['p' => 0, 'l' => 0, 'a' => 0];
                        $attended = (int)$summary['p'] + (int)$summary['l'];
                        $percentage = ($totalClassesHeld > 0) ? round(($attended / $totalClassesHeld) * 100) : 0;
                        
                        // Status styling
                        $pillChar = 'u'; $pillLabel = 'Pending';
                        if ($status === 'present') { $pillChar = 'p'; $pillLabel = 'Present'; }
                        if ($status === 'absent') { $pillChar = 'a'; $pillLabel = 'Absent'; }
                        if ($status === 'late') { $pillChar = 'l'; $pillLabel = 'Late'; }
                    ?>
                        <div class="student-card" data-name="<?php echo strtolower($student['name']); ?>" data-reg="<?php echo strtolower($student['reg_no']); ?>">
                            <div class="student-main-info">
                                <div class="reg-badge"><?php echo htmlspecialchars($student['reg_no']); ?></div>
                                <div class="student-details">
                                    <h4><?php echo htmlspecialchars($student['name']); ?></h4>
                                    <div class="mini-stats">
                                        <div class="mini-badge">P: <?php echo $summary['p']; ?></div>
                                        <div class="mini-badge">L: <?php echo $summary['l']; ?></div>
                                        <div class="mini-badge">A: <?php echo $summary['a']; ?></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div style="display: flex; align-items: center; gap: 1.5rem;">
                                <div class="status-pill <?php echo $pillChar; ?>">
                                    <i class="fas <?php 
                                        if ($pillChar == 'p') echo 'fa-check-circle';
                                        elseif ($pillChar == 'a') echo 'fa-times-circle';
                                        elseif ($pillChar == 'l') echo 'fa-clock';
                                        else echo 'fa-question-circle';
                                    ?>"></i>
                                    <span><?php echo $pillLabel; ?></span>
                                </div>
                                <div class="pct-circle" style="color: <?php echo ($percentage >= 75) ? '#166534' : '#991b1b'; ?>;">
                                    <?php echo $percentage; ?>%
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-users-slash"></i>
                    <h3>No Records</h3>
                    <p>No students are currently enrolled in this group.</p>
                </div>
            <?php endif; ?>

        <?php endif; ?>

    </div>

    <script>
        function filterStudents() {
            const input = document.getElementById('studentSearch').value.toLowerCase();
            const cards = document.querySelectorAll('.student-card');
            
            cards.forEach(card => {
                const name = card.getAttribute('data-name');
                const reg = card.getAttribute('data-reg');
                
                if (name.includes(input) || reg.includes(input)) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>

