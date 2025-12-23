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

<div class="premium-banner">
    <div class="banner-content">
        <div class="banner-icon-wrapper">
            <i class="fas fa-file-import"></i>
        </div>
        <div class="banner-text">
            <h1>Bulk Student Import</h1>
            <p>Populate your database with student records from university manifests</p>
        </div>
        <div class="banner-actions">
            <a href="students.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Database
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>" style="margin-bottom: 2rem; border-radius: 16px;">
                <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
            <div>
                <div style="background: #f8fafc; padding: 2.5rem; border-radius: 24px; border: 1px solid #e2e8f0;">
                    <h3 style="margin-bottom: 1.5rem; font-weight: 800; color: #1e293b; display: flex; align-items: center; gap: 0.75rem;">
                        <i class="fas fa-database" style="color: #6366f1;"></i> Manifest Specifications
                    </h3>
                    <p style="margin-bottom: 1.5rem; color: #64748b; font-weight: 500;">This operation will import <strong>459 students</strong> from the University of Jaffna Pre-Semester Programme (2023/2024). The students will be allocated to their respective laboratory batches:</p>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <?php 
                        $import_groups = [
                            'GROUP-01 (1P Physics)' => '46', 'GROUP-02 (2P Physics)' => '46',
                            'GROUP-03 (3P Physics)' => '46', 'GROUP-04 (4P Physics)' => '46',
                            'GROUP-05 (SWC)' => '46', 'GROUP-06 (1B Botany)' => '46',
                            'GROUP-07 (2B Botany)' => '46', 'GROUP-08 (4M Mathematics)' => '46',
                            'GROUP-09 (FSL Fisheries)' => '46', 'GROUP-10 (CSH CS)' => '45'
                        ];
                        foreach ($import_groups as $group_name => $count):
                        ?>
                        <div style="background: white; padding: 0.75rem 1rem; border-radius: 12px; border: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 0.85rem; font-weight: 600; color: #475569;"><?php echo $group_name; ?></span>
                            <span class="badge" style="background: #eef2ff; color: #4f46e5; font-weight: 800;"><?php echo $count; ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="alert alert-warning" style="margin-top: 2rem; border-radius: 16px; background: #fffbeb; border: 1px solid #fef3c7; color: #92400e;">
                    <i class="fas fa-triangle-exclamation" style="font-size: 1.25rem;"></i>
                    <div style="font-weight: 600;">System Pre-requisite</div>
                    <p style="font-size: 0.9rem; opacity: 0.9;">Ensure that the corresponding Group IDs exist in your database before proceeding with the bulk import. This operation cannot be easily undone.</p>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                <div class="stat-card" style="--bg-accent: #6366f1; text-align: left; align-items: flex-start;">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div class="stat-value"><?php echo $studentCount; ?></div>
                    <div class="stat-label">Current Students</div>
                </div>

                <div style="background: white; padding: 2rem; border-radius: 24px; border: 1px solid #e2e8f0; flex-grow: 1; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center;">
                    <div style="width: 80px; height: 80px; background: #f1f5f9; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 1.5rem;">
                        <i class="fas fa-rocket" style="font-size: 2rem; color: #6366f1;"></i>
                    </div>
                    <h4 style="margin-bottom: 0.5rem; font-weight: 800;">Ready to Import?</h4>
                    <p style="color: #64748b; font-size: 0.9rem; margin-bottom: 2rem;">Start the bulk migration process now.</p>
                    
                    <form method="POST" onsubmit="return confirm('Initiate bulk import of 459 student records?');" style="width: 100%;">
                        <button type="submit" name="import" class="btn btn-primary btn-block" style="padding: 1.25rem; font-size: 1.1rem; border-radius: 18px; box-shadow: 0 15px 30px -10px rgba(99, 102, 241, 0.4);">
                            <i class="fas fa-bolt"></i> Execute Import
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
