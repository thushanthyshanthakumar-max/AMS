<?php
$pageTitle = 'Manage Class Partitions';
$baseUrl = '..';
require_once '../includes/header.php';
requireAdmin();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            if ($_POST['action'] === 'create') {
                $groupIds = $_POST['group_ids'] ?? [];
                $name = sanitize($_POST['name']);
                $startTime = sanitize($_POST['start_time']);
                $endTime = sanitize($_POST['end_time']);
                $dayOfWeek = sanitize($_POST['day_of_week']);
                
                if (empty($groupIds) || empty($name) || empty($startTime) || empty($endTime) || empty($dayOfWeek)) {
                    setFlashMessage('danger', 'All fields are required and at least one group must be selected.');
                } else {
                    $stmt = $pdo->prepare("INSERT INTO partitions (group_id, name, start_time, end_time, day_of_week) VALUES (?, ?, ?, ?, ?)");
                    foreach ($groupIds as $groupId) {
                        $stmt->execute([(int)$groupId, $name, $startTime, $endTime, $dayOfWeek]);
                    }
                    setFlashMessage('success', 'Partition(s) added successfully.');
                    redirect('partitions.php');
                }
            } elseif ($_POST['action'] === 'delete_slot') {
                $name = sanitize($_POST['name']);
                $startTime = sanitize($_POST['start_time']);
                $endTime = sanitize($_POST['end_time']);
                $dayOfWeek = sanitize($_POST['day_of_week']);
                
                $stmt = $pdo->prepare("DELETE FROM partitions WHERE name = ? AND start_time = ? AND end_time = ? AND day_of_week = ?");
                $stmt->execute([$name, $startTime, $endTime, $dayOfWeek]);
                setFlashMessage('success', 'Time slot deleted successfully.');
                redirect('partitions.php');
            } elseif ($_POST['action'] === 'delete_single') {
                $id = (int)$_POST['id'];
                $stmt = $pdo->prepare("DELETE FROM partitions WHERE id = ?");
                $stmt->execute([$id]);
                setFlashMessage('success', 'Group removed from partition.');
                redirect('partitions.php');
            }
        } catch (PDOException $e) {
            setFlashMessage('danger', 'An error occurred: ' . $e->getMessage());
        }
    }
}

// Get all groups
try {
    $stmt = $pdo->query("SELECT id, name FROM groups ORDER BY name");
    $groups = $stmt->fetchAll();
} catch (PDOException $e) {
    $groups = [];
}

// Get all partitions grouped by day and time slot
try {
    $stmt = $pdo->query("
        SELECT p.*, g.name as group_name
        FROM partitions p
        JOIN groups g ON p.group_id = g.id
        ORDER BY 
            FIELD(p.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'),
            p.start_time
    ");
    $allPartitions = $stmt->fetchAll();
    
    // Group by day and then by time slot
    $partitionsByDay = [
        'Monday' => [],
        'Tuesday' => [],
        'Wednesday' => [],
        'Thursday' => [],
        'Friday' => [],
        'Saturday' => [],
        'Sunday' => []
    ];
    
    foreach ($allPartitions as $partition) {
        $key = $partition['name'] . '|' . $partition['start_time'] . '|' . $partition['end_time'];
        if (!isset($partitionsByDay[$partition['day_of_week']][$key])) {
            $partitionsByDay[$partition['day_of_week']][$key] = [
                'name' => $partition['name'],
                'start_time' => $partition['start_time'],
                'end_time' => $partition['end_time'],
                'day_of_week' => $partition['day_of_week'],
                'groups' => []
            ];
        }
        $partitionsByDay[$partition['day_of_week']][$key]['groups'][] = [
            'id' => $partition['id'],
            'group_id' => $partition['group_id'],
            'group_name' => $partition['group_name']
        ];
    }
} catch (PDOException $e) {
    $partitionsByDay = [];
}

$selectedDay = isset($_GET['day']) ? $_GET['day'] : 'Monday';
?>

<style>
.week-calendar {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 1rem;
    margin-bottom: 2rem;
}

.day-card {
    background: white;
    border-radius: var(--radius-lg);
    padding: 1.5rem;
    text-align: center;
    cursor: pointer;
    transition: var(--transition);
    border: 3px solid transparent;
    box-shadow: var(--shadow-sm);
}

.day-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-lg);
    border-color: var(--primary-light);
}

.day-card.active {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    color: white;
    border-color: var(--primary-dark);
    box-shadow: var(--shadow-xl);
}

.day-name {
    font-size: 1.25rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.day-count {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
}

.day-label {
    font-size: 0.875rem;
    opacity: 0.8;
}

.time-slot-card {
    background: white;
    border-left: 5px solid var(--primary-color);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-md);
    transition: var(--transition);
}

.time-slot-card:hover {
    box-shadow: var(--shadow-lg);
    transform: translateX(5px);
}

