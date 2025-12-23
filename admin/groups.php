<?php
$pageTitle = 'Manage Groups';
$baseUrl = '..';
require_once '../includes/header.php';
requireAdmin();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            if ($_POST['action'] === 'create') {
                $name = sanitize($_POST['name']);
                
                if (empty($name)) {
                    setFlashMessage('danger', 'Group name is required.');
                } else {
                    $stmt = $pdo->prepare("INSERT INTO groups (name, created_by) VALUES (?, ?)");
                    $stmt->execute([$name, $_SESSION['user_id']]);
                    setFlashMessage('success', 'Group created successfully.');
                    redirect('groups.php');
                }
            } elseif ($_POST['action'] === 'update') {
                $id = (int)$_POST['id'];
                $name = sanitize($_POST['name']);
                
                if (empty($name)) {
                    setFlashMessage('danger', 'Group name is required.');
                } else {
                    $stmt = $pdo->prepare("UPDATE groups SET name = ? WHERE id = ?");
                    $stmt->execute([$name, $id]);
                    setFlashMessage('success', 'Group updated successfully.');
                    redirect('groups.php');
                }
            } elseif ($_POST['action'] === 'delete') {
                $id = (int)$_POST['id'];
                $stmt = $pdo->prepare("DELETE FROM groups WHERE id = ?");
                $stmt->execute([$id]);
                setFlashMessage('success', 'Group deleted successfully.');
                redirect('groups.php');
            } elseif ($_POST['action'] === 'assign_lecturer') {
                $groupId = (int)$_POST['group_id'];
                $lecturerId = (int)$_POST['lecturer_id'];
                
                $stmt = $pdo->prepare("INSERT IGNORE INTO group_assignments (group_id, lecturer_id) VALUES (?, ?)");
                $stmt->execute([$groupId, $lecturerId]);
                setFlashMessage('success', 'Lecturer assigned successfully.');
                redirect('groups.php?view=' . $groupId);
            } elseif ($_POST['action'] === 'remove_lecturer') {
                $groupId = (int)$_POST['group_id'];
                $lecturerId = (int)$_POST['lecturer_id'];
                
                $stmt = $pdo->prepare("DELETE FROM group_assignments WHERE group_id = ? AND lecturer_id = ?");
                $stmt->execute([$groupId, $lecturerId]);
                setFlashMessage('success', 'Lecturer removed successfully.');
                redirect('groups.php?view=' . $groupId);
            }
        } catch (PDOException $e) {
            setFlashMessage('danger', 'An error occurred: ' . $e->getMessage());
        }
    }
}

