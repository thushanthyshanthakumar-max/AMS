<?php
$pageTitle = 'Manage Lecturers';
$baseUrl = '..';
require_once '../includes/header.php';
requireAdmin();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            if ($_POST['action'] === 'create') {
                $name = sanitize($_POST['name']);
                $email = sanitize($_POST['email']);
                $phone = sanitize($_POST['phone']);
                $username = sanitize($_POST['username']);
                $password = $_POST['password'];
                
                // Validation
                if (empty($name) || empty($email) || empty($username) || empty($password)) {
                    setFlashMessage('danger', 'All fields are required.');
                } elseif (!validateEmail($email)) {
                    setFlashMessage('danger', 'Invalid email format.');
                } else {
                    // Check if username exists
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
                    $stmt->execute([$username]);
                    if ($stmt->fetchColumn() > 0) {
                        setFlashMessage('danger', 'Username already exists.');
                    } else {
                        // Start transaction
                        $pdo->beginTransaction();
                        
                        // Create user
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'lecturer')");
                        $stmt->execute([$username, $hashedPassword]);
                        $userId = $pdo->lastInsertId();
                        
                        // Create lecturer
                        $stmt = $pdo->prepare("INSERT INTO lecturers (user_id, name, email, phone) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$userId, $name, $email, $phone]);
                        
                        $pdo->commit();
                        setFlashMessage('success', 'Lecturer created successfully.');
                        redirect('lecturers.php');
                    }
                }
            } elseif ($_POST['action'] === 'update') {
                $id = (int)$_POST['id'];
                $name = sanitize($_POST['name']);
                $email = sanitize($_POST['email']);
                $phone = sanitize($_POST['phone']);
                
                if (empty($name) || empty($email)) {
                    setFlashMessage('danger', 'Name and email are required.');
                } elseif (!validateEmail($email)) {
                    setFlashMessage('danger', 'Invalid email format.');
                } else {
                    $stmt = $pdo->prepare("UPDATE lecturers SET name = ?, email = ?, phone = ? WHERE id = ?");
                    $stmt->execute([$name, $email, $phone, $id]);
                    setFlashMessage('success', 'Lecturer updated successfully.');
                    redirect('lecturers.php');
                }
            } elseif ($_POST['action'] === 'delete') {
                $id = (int)$_POST['id'];
                
                // Get user_id first
                $stmt = $pdo->prepare("SELECT user_id FROM lecturers WHERE id = ?");
                $stmt->execute([$id]);
                $lecturer = $stmt->fetch();
                
                if ($lecturer) {
                    // Delete user (will cascade delete lecturer)
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$lecturer['user_id']]);
                    setFlashMessage('success', 'Lecturer deleted successfully.');
                }
                redirect('lecturers.php');
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            setFlashMessage('danger', 'An error occurred: ' . $e->getMessage());
        }
    }
}

