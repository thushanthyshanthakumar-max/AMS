# Railway Deployment Guide for Student Attendance Management System

This guide will walk you through deploying your Student Attendance Management System to Railway.

## Prerequisites

1. **Railway Account**: Sign up at [railway.app](https://railway.app)
2. **Git Repository**: Your code must be in a GitHub, GitLab, or Bitbucket repository
3. **Railway CLI** (Optional): Install from [docs.railway.app/develop/cli](https://docs.railway.app/develop/cli)

## Step-by-Step Deployment

### 1. Prepare Your Repository

#### A. Initialize Git (if not already done)
```bash
cd c:\xampp\htdocs\AMS
git init
git add .
git commit -m "Initial commit - Student Attendance Management System"
```

#### B. Create a GitHub Repository
1. Go to [github.com](https://github.com) and create a new repository
2. Name it `student-attendance-system` or similar
3. **Do NOT** initialize with README (you already have one)

#### C. Push to GitHub
```bash
git remote add origin https://github.com/YOUR_USERNAME/YOUR_REPO_NAME.git
git branch -M main
git push -u origin main
```

### 2. Set Up Railway Project

#### Option A: Using Railway Dashboard (Recommended for Beginners)

1. **Login to Railway**
   - Go to [railway.app](https://railway.app)
   - Click "Login" and authenticate with GitHub

2. **Create New Project**
   - Click "New Project"
   - Select "Deploy from GitHub repo"
   - Choose your `student-attendance-system` repository
   - Railway will automatically detect it's a PHP project

3. **Add MySQL Database**
   - In your project dashboard, click "+ New"
   - Select "Database" → "Add MySQL"
   - Railway will automatically create a MySQL database and set environment variables

4. **Configure Environment Variables**
   - Railway automatically sets these for MySQL:
     - `MYSQLHOST`
     - `MYSQLDATABASE`
     - `MYSQLUSER`
     - `MYSQLPASSWORD`
     - `MYSQLPORT`
   - These match the variables in `includes/config.railway.php`

5. **Update Config File Reference**
   - You need to modify your includes to use `config.railway.php` instead of `config.php` when deployed
   - See "Configuration Updates" section below

#### Option B: Using Railway CLI

```bash
# Install Railway CLI
npm i -g @railway/cli

# Login to Railway
railway login

# Initialize project
railway init

# Link to your GitHub repo
railway link

# Add MySQL database
railway add --database mysql

# Deploy
railway up
```

### 3. Configuration Updates

You need to update your application to use the Railway configuration when deployed.

**Update `includes/config.php`:**

Replace the entire content with:

```php
<?php
/**
 * Database Configuration File
 * Automatically detects Railway environment and uses appropriate config
 */

// Check if running on Railway (Railway sets RAILWAY_ENVIRONMENT variable)
if (getenv('RAILWAY_ENVIRONMENT')) {
    // Use Railway configuration
    require_once __DIR__ . '/config.railway.php';
} else {
    // Use local configuration
    
    // Database configuration
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'attendance_system');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    
    // Application configuration
    define('APP_NAME', 'Student Attendance Management System');
    define('SESSION_TIMEOUT', 1800); // 30 minutes in seconds
    
    // Timezone
    date_default_timezone_set('Asia/Kolkata');
    
    // Database connection using PDO
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}
?>
```

### 4. Import Database Schema

After deployment, you need to import your database schema to Railway's MySQL.

#### Method 1: Using Railway CLI
```bash
# Connect to Railway MySQL
railway connect mysql

# Then run your SQL commands or import the file
# In the MySQL prompt:
source database_setup.sql;
```

#### Method 2: Using MySQL Client
1. Get database credentials from Railway dashboard:
   - Go to your MySQL service
   - Click "Connect"
   - Copy the connection details

2. Connect using MySQL Workbench or command line:
```bash
mysql -h MYSQLHOST -P MYSQLPORT -u MYSQLUSER -p MYSQLDATABASE < database_setup.sql
```

#### Method 3: Create a Setup Script
Access `https://your-app.railway.app/setup.php` (if you want to use the existing setup.php)

**Note**: Make sure to delete or protect `setup.php` after initial setup for security!

### 5. Verify Deployment

1. **Check Build Logs**
   - In Railway dashboard, click on your service
   - Go to "Deployments" tab
   - Check the build logs for any errors

2. **Access Your Application**
   - Railway will provide a URL like `https://your-app.railway.app`
   - Click "Generate Domain" in the Settings tab if not auto-generated
   - Visit the URL to test your application

3. **Test Login**
   - Try logging in with admin credentials
   - Test teacher login
   - Verify all features work correctly

### 6. Post-Deployment Security

1. **Remove Setup File**
   ```bash
   # Delete setup.php or add authentication
   git rm setup.php
   git commit -m "Remove setup file for security"
   git push
   ```

2. **Update .htaccess** (if needed)
   - Railway uses Nginx, so .htaccess might not work
   - You may need to configure redirects differently

3. **Enable HTTPS**
   - Railway automatically provides SSL certificates
   - Ensure all links use HTTPS

4. **Set Production Error Handling**
   - Disable error display in production
   - Log errors to files instead

## Troubleshooting

### Common Issues

1. **Database Connection Failed**
   - Verify environment variables are set correctly
   - Check Railway MySQL service is running
   - Ensure `config.railway.php` is being used

2. **500 Internal Server Error**
   - Check deployment logs in Railway dashboard
   - Verify PHP extensions are installed (see `nixpacks.toml`)
   - Check file permissions

3. **CSS/JS Not Loading**
   - Verify paths are relative, not absolute
   - Check .htaccess rules (may need conversion for Nginx)

4. **Session Issues**
   - Railway uses ephemeral storage
   - Consider using database sessions for persistence

### Viewing Logs
```bash
# Using Railway CLI
railway logs

# Or view in dashboard under "Deployments" → "View Logs"
```

## Updating Your Application

After making changes locally:

```bash
git add .
git commit -m "Description of changes"
git push origin main
```

Railway will automatically detect the push and redeploy your application.

## Cost Considerations

- **Free Tier**: Railway offers $5 free credit per month
- **Usage-Based**: After free tier, you pay for what you use
- **Estimated Cost**: Small PHP + MySQL app typically costs $5-15/month

## Additional Resources

- [Railway Documentation](https://docs.railway.app)
- [Railway PHP Guide](https://docs.railway.app/guides/php)
- [Railway MySQL Guide](https://docs.railway.app/databases/mysql)
- [Railway CLI Reference](https://docs.railway.app/develop/cli)

## Support

If you encounter issues:
1. Check Railway documentation
2. Visit Railway Discord community
3. Check deployment logs for specific errors
4. Review this guide's troubleshooting section
