# Login Issue - Quick Fix Guide

## Problem: "Invalid username or password" error

This happens because the database hasn't been set up yet or the password hashes are incorrect.

## Solution: Use the Setup Script

### Step 1: Run the Setup Script

1. Make sure XAMPP is running (Apache + MySQL)
2. Open your browser
3. Go to: `http://localhost/AMS/setup.php`
4. Wait for the setup to complete
5. You should see "✓ Setup Complete!"

### Step 2: Login

1. Click "Go to Login Page" or visit: `http://localhost/AMS/login.php`
2. Use these credentials:
   - **Username**: `admin`
   - **Password**: `admin123`

### Step 3: Delete Setup File (Important!)

After successful login, delete the `setup.php` file for security:
```
Delete: c:\vihirthan\002\AMS\setup.php
```

## Alternative: Manual Database Setup

If the setup script doesn't work, use phpMyAdmin:

1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Click "Import" tab
3. Choose file: `database_setup.sql`
4. Click "Go"
5. Try logging in again

## Still Not Working?

### Check 1: Is MySQL Running?
- Open XAMPP Control Panel
- Make sure MySQL is started (green)

### Check 2: Database Credentials
Edit `includes/config.php` and verify:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'attendance_system');
define('DB_USER', 'root');
define('DB_PASS', '');  // Usually empty for XAMPP
```

### Check 3: Database Exists
1. Open phpMyAdmin
2. Look for database named `attendance_system`
3. If not found, run `setup.php` again

### Check 4: Users Table
1. In phpMyAdmin, click `attendance_system` database
2. Click `users` table
3. You should see:
   - admin (role: admin)
   - teacher1 (role: teacher)

## Test Password Hash

Create a file `test_password.php` with this code:

```php
<?php
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "Password: $password<br>";
echo "Hash: $hash<br>";
echo "Verify: " . (password_verify($password, $hash) ? 'SUCCESS' : 'FAILED');
?>
```

Run it: `http://localhost/AMS/test_password.php`

If it shows "Verify: SUCCESS", your PHP password functions work correctly.

## Quick Reset

If all else fails, run these SQL commands in phpMyAdmin:

```sql
DROP DATABASE IF EXISTS attendance_system;
```

Then run `setup.php` again.

## Contact Support

If you're still having issues:
1. Check PHP error logs
2. Check MySQL error logs
3. Verify PHP version is 7.4+
4. Ensure PDO extension is enabled

---

**Most Common Fix**: Just run `http://localhost/AMS/setup.php` and it will fix everything!
