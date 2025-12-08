<?php
$pageTitle = 'Manage Teachers';
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
                        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'teacher')");
                        $stmt->execute([$username, $hashedPassword]);
                        $userId = $pdo->lastInsertId();
                        
                        // Create teacher
                        $stmt = $pdo->prepare("INSERT INTO teachers (user_id, name, email, phone) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$userId, $name, $email, $phone]);
                        
                        $pdo->commit();
                        setFlashMessage('success', 'Teacher created successfully.');
                        redirect('teachers.php');
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
                    $stmt = $pdo->prepare("UPDATE teachers SET name = ?, email = ?, phone = ? WHERE id = ?");
                    $stmt->execute([$name, $email, $phone, $id]);
                    setFlashMessage('success', 'Teacher updated successfully.');
                    redirect('teachers.php');
                }
            } elseif ($_POST['action'] === 'delete') {
                $id = (int)$_POST['id'];
                
                // Get user_id first
                $stmt = $pdo->prepare("SELECT user_id FROM teachers WHERE id = ?");
                $stmt->execute([$id]);
                $teacher = $stmt->fetch();
                
                if ($teacher) {
                    // Delete user (will cascade delete teacher)
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$teacher['user_id']]);
                    setFlashMessage('success', 'Teacher deleted successfully.');
                }
                redirect('teachers.php');
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            setFlashMessage('danger', 'An error occurred: ' . $e->getMessage());
        }
    }
}

// Get all teachers
try {
    $stmt = $pdo->query("
        SELECT t.*, u.username,
               (SELECT COUNT(DISTINCT ga.group_id) FROM group_assignments ga WHERE ga.teacher_id = t.id) as group_count
        FROM teachers t
        JOIN users u ON t.user_id = u.id
        ORDER BY t.created_at DESC
    ");
    $teachers = $stmt->fetchAll();
} catch (PDOException $e) {
    setFlashMessage('danger', 'Error loading teachers.');
    $teachers = [];
}
?>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-chalkboard-teacher"></i> Manage Teachers</h2>
        <button onclick="openModal('createTeacherModal')" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> Add Teacher
        </button>
    </div>
    <div class="card-body">
        <?php if (empty($teachers)): ?>
            <p class="text-center" style="color: var(--text-secondary); padding: 2rem;">
                <i class="fas fa-info-circle"></i> No teachers added yet.
            </p>
        <?php else: ?>
            <div class="table-responsive">
                <table id="teachersTable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Username</th>
                            <th>Groups Assigned</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($teachers as $teacher): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($teacher['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($teacher['email']); ?></td>
                            <td><?php echo htmlspecialchars($teacher['phone']); ?></td>
                            <td><?php echo htmlspecialchars($teacher['username']); ?></td>
                            <td><?php echo $teacher['group_count']; ?> groups</td>
                            <td><?php echo formatDate($teacher['created_at']); ?></td>
                            <td>
                                <button onclick="editTeacher(<?php echo $teacher['id']; ?>, '<?php echo htmlspecialchars($teacher['name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($teacher['email'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($teacher['phone'], ENT_QUOTES); ?>')" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this teacher? This will also remove their user account.');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $teacher['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Create Teacher Modal -->
<div id="createTeacherModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-plus"></i> Add New Teacher</h3>
            <button class="modal-close" onclick="closeModal('createTeacherModal')">&times;</button>
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
                <button type="button" class="btn btn-secondary" onclick="closeModal('createTeacherModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Add Teacher
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Teacher Modal -->
<div id="editTeacherModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Edit Teacher</h3>
            <button class="modal-close" onclick="closeModal('editTeacherModal')">&times;</button>
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
                <button type="button" class="btn btn-secondary" onclick="closeModal('editTeacherModal')">Cancel</button>
                <button type="submit" class="btn btn-warning">
                    <i class="fas fa-save"></i> Update Teacher
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function editTeacher(id, name, email, phone) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_email').value = email;
    document.getElementById('edit_phone').value = phone;
    openModal('editTeacherModal');
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
