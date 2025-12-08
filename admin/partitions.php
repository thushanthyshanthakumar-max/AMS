<?php
$pageTitle = 'Manage Partitions';
$baseUrl = '..';
$additionalJS = 'partitions.js';
require_once '../includes/header.php';
requireAdmin();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            if ($_POST['action'] === 'create_multiple') {
                $groupId = (int)$_POST['group_id'];
                $partitions = $_POST['partitions'] ?? [];
                
                if (empty($groupId) || empty($partitions)) {
                    setFlashMessage('danger', 'Group and at least one partition are required.');
                } else {
                    $pdo->beginTransaction();
                    
                    $stmt = $pdo->prepare("INSERT INTO partitions (group_id, name, start_time, end_time, day_of_week) VALUES (?, ?, ?, ?, ?)");
                    
                    foreach ($partitions as $partition) {
                        $name = sanitize($partition['name']);
                        $startTime = sanitize($partition['start_time']);
                        $endTime = sanitize($partition['end_time']);
                        $dayOfWeek = sanitize($partition['day_of_week']);
                        
                        if (!empty($name) && !empty($startTime) && !empty($endTime) && !empty($dayOfWeek)) {
                            $stmt->execute([$groupId, $name, $startTime, $endTime, $dayOfWeek]);
                        }
                    }
                    
                    $pdo->commit();
                    setFlashMessage('success', 'Partitions created successfully.');
                    redirect('partitions.php');
                }
            } elseif ($_POST['action'] === 'update') {
                $id = (int)$_POST['id'];
                $name = sanitize($_POST['name']);
                $startTime = sanitize($_POST['start_time']);
                $endTime = sanitize($_POST['end_time']);
                $dayOfWeek = sanitize($_POST['day_of_week']);
                
                if (empty($name) || empty($startTime) || empty($endTime) || empty($dayOfWeek)) {
                    setFlashMessage('danger', 'All fields are required.');
                } else {
                    $stmt = $pdo->prepare("UPDATE partitions SET name = ?, start_time = ?, end_time = ?, day_of_week = ? WHERE id = ?");
                    $stmt->execute([$name, $startTime, $endTime, $dayOfWeek, $id]);
                    setFlashMessage('success', 'Partition updated successfully.');
                    redirect('partitions.php');
                }
            } elseif ($_POST['action'] === 'delete') {
                $id = (int)$_POST['id'];
                $stmt = $pdo->prepare("DELETE FROM partitions WHERE id = ?");
                $stmt->execute([$id]);
                setFlashMessage('success', 'Partition deleted successfully.');
                redirect('partitions.php');
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            setFlashMessage('danger', 'An error occurred: ' . $e->getMessage());
        }
    }
}

// Get all partitions
try {
    $stmt = $pdo->query("
        SELECT p.*, g.name as group_name
        FROM partitions p
        JOIN groups g ON p.group_id = g.id
        ORDER BY g.name, p.day_of_week, p.start_time
    ");
    $partitions = $stmt->fetchAll();
    
    // Get all groups for dropdown
    $stmt = $pdo->query("SELECT id, name FROM groups ORDER BY name");
    $groups = $stmt->fetchAll();
} catch (PDOException $e) {
    setFlashMessage('danger', 'Error loading partitions.');
    $partitions = [];
    $groups = [];
}

$daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
?>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-clock"></i> Manage Partitions</h2>
        <button onclick="openModal('createPartitionModal')" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> Create Partitions
        </button>
    </div>
    <div class="card-body">
        <?php if (empty($partitions)): ?>
            <p class="text-center" style="color: var(--text-secondary); padding: 2rem;">
                <i class="fas fa-info-circle"></i> No partitions created yet.
            </p>
        <?php else: ?>
            <div class="table-responsive">
                <table id="partitionsTable">
                    <thead>
                        <tr>
                            <th>Group</th>
                            <th>Partition Name</th>
                            <th>Day</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($partitions as $partition): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($partition['group_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($partition['name']); ?></td>
                            <td><?php echo htmlspecialchars($partition['day_of_week']); ?></td>
                            <td><?php echo formatTime($partition['start_time']); ?></td>
                            <td><?php echo formatTime($partition['end_time']); ?></td>
                            <td>
                                <button onclick="editPartition(<?php echo $partition['id']; ?>, '<?php echo htmlspecialchars($partition['name'], ENT_QUOTES); ?>', '<?php echo $partition['start_time']; ?>', '<?php echo $partition['end_time']; ?>', '<?php echo htmlspecialchars($partition['day_of_week'], ENT_QUOTES); ?>')" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this partition? All attendance records for this partition will also be deleted.');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $partition['id']; ?>">
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

<!-- Create Partitions Modal -->
<div id="createPartitionModal" class="modal">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h3><i class="fas fa-plus"></i> Create Partitions</h3>
            <button class="modal-close" onclick="closeModal('createPartitionModal')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="create_multiple">
                
                <div class="form-group">
                    <label for="group_id" class="form-label">Select Group</label>
                    <select id="group_id" name="group_id" class="form-control" required>
                        <option value="">Select a group...</option>
                        <?php foreach ($groups as $group): ?>
                            <option value="<?php echo $group['id']; ?>">
                                <?php echo htmlspecialchars($group['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div id="partitionsContainer">
                    <div class="partition-item">
                        <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr 50px; gap: 1rem; align-items: end;">
                            <div class="form-group">
                                <label class="form-label">Partition Name</label>
                                <input type="text" name="partitions[0][name]" class="form-control" placeholder="e.g., Morning Class" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Start Time</label>
                                <input type="time" name="partitions[0][start_time]" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">End Time</label>
                                <input type="time" name="partitions[0][end_time]" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Day</label>
                                <select name="partitions[0][day_of_week]" class="form-control" required>
                                    <option value="">Day</option>
                                    <?php foreach ($daysOfWeek as $day): ?>
                                        <option value="<?php echo $day; ?>"><?php echo $day; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <!-- Placeholder for remove button -->
                            </div>
                        </div>
                    </div>
                </div>
                
                <button type="button" class="btn btn-secondary mt-2" onclick="addPartitionField()">
                    <i class="fas fa-plus"></i> Add Another Partition
                </button>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('createPartitionModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Create Partitions
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Partition Modal -->
<div id="editPartitionModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Edit Partition</h3>
            <button class="modal-close" onclick="closeModal('editPartitionModal')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="update">
                <input type="hidden" id="edit_id" name="id">
                
                <div class="form-group">
                    <label for="edit_name" class="form-label">Partition Name</label>
                    <input type="text" id="edit_name" name="name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_start_time" class="form-label">Start Time</label>
                    <input type="time" id="edit_start_time" name="start_time" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_end_time" class="form-label">End Time</label>
                    <input type="time" id="edit_end_time" name="end_time" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_day_of_week" class="form-label">Day of Week</label>
                    <select id="edit_day_of_week" name="day_of_week" class="form-control" required>
                        <?php foreach ($daysOfWeek as $day): ?>
                            <option value="<?php echo $day; ?>"><?php echo $day; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editPartitionModal')">Cancel</button>
                <button type="submit" class="btn btn-warning">
                    <i class="fas fa-save"></i> Update Partition
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
