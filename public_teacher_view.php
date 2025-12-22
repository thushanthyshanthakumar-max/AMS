<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$teacher_id = isset($_GET['teacher_id']) ? (int)$_GET['teacher_id'] : 0;

if (!$teacher_id) {
    header('Location: public_teachers.php');
    exit;
}

// Fetch teacher details
try {
    $stmt = $pdo->prepare("SELECT * FROM teachers WHERE id = ?");
    $stmt->execute([$teacher_id]);
    $teacher = $stmt->fetch();

    if (!$teacher) {
        die("Teacher not found");
    }

    // Fetch students and attendance stats for groups assigned to this teacher
    $query = "
        SELECT 
            s.id, s.name, g.name as group_name,
            COUNT(a.id) as total_classes,
            SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count,
            SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_count,
            SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_count
        FROM students s
        JOIN groups g ON s.group_id = g.id
        JOIN group_assignments ga ON g.id = ga.group_id
        LEFT JOIN attendance a ON s.id = a.student_id
        WHERE ga.teacher_id = ?
        GROUP BY s.id, s.name, g.name
        ORDER BY g.name, s.name
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$teacher_id]);
    $students = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Error fetching data: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Attendance - <?php echo htmlspecialchars($teacher['name']); ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .view-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        .header-profile {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 2rem;
        }
        .profile-avatar {
            width: 100px;
            height: 100px;
            background: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
        }
        .profile-info h1 {
            margin: 0 0 0.5rem 0;
            color: var(--text-primary);
        }
        .profile-info p {
            color: var(--text-secondary);
            margin: 0;
        }
        
        .attendance-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        .table th, .table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        .table th {
            background: var(--bg-secondary);
            font-weight: 600;
        }
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 1rem;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .status-high { background: #dcfce7; color: #166534; }
        .status-medium { background: #fef9c3; color: #854d0e; }
        .status-low { background: #fee2e2; color: #991b1b; }
        
        .progress-bar {
            width: 100px;
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background: var(--primary-color);
        }
    </style>
</head>
<body>
    <div class="view-container">
        <a href="public_teachers.php" class="back-link" style="margin-bottom: 1rem; display: inline-block; color: var(--text-primary); text-decoration: none;">
            <i class="fas fa-arrow-left"></i> &nbsp; Back to Teachers
        </a>

        <div class="header-profile">
            <div class="profile-avatar">
                <?php echo strtoupper(substr($teacher['name'], 0, 1)); ?>
            </div>
            <div class="profile-info">
                <h1><?php echo htmlspecialchars($teacher['name']); ?></h1>
                <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($teacher['email']); ?></p>
                <p><i class="fas fa-users"></i> Students Overview</p>
            </div>
        </div>

        <div class="attendance-card">
            <?php if (empty($students)): ?>
                <div style="padding: 2rem; text-align: center;">No students found for this teacher.</div>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Group</th>
                                <th>Present</th>
                                <th>Absent</th>
                                <th>Late</th>
                                <th>Attendance %</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): 
                                $total = $student['total_classes'];
                                $present = $student['present_count']; // Treat present as full
                                $late = $student['late_count']; // Treat late as present for calculation? Or partial? usually late is present.
                                // Let's simplify: Percentage = (Present + Late) / Total * 100
                                $effective_present = $present + $late;
                                $percentage = $total > 0 ? round(($effective_present / $total) * 100, 1) : 0;
                                
                                $status_class = 'status-high';
                                if ($percentage < 75) $status_class = 'status-medium';
                                if ($percentage < 60) $status_class = 'status-low';
                            ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 500;"><?php echo htmlspecialchars($student['name']); ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($student['group_name']); ?></td>
                                <td style="color: var(--success-color);"><?php echo $student['present_count']; ?></td>
                                <td style="color: var(--danger-color);"><?php echo $student['absent_count']; ?></td>
                                <td style="color: var(--warning-color);"><?php echo $student['late_count']; ?></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <span><?php echo $percentage; ?>%</span>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo $percentage; ?>%; background-color: <?php echo $percentage < 60 ? '#ef4444' : ($percentage < 75 ? '#eab308' : '#22c55e'); ?>"></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $status_class; ?>">
                                        <?php echo $percentage >= 75 ? 'Good' : ($percentage >= 60 ? 'Warning' : 'Critical'); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
