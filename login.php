<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('admin/dashboard.php');
    } else {
        redirect('teacher/dashboard.php');
    }
}

// Fetch teachers for the login selection
try {
    $stmt = $pdo->query("SELECT t.name, u.username FROM teachers t JOIN users u ON t.user_id = u.id ORDER BY t.name ASC");
    $teachers = $stmt->fetchAll();
} catch (PDOException $e) {
    $teachers = [];
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
                    redirect('teacher/dashboard.php');
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
        .login-wrapper {
            width: 100%;
            max-width: 900px;
            padding: 2rem;
        }
        
        .role-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            animation: slideUp 0.5s ease-out;
        }

        .role-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: 3rem;
            text-align: center;
            box-shadow: var(--shadow-xl);
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            border: 2px solid transparent;
        }

        .role-card:hover {
            transform: translateY(-10px);
            border-color: var(--primary-color);
        }
        
        .role-card i {
            font-size: 5rem;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            transition: transform 0.3s ease;
        }

        .role-card:hover i {
            transform: scale(1.1);
        }

        .role-card h2 {
            font-size: 2rem;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }
        
        .role-card p {
            color: var(--text-secondary);
        }

        /* Teacher selection specific */
        .teacher-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .teacher-card {
            background: white;
            padding: 1.5rem;
            border-radius: var(--radius-md);
            text-align: center;
            box-shadow: var(--shadow-md);
            cursor: pointer;
            transition: var(--transition);
            border: 2px solid var(--border-color);
        }

        .teacher-card:hover {
            border-color: var(--primary-color);
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .teacher-avatar {
            width: 80px;
            height: 80px;
            background: var(--primary-light);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 1rem;
        }

        /* Transitions */
        .view-section {
            display: none;
            animation: fadeIn 0.4s ease-out;
        }

        .view-section.active {
            display: block;
        }

        .back-btn {
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 1.1rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            padding: 0.5rem 0;
        }

        .back-btn:hover {
            color: var(--primary-color);
        }

        .login-box-centered {
            max-width: 450px;
            margin: 0 auto;
            background: white;
            padding: 3rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-xl);
            text-align: center;
        }

        .selected-user-info {
            margin-bottom: 2rem;
        }

        .selected-user-icon {
            font-size: 4rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-wrapper">
            
            <div class="login-header">
                <i class="fas fa-graduation-cap"></i>
                <h1><?php echo APP_NAME; ?></h1>
                <p>Attendance Management System</p>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-danger" style="max-width: 500px; margin: 0 auto 2rem;">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <!-- View 1: Initial Role Selection -->
            <div id="roleSelection" class="view-section active">
                <div class="role-cards">
                    <!-- Admin Card -->
                    <div class="role-card" onclick="selectRole('admin')">
                        <i class="fas fa-user-shield"></i>
                        <h2>Admin</h2>
                        <p>Login to manage system</p>
                    </div>
                    
                    <!-- Teacher Card -->
                    <div class="role-card" onclick="selectRole('teacher')">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <h2>Teacher</h2>
                        <p>Login to mark attendance</p>
                    </div>
                </div>
            </div>

            <!-- View 2: Teacher Selection -->
            <div id="teacherSelection" class="view-section">
                <button class="back-btn" onclick="showView('roleSelection')">
                    <i class="fas fa-arrow-left"></i> Back to Roles
                </button>
                <h2 style="text-align: center; color: white; margin-bottom: 1rem;">Select Profile</h2>
                
                <?php if (empty($teachers)): ?>
                    <div class="alert alert-info text-center">
                        No teachers found. Please login as Admin to add teachers.
                    </div>
                <?php else: ?>
                    <div class="teacher-grid">
                        <?php foreach ($teachers as $teacher): ?>
                            <div class="teacher-card" onclick="selectTeacher('<?php echo htmlspecialchars($teacher['username']); ?>', '<?php echo htmlspecialchars($teacher['name']); ?>')">
                                <div class="teacher-avatar">
                                    <?php 
                                        $initials = '';
                                        $parts = explode(' ', $teacher['name']);
                                        foreach ($parts as $part) {
                                            if (strlen($part) > 0) $initials .= $part[0];
                                            if (strlen($initials) >= 2) break;
                                        }
                                        echo strtoupper($initials);
                                    ?>
                                </div>
                                <h3><?php echo htmlspecialchars($teacher['name']); ?></h3>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- View 3: Login Form -->
            <div id="loginFormSection" class="view-section">
                <div class="login-box-centered">
                    <button class="back-btn" onclick="goBackFromLogin()">
                        <i class="fas fa-arrow-left"></i> Back
                    </button>
                    
                    <div class="selected-user-info">
                        <i id="selectedUserIcon" class="fas fa-user-circle selected-user-icon"></i>
                        <h2 id="selectedUserName">User Name</h2>
                        <p class="text-secondary">Enter your password to continue</p>
                    </div>

                    <form method="POST" action="" id="loginForm">
                        <input type="hidden" name="username" id="usernameInput">
                        
                        <div class="form-group">
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                class="form-control" 
                                placeholder="Password"
                                required
                                style="text-align: center; font-size: 1.2rem; letter-spacing: 2px;"
                            >
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-block btn-lg">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>
    
    <script>
        // State management
        const views = {
            roleSelection: document.getElementById('roleSelection'),
            teacherSelection: document.getElementById('teacherSelection'),
            loginFormSection: document.getElementById('loginFormSection')
        };
        
        let previousView = 'roleSelection';

        function showView(viewName) {
            // Hide all views
            Object.values(views).forEach(el => el.classList.remove('active'));
            // Show selected view
            views[viewName].classList.add('active');
        }

        function selectRole(role) {
            if (role === 'admin') {
                prepareLogin('admin', 'Administrator', 'fas fa-user-shield');
                previousView = 'roleSelection';
                showView('loginFormSection');
            } else {
                showView('teacherSelection');
            }
        }

        function selectTeacher(username, name) {
            prepareLogin(username, name, 'fas fa-user-circle');
            previousView = 'teacherSelection';
            showView('loginFormSection');
        }

        function prepareLogin(username, displayName, iconClass) {
            document.getElementById('usernameInput').value = username;
            document.getElementById('selectedUserName').textContent = displayName;
            document.getElementById('selectedUserIcon').className = iconClass + ' selected-user-icon';
            document.getElementById('password').value = '';
            document.getElementById('password').focus();
        }

        function goBackFromLogin() {
            showView(previousView);
        }

        // Handle error state (if PHP returns error on POST)
        <?php if ($error): ?>
            // If there was an error, we need to know where to go back to.
            // Since PHP reloads the page, we might lose state.
            // For now, we will default to Admin login if the username was admin, 
            // but since we don't persist the 'target' easily without more complex logic,
            // we will just show the role selection again or maybe try to infer?
            // Actually, the error message is displayed at the top.
            // A simple enhancement: check the posted username
            <?php if (isset($_POST['username']) && $_POST['username'] === 'admin'): ?>
                selectRole('admin');
            <?php elseif (isset($_POST['username'])): ?>
                // For teachers, we'd need to fuzzy match or just let them pick again.
                // Let's just show the error and stay on role selection to be safe/simple
                // Or better, if it's a teacher login fail, show teacher selection?
            <?php endif; ?>
        <?php endif; ?>
    </script>
</body>
</html>