// Get all lecturers
try {
    $stmt = $pdo->query("
        SELECT l.*, u.username,
               (SELECT COUNT(DISTINCT ga.group_id) FROM group_assignments ga WHERE ga.lecturer_id = l.id) as group_count
        FROM lecturers l
        JOIN users u ON l.user_id = u.id
        ORDER BY l.created_at DESC
    ");
    $lecturers = $stmt->fetchAll();
} catch (PDOException $e) {
    setFlashMessage('danger', 'Error loading lecturers.');
    $lecturers = [];
}
?>

<div class="premium-banner">
    <div class="banner-content">
        <div class="banner-icon-wrapper">
            <i class="fas fa-chalkboard-teacher"></i>
        </div>
        <div class="banner-text">
            <h1>Faculty Management</h1>
            <p>Oversee lecturer accounts and group assignments</p>
        </div>
        <div class="banner-actions">
            <button onclick="openModal('createLecturerModal')" class="btn btn-primary">
                <i class="fas fa-user-plus"></i> Add New Lecturer
            </button>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if (empty($lecturers)): ?>
            <div style="text-align: center; padding: 4rem 2rem; background: #f8fafc; border-radius: 20px; border: 2px dashed #e2e8f0;">
                <i class="fas fa-user-tie" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 1.5rem;"></i>
                <h3 style="color: #64748b; font-weight: 700;">No Lecturers Registered</h3>
                <p style="color: #94a3b8; margin-bottom: 2rem;">Register your first faculty member to begin assigning groups.</p>
                <button onclick="openModal('createLecturerModal')" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Register Lecturer
                </button>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table id="lecturersTable">
                    <thead>
                        <tr>
                            <th>Academic Staff</th>
                            <th>Contact Details</th>
                            <th>Identity</th>
                            <th>Assignments</th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lecturers as $lecturer): ?>
                        <tr style="transition: all 0.2s;">
                            <td>
                                <div style="display: flex; align-items: center; gap: 1rem;">
                                    <div style="width: 42px; height: 42px; border-radius: 12px; background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%); color: white; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.1rem; box-shadow: 0 4px 6px -1px rgba(99, 102, 241, 0.2);">
                                        <?php echo strtoupper(substr($lecturer['name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div style="font-weight: 700; color: #0f172a; font-size: 1rem;"><?php echo htmlspecialchars($lecturer['name']); ?></div>
                                        <div style="font-size: 0.8rem; color: #64748b; font-weight: 500;">Joined <?php echo date('M Y', strtotime($lecturer['created_at'])); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                                    <div style="font-size: 0.85rem; color: #1e293b; font-weight: 600; display: flex; align-items: center; gap: 0.4rem;">
                                        <i class="fas fa-envelope" style="color: #94a3b8; font-size: 0.75rem;"></i>
                                        <?php echo htmlspecialchars($lecturer['email']); ?>
                                    </div>
                                    <div style="font-size: 0.8rem; color: #64748b; display: flex; align-items: center; gap: 0.4rem;">
                                        <i class="fas fa-phone" style="color: #94a3b8; font-size: 0.75rem;"></i>
                                        <?php echo htmlspecialchars($lecturer['phone'] ?: 'N/A'); ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <code style="padding: 0.3rem 0.6rem; background: #f1f5f9; border-radius: 8px; color: #475569; font-weight: 600; font-size: 0.85rem;">
                                    @<?php echo htmlspecialchars($lecturer['username']); ?>
                                </code>
                            </td>
                            <td>
                                <span class="badge" style="background: #e0f2fe; color: #0369a1; font-weight: 800; letter-spacing: 0;">
                                    <i class="fas fa-layer-group" style="margin-right: 0.3rem; font-size: 0.75rem;"></i>
                                    <?php echo $lecturer['group_count']; ?> Groups
                                </span>
                            </td>
                            <td>
                                <span class="badge" style="background: #ecfdf5; color: #059669; font-weight: 800; font-size: 0.7rem; letter-spacing: 0.05em;">ACTIVE</span>
                            </td>
                            <td class="text-right">
                                <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                                    <button onclick="editLecturer(<?php echo $lecturer['id']; ?>, '<?php echo htmlspecialchars($lecturer['name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($lecturer['email'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($lecturer['phone'], ENT_QUOTES); ?>')" class="btn btn-sm" style="background: #f1f5f9; color: #475569; border: none;" title="Edit Profile">
                                        <i class="fas fa-user-edit"></i>
                                    </button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Archive this lecturer profile? This action cannot be undone.');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $lecturer['id']; ?>">
                                        <button type="submit" class="btn btn-sm" style="background: #fee2e2; color: #991b1b; border: none;" title="Remove Access">
                                            <i class="fas fa-user-slash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Create Lecturer Modal -->
<div id="createLecturerModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-plus"></i> Add New Lecturer</h3>
            <button class="modal-close" onclick="closeModal('createLecturerModal')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="create">
                
                <div class="form-group">
                    <label for="name" class="form-label">Full Name</label>
                    <input type="text" id="name" name="name" class="form-control" placeholder="John Smith" required>
                </div>
                
                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="john.smith@school.com" required>
                </div>
                
                <div class="form-group">
                    <label for="phone" class="form-label">Phone</label>
                    <input type="tel" id="phone" name="phone" class="form-control" placeholder="555-0101">
                </div>
                
                <div class="form-group">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" id="username" name="username" class="form-control" placeholder="jsmith" required>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="text" id="password" name="password" class="form-control" required>
                    <button type="button" class="btn btn-sm btn-secondary mt-1" onclick="document.getElementById('password').value = generateRandomPassword()">
                        <i class="fas fa-random"></i> Generate Password
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('createLecturerModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Add Lecturer
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Lecturer Modal -->
<div id="editLecturerModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Edit Lecturer</h3>
            <button class="modal-close" onclick="closeModal('editLecturerModal')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="update">
                <input type="hidden" id="edit_id" name="id">
                
                <div class="form-group">
                    <label for="edit_name" class="form-label">Full Name</label>
                    <input type="text" id="edit_name" name="name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_email" class="form-label">Email</label>
                    <input type="email" id="edit_email" name="email" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_phone" class="form-label">Phone</label>
                    <input type="tel" id="edit_phone" name="phone" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editLecturerModal')">Cancel</button>
                <button type="submit" class="btn btn-warning">
                    <i class="fas fa-save"></i> Update Lecturer
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function editLecturer(id, name, email, phone) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_email').value = email;
    document.getElementById('edit_phone').value = phone;
    openModal('editLecturerModal');
}

function generateRandomPassword() {
    const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    let password = '';
    for (let i = 0; i < 10; i++) {
        password += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return password;
}
</script>

<?php require_once '../includes/footer.php'; ?>
