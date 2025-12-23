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
                $regNo = sanitize($_POST['reg_no']);
                $title = sanitize($_POST['title']);
                $name = sanitize($_POST['name']);
                $groupId = (int)$_POST['group_id'];
                
                if (empty($regNo) || empty($title) || empty($name) || empty($groupId)) {
                    setFlashMessage('danger', 'Registration number, title, name and group are required.');
                } else {
                    $stmt = $pdo->prepare("INSERT INTO students (reg_no, title, name, group_id) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$regNo, $title, $name, $groupId]);
                    setFlashMessage('success', 'Student added successfully.');
                    redirect('students.php');
                }
            } elseif ($_POST['action'] === 'update') {
                $id = (int)$_POST['id'];
                $regNo = sanitize($_POST['reg_no']);
                $title = sanitize($_POST['title']);
                $name = sanitize($_POST['name']);
                $groupId = (int)$_POST['group_id'];
                
                if (empty($regNo) || empty($title) || empty($name) || empty($groupId)) {
                    setFlashMessage('danger', 'Registration number, title, name and group are required.');
                } else {
                    $stmt = $pdo->prepare("UPDATE students SET reg_no = ?, title = ?, name = ?, group_id = ? WHERE id = ?");
                    $stmt->execute([$regNo, $title, $name, $groupId, $id]);
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

<div class="premium-banner">
    <div class="banner-content">
        <div class="banner-icon-wrapper">
            <i class="fas fa-user-graduate"></i>
        </div>
        <div class="banner-text">
            <h1>Student Database</h1>
            <p>Manage student records and class assignments</p>
        </div>
        <div class="banner-actions">
            <button type="button" onclick="openModal('createStudentModal')" class="btn btn-primary">
                <i class="fas fa-plus-circle"></i> Enroll New Student
            </button>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; gap: 1rem;">
            <div style="position: relative; flex: 1; max-width: 400px;">
                <i class="fas fa-search" style="position: absolute; left: 1.25rem; top: 50%; transform: translateY(-50%); color: #94a3b8;"></i>
                <input type="text" id="searchInput" class="form-control" placeholder="Search by name, ID or group..." style="padding-left: 3rem; border-radius: 15px; border: 1px solid #e2e8f0; background: #f8fafc;">
            </div>
            <div style="display: flex; gap: 0.5rem;">
                <a href="import_students.php" class="btn btn-secondary">
                    <i class="fas fa-file-import"></i> Bulk Import
                </a>
            </div>
        </div>

        <?php if (empty($students)): ?>
            <div style="text-align: center; padding: 4rem 2rem; background: #f8fafc; border-radius: 20px; border: 2px dashed #e2e8f0;">
                <i class="fas fa-user-slash" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 1.5rem;"></i>
                <h3 style="color: #64748b; font-weight: 700;">No Students Found</h3>
                <p style="color: #94a3b8; margin-bottom: 2rem;">Start by adding your first student to the system.</p>
                <button type="button" onclick="openModal('createStudentModal')" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add First Student
                </button>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table id="studentsTable">
                    <thead>
                        <tr>
                            <th>Registration</th>
                            <th>Identity</th>
                            <th>Assigned Group</th>
                            <th>Entry Date</th>
                            <th class="text-right">Manage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                        <tr style="transition: all 0.2s;">
                            <td style="font-family: 'JetBrains Mono', monospace; font-size: 0.9rem; font-weight: 700; color: var(--primary-color);">
                                <?php echo htmlspecialchars($student['reg_no']); ?>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <div style="width: 35px; height: 35px; border-radius: 10px; background: <?php echo $student['title'] === 'MR' ? '#e0f2fe' : '#fef2f2'; ?>; color: <?php echo $student['title'] === 'MR' ? '#0369a1' : '#dc2626'; ?>; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.7rem;">
                                        <?php echo $student['title']; ?>
                                    </div>
                                    <div style="font-weight: 600; color: #1e293b;"><?php echo htmlspecialchars($student['name']); ?></div>
                                </div>
                            </td>
                            <td>
                                <span class="badge" style="background: #f1f5f9; color: #475569; letter-spacing: 0;">
                                    <i class="fas fa-users-line" style="margin-right: 0.3rem; font-size: 0.75rem;"></i>
                                    <?php echo htmlspecialchars($student['group_name']); ?>
                                </span>
                            </td>
                            <td style="color: #64748b; font-size: 0.9rem;">
                                <?php echo date('M j, Y', strtotime($student['created_at'])); ?>
                            </td>
                            <td class="text-right">
                                <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                                    <button onclick="editStudent(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['reg_no'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($student['title'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($student['name'], ENT_QUOTES); ?>', <?php echo $student['group_id']; ?>)" class="btn btn-sm" style="background: #fef9c3; color: #854d0e; border: none;" title="Edit Profile">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Archive this student?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $student['id']; ?>">
                                        <button type="submit" class="btn btn-sm" style="background: #fee2e2; color: #991b1b; border: none;" title="Remove">
                                            <i class="fas fa-trash-alt"></i>
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
                    <label for="reg_no" class="form-label">Registration Number</label>
                    <input type="text" id="reg_no" name="reg_no" class="form-control" placeholder="2024/CSC/001" required>
                </div>
                
                <div class="form-group">
                    <label for="title" class="form-label">Title</label>
                    <select id="title" name="title" class="form-control" required>
                        <option value="">Select title...</option>
                        <option value="MR">MR</option>
                        <option value="MISS">MISS</option>
                        <option value="MRS">MRS</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="name" class="form-label">Name with Initials</label>
                    <input type="text" id="name" name="name" class="form-control" placeholder="AHAMED A.G.I." required>
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
                    <label for="edit_reg_no" class="form-label">Registration Number</label>
                    <input type="text" id="edit_reg_no" name="reg_no" class="form-control" value="<?php echo $editStudent ? htmlspecialchars($editStudent['reg_no']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_title" class="form-label">Title</label>
                    <select id="edit_title" name="title" class="form-control" required>
                        <option value="">Select title...</option>
                        <option value="MR" <?php echo ($editStudent && $editStudent['title'] == 'MR') ? 'selected' : ''; ?>>MR</option>
                        <option value="MISS" <?php echo ($editStudent && $editStudent['title'] == 'MISS') ? 'selected' : ''; ?>>MISS</option>
                        <option value="MRS" <?php echo ($editStudent && $editStudent['title'] == 'MRS') ? 'selected' : ''; ?>>MRS</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="edit_name" class="form-label">Name with Initials</label>
                    <input type="text" id="edit_name" name="name" class="form-control" value="<?php echo $editStudent ? htmlspecialchars($editStudent['name']) : ''; ?>" required>
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
function editStudent(id, regNo, title, name, groupId) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_reg_no').value = regNo;
    document.getElementById('edit_title').value = title;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_group_id').value = groupId;
    openModal('editStudentModal');
}

// Initialize search filter
filterTable('searchInput', 'studentsTable');
</script>

<?php require_once '../includes/footer.php'; ?>
