<?php
$pageTitle = 'Import Student Data';
$baseUrl = '..';
require_once '../includes/header.php';
requireAdmin();

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import'])) {
    try {
        // Read the SQL file
        $sqlFile = __DIR__ . '/../students_data.sql';
        
        if (!file_exists($sqlFile)) {
            throw new Exception('students_data.sql file not found!');
        }
        
        $sql = file_get_contents($sqlFile);
        
        // Execute the SQL
        $pdo->exec($sql);
        
        $message = 'Student data imported successfully!';
        $messageType = 'success';
        
    } catch (Exception $e) {
        $message = 'Error importing data: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Get current student count
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM students");
    $studentCount = $stmt->fetch()['count'];
} catch (PDOException $e) {
    $studentCount = 0;
}
?>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-file-import"></i> Import Student Data</h2>
    </div>
    <div class="card-body">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            <div>
                <strong>Current Status:</strong> <?php echo $studentCount; ?> students in database
            </div>
        </div>
        
        <div style="background: var(--light-color); padding: 1.5rem; border-radius: var(--radius-md); margin-bottom: 1.5rem;">
            <h3 style="margin-bottom: 1rem;"><i class="fas fa-database"></i> Import Information</h3>
            <p style="margin-bottom: 0.5rem;">This will import <strong>459 students</strong> from the University of Jaffna Pre-Semester Programme 2023/2024:</p>
            <ul style="margin-left: 1.5rem; margin-top: 1rem;">
                <li>GROUP-01 (1P Physics) - 46 students</li>
                <li>GROUP-02 (2P Physics) - 46 students</li>
                <li>GROUP-03 (3P Physics) - 46 students</li>
                <li>GROUP-04 (4P Physics) - 46 students</li>
                <li>GROUP-05 (SWC) - 46 students</li>
                <li>GROUP-06 (1B Botany) - 46 students</li>
                <li>GROUP-07 (2B Botany) - 46 students</li>
                <li>GROUP-08 (4M Mathematics) - 46 students</li>
                <li>GROUP-09 (FSL Fisheries) - 46 students</li>
                <li>GROUP-10 (CSH Computer Science) - 45 students</li>
            </ul>
        </div>
        
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Warning:</strong> This will add all students to the database. Make sure the groups have been created first!
        </div>
        
        <form method="POST" onsubmit="return confirm('Are you sure you want to import all student data? This will add 459 students to the database.');">
            <button type="submit" name="import" class="btn btn-primary btn-lg">
                <i class="fas fa-file-import"></i> Import All Students
            </button>
            <a href="students.php" class="btn btn-secondary btn-lg">
                <i class="fas fa-arrow-left"></i> Back to Students
            </a>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