// Get all groups
try {
    $stmt = $pdo->query("
        SELECT g.*, u.username as created_by_name,
               (SELECT COUNT(*) FROM students WHERE group_id = g.id) as student_count,
               (SELECT COUNT(*) FROM group_assignments WHERE group_id = g.id) as lecturer_count
        FROM groups g
        JOIN users u ON g.created_by = u.id
        ORDER BY g.created_at DESC
    ");
    $groups = $stmt->fetchAll();
} catch (PDOException $e) {
    setFlashMessage('danger', 'Error loading groups.');
    $groups = [];
}

// View specific group
$viewGroup = null;
if (isset($_GET['view'])) {
    $groupId = (int)$_GET['view'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM groups WHERE id = ?");
        $stmt->execute([$groupId]);
        $viewGroup = $stmt->fetch();
        
        if ($viewGroup) {
            // Get students in this group
            $stmt = $pdo->prepare("SELECT * FROM students WHERE group_id = ? ORDER BY name");
            $stmt->execute([$groupId]);
            $groupStudents = $stmt->fetchAll();
            
            // Get assigned lecturers
            $stmt = $pdo->prepare("
                SELECT l.*, u.username 
                FROM lecturers l
                JOIN users u ON l.user_id = u.id
                JOIN group_assignments ga ON l.id = ga.lecturer_id
                WHERE ga.group_id = ?
            ");
            $stmt->execute([$groupId]);
            $assignedLecturers = $stmt->fetchAll();
            
            // Get available lecturers (not assigned)
            $stmt = $pdo->prepare("
                SELECT l.*, u.username 
                FROM lecturers l
                JOIN users u ON l.user_id = u.id
                WHERE l.id NOT IN (
                    SELECT lecturer_id FROM group_assignments WHERE group_id = ?
                )
            ");
            $stmt->execute([$groupId]);
            $availableLecturers = $stmt->fetchAll();
        }
    } catch (PDOException $e) {
        setFlashMessage('danger', 'Error loading group details.');
    }
}
?>

<?php if ($viewGroup): ?>
    <!-- View Group Details -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-users"></i> <?php echo htmlspecialchars($viewGroup['name']); ?></h2>
            <a href="groups.php" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Back to Groups
            </a>
        </div>
    </div>
    
    <!-- Assigned Lecturers -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-chalkboard-teacher"></i> Assigned Lecturers</h2>
        </div>
        <div class="card-body">
            <?php if (empty($assignedLecturers)): ?>
                <p class="text-center" style="color: var(--text-secondary); padding: 1rem;">
                    No lecturers assigned yet.
                </p>
            <?php else: ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Username</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assignedLecturers as $lecturer): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($lecturer['name']); ?></td>
                                <td><?php echo htmlspecialchars($lecturer['email']); ?></td>
                                <td><?php echo htmlspecialchars($lecturer['phone']); ?></td>
                                <td><?php echo htmlspecialchars($lecturer['username']); ?></td>
                                <td>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Remove this lecturer from the group?');">
                                        <input type="hidden" name="action" value="remove_lecturer">
                                        <input type="hidden" name="group_id" value="<?php echo $viewGroup['id']; ?>">
                                        <input type="hidden" name="lecturer_id" value="<?php echo $lecturer['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fas fa-times"></i> Remove
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($availableLecturers)): ?>
                <form method="POST" class="mt-3">
                    <input type="hidden" name="action" value="assign_lecturer">
                    <input type="hidden" name="group_id" value="<?php echo $viewGroup['id']; ?>">
                    <div class="d-flex gap-2 align-center">
                        <select name="lecturer_id" class="form-control" required>
                            <option value="">Select a lecturer to assign...</option>
                            <?php foreach ($availableLecturers as $lecturer): ?>
                                <option value="<?php echo $lecturer['id']; ?>">
                                    <?php echo htmlspecialchars($lecturer['name']); ?> (<?php echo htmlspecialchars($lecturer['email']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-plus"></i> Assign Lecturer
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Students in Group -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-user-graduate"></i> Students (<?php echo count($groupStudents); ?>)</h2>
            <a href="students.php?action=create&group_id=<?php echo $viewGroup['id']; ?>" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Add Student
            </a>
        </div>
        <div class="card-body">
            <?php if (empty($groupStudents)): ?>
                <p class="text-center" style="color: var(--text-secondary); padding: 1rem;">
                    No students in this group yet.
                </p>
            <?php else: ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($groupStudents as $student): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['name']); ?></td>
                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                                <td><?php echo htmlspecialchars($student['phone']); ?></td>
                                <td>
                                    <a href="students.php?edit=<?php echo $student['id']; ?>" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php else: ?>
    <!-- List All Groups -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-users"></i> Manage Groups</h2>
            <button onclick="openModal('createGroupModal')" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Create Group
            </button>
        </div>
        <div class="card-body">
            <?php if (empty($groups)): ?>
                <p class="text-center" style="color: var(--text-secondary); padding: 2rem;">
                    <i class="fas fa-info-circle"></i> No groups created yet.
                </p>
            <?php else: ?>
                <div class="table-responsive">
                    <table id="groupsTable">
                        <thead>
                            <tr>
                                <th>Group Name</th>
                                <th>Students</th>
                                <th>Lecturers</th>
                                <th>Created By</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($groups as $group): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($group['name']); ?></strong></td>
                                <td><?php echo $group['student_count']; ?></td>
                                <td><?php echo $group['lecturer_count']; ?></td>
                                <td><?php echo htmlspecialchars($group['created_by_name']); ?></td>
                                <td><?php echo formatDate($group['created_at']); ?></td>
                                <td>
                                    <a href="groups.php?view=<?php echo $group['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <button onclick="editGroup(<?php echo $group['id']; ?>, '<?php echo htmlspecialchars($group['name'], ENT_QUOTES); ?>')" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this group? All students and partitions will also be deleted.');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $group['id']; ?>">
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
<?php endif; ?>

<!-- Create Group Modal -->
<div id="createGroupModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-plus"></i> Create New Group</h3>
            <button class="modal-close" onclick="closeModal('createGroupModal')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="create">
                <div class="form-group">
                    <label for="name" class="form-label">Group Name</label>
                    <input type="text" id="name" name="name" class="form-control" placeholder="e.g., Class 10A" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('createGroupModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Create Group
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Group Modal -->
<div id="editGroupModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Edit Group</h3>
            <button class="modal-close" onclick="closeModal('editGroupModal')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="update">
                <input type="hidden" id="edit_id" name="id">
                <div class="form-group">
                    <label for="edit_name" class="form-label">Group Name</label>
                    <input type="text" id="edit_name" name="name" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editGroupModal')">Cancel</button>
                <button type="submit" class="btn btn-warning">
                    <i class="fas fa-save"></i> Update Group
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function editGroup(id, name) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_name').value = name;
    openModal('editGroupModal');
}
</script>

<?php require_once '../includes/footer.php'; ?>
