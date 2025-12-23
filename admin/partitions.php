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

<div class="premium-banner">
    <div class="banner-content">
        <div class="banner-icon-wrapper">
            <i class="fas fa-calendar-alt"></i>
        </div>
        <div class="banner-text">
            <h1>Academic Schedule</h1>
            <p>Configure time slots and class sessions for the semester</p>
        </div>
        <div class="banner-actions">
            <button onclick="openModal('createPartitionModal')" class="btn btn-primary">
                <i class="fas fa-plus"></i> New Time Slot
            </button>
        </div>
    </div>
</div>

<div class="card" style="background: transparent; border: none; box-shadow: none;">
    <div class="card-body" style="padding: 0;">
        <!-- Week Calendar View -->
        <div class="week-calendar" style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 1rem; margin-bottom: 2.5rem;">
            <?php 
            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            foreach ($days as $day): 
                $count = count($partitionsByDay[$day]);
                $isActive = ($selectedDay === $day);
            ?>
                <a href="?day=<?php echo $day; ?>" style="text-decoration: none;">
                    <div class="day-card" style="
                        background: <?php echo $isActive ? 'var(--accent-gradient)' : 'white'; ?>;
                        color: <?php echo $isActive ? 'white' : '#1e293b'; ?>;
                        padding: 1.5rem 1rem;
                        border-radius: 24px;
                        text-align: center;
                        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                        border: 1px solid <?php echo $isActive ? 'transparent' : '#f1f5f9'; ?>;
                        box-shadow: <?php echo $isActive ? '0 20px 25px -5px rgba(99, 102, 241, 0.4)' : '0 4px 6px -1px rgba(0,0,0,0.05)'; ?>;
                        position: relative;
                        overflow: hidden;
                    ">
                        <?php if ($isActive): ?>
                            <div style="position: absolute; top: -10px; right: -10px; width: 40px; height: 40px; background: rgba(255,255,255,0.2); border-radius: 50%; blur: 10px;"></div>
                        <?php endif; ?>
                        <div style="font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 0.5rem; opacity: <?php echo $isActive ? '0.9' : '0.5'; ?>;">
                            <?php echo substr($day, 0, 3); ?>
                        </div>
                        <div style="font-size: 1.75rem; font-weight: 800; line-height: 1;"><?php echo $count; ?></div>
                        <div style="font-size: 0.75rem; font-weight: 600; margin-top: 0.25rem; opacity: <?php echo $isActive ? '0.8' : '0.4'; ?>;">
                            SLOTS
                        </div>
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
