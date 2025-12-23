<?php
$pageTitle = 'My Profile';
$baseUrl = '..';
require_once '../includes/header.php';
requireLecturer();

$lecturerId = getLecturerId($pdo, $_SESSION['user_id']);
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

// Get lecturer details
try {
    $stmt = $pdo->prepare("
        SELECT l.*, u.username 
        FROM lecturers l 
        JOIN users u ON l.user_id = u.id 
        WHERE l.id = ?
    ");
    $stmt->execute([$lecturerId]);
    $lecturer = $stmt->fetch();
} catch (PDOException $e) {
    $lecturer = null;
}
?>

<div class="premium-banner">
    <div class="banner-content">
        <div class="banner-icon-wrapper">
            <i class="fas fa-user-shield"></i>
        </div>
        <div class="banner-text">
            <h1>Account Security</h1>
            <p>Manage your academic profile and authentication credentials</p>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
    <!-- Profile Information -->
    <div class="card">
        <div class="card-body" style="padding: 2.5rem;">
            <div style="display: flex; align-items: center; gap: 1.5rem; margin-bottom: 2.5rem;">
                <div style="width: 80px; height: 80px; background: #f1f5f9; border-radius: 24px; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: #6366f1; font-weight: 800; border: 4px solid white; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);">
                    <?php echo strtoupper(substr($lecturer['name'], 0, 1)); ?>
                </div>
                <div>
                    <h3 style="margin: 0; font-weight: 800; color: #1e293b; font-size: 1.5rem;"><?php echo htmlspecialchars($lecturer['name']); ?></h3>
                    <p style="margin: 2px 0 0 0; color: #94a3b8; font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.1em;">Academic Faculty</p>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                <div style="background: #f8fafc; padding: 1.25rem; border-radius: 16px; border: 1px solid #f1f5f9;">
                    <div style="font-size: 0.75rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; margin-bottom: 4px;">Username Handle</div>
                    <div style="font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-at" style="color: #6366f1;"></i>
                        <?php echo htmlspecialchars($lecturer['username']); ?>
                    </div>
                </div>

                <div style="background: #f8fafc; padding: 1.25rem; border-radius: 16px; border: 1px solid #f1f5f9;">
                    <div style="font-size: 0.75rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; margin-bottom: 4px;">Primary Email Address</div>
                    <div style="font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-envelope-open" style="color: #6366f1;"></i>
                        <?php echo htmlspecialchars($lecturer['email']); ?>
                    </div>
                </div>
            </div>

            <div style="margin-top: 2.5rem; background: #fdf2f8; border-radius: 20px; padding: 1.5rem; border: 1px dashed #f9a8d4;">
                <h4 style="margin: 0 0 0.5rem 0; color: #be185d; font-weight: 800; font-size: 1rem;">
                    <i class="fas fa-lightbulb" style="margin-right: 8px;"></i> Security Insight
                </h4>
                <p style="margin: 0; color: #db2777; font-size: 0.85rem; font-weight: 500; line-height: 1.6;">
                    Enable two-factor authentication in the next system update to further protect your grading and attendance data.
                </p>
            </div>
        </div>
    </div>

    <!-- Password Update -->
    <div class="card">
        <div class="card-body" style="padding: 2.5rem;">
            <h3 style="margin-bottom: 2rem; color: #1e293b; font-weight: 800; display: flex; align-items: center; gap: 12px;">
                <i class="fas fa-key" style="color: #6366f1;"></i> Update Passport
            </h3>
            
            <form method="POST">
                <input type="hidden" name="action" value="update_password">
                
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label for="current_password" class="form-label" style="font-weight: 700; color: #475569;">Current Authentication Key</label>
                    <input type="password" id="current_password" name="current_password" class="form-control" required style="border-radius: 12px; height: 50px;">
                </div>
                
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label for="new_password" class="form-label" style="font-weight: 700; color: #475569;">New Secret Key</label>
                    <input type="password" id="new_password" name="new_password" class="form-control" required minlength="6" style="border-radius: 12px; height: 50px;">
                    <p style="margin: 6px 0 0 0; font-size: 0.75rem; color: #94a3b8; font-weight: 600;">Minimum strength: 6 characters</p>
                </div>
                
                <div class="form-group" style="margin-bottom: 2rem;">
                    <label for="confirm_password" class="form-label" style="font-weight: 700; color: #475569;">Verify New Key</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required style="border-radius: 12px; height: 50px;">
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%; height: 55px; border-radius: 15px; font-weight: 800; font-size: 1rem; box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.3);">
                    Commit Changes <i class="fas fa-shield-check" style="margin-left: 8px;"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
