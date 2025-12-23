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
            --glass-bg: rgba(255, 255, 255, 0.7);
            --glass-border: rgba(255, 255, 255, 0.3);
            --primary-gradient: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            --surface-bg: #f8fafc;
        }

        body {
            font-family: 'Outfit', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #4f46e5;
            background-image:
                radial-gradient(at 0% 0%, #4338ca 0px, transparent 50%),
                radial-gradient(at 100% 0%, #7c3aed 0px, transparent 50%),
                radial-gradient(at 100% 100%, #1e1b4b 0px, transparent 50%),
                radial-gradient(at 0% 100%, #312e81 0px, transparent 50%);
            background-attachment: fixed;
            min-height: 100vh;
            color: #1e293b;
            margin: 0;
        }

        .page-header {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 1.25rem 2rem;
            position: sticky;
            top: 0;
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 2.5rem 1.5rem;
        }

        /* Hero Banner */
        .premium-banner {
            background: #0f172a;
            padding: 3.5rem 2.5rem;
            border-radius: 30px;
            position: relative;
            overflow: hidden;
            margin-bottom: 3rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        .premium-banner::after {
            content: '';
            position: absolute;
            top: 0; right: 0; bottom: 0; left: 0;
            background: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 86c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zm66-3c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zm-40-39c.552 0 1-.448 1-1s-.448-1-1-1-1 .448-1 1 .448 1 1 1zm50 38c.552 0 1-.448 1-1s-.448-1-1-1-1 .448-1 1 .448 1 1 1zM21 39c.552 0 1-.448 1-1s-.448-1-1-1-1 .448-1 1 .448 1 1 1zm56-38c.552 0 1-.448 1-1s-.448-1-1-1-1 .448-1 1 .448 1 1 1zM9.5 54c.276 0 .5-.224.5-.5s-.224-.5-.5-.5-.5.224-.5.5.224.5.5.5zM75.4 49c.276 0 .5-.224.5-.5s-.224-.5-.5-.5-.5.224-.5.5.224.5.5.5zM27.7 97c.276 0 .5-.224.5-.5s-.224-.5-.5-.5-.5.224-.5.5.224.5.5.5zM3.5 17c.138 0 .25-.112.25-.25s-.112-.25-.25-.25-.25.112-.25.25.112.25.25.25zM96.7 57c.138 0 .25-.112.25-.25s-.112-.25-.25-.25-.25.112-.25.25.112.25.25.25zM11.1 60c.138 0 .25-.112.25-.25s-.112-.25-.25-.25-.25.112-.25.25.112.25.25.25zM5.2 76c.069 0 .125-.056.125-.125s-.056-.125-.125-.125-.125.056-.125.125.125.056.125.125zm90.7-34c.069 0 .125-.056.125-.125s-.056-.125-.125-.125-.125.056-.125.125.125.056.125.125z' fill='%23ffffff' fill-opacity='0.05' fill-rule='evenodd'/%3E%3C/svg%3E");
            opacity: 0.5;
        }

        .banner-content {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
            gap: 2.5rem;
        }

        .banner-icon-bg {
            width: 90px;
            height: 90px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: #818cf8;
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }

        .banner-text h1 {
            margin: 0;
            font-size: 2.5rem;
            font-weight: 800;
            letter-spacing: -0.04em;
            background: linear-gradient(135deg, #ffffff 0%, #cbd5e1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Control Panel */
        .controls-wrapper {
            background: white;
            padding: 1.5rem;
            border-radius: 24px;
            margin-bottom: 3rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 2rem;
            align-items: center;
        }

        .date-btn {
            background: #f1f5f9;
            border: 2px solid #e2e8f0;
            padding: 0.75rem 1.25rem;
            border-radius: 14px;
            font-weight: 700;
            color: #475569;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .date-btn:hover {
            border-color: #6366f1;
            color: #6366f1;
            background: white;
        }

        .search-inner {
            position: relative;
        }

        .search-inner i {
            position: absolute;
            left: 1.25rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 1.1rem;
        }

        .search-pill {
            width: 100%;
            padding: 1rem 1rem 1rem 3.5rem;
            background: #f8fafc;
            border: 2px solid #f1f5f9;
            border-radius: 16px;
            font-family: inherit;
            font-size: 1rem;
            font-weight: 500;
            color: #1e293b;
            outline: none;
            transition: all 0.2s;
        }

        .search-pill:focus {
            background: white;
            border-color: #6366f1;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }

        /* Session Navigation */
        .session-track {
            display: flex;
            gap: 1rem;
            margin-bottom: 2.5rem;
            overflow-x: auto;
            padding-bottom: 1rem;
            scrollbar-width: none;
        }

        .session-card {
            background: white;
            padding: 1rem 2rem;
            border-radius: 18px;
            white-space: nowrap;
            text-decoration: none;
            color: #64748b;
            font-weight: 700;
            border: 2px solid transparent;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .session-card:hover {
            transform: translateY(-4px);
            color: #6366f1;
            border-color: rgba(99, 102, 241, 0.2);
        }

        .session-card.active {
            background: #6366f1;
            color: white;
            box-shadow: 0 15px 30px -10px rgba(99, 102, 241, 0.5);
            transform: translateY(-4px);
        }

        /* Student Grid */
        .record-stack {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 1.5rem;
        }

        .student-item {
            background: white;
            padding: 1.5rem;
            border-radius: 24px;
            border: 1px solid #f1f5f9;
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            transition: all 0.3s ease;
            position: relative;
        }

        .student-item:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05);
            border-color: #e2e8f0;
        }

        .student-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
        }

        .student-bio {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .avatar-circle {
            width: 48px;
            height: 48px;
            background: #f1f5f9;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            color: #6366f1;
            font-size: 0.9rem;
        }

        .student-name {
            font-weight: 800;
            color: #1e293b;
            font-size: 1.1rem;
            margin: 0;
        }

        .student-id {
            font-size: 0.75rem;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .status-badge {
            padding: 0.6rem 1rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .status-badge.present { background: #dcfce7; color: #15803d; }
        .status-badge.absent { background: #fee2e2; color: #b91c1c; }
        .status-badge.late { background: #fef3c7; color: #b45309; }
        .status-badge.unknown { background: #f1f5f9; color: #64748b; }

        .performance-section {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #f8fafc;
            padding: 1rem;
            border-radius: 16px;
        }

        .stat-group {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .stat-label {
            font-size: 0.65rem;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
        }

        .stat-value {
            font-weight: 800;
            color: #475569;
            font-size: 0.9rem;
        }

        .percentage-indicator {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 0.85rem;
            border: 4px solid #e2e8f0;
        }

        .percentage-indicator.high { border-color: #10b981; color: #059669; }
        .percentage-indicator.medium { border-color: #f59e0b; color: #d97706; }
        .percentage-indicator.low { border-color: #ef4444; color: #dc2626; }

        .empty-visual {
            text-align: center;
            padding: 6rem 2rem;
            background: white;
            border-radius: 32px;
            color: #94a3b8;
            border: 2px dashed #e2e8f0;
        }

        @media (max-width: 768px) {
            .controls-wrapper {
                grid-template-columns: 1fr;
            }
            .premium-banner {
                padding: 2.5rem 1.5rem;
            }
            .banner-content {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>

    <header class="page-header">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <div style="width: 40px; height: 40px; background: #6366f1; border-radius: 12px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);">
                <i class="fas fa-fingerprint" style="color: white; font-size: 1.2rem;"></i>
            </div>
            <span style="font-weight: 900; font-size: 1.4rem; letter-spacing: -0.04em; color: #0f172a;"><?php echo APP_NAME; ?></span>
        </div>
        <a href="login.php" style="text-decoration: none; color: #6366f1; font-weight: 800; display: flex; align-items: center; gap: 8px; font-size: 0.9rem; background: rgba(99, 102, 241, 0.1); padding: 0.6rem 1.2rem; border-radius: 12px; transition: all 0.2s;">
            <span>Staff Engine</span>
            <i class="fas fa-shuttle-space"></i>
        </a>
    </header>

    <div class="container">
        <!-- Hero Section -->
        <div class="premium-banner">
            <div class="banner-content">
                <div class="banner-icon-bg">
                    <i class="fas fa-id-card-clip"></i>
                </div>
                <div class="banner-text">
                    <div style="font-weight: 800; font-size: 0.8rem; text-transform: uppercase; color: #818cf8; letter-spacing: 0.2em; margin-bottom: 8px;">Academic Roster</div>
                    <h1><?php echo htmlspecialchars($lecturer['name']); ?></h1>
                    <p style="margin: 8px 0 0 0; color: rgba(255,255,255,0.5); font-weight: 600; display: flex; align-items: center; gap: 15px;">
                        <span><i class="fas fa-calendar-check" style="color: #818cf8;"></i> <?php echo date('l, F j', strtotime($selectedDate)); ?></span>
                        <span style="width: 4px; height: 4px; background: rgba(255,255,255,0.2); border-radius: 50%;"></span>
                        <span><i class="fas fa-location-dot" style="color: #818cf8;"></i> Live Monitoring</span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Dashboard Controls -->
        <div class="controls-wrapper">
            <form method="GET" id="dateForm">
                <input type="hidden" name="lecturer_id" value="<?php echo $lecturerId; ?>">
                <label for="date_trigger" class="date-btn">
                    <i class="fas fa-calendar-alt"></i>
                    <span><?php echo date('d M Y', strtotime($selectedDate)); ?></span>
                    <i class="fas fa-chevron-down" style="font-size: 0.7rem;"></i>
                </label>
                <input type="date" id="date_trigger" name="date" class="custom-input" value="<?php echo $selectedDate; ?>" onchange="this.form.submit()" style="position: absolute; opacity: 0; width: 0; height: 0;">
            </form>

            <?php if ($selectedPartitionId): ?>
            <div class="search-inner">
                <i class="fas fa-magnifying-glass"></i>
                <input type="text" id="studentSearch" class="search-pill" placeholder="Locate student by name or registration ID..." onkeyup="filterStudents()">
            </div>
            <?php endif; ?>
        </div>

        <!-- Academic Sessions -->
        <?php if (!empty($partitions)): ?>
            <div class="session-track">
                <?php foreach ($partitions as $p): 
                    $isActive = ($selectedPartitionId == $p['id']);
                ?>
                    <a href="?lecturer_id=<?php echo $lecturerId; ?>&date=<?php echo $selectedDate; ?>&partition_id=<?php echo $p['id']; ?>" class="session-card <?php echo $isActive ? 'active' : ''; ?>">
                        <span style="font-size: 0.7rem; opacity: 0.7; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">
                            <?php echo date('H:i', strtotime($p['start_time'])); ?> Session
                        </span>
                        <span><i class="fas fa-layer-group" style="margin-right: 6px;"></i> <?php echo htmlspecialchars($p['name']); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-visual">
                <i class="fas fa-calendar-day" style="font-size: 4rem; margin-bottom: 1.5rem; display: block;"></i>
                <h3 style="color: #1e293b; font-weight: 800; margin-bottom: 8px;">Zero Sessions Scheduled</h3>
                <p style="font-weight: 600; margin: 0;">There are no academic partitions listed for this date.</p>
            </div>
        <?php endif; ?>

        <!-- Attendance Ledger -->
        <?php if ($selectedPartitionId): 
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
                <div class="record-stack" id="studentGrid">
                    <?php foreach ($students as $student): 
                        $status = $attendanceMap[$student['id']] ?? 'unknown';
                        $summary = $summaryMap[$student['id']] ?? ['p' => 0, 'l' => 0, 'a' => 0];
                        $attended = (int)$summary['p'] + (int)$summary['l'];
                        $percentage = ($totalClassesHeld > 0) ? round(($attended / $totalClassesHeld) * 100) : 0;
                        
                        $statusClass = $status;
                        $statusLabel = ucfirst($status);
                        if ($status === 'unknown') $statusLabel = 'Pending';
                    ?>
                        <div class="student-item" data-name="<?php echo strtolower($student['name']); ?>" data-reg="<?php echo strtolower($student['reg_no']); ?>">
                            <div class="student-header">
                                <div class="student-bio">
                                    <div class="avatar-circle">
                                        <?php echo substr($student['reg_no'], -2); ?>
                                    </div>
                                    <div>
                                        <h4 class="student-name"><?php echo htmlspecialchars($student['name']); ?></h4>
                                        <div class="student-id"><?php echo htmlspecialchars($student['reg_no']); ?></div>
                                    </div>
                                </div>
                                <div class="status-badge <?php echo $statusClass; ?>">
                                    <i class="fas <?php 
                                        if ($status === 'present') echo 'fa-check-circle';
                                        elseif ($status === 'absent') echo 'fa-times-circle';
                                        elseif ($status === 'late') echo 'fa-clock';
                                        else echo 'fa-circle-notch fa-spin';
                                    ?>"></i>
                                    <span><?php echo $statusLabel; ?></span>
                                </div>
                            </div>
                            
                            <div class="performance-section">
                                <div style="display: flex; gap: 1.5rem;">
                                    <div class="stat-group">
                                        <span class="stat-label">Present</span>
                                        <span class="stat-value" style="color: #10b981;"><?php echo $summary['p']; ?></span>
                                    </div>
                                    <div class="stat-group">
                                        <span class="stat-label">Late</span>
                                        <span class="stat-value" style="color: #f59e0b;"><?php echo $summary['l']; ?></span>
                                    </div>
                                    <div class="stat-group">
                                        <span class="stat-label">Absent</span>
                                        <span class="stat-value" style="color: #ef4444;"><?php echo $summary['a']; ?></span>
                                    </div>
                                </div>
                                <div class="percentage-indicator <?php echo ($percentage >= 75) ? 'high' : (($percentage >= 50) ? 'medium' : 'low'); ?>">
                                    <?php echo $percentage; ?>%
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-visual" style="border-style: solid; background: white;">
                    <i class="fas fa-users-slash" style="font-size: 3.5rem; color: #f1f5f9;"></i>
                    <h3 style="color: #475569; font-weight: 800; margin-top: 1rem;">Roster Vacant</h3>
                    <p style="color: #94a3b8; font-weight: 600;">No students are registered in this academic group.</p>
                </div>
            <?php endif; ?>

        <?php endif; ?>

    </div>

    <script>
        function filterStudents() {
            const input = document.getElementById('studentSearch').value.toLowerCase();
            const cards = document.querySelectorAll('.student-item');
            
            cards.forEach(card => {
                const name = card.getAttribute('data-name');
                const reg = card.getAttribute('data-reg');
                
                if (name.includes(input) || reg.includes(input)) {
                    card.style.display = 'flex';
                    card.style.animation = 'fadeIn 0.4s easeOut';
                } else {
                    card.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>

