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
    <div class="premium-banner">
        <div class="banner-content">
            <div class="banner-icon-wrapper">
                <i class="fas fa-layer-group"></i>
            </div>
            <div class="banner-text">
                <h1><?php echo htmlspecialchars($viewGroup['name']); ?></h1>
                <p>Comprehensive group management and assignments</p>
            </div>
            <div class="banner-actions">
                <a href="groups.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> All Groups
                </a>
            </div>
        </div>
    </div>
    
    <div class="grid grid-cols-2">
        <!-- Assigned Lecturers -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-chalkboard-teacher"></i> Teaching Faculty</h2>
            </div>
            <div class="card-body">
                <?php if (empty($assignedLecturers)): ?>
                    <div style="text-align: center; padding: 2rem; background: #f8fafc; border-radius: 15px; border: 2px dashed #e2e8f0;">
                        <p style="color: #64748b; font-weight: 600;">No lecturers assigned</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table>
                            <tbody>
                                <?php foreach ($assignedLecturers as $lecturer): ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                                            <div style="width: 32px; height: 32px; border-radius: 8px; background: #f1f5f9; color: #475569; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.8rem;">
                                                <?php echo strtoupper(substr($lecturer['name'], 0, 1)); ?>
                                            </div>
                                            <div style="font-weight: 700; color: #1e293b;"><?php echo htmlspecialchars($lecturer['name']); ?></div>
                                        </div>
                                    </td>
                                    <td class="text-right">
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="remove_lecturer">
                                            <input type="hidden" name="group_id" value="<?php echo $viewGroup['id']; ?>">
                                            <input type="hidden" name="lecturer_id" value="<?php echo $lecturer['id']; ?>">
                                            <button type="submit" class="btn btn-sm" style="background: #fee2e2; color: #991b1b; border: none;">
                                                <i class="fas fa-times"></i>
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
                    <form method="POST" style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #f1f5f9;">
                        <input type="hidden" name="action" value="assign_lecturer">
                        <input type="hidden" name="group_id" value="<?php echo $viewGroup['id']; ?>">
                        <div style="display: flex; gap: 0.5rem;">
                            <select name="lecturer_id" class="form-control" required style="font-size: 0.9rem;">
                                <option value="">Assign lecturer...</option>
                                <?php foreach ($availableLecturers as $lecturer): ?>
                                    <option value="<?php echo $lecturer['id']; ?>">
                                        <?php echo htmlspecialchars($lecturer['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Students in Group Summary -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-user-graduate"></i> Enrolled Students</h2>
                <span class="badge" style="background: #f1f5f9; color: #475569;"><?php echo count($groupStudents); ?> Total</span>
            </div>
            <div class="card-body">
                <?php if (empty($groupStudents)): ?>
                    <div style="text-align: center; padding: 2rem; background: #f8fafc; border-radius: 15px; border: 2px dashed #e2e8f0;">
                        <p style="color: #64748b; font-weight: 600;">No students enrolled</p>
                    </div>
                <?php else: ?>
                    <div style="max-height: 300px; overflow-y: auto;">
                        <table style="font-size: 0.9rem;">
                            <tbody>
                                <?php foreach ($groupStudents as $student): ?>
                                <tr>
                                    <td style="font-weight: 600; color: #1e293b;"><?php echo htmlspecialchars($student['name']); ?></td>
                                    <td style="color: #64748b; font-family: 'JetBrains Mono', monospace; font-size: 0.8rem;"><?php echo htmlspecialchars($student['reg_no']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
                <div style="margin-top: 1.5rem; text-align: right;">
                    <a href="students.php?group_id=<?php echo $viewGroup['id']; ?>" class="btn btn-secondary btn-sm">
                        <i class="fas fa-external-link-alt"></i> Manage Student List
                    </a>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- List All Groups -->
    <div class="premium-banner">
        <div class="banner-content">
            <div class="banner-icon-wrapper">
                <i class="fas fa-users"></i>
            </div>
            <div class="banner-text">
                <h1>Group Management</h1>
                <p>Organize students into batches and assign faculty</p>
            </div>
            <div class="banner-actions">
                <button onclick="openModal('createGroupModal')" class="btn btn-primary">
                    <i class="fas fa-plus-circle"></i> Create New Group
                </button>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <?php if (empty($groups)): ?>
                <div style="text-align: center; padding: 4rem 2rem; background: #f8fafc; border-radius: 20px; border: 2px dashed #e2e8f0;">
                    <i class="fas fa-users-slash" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 1.5rem;"></i>
                    <h3 style="color: #64748b; font-weight: 700;">No Groups Created</h3>
                    <p style="color: #94a3b8; margin-bottom: 2rem;">Create your first student group to begin managing attendance.</p>
                    <button onclick="openModal('createGroupModal')" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create First Group
                    </button>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table id="groupsTable">
                        <thead>
                            <tr>
                                <th>Group Name</th>
                                <th>Population</th>
                                <th>Faculty</th>
                                <th>Metadata</th>
                                <th class="text-right">Manage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($groups as $group): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 800; color: #0f172a; font-size: 1.1rem;"><?php echo htmlspecialchars($group['name']); ?></div>
                                    <div style="font-size: 0.8rem; color: #64748b; font-weight: 500;">ID: #<?php echo str_pad($group['id'], 3, '0', STR_PAD_LEFT); ?></div>
                                </td>
                                <td>
                                    <span class="badge" style="background: #ecfdf5; color: #059669; font-weight: 800;">
                                        <i class="fas fa-user-graduate" style="margin-right: 0.3rem;"></i>
                                        <?php echo $group['student_count']; ?> Students
                                    </span>
                                </td>
                                <td>
                                    <span class="badge" style="background: #eef2ff; color: #4f46e5; font-weight: 800;">
                                        <i class="fas fa-chalkboard-teacher" style="margin-right: 0.3rem;"></i>
                                        <?php echo $group['lecturer_count']; ?> Assigned
                                    </span>
                                </td>
                                <td>
                                    <div style="font-size: 0.85rem; color: #1e293b; font-weight: 600;">By <?php echo htmlspecialchars($group['created_by_name']); ?></div>
                                    <div style="font-size: 0.8rem; color: #94a3b8;"><?php echo date('M j, Y', strtotime($group['created_at'])); ?></div>
                                </td>
                                <td class="text-right">
                                    <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                                        <a href="groups.php?view=<?php echo $group['id']; ?>" class="btn btn-sm" style="background: #f1f5f9; color: #475569; border: none;" title="Configure Group">
                                            <i class="fas fa-cog"></i>
                                        </a>
                                        <button onclick="editGroup(<?php echo $group['id']; ?>, '<?php echo htmlspecialchars($group['name'], ENT_QUOTES); ?>')" class="btn btn-sm" style="background: #fef9c3; color: #854d0e; border: none;" title="Rename">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this group?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $group['id']; ?>">
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
