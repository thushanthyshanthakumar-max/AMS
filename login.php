<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('admin/dashboard.php');
    } else {
        redirect('lecturer/dashboard.php');
    }
}

// Fetch lecturers for the login selection
try {
    $stmt = $pdo->query("SELECT l.id, l.name, u.username FROM lecturers l JOIN users u ON l.user_id = u.id ORDER BY l.name ASC");
    $lecturers = $stmt->fetchAll();
} catch (PDOException $e) {
    $lecturers = [];
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter password.'; // Username is selected, so just ask for password
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['last_activity'] = time();
                
                // Redirect based on role
                if ($user['role'] === 'admin') {
                    redirect('admin/dashboard.php');
                } else {
                    redirect('lecturer/dashboard.php');
                }
            } else {
                $error = 'Invalid password.';
            }
        } catch (PDOException $e) {
            $error = 'An error occurred. Please try again.';
        }
    }
}

// Check for timeout message
if (isset($_GET['timeout'])) {
    $error = 'Your session has expired. Please login again.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #0f172a;
            background-image: 
                radial-gradient(at 0% 0%, hsla(253,16%,7%,1) 0, transparent 50%), 
                radial-gradient(at 50% 0%, hsla(225,39%,30%,1) 0, transparent 50%), 
                radial-gradient(at 100% 0%, hsla(339,49%,30%,1) 0, transparent 50%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .login-wrapper {
            width: 100%;
            max-width: 1000px;
            padding: 2rem;
            animation: fadeIn 0.6s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .login-header {
            text-align: center;
            margin-bottom: 4rem;
        }

        .login-header i {
            font-size: 5rem;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            filter: drop-shadow(0 0 20px rgba(99, 102, 241, 0.3));
        }

        .login-header h1 {
            color: white;
            font-size: 3rem;
            font-weight: 800;
            letter-spacing: -0.05em;
            margin-bottom: 0.5rem;
        }

        .login-header p {
            color: rgba(255, 255, 255, 0.6);
            font-size: 1.2rem;
            font-weight: 500;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .role-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 2.5rem;
        }

        .role-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 40px;
            padding: 4rem 3rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .role-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(168, 85, 247, 0.1) 100%);
            opacity: 0;
            transition: opacity 0.4s;
        }

        .role-card:hover {
            transform: translateY(-15px) scale(1.02);
            border-color: rgba(99, 102, 241, 0.5);
            box-shadow: 0 30px 60px -12px rgba(0, 0, 0, 0.5);
        }

        .role-card:hover::before {
            opacity: 1;
        }

        .role-card i {
            display: block;
            font-size: 4.5rem;
            color: white;
            margin-bottom: 2rem;
            transition: all 0.4s;
            position: relative;
            z-index: 1;
        }

        .role-card:hover i {
            transform: scale(1.1);
            color: #818cf8;
        }

        .role-card h2 {
            font-size: 2.4rem;
            color: white;
            font-weight: 800;
            margin-bottom: 1rem;
            letter-spacing: -0.02em;
            position: relative;
            z-index: 1;
        }

        .role-card p {
            color: rgba(255, 255, 255, 0.5);
            font-size: 1.1rem;
            font-weight: 500;
            position: relative;
            z-index: 1;
        }

        /* Lecturer Grid */
        .lecturer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .lecturer-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 30px;
            padding: 2.5rem 1.5rem;
            text-align: center;
            transition: all 0.3s;
        }

        .lecturer-card:hover {
            background: rgba(255, 255, 255, 0.06);
            transform: translateY(-8px);
            border-color: rgba(99, 102, 241, 0.3);
        }

        .lecturer-avatar {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
            color: white;
            border-radius: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: 800;
            margin: 0 auto 1.5rem;
            box-shadow: 0 10px 20px -5px rgba(99, 102, 241, 0.4);
        }

        .lecturer-card h3 {
            color: white;
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }

        /* Forms */
        .login-box-centered {
            max-width: 500px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(30px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 40px;
            padding: 4rem;
            text-align: center;
            box-shadow: 0 40px 100px -20px rgba(0, 0, 0, 0.6);
        }

        .selected-user-icon {
            font-size: 5rem;
            color: #818cf8;
            margin-bottom: 1.5rem;
            filter: drop-shadow(0 0 15px rgba(129, 140, 248, 0.3));
        }

        .selected-user-info h2 {
            color: white;
            font-size: 2.2rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            margin-bottom: 0.5rem;
        }

        .selected-user-info p {
            color: rgba(255, 255, 255, 0.4);
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 2.5rem;
        }

        .form-control {
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.1);
            color: white;
            border-radius: 18px;
            padding: 1.25rem;
            font-size: 1.4rem;
            transition: all 0.3s;
            text-align: center;
            letter-spacing: 4px;
        }

        .form-control:focus {
            background: rgba(255, 255, 255, 0.08);
            border-color: #6366f1;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15);
        }

        .back-btn {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
            padding: 0.75rem 1.25rem;
            border-radius: 14px;
            cursor: pointer;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 2rem;
            transition: all 0.2s;
        }

        .back-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(-5px);
        }

        .view-section {
            display: none;
        }

        .view-section.active {
            display: block;
            animation: slideIn 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateX(30px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .btn-action {
            width: 100%;
            padding: 0.8rem;
            border-radius: 12px;
            font-weight: 700;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            margin-bottom: 0.75rem;
            transition: all 0.2s;
        }

        .btn-action.login {
            background: var(--primary-color);
            color: white;
        }

        .btn-action.view {
            background: rgba(255, 255, 255, 0.05);
            color: rgba(255, 255, 255, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .btn-action:hover {
            transform: scale(1.03);
            filter: brightness(1.2);
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-header">
            <i class="fas fa-graduation-cap"></i>
            <h1>AMS Portal</h1>
            <p>Smart Attendance Ecosystem</p>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-danger" style="backdrop-filter: blur(10px); border-radius: 20px; background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.2); color: #fecaca; padding: 1.5rem; max-width: 500px; margin: 0 auto 3rem; text-align: center; font-weight: 600;">
            <i class="fas fa-exclamation-triangle" style="margin-right: 0.5rem; color: #ef4444;"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <!-- Role Selection -->
        <div id="roleSelection" class="view-section active">
            <div class="role-cards">
                <div class="role-card" onclick="selectRole('admin')">
                    <i class="fas fa-user-shield"></i>
                    <h2>Administrator</h2>
                    <p>System Management & Analytics</p>
                </div>
                
                <div class="role-card" onclick="selectRole('lecturer')">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <h2>Lecturer</h2>
                    <p>Manage Classes & Attendance</p>
                </div>
            </div>
        </div>

        <!-- Lecturer Selection -->
        <div id="lecturerSelection" class="view-section">
            <div style="text-align: center; margin-bottom: 3rem;">
                <button class="back-btn" onclick="showView('roleSelection')">
                    <i class="fas fa-chevron-left"></i> Change Role
                </button>
                <h2 style="color: white; font-size: 2.5rem; font-weight: 800; letter-spacing: -0.04em;">Select your Profile</h2>
            </div>
            
            <?php if (empty($lecturers)): ?>
                <div class="login-box-centered">
                    <i class="fas fa-info-circle" style="font-size: 3rem; color: #3b82f6; margin-bottom: 1rem;"></i>
                    <h2 style="color: white;">No Profiles Found</h2>
                    <p style="color: rgba(255,255,255,0.5);">Please contact system administrator to create your profile.</p>
                </div>
            <?php else: ?>
                <div class="lecturer-grid">
                    <?php foreach ($lecturers as $lecturer): ?>
                        <div class="lecturer-card">
                            <div class="lecturer-avatar">
                                <?php 
                                    $initials = '';
                                    $parts = explode(' ', $lecturer['name']);
                                    foreach ($parts as $part) {
                                        if (strlen($part) > 0) $initials .= $part[0];
                                        if (strlen($initials) >= 2) break;
                                    }
                                    echo strtoupper($initials);
                                ?>
                            </div>
                            <h3><?php echo htmlspecialchars($lecturer['name']); ?></h3>
                            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                <button class="btn-action login" onclick="selectLecturer('<?php echo htmlspecialchars($lecturer['username'] ?? ''); ?>', '<?php echo htmlspecialchars($lecturer['name']); ?>')">
                                    <i class="fas fa-fingerprint"></i> Sign In
                                </button>
                                <a href="public_attendance_view.php?lecturer_id=<?php echo $lecturer['id']; ?>" class="btn-action view">
                                    <i class="fas fa-eye"></i> View Only
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Login Form -->
        <div id="loginFormSection" class="view-section">
            <div class="login-box-centered">
                <div style="text-align: left;">
                    <button class="back-btn" onclick="goBackFromLogin()">
                        <i class="fas fa-chevron-left"></i> Back
                    </button>
                </div>
                
                <div class="selected-user-info">
                    <div id="selectedUserIconContainer">
                        <i id="selectedUserIcon" class="fas fa-user-circle selected-user-icon"></i>
                    </div>
                    <h2 id="selectedUserName">User Name</h2>
                    <p>Security Verification Required</p>
                </div>

                <form method="POST" action="" id="loginForm">
                    <input type="hidden" name="username" id="usernameInput">
                    
                    <div style="margin-bottom: 2.5rem;">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-control" 
                            placeholder="····"
                            required
                        >
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1.25rem; font-size: 1.1rem; border-radius: 18px; box-shadow: 0 15px 30px -10px rgba(99, 102, 241, 0.4);">
                        Confirm & Access <i class="fas fa-arrow-right" style="margin-left: 0.5rem;"></i>
                    </button>
                </form>
            </div>
        </div>

    </div>
    
    <script>
        const views = {
            roleSelection: document.getElementById('roleSelection'),
            lecturerSelection: document.getElementById('lecturerSelection'),
            loginFormSection: document.getElementById('loginFormSection')
        };
        
        let previousView = 'roleSelection';

        function showView(viewName) {
            Object.values(views).forEach(el => el.classList.remove('active'));
            views[viewName].classList.add('active');
        }

        function selectRole(role) {
            if (role === 'admin') {
                prepareLogin('admin', 'System Admin', 'fas fa-user-shield');
                previousView = 'roleSelection';
                showView('loginFormSection');
            } else {
                showView('lecturerSelection');
            }
        }

        function selectLecturer(username, name) {
            prepareLogin(username, name, 'fas fa-user-circle');
            previousView = 'lecturerSelection';
            showView('loginFormSection');
        }

        function prepareLogin(username, displayName, iconClass) {
            document.getElementById('usernameInput').value = username;
            document.getElementById('selectedUserName').textContent = displayName;
            document.getElementById('selectedUserIcon').className = iconClass + ' selected-user-icon';
            document.getElementById('password').value = '';
            setTimeout(() => document.getElementById('password').focus(), 500);
        }

        function goBackFromLogin() {
            showView(previousView);
        }

        <?php if ($error): ?>
            <?php if (isset($_POST['username']) && $_POST['username'] === 'admin'): ?>
                selectRole('admin');
            <?php endif; ?>
        <?php endif; ?>
    </script>
</body>
</html>

</body>
</html>
