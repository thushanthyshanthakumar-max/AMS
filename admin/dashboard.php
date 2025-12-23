<?php
$pageTitle = 'Admin Dashboard';
$baseUrl = '..';
require_once '../includes/header.php';
requireAdmin();

// Get statistics
try {
    // Total groups
    $stmt = $pdo->query("SELECT COUNT(*) FROM groups");
    $totalGroups = $stmt->fetchColumn();
    
    // Total lecturers
    $stmt = $pdo->query("SELECT COUNT(*) FROM lecturers");
    $totalLecturers = $stmt->fetchColumn();
    
    // Total students
    $stmt = $pdo->query("SELECT COUNT(*) FROM students");
    $totalStudents = $stmt->fetchColumn();
    
    // Total partitions
    $stmt = $pdo->query("SELECT COUNT(*) FROM partitions");
    $totalPartitions = $stmt->fetchColumn();
    
    // Recent attendance (last 7 days)
    $stmt = $pdo->query("
        SELECT COUNT(*) FROM attendance 
        WHERE date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ");
    $recentAttendance = $stmt->fetchColumn();
    
    // Recent groups
    $stmt = $pdo->query("
        SELECT g.*, u.username as created_by_name, 
               (SELECT COUNT(*) FROM students WHERE group_id = g.id) as student_count
        FROM groups g
        JOIN users u ON g.created_by = u.id
        ORDER BY g.created_at DESC
        LIMIT 5
    ");
    $recentGroups = $stmt->fetchAll();
    
} catch (PDOException $e) {
    setFlashMessage('danger', 'Error loading dashboard data.');
    $totalGroups = $totalLecturers = $totalStudents = $totalPartitions = $recentAttendance = 0;
    $recentGroups = [];
}
?>

<div class="premium-banner">
    <div class="banner-content">
        <div class="banner-icon-wrapper">
            <i class="fas fa-grid-2"></i>
        </div>
        <div class="banner-text">
            <h1>Operations Dashboard</h1>
            <div class="banner-meta">
                <span><i class="fas fa-user-shield"></i> Root Administrator</span>
                <span><i class="fas fa-calendar-day"></i> <?php echo date('l, F j'); ?></span>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-4" style="margin-bottom: 2.5rem;">
    <div class="mini-stat-card" style="--bg-accent: #6366f1;">
        <div class="mini-stat-icon"><i class="fas fa-layer-group"></i></div>
        <div class="mini-stat-info">
            <h2><?php echo $totalGroups; ?></h2>
            <p>Academic Batches</p>
        </div>
    </div>
    
    <div class="mini-stat-card" style="--bg-accent: #a855f7;">
        <div class="mini-stat-icon"><i class="fas fa-user-tie"></i></div>
        <div class="mini-stat-info">
            <h2><?php echo $totalLecturers; ?></h2>
            <p>Faculty Members</p>
        </div>
    </div>
    
    <div class="mini-stat-card" style="--bg-accent: #10b981;">
        <div class="mini-stat-icon"><i class="fas fa-graduation-cap"></i></div>
        <div class="mini-stat-info">
            <h2><?php echo $totalStudents; ?></h2>
            <p>Total Enrollment</p>
        </div>
    </div>
    
    <div class="mini-stat-card" style="--bg-accent: #f59e0b;">
        <div class="mini-stat-icon"><i class="fas fa-calendar-alt"></i></div>
        <div class="mini-stat-info">
            <h2><?php echo $totalPartitions; ?></h2>
            <p>Scheduled Events</p>
        </div>
    </div>
</div>

<div class="grid grid-cols-2" style="margin-bottom: 2.5rem; gap: 2rem;">
    <div class="card">
        <div class="card-body">
            <h3 style="margin-bottom: 1.5rem; font-weight: 800; color: #1e293b;"><i class="fas fa-bolt" style="color: #6366f1; margin-right: 12px;"></i> Core Operations</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <a href="groups.php?action=create" style="text-decoration: none; background: #f8fafc; padding: 1.5rem; border-radius: 20px; border: 1px solid #f1f5f9; display: flex; flex-direction: column; gap: 10px; transition: all 0.2s;">
                    <i class="fas fa-plus-circle" style="color: #6366f1; font-size: 1.5rem;"></i>
                    <span style="font-weight: 800; color: #1e293b; font-size: 0.95rem;">New Batch</span>
                </a>
                <a href="lecturers.php?action=create" style="text-decoration: none; background: #f8fafc; padding: 1.5rem; border-radius: 20px; border: 1px solid #f1f5f9; display: flex; flex-direction: column; gap: 10px; transition: all 0.2s;">
                    <i class="fas fa-user-plus" style="color: #a855f7; font-size: 1.5rem;"></i>
                    <span style="font-weight: 800; color: #1e293b; font-size: 0.95rem;">Add Faculty</span>
                </a>
                <a href="students.php?action=create" style="text-decoration: none; background: #f8fafc; padding: 1.5rem; border-radius: 20px; border: 1px solid #f1f5f9; display: flex; flex-direction: column; gap: 10px; transition: all 0.2s;">
                    <i class="fas fa-user-graduate" style="color: #10b981; font-size: 1.5rem;"></i>
                    <span style="font-weight: 800; color: #1e293b; font-size: 0.95rem;">Enrollment</span>
                </a>
                <a href="partitions.php" style="text-decoration: none; background: #f8fafc; padding: 1.5rem; border-radius: 20px; border: 1px solid #f1f5f9; display: flex; flex-direction: column; gap: 10px; transition: all 0.2s;">
                    <i class="fas fa-clock-rotate-left" style="color: #f59e0b; font-size: 1.5rem;"></i>
                    <span style="font-weight: 800; color: #1e293b; font-size: 0.95rem;">Scheduling</span>
                </a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h3 style="margin-bottom: 1.5rem; font-weight: 800; color: #1e293b;"><i class="fas fa-wave-pulse" style="color: #6366f1; margin-right: 12px;"></i> System Pulse</h3>
            <div style="background: #f8fafc; border-radius: 24px; padding: 2rem; border: 1px solid #f1f5f9; text-align: center;">
                <div style="font-size: 3.5rem; font-weight: 900; color: #6366f1; letter-spacing: -0.05em; line-height: 1;">
                    <?php echo $recentAttendance; ?>
                </div>
                <div style="font-size: 0.75rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.1em; margin-top: 10px;">
                    Attendance Captures (7 Days)
                </div>
                <div style="margin-top: 2rem; height: 10px; background: #e2e8f0; border-radius: 10px; overflow: hidden; display: flex;">
                    <div style="width: 75%; height: 100%; background: linear-gradient(90deg, #6366f1, #a855f7); border-radius: 10px;"></div>
                </div>
                <div style="display: flex; justify-content: space-between; margin-top: 8px; font-size: 0.75rem; font-weight: 700; color: #64748b;">
                    <span>System Utilization</span>
                    <span>75% Capacity</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem;">
            <h3 style="margin: 0; font-weight: 800; color: #1e293b;">Recently Initialized Batches</h3>
            <a href="groups.php" class="btn btn-secondary" style="font-size: 0.85rem; padding: 0.6rem 1.2rem; border-radius: 12px; background: #f1f5f9; border: none; color: #475569; font-weight: 700;">
                Review All <i class="fas fa-arrow-right" style="margin-left: 8px;"></i>
            </a>
        </div>

        <?php if (empty($recentGroups)): ?>
            <div style="text-align: center; padding: 4rem;">
                <i class="fas fa-inbox" style="font-size: 3rem; color: #e2e8f0; margin-bottom: 1rem;"></i>
                <p style="color: #94a3b8; font-weight: 600;">No recent groups detected in the registry.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table id="recentGroupsTable">
                    <thead>
                        <tr>
                            <th>Batch Identity</th>
                            <th>Metrics</th>
                            <th>Registrar</th>
                            <th class="text-right">Administration</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentGroups as $group): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div style="width: 40px; height: 40px; background: #f5f3ff; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #7c3aed; font-weight: 800; font-size: 0.9rem;">
                                        <?php echo substr($group['name'], 0, 2); ?>
                                    </div>
                                    <div>
                                        <div style="font-weight: 800; color: #1e293b;"><?php echo htmlspecialchars($group['name']); ?></div>
                                        <div style="font-size: 0.7rem; color: #94a3b8; font-weight: 700; text-transform: uppercase;">ID: #G-<?php echo $group['id']; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <span style="font-weight: 800; color: #1e293b; font-size: 0.95rem;"><?php echo $group['student_count']; ?></span>
                                    <span style="font-size: 0.75rem; color: #94a3b8; font-weight: 600;">Managed Entities</span>
                                </div>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <i class="fas fa-user-circle" style="color: #cbd5e1; font-size: 1.2rem;"></i>
                                    <span style="font-weight: 700; color: #475569; font-size: 0.9rem;"><?php echo htmlspecialchars($group['created_by_name']); ?></span>
                                </div>
                            </td>
                            <td class="text-right">
                                <a href="groups.php?view=<?php echo $group['id']; ?>" class="btn" style="background: white; border: 1px solid #e2e8f0; color: #1e293b; font-weight: 700; font-size: 0.85rem; border-radius: 10px; padding: 0.6rem 1.2rem;">
                                    Manage <i class="fas fa-gear" style="margin-left: 6px; color: #6366f1;"></i>
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

<?php require_once '../includes/footer.php'; ?>
