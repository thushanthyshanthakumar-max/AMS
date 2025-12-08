<?php
$pageTitle = 'Manage Students';
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
                $groupId = (int)$_POST['group_id'];
                
                if (empty($name) || empty($groupId)) {
                    setFlashMessage('danger', 'Name and group are required.');
                } elseif (!empty($email) && !validateEmail($email)) {
                    setFlashMessage('danger', 'Invalid email format.');
                } else {
                    $stmt = $pdo->prepare("INSERT INTO students (name, email, phone, group_id) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$name, $email, $phone, $groupId]);
                    setFlashMessage('success', 'Student added successfully.');
                    redirect('students.php');
                }
            } elseif ($_POST['action'] === 'update') {
                $id = (int)$_POST['id'];
                $name = sanitize($_POST['name']);
                $email = sanitize($_POST['email']);
                $phone = sanitize($_POST['phone']);
                $groupId = (int)$_POST['group_id'];
                
                if (empty($name) || empty($groupId)) {
                    setFlashMessage('danger', 'Name and group are required.');
                } elseif (!empty($email) && !validateEmail($email)) {
                    setFlashMessage('danger', 'Invalid email format.');
                } else {
                    $stmt = $pdo->prepare("UPDATE students SET name = ?, email = ?, phone = ?, group_id = ? WHERE id = ?");
                    $stmt->execute([$name, $email, $phone, $groupId, $id]);
                    setFlashMessage('success', 'Student updated successfully.');
                    redirect('students.php');
                }
            } elseif ($_POST['action'] === 'delete') {
                $id = (int)$_POST['id'];
                $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
                $stmt->execute([$id]);
                setFlashMessage('success', 'Student deleted successfully.');
                redirect('students.php');
            }
        } catch (PDOException $e) {
            setFlashMessage('danger', 'An error occurred: ' . $e->getMessage());
        }
    }
}

// Get all students
try {
    $stmt = $pdo->query("
        SELECT s.*, g.name as group_name
        FROM students s
        JOIN groups g ON s.group_id = g.id
        ORDER BY s.created_at DESC
    ");
    $students = $stmt->fetchAll();
    
    // Get all groups for dropdown
    $stmt = $pdo->query("SELECT id, name FROM groups ORDER BY name");
    $groups = $stmt->fetchAll();
} catch (PDOException $e) {
    setFlashMessage('danger', 'Error loading students.');
    $students = [];
    $groups = [];
}

// Check if editing
$editStudent = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
        $stmt->execute([$editId]);
        $editStudent = $stmt->fetch();
    } catch (PDOException $e) {
        setFlashMessage('danger', 'Error loading student data.');
    }
}

// Pre-select group if provided
$preselectedGroup = isset($_GET['group_id']) ? (int)$_GET['group_id'] : null;
?>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-user-graduate"></i> Manage Students</h2>
        <button onclick="openModal('createStudentModal')" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> Add Student
        </button>
    </div>
    <div class="card-body">
        <?php if (empty($students)): ?>
            <p class="text-center" style="color: var(--text-secondary); padding: 2rem;">
                <i class="fas fa-info-circle"></i> No students added yet.
            </p>
        <?php else: ?>
            <div class="form-group">
                <input type="text" id="searchInput" class="form-control" placeholder="Search students...">
            </div>
            
            <div class="table-responsive">
                <table id="studentsTable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Group</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($student['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                            <td><?php echo htmlspecialchars($student['phone']); ?></td>
                            <td><?php echo htmlspecialchars($student['group_name']); ?></td>
                            <td><?php echo formatDate($student['created_at']); ?></td>
                            <td>
                                <button onclick="editStudent(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($student['email'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($student['phone'], ENT_QUOTES); ?>', <?php echo $student['group_id']; ?>)" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this student? All attendance records will also be deleted.');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $student['id']; ?>">
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

<!-- Create Student Modal -->
<div id="createStudentModal" class="modal <?php echo $preselectedGroup ? 'active' : ''; ?>">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-plus"></i> Add New Student</h3>
            <button class="modal-close" onclick="closeModal('createStudentModal')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="create">
                
                <div class="form-group">
                    <label for="name" class="form-label">Full Name</label>
                    <input type="text" id="name" name="name" class="form-control" placeholder="Alice Johnson" required>
                </div>
                
                <div class="form-group">
                    <label for="email" class="form-label">Email (Optional)</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="alice.j@student.com">
                </div>
                
                <div class="form-group">
                    <label for="phone" class="form-label">Phone (Optional)</label>
                    <input type="tel" id="phone" name="phone" class="form-control" placeholder="555-0201">
                </div>
                
                <div class="form-group">
                    <label for="group_id" class="form-label">Group</label>
                    <select id="group_id" name="group_id" class="form-control" required>
                        <option value="">Select a group...</option>
                        <?php foreach ($groups as $group): ?>
                            <option value="<?php echo $group['id']; ?>" <?php echo ($preselectedGroup == $group['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($group['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('createStudentModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Add Student
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Student Modal -->
<div id="editStudentModal" class="modal <?php echo $editStudent ? 'active' : ''; ?>">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Edit Student</h3>
            <button class="modal-close" onclick="closeModal('editStudentModal')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="update">
                <input type="hidden" id="edit_id" name="id" value="<?php echo $editStudent ? $editStudent['id'] : ''; ?>">
                
                <div class="form-group">
                    <label for="edit_name" class="form-label">Full Name</label>
                    <input type="text" id="edit_name" name="name" class="form-control" value="<?php echo $editStudent ? htmlspecialchars($editStudent['name']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_email" class="form-label">Email (Optional)</label>
                    <input type="email" id="edit_email" name="email" class="form-control" value="<?php echo $editStudent ? htmlspecialchars($editStudent['email']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="edit_phone" class="form-label">Phone (Optional)</label>
                    <input type="tel" id="edit_phone" name="phone" class="form-control" value="<?php echo $editStudent ? htmlspecialchars($editStudent['phone']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="edit_group_id" class="form-label">Group</label>
                    <select id="edit_group_id" name="group_id" class="form-control" required>
                        <option value="">Select a group...</option>
                        <?php foreach ($groups as $group): ?>
                            <option value="<?php echo $group['id']; ?>" <?php echo ($editStudent && $editStudent['group_id'] == $group['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($group['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editStudentModal')">Cancel</button>
                <button type="submit" class="btn btn-warning">
                    <i class="fas fa-save"></i> Update Student
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function editStudent(id, name, email, phone, groupId) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_email').value = email;
    document.getElementById('edit_phone').value = phone;
    document.getElementById('edit_group_id').value = groupId;
    openModal('editStudentModal');
}

// Initialize search filter
filterTable('searchInput', 'studentsTable');
</script>

<?php require_once '../includes/footer.php'; ?>
