# Quick Setup Guide

## For XAMPP Users (Windows)

### Step 1: Install XAMPP
1. Download XAMPP from https://www.apachefriends.org/
2. Install XAMPP to `C:\xampp`
3. Start XAMPP Control Panel

### Step 2: Setup Database
1. Start Apache and MySQL from XAMPP Control Panel
2. Open browser and go to: `http://localhost/phpmyadmin`
3. Click "New" to create a new database
4. Name it: `attendance_system`
5. Click "Import" tab
6. Choose file: `database_setup.sql` from the AMS folder
7. Click "Go" to import

### Step 3: Configure the Application
1. Open `includes/config.php`
2. Verify these settings:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'attendance_system');
   define('DB_USER', 'root');
   define('DB_PASS', '');  // Empty for XAMPP default
   ```

### Step 4: Access the System
1. Open browser
2. Go to: `http://localhost/AMS/login.php`
3. Login with:
   - Username: `admin`
   - Password: `admin123`

## For Other Servers

### WAMP
- Place files in: `C:\wamp64\www\AMS`
- Access: `http://localhost/AMS`

### Linux/Apache
- Place files in: `/var/www/html/AMS`
- Set permissions: `chmod -R 755 AMS`
- Access: `http://localhost/AMS`

## First Steps After Login

### As Admin:
1. Change your password (recommended)
2. Create a group (e.g., "Class 10A")
3. Add a lecturer
4. Add students to the group
5. Assign lecturer to the group
6. Create partitions for the group

### As Lecturer:
1. Login with credentials provided by admin
2. View assigned groups
3. Mark attendance for your classes
4. Generate reports

## Common Issues

### "Database connection failed"
- Check if MySQL is running in XAMPP
- Verify database name is `attendance_system`
- Check credentials in `config.php`

### "Page not found"
- Ensure files are in correct directory
- Check URL: should be `http://localhost/AMS/login.php`

### "Session error"
- Clear browser cookies
- Restart Apache in XAMPP

## Need Help?
- Check README.md for detailed documentation
- Review troubleshooting section
- Verify all files are uploaded correctly
