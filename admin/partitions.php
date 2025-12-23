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

<div class="card" style="background: transparent; border: none; box-shadow: none; padding: 0;">
    <div class="card-body" style="padding: 0;">
        <!-- Week Calendar View -->
        <div class="week-calendar" style="display: flex; gap: 0.75rem; overflow-x: auto; padding: 0.5rem; margin-bottom: 2.5rem; scrollbar-width: none; -ms-overflow-style: none;">
            <style>
                .week-calendar::-webkit-scrollbar { display: none; }
                .day-card-container { flex: 1; min-width: 120px; text-decoration: none; }
                .day-card {
                    background: white;
                    color: #1e293b;
                    padding: 1.25rem 0.75rem;
                    border-radius: 20px;
                    text-align: center;
                    transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
                    border: 1px solid #f1f5f9;
                    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
                    cursor: pointer;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                }
                .day-card.active {
                    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
                    color: white;
                    border: none;
                    box-shadow: 0 15px 25px -5px rgba(99, 102, 241, 0.4);
                    transform: translateY(-8px) scale(1.05);
                }
                .day-card:hover:not(.active) {
                    transform: translateY(-5px);
                    border-color: #6366f1;
                    box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.1);
                }
                .day-name { font-size: 1.1rem; font-weight: 800; line-height: 1; }
                .slot-label { font-size: 0.65rem; font-weight: 700; margin-top: 0.4rem; opacity: 0.6; text-transform: uppercase; letter-spacing: 0.05em; }
                .active .slot-label { opacity: 0.9; }

                .session-slot {
                    background: white;
                    border-radius: 24px;
                    padding: 2.5rem;
                    margin-bottom: 2rem;
                    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
                    border: 1px solid #f1f5f9;
                    transition: transform 0.3s ease;
                }
                .session-slot:hover {
                    transform: translateY(-5px);
                }
                .session-header-info { width: 100%; }
                .session-name-row { 
                    font-size: 1.25rem; 
                    font-weight: 700; 
                    color: #1e293b; 
                    display: flex; 
                    align-items: center; 
                    gap: 0.75rem; 
                    margin-bottom: 0.5rem; 
                }
                .session-time-row { 
                    font-size: 1.5rem; 
                    font-weight: 800; 
                    color: #0f172a; 
                    display: flex; 
                    align-items: center; 
                    gap: 0.75rem; 
                    margin-bottom: 1.5rem;
                }
                .session-time-row span { font-size: 1.1rem; color: #94a3b8; font-weight: 600; margin: 0 0.4rem; }

                .btn-delete-slot {
                    background: #f8fafc;
                    color: #1e293b;
                    border: none;
                    padding: 0.85rem 2.5rem;
                    border-radius: 14px;
                    font-weight: 800;
                    font-size: 1rem;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 0.85rem;
                    cursor: pointer;
                    margin-bottom: 2rem;
                    width: fit-content;
                    transition: all 0.2s;
                }
                .btn-delete-slot:hover {
                    background: #f1f5f9;
                    color: #ef4444;
                }

                .assigned-title {
                    font-size: 1rem;
                    font-weight: 700;
                    color: #94a3b8;
                    display: flex;
                    align-items: center;
                    gap: 0.75rem;
                    margin-bottom: 1.5rem;
                }
                .group-list-vertical { display: flex; flex-direction: column; gap: 1.25rem; }
                .group-item-row {
                    display: flex;
                    align-items: center;
                    gap: 1rem;
                    color: #0f172a;
                    font-weight: 900;
                    font-size: 1.2rem;
                    letter-spacing: -0.02em;
                }
                .group-item-row i { color: #0f172a; font-size: 1.35rem; width: 28px; text-align: center; }
                
                .remove-group-box {
                    width: 26px;
                    height: 26px;
                    border: 1.5px solid #0f172a;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 0.85rem;
                    cursor: pointer;
                    background: transparent;
                    color: #0f172a;
                    padding: 0;
                    line-height: 1;
                    margin-left: 0.5rem;
                    border-radius: 4px;
                    transition: all 0.2s;
                }
                .remove-group-box:hover {
                    background: #0f172a;
                    color: white;
                }

                .btn-add-group-simple {
                    background: transparent;
                    color: #0f172a;
                    border: 1.5px solid #0f172a;
                    padding: 0.6rem 1.25rem;
                    font-size: 1.15rem;
                    font-weight: 900;
                    display: flex;
                    align-items: center;
                    gap: 0.6rem;
                    cursor: pointer;
                    margin-top: 1.5rem;
                    width: fit-content;
                    border-radius: 4px;
                    transition: all 0.2s;
                    letter-spacing: -0.01em;
                }
                .btn-add-group-simple:hover {
                    background: #f8fafc;
                    transform: translateX(5px);
                }
            </style>

            <?php 
            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            foreach ($days as $day): 
                $isActive = ($selectedDay === $day);
            ?>
                <a href="?day=<?php echo $day; ?>" class="day-card-container">
                    <div class="day-card <?php echo $isActive ? 'active' : ''; ?>">
                        <div class="day-name"><?php echo $day; ?></div>
                        <div class="slot-label">SLOTS</div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="schedule-container" style="background: transparent; padding: 0; border: none; box-shadow: none;">
            <div class="schedule-header" style="border: none; padding-bottom: 2rem; display: flex; align-items: center; justify-content: space-between;">
                <h2 style="font-size: 1.75rem; color: #0f172a; font-weight: 800;"><i class="fas fa-calendar-check" style="color: #6366f1;"></i> <?php echo $selectedDay; ?> Sessions</h2>
                <button onclick="openModalWithDay('<?php echo $selectedDay; ?>')" class="btn btn-primary" style="padding: 0.75rem 1.5rem; border-radius: 16px; background: #6366f1;">
                    <i class="fas fa-plus-circle"></i> Add New Session
                </button>
            </div>

            <div class="schedule-body">
                <?php if (empty($partitionsByDay[$selectedDay])): ?>
                    <div style="background: white; border-radius: 32px; padding: 6rem 2rem; text-align: center; border: 1px dashed #cbd5e1;">
                        <div style="width: 100px; height: 100px; background: #f8fafc; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 2rem; color: #cbd5e1; font-size: 3rem;">
                            <i class="fas fa-calendar-day"></i>
                        </div>
                        <h3 style="color: #334155; font-weight: 800; font-size: 1.5rem; margin-bottom: 0.75rem;">No Academic Slots Defined</h3>
                        <p style="color: #64748b; margin-bottom: 2.5rem; font-size: 1.1rem;">Start by adding your first class session to this day's schedule.</p>
                        <button onclick="openModalWithDay('<?php echo $selectedDay; ?>')" class="btn btn-primary" style="padding: 1rem 2.5rem; border-radius: 18px;">
                            <i class="fas fa-plus"></i> Configure Day
                        </button>
                    </div>
                <?php else: ?>
                    <?php foreach ($partitionsByDay[$selectedDay] as $slot): ?>
                        <div class="session-slot">
                            <div class="session-header-info">
                                <div class="session-name-row">
                                    <i class="fas fa-clock" style="color: #6366f1;"></i>
                                    <?php echo htmlspecialchars($slot['name']); ?>
                                </div>
                                <div class="session-time-row">
                                    <i class="fas fa-hourglass-start" style="color: #6366f1;"></i>
                                    <?php echo date('g:i A', strtotime($slot['start_time'])); ?>
                                    <span>to</span>
                                    <?php echo date('g:i A', strtotime($slot['end_time'])); ?>
                                </div>
                                
                                <form method="POST" onsubmit="return confirm('Delete this entire session?');">
                                    <input type="hidden" name="action" value="delete_slot">
                                    <input type="hidden" name="name" value="<?php echo htmlspecialchars($slot['name']); ?>">
                                    <input type="hidden" name="start_time" value="<?php echo $slot['start_time']; ?>">
                                    <input type="hidden" name="end_time" value="<?php echo $slot['end_time']; ?>">
                                    <input type="hidden" name="day_of_week" value="<?php echo $slot['day_of_week']; ?>">
                                    <button type="submit" class="btn-delete-slot">
                                        <i class="fas fa-trash-alt"></i> Delete Slot
                                    </button>
                                </form>

                                <div class="assigned-title">
                                    <i class="fas fa-users"></i> Assigned Groups (<?php echo count($slot['groups']); ?>):
                                </div>
                                
                                <div class="group-list-vertical">
                                    <?php foreach ($slot['groups'] as $group): ?>
                                        <div class="group-item-row">
                                            <i class="fas fa-user-graduate"></i>
                                            <?php echo htmlspecialchars($group['group_name']); ?>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Remove this group from this slot?');">
                                                <input type="hidden" name="action" value="delete_single">
                                                <input type="hidden" name="id" value="<?php echo $group['id']; ?>">
                                                <button type="submit" class="remove-group-box">×</button>
                                            </form>
                                        </div>
                                    <?php endforeach; ?>
                                    
                                    <button class="btn-add-group-simple" onclick="addGroupToSlot('<?php echo htmlspecialchars($slot['name'], ENT_QUOTES); ?>', '<?php echo $slot['start_time']; ?>', '<?php echo $slot['end_time']; ?>', '<?php echo $slot['day_of_week']; ?>', [<?php echo implode(',', array_column($slot['groups'], 'group_id')); ?>])">
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
