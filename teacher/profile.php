<?php
$pageTitle = 'My Profile';
$baseUrl = '..';
require_once '../includes/header.php';
requireTeacher();

$teacherId = getTeacherId($pdo, $_SESSION['user_id']);
$userId = $_SESSION['user_id'];

// Handle password update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_password') {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // Validation
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        setFlashMessage('danger', 'All fields are required.');
    } elseif ($newPassword !== $confirmPassword) {
        setFlashMessage('danger', 'New passwords do not match.');
    } elseif (strlen($newPassword) < 6) {
        setFlashMessage('danger', 'New password must be at least 6 characters long.');
    } else {
        // Verify current password
        try {
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($currentPassword, $user['password'])) {
                // Update password
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $updateStmt->execute([$hashedPassword, $userId]);
                
                setFlashMessage('success', 'Password updated successfully.');
            } else {
                setFlashMessage('danger', 'Incorrect current password.');
            }
        } catch (PDOException $e) {
            setFlashMessage('danger', 'An error occurred while updating password.');
        }
    }
}

// Get teacher details
try {
    $stmt = $pdo->prepare("
        SELECT t.*, u.username 
        FROM teachers t 
        JOIN users u ON t.user_id = u.id 
        WHERE t.id = ?
    ");
    $stmt->execute([$teacherId]);
    $teacher = $stmt->fetch();
} catch (PDOException $e) {
    $teacher = null;
}
?>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-user-circle"></i> My Profile</h2>
    </div>
    <div class="card-body">
        <div style="display: flex; gap: 2rem; flex-wrap: wrap;">
            <!-- Profile Info -->
            <div style="flex: 1; min-width: 300px;">
                <h3 style="margin-bottom: 1.5rem; color: var(--text-secondary); border-bottom: 2px solid var(--border-color); padding-bottom: 0.5rem;">
                    Account Details
                </h3>
                
                <div style="margin-bottom: 1.5rem;">
                    <label style="font-weight: 600; color: var(--text-secondary);">Full Name</label>
                    <div style="font-size: 1.25rem; color: var(--text-primary);">
                        <?php echo htmlspecialchars($teacher['name']); ?>
                    </div>
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <label style="font-weight: 600; color: var(--text-secondary);">Username</label>
                    <div style="font-size: 1.25rem; color: var(--text-primary); display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-user-tag text-secondary"></i>
                        <?php echo htmlspecialchars($teacher['username']); ?>
                    </div>
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <label style="font-weight: 600; color: var(--text-secondary);">Email</label>
                    <div style="font-size: 1.25rem; color: var(--text-primary); display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-envelope text-secondary"></i>
                        <?php echo htmlspecialchars($teacher['email']); ?>
                    </div>
                </div>

                <div style="background: var(--light-color); padding: 1.5rem; border-radius: var(--radius-md); margin-top: 2rem;">
                    <h4 style="margin-bottom: 1rem;"><i class="fas fa-shield-alt"></i> Security Tips</h4>
                    <ul style="padding-left: 1.25rem; color: var(--text-secondary); line-height: 1.6;">
                        <li>Use a strong password with minimal 6 characters.</li>
                        <li>Include numbers and special symbols.</li>
                        <li>Do not share your password with anyone.</li>
                    </ul>
                </div>
            </div>

            <!-- Change Password Form -->
            <div style="flex: 1; min-width: 300px; background: #fff; border: 1px solid var(--border-color); padding: 2rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm);">
                <h3 style="margin-bottom: 1.5rem; color: var(--primary-color); display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-key"></i> Change Password
                </h3>
                
                <form method="POST">
                    <input type="hidden" name="action" value="update_password">
                    
                    <div class="form-group">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" id="current_password" name="current_password" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" id="new_password" name="new_password" class="form-control" required minlength="6">
                        <small style="color: var(--text-secondary);">Minimum 6 characters</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">
                        Update Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
