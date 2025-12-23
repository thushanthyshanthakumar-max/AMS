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

// If no partition selected but we have partitions, maybe select the first one or let user choose?
// Let's show the list of partitions, and if one is selected, show students.

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance View - <?php echo htmlspecialchars($lecturer['name']); ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .page-header {
            background: white;
            padding: 1rem 2rem;
            box-shadow: var(--shadow-sm);
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .back-btn {
            text-decoration: none;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
        }

        .back-btn:hover {
            color: var(--primary-color);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        .lecturer-banner {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 2rem;
            border-radius: var(--radius-lg);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
            box-shadow: var(--shadow-lg);
        }

        .banner-icon {
            font-size: 3rem;
            background: rgba(255, 255, 255, 0.2);
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .filters {
            background: white;
            padding: 1.5rem;
            border-radius: var(--radius-md);
            margin-bottom: 2rem;
            box-shadow: var(--shadow-sm);
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .partition-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .partition-card {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 1.5rem;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .partition-card:hover {
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .partition-card.active {
            border-color: var(--primary-color);
            background: var(--primary-light-bg, #f0f9ff);
            box-shadow: 0 0 0 2px var(--primary-color);
        }

        .time-badge {
            display: inline-block;
            background: var(--light-color);
            padding: 0.25rem 0.5rem;
            border-radius: var(--radius-sm);
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
        }

        .student-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: var(--radius-md);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        .student-table th, .student-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .student-table th {
            background: #f8fafc;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-present { background: #dcfce7; color: #166534; }
        .status-absent { background: #fee2e2; color: #991b1b; }
        .status-late { background: #fef3c7; color: #92400e; }
        .status-unknown { background: #f1f5f9; color: #64748b; }
    </style>
</head>
<body>

    <header class="page-header">
        <div class="logo">
            <i class="fas fa-graduation-cap" style="color: var(--primary-color); margin-right: 0.5rem;"></i>
            <span style="font-weight: 700; font-size: 1.25rem;"><?php echo APP_NAME; ?></span>
        </div>
        <a href="login.php" class="back-btn">
            <i class="fas fa-sign-in-alt"></i> Back to Login
        </a>
    </header>

    <div class="container">
        
        <div class="lecturer-banner">
            <div class="banner-icon">
                <i class="fas fa-chalkboard-teacher"></i>
            </div>
            <div>
                <h1 style="margin: 0; font-size: 1.75rem;">Public Attendance View</h1>
                <p style="margin: 0.5rem 0 0; opacity: 0.9;">Lecturer: <strong><?php echo htmlspecialchars($lecturer['name']); ?></strong></p>
            </div>
        </div>

        <form method="GET" class="filters">
            <input type="hidden" name="lecturer_id" value="<?php echo $lecturerId; ?>">
            <div class="form-group" style="margin: 0;">
                <label for="date" style="display: block; font-size: 0.875rem; margin-bottom: 0.25rem; font-weight: 600;">Select Date</label>
                <input type="date" id="date" name="date" class="form-control" value="<?php echo $selectedDate; ?>" onchange="this.form.submit()">
            </div>
            <div style="flex: 1;"></div>
            <div style="font-size: 0.9rem; color: var(--text-secondary);">
                Showing schedule for: <strong><?php echo $dayOfWeek; ?></strong>
            </div>
        </form>

        <?php if (empty($partitions)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> No classes scheduled for this lecturer on <?php echo $selectedDate; ?>.
            </div>
        <?php else: ?>
            <h3 style="margin-bottom: 1rem; color: var(--text-primary);">Classes</h3>
            <div class="partition-grid">
                <?php foreach ($partitions as $p): 
                    $isActive = ($selectedPartitionId == $p['id']);
                ?>
                    <a href="?lecturer_id=<?php echo $lecturerId; ?>&date=<?php echo $selectedDate; ?>&partition_id=<?php echo $p['id']; ?>" class="partition-card <?php echo $isActive ? 'active' : ''; ?>">
                        <div class="time-badge">
                            <i class="far fa-clock"></i> 
                            <?php echo date('g:i A', strtotime($p['start_time'])); ?> - <?php echo date('g:i A', strtotime($p['end_time'])); ?>
                        </div>
                        <h3 style="margin: 0 0 0.5rem; font-size: 1.1rem;"><?php echo htmlspecialchars($p['name']); ?></h3>
                        <div style="color: var(--text-secondary); font-size: 0.9rem;">
                            <i class="fas fa-users"></i> <?php echo htmlspecialchars($p['group_name']); ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($selectedPartitionId): 
            // Fetch students and attendance
            $students = [];
            try {
                // Get groupId from partition
                $stmt = $pdo->prepare("SELECT group_id FROM partitions WHERE id = ?");
                $stmt->execute([$selectedPartitionId]);
                $partitionInfo = $stmt->fetch();
                
                if ($partitionInfo) {
                    $groupId = $partitionInfo['group_id'];
                    
                    // Fetch students
                    $stmt = $pdo->prepare("SELECT * FROM students WHERE group_id = ? ORDER BY reg_no");
                    $stmt->execute([$groupId]);
                    $students = $stmt->fetchAll();

                    // Fetch attendance
                    $stmt = $pdo->prepare("SELECT student_id, status FROM attendance WHERE partition_id = ? AND date = ?");
                    $stmt->execute([$selectedPartitionId, $selectedDate]);
                    $attendanceMap = [];
                    while ($row = $stmt->fetch()) {
                        $attendanceMap[$row['student_id']] = $row['status'];
                    }
                }
            } catch (PDOException $e) {
                echo '<div class="alert alert-danger">Error loading student data.</div>';
            }
        ?>
            
            <?php if (!empty($students)): ?>
                <h3 style="margin-bottom: 1rem; color: var(--text-primary);">Student Attendance List</h3>
                <div style="overflow-x: auto;">
                    <table class="student-table">
                        <thead>
                            <tr>
                                <th>Reg No</th>
                                <th>Name</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): 
                                $status = $attendanceMap[$student['id']] ?? 'unknown';
                                $statusClass = 'status-' . $status;
                                $statusLabel = ucfirst($status);
                                if ($status === 'unknown') $statusLabel = 'Not Marked';
                            ?>
                                <tr>
                                    <td style="font-family: monospace; font-weight: 600;"><?php echo htmlspecialchars($student['reg_no']); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($student['name']); ?>
                                        <div style="font-size: 0.8rem; color: var(--text-secondary);">
                                            <?php echo htmlspecialchars($student['title']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $statusClass; ?>">
                                            <?php echo $statusLabel; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">No students found for this class.</div>
            <?php endif; ?>

        <?php endif; ?>

    </div>

</body>
</html>