.slot-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid var(--border-color);
}

.slot-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.slot-time {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--text-secondary);
    font-size: 1rem;
    font-weight: 600;
}

.groups-container {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    margin-top: 1rem;
}

.group-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: linear-gradient(135deg, var(--primary-light), var(--primary-color));
    color: white;
    border-radius: var(--radius-md);
    font-size: 0.875rem;
    font-weight: 600;
    box-shadow: var(--shadow-sm);
    transition: var(--transition);
}

.group-badge:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.group-badge button {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: var(--transition);
    padding: 0;
    font-size: 0.75rem;
}

.group-badge button:hover {
    background: rgba(255, 255, 255, 0.4);
}

.add-group-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: var(--light-color);
    color: var(--text-primary);
    border: 2px dashed var(--border-color);
    border-radius: var(--radius-md);
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
}

.add-group-btn:hover {
    background: var(--primary-light);
    color: white;
    border-color: var(--primary-color);
}

.empty-state {
    text-align: center;
    padding: 3rem;
    color: var(--text-secondary);
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.3;
}

@media (max-width: 1024px) {
    .week-calendar {
        grid-template-columns: repeat(4, 1fr);
    }
}

@media (max-width: 768px) {
    .week-calendar {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-calendar-alt"></i> Weekly Class Schedule</h2>
        <button onclick="openModal('createPartitionModal')" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> Add Time Slot
        </button>
    </div>
    <div class="card-body">
        <!-- Week Calendar -->
        <div class="week-calendar">
            <?php 
            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            foreach ($days as $day): 
                $count = count($partitionsByDay[$day]);
                $isActive = ($selectedDay === $day);
            ?>
                <a href="?day=<?php echo $day; ?>" style="text-decoration: none;">
                    <div class="day-card <?php echo $isActive ? 'active' : ''; ?>">
                        <div class="day-name"><?php echo substr($day, 0, 3); ?></div>
                        <div class="day-count"><?php echo $count; ?></div>
                        <div class="day-label"><?php echo $count === 1 ? 'slot' : 'slots'; ?></div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Selected Day Partitions -->
        <div class="card" style="background: var(--light-color); margin-top: 2rem;">
            <div class="card-header" style="background: white;">
                <h3><i class="fas fa-calendar-day"></i> <?php echo $selectedDay; ?> Schedule</h3>
                <button onclick="openModalWithDay('<?php echo $selectedDay; ?>')" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Add Time Slot
                </button>
            </div>
            <div class="card-body">
                <?php if (empty($partitionsByDay[$selectedDay])): ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-times"></i>
                        <p>No time slots scheduled for <?php echo $selectedDay; ?></p>
                        <button onclick="openModalWithDay('<?php echo $selectedDay; ?>')" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add First Time Slot
                        </button>
                    </div>
                <?php else: ?>
                    <?php foreach ($partitionsByDay[$selectedDay] as $slot): ?>
                        <div class="time-slot-card">
                            <div class="slot-header">
                                <div>
                                    <div class="slot-title">
                                        <i class="fas fa-clock"></i>
                                        <?php echo htmlspecialchars($slot['name']); ?>
                                    </div>
                                    <div class="slot-time">
                                        <i class="fas fa-hourglass-start"></i>
                                        <strong><?php echo date('g:i A', strtotime($slot['start_time'])); ?></strong>
                                        <span>to</span>
                                        <strong><?php echo date('g:i A', strtotime($slot['end_time'])); ?></strong>
                                    </div>
                                </div>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this entire time slot and all assigned groups?');">
                                    <input type="hidden" name="action" value="delete_slot">
                                    <input type="hidden" name="name" value="<?php echo htmlspecialchars($slot['name']); ?>">
                                    <input type="hidden" name="start_time" value="<?php echo $slot['start_time']; ?>">
                                    <input type="hidden" name="end_time" value="<?php echo $slot['end_time']; ?>">
                                    <input type="hidden" name="day_of_week" value="<?php echo $slot['day_of_week']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash"></i> Delete Slot
                                    </button>
                                </form>
                            </div>
                            
                            <div>
                                <strong style="color: var(--text-secondary); font-size: 0.875rem; display: block; margin-bottom: 0.5rem;">
                                    <i class="fas fa-users"></i> Assigned Groups (<?php echo count($slot['groups']); ?>):
                                </strong>
                                <div class="groups-container">
                                    <?php foreach ($slot['groups'] as $group): ?>
                                        <div class="group-badge">
                                            <i class="fas fa-user-graduate"></i>
                                            <?php echo htmlspecialchars($group['group_name']); ?>
                                            <form method="POST" style="display: inline; margin: 0;" onsubmit="return confirm('Remove this group from this time slot?');">
                                                <input type="hidden" name="action" value="delete_single">
                                                <input type="hidden" name="id" value="<?php echo $group['id']; ?>">
                                                <button type="submit" title="Remove group">×</button>
                                            </form>
                                        </div>
                                    <?php endforeach; ?>
                                    
                                    <button class="add-group-btn" onclick="addGroupToSlot('<?php echo htmlspecialchars($slot['name'], ENT_QUOTES); ?>', '<?php echo $slot['start_time']; ?>', '<?php echo $slot['end_time']; ?>', '<?php echo $slot['day_of_week']; ?>', [<?php echo implode(',', array_column($slot['groups'], 'group_id')); ?>])">
                                        <i class="fas fa-plus"></i> Add Group
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Create Partition Modal -->
<div id="createPartitionModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-plus"></i> Add New Time Slot</h3>
            <button class="modal-close" onclick="closeModal('createPartitionModal')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="create">
                
                <div class="form-group">
                    <label for="day_of_week" class="form-label">Day of Week</label>
                    <select id="day_of_week" name="day_of_week" class="form-control" required>
                        <option value="">Select day...</option>
                        <option value="Monday">Monday</option>
                        <option value="Tuesday">Tuesday</option>
                        <option value="Wednesday">Wednesday</option>
                        <option value="Thursday">Thursday</option>
                        <option value="Friday">Friday</option>
                        <option value="Saturday">Saturday</option>
                        <option value="Sunday">Sunday</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="name" class="form-label">Session Name</label>
                    <input type="text" id="name" name="name" class="form-control" placeholder="Morning Session" required>
                    <small style="color: var(--text-secondary);">e.g., Morning Session, Evening Session</small>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="start_time" class="form-label">Start Time</label>
                        <input type="time" id="start_time" name="start_time" class="form-control" value="09:00" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="end_time" class="form-label">End Time</label>
                        <input type="time" id="end_time" name="end_time" class="form-control" value="11:00" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Select Groups (Multiple)</label>
                    <div style="max-height: 200px; overflow-y: auto; border: 2px solid var(--border-color); border-radius: var(--radius-md); padding: 1rem;">
                        <?php foreach ($groups as $group): ?>
                            <label style="display: block; padding: 0.5rem; cursor: pointer; border-radius: var(--radius-sm); transition: var(--transition);" onmouseover="this.style.background='var(--light-color)'" onmouseout="this.style.background='transparent'">
                                <input type="checkbox" name="group_ids[]" value="<?php echo $group['id']; ?>" style="margin-right: 0.5rem;">
                                <?php echo htmlspecialchars($group['name']); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="alert alert-info" style="margin-top: 1rem;">
                    <i class="fas fa-info-circle"></i>
                    <strong>Quick Presets:</strong>
                    <div style="margin-top: 0.5rem; display: flex; gap: 0.5rem; flex-wrap: wrap;">
                        <button type="button" class="btn btn-sm btn-secondary" onclick="setTime('09:00', '11:00')">
                            Morning (9-11 AM)
                        </button>
                        <button type="button" class="btn btn-sm btn-secondary" onclick="setTime('13:00', '15:00')">
                            Evening (1-3 PM)
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('createPartitionModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Create Time Slot
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openModalWithDay(day) {
    // Reset form for new time slot
    resetForm();
    document.getElementById('day_of_week').value = day;
    openModal('createPartitionModal');
}

function setTime(start, end) {
    document.getElementById('start_time').value = start;
    document.getElementById('end_time').value = end;
}

function resetForm() {
    document.getElementById('name').readOnly = false;
    document.getElementById('start_time').readOnly = false;
    document.getElementById('end_time').readOnly = false;
    document.getElementById('day_of_week').disabled = false;
    
    // Show all groups
    const checkboxes = document.querySelectorAll('input[name="group_ids[]"]');
    checkboxes.forEach(cb => {
        cb.checked = false;
        cb.parentElement.style.display = 'block';
    });
}

function addGroupToSlot(name, startTime, endTime, dayOfWeek, assignedGroupIds) {
    resetForm();
    
    document.getElementById('name').value = name;
    document.getElementById('start_time').value = startTime;
    document.getElementById('end_time').value = endTime;
    document.getElementById('day_of_week').value = dayOfWeek;
    
    // Make fields readonly
    document.getElementById('name').readOnly = true;
    document.getElementById('start_time').readOnly = true;
    document.getElementById('end_time').readOnly = true;
    document.getElementById('day_of_week').disabled = true;
    
    // Hide already assigned groups
    const checkboxes = document.querySelectorAll('input[name="group_ids[]"]');
    checkboxes.forEach(cb => {
        const groupId = parseInt(cb.value);
        if (assignedGroupIds.includes(groupId)) {
            cb.parentElement.style.display = 'none';
        } else {
            cb.parentElement.style.display = 'block';
        }
    });
    
    openModal('createPartitionModal');
}
</script>

<?php require_once '../includes/footer.php'; ?>
