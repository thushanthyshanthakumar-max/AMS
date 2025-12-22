<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Fetch all teachers with their usernames
try {
    $stmt = $pdo->query("
        SELECT t.*, u.username 
        FROM teachers t 
        JOIN users u ON t.user_id = u.id 
        ORDER BY t.name ASC
    ");
    $teachers = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error fetching teachers: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teachers - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .teachers-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        .header-section {
            text-align: center;
            margin-bottom: 3rem;
            color: var(--text-primary);
        }
        .teachers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
        }
        .teacher-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .teacher-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        .teacher-info {
            padding: 1.5rem;
            text-align: center;
            border-bottom: 1px solid var(--border-color);
        }
        .teacher-avatar {
            width: 80px;
            height: 80px;
            background: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 1rem;
        }
        .teacher-name {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }
        .teacher-contact {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }
        .teacher-actions {
            padding: 1rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            background: var(--bg-secondary);
        }
        .action-btn {
            padding: 0.75rem;
            border-radius: 0.5rem;
            text-align: center;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.875rem;
            transition: all 0.2s;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-login {
            background: white;
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
        }
        .btn-login:hover {
            background: var(--primary-color);
            color: white;
        }
        .btn-view {
            background: var(--secondary-color);
            color: white;
            border: 1px solid var(--secondary-color);
        }
        .btn-view:hover {
            background: var(--secondary-hover);
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            color: var(--text-secondary);
            text-decoration: none;
            margin-bottom: 1rem;
        }
        .back-link:hover {
            color: var(--primary-color);
        }
    </style>
</head>
<body>
    <div class="teachers-container">
        <a href="login.php" class="back-link">
            <i class="fas fa-arrow-left"></i> &nbsp; Back to Login
        </a>
        
        <div class="header-section">
            <h1><i class="fas fa-chalkboard-teacher"></i> Our Teachers</h1>
            <p>Select a teacher to view attendance or login</p>
        </div>

        <div class="teachers-grid">
            <?php foreach ($teachers as $teacher): ?>
            <div class="teacher-card">
                <div class="teacher-info">
                    <div class="teacher-avatar">
                        <?php echo strtoupper(substr($teacher['name'], 0, 1)); ?>
                    </div>
                    <div class="teacher-name"><?php echo htmlspecialchars($teacher['name']); ?></div>
                    <div class="teacher-contact">
                        <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($teacher['email']); ?>
                    </div>
                </div>
                <div class="teacher-actions">
                    <a href="login.php?username=<?php echo urlencode($teacher['username']); ?>" class="action-btn btn-login">
                        <i class="fas fa-sign-in-alt"></i>
                        <span>Manager Login</span>
                    </a>
                    <a href="public_teacher_view.php?teacher_id=<?php echo $teacher['id']; ?>" class="action-btn btn-view">
                        <i class="fas fa-clipboard-list"></i>
                        <span>View Attendance</span>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
