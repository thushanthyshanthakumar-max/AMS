# Student Attendance Management System

A comprehensive web-based attendance management system built with PHP, MySQL, HTML, CSS, and JavaScript. This system supports role-based access control for Admins and Lecturers with full CRUD operations and reporting capabilities.

## Features

### Admin Features
- **Dashboard**: Overview with statistics (groups, lecturers, students, partitions)
- **Group Management**: Create, edit, delete groups and assign lecturers/students
- **Lecturer Management**: Add lecturers with auto-generated passwords, manage accounts
- **Student Management**: Add students, assign to groups, manage records
- **Partition Management**: Create dynamic class sessions (e.g., Morning/Evening) with specific times and days
- **Attendance Viewing**: View all attendance records with filtering
- **Full Access Control**: Admins can view and manage all data

### Lecturer Features
- **Dashboard**: View assigned groups, statistics, and scheduled partitions
- **Mark Attendance**: Mark student attendance for assigned groups and partitions
- **Student Management**: View students in assigned groups with attendance history
- **Reports**: Generate attendance reports with date ranges and export to CSV
- **Access Control**: Lecturers can only access their assigned groups and students

## Technology Stack

- **Backend**: PHP 7.4+ with PDO
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Security**: Password hashing, prepared statements, session management
- **Design**: Responsive design with modern UI/UX

## Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- phpMyAdmin (optional, for database management)

### Step 1: Database Setup

1. Create a new MySQL database:
```sql
CREATE DATABASE attendance_system;
```

2. Import the database schema:
```bash
mysql -u root -p attendance_system < database_setup.sql
```

Or use phpMyAdmin to import `database_setup.sql`

### Step 2: Configure Database Connection

Edit `includes/config.php` and update the database credentials:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'attendance_system');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

### Step 3: Web Server Configuration

#### For Apache (XAMPP/WAMP)
1. Copy the project folder to `htdocs` directory
2. Access via: `http://localhost/AMS`

#### For XAMPP
1. Place the project in `C:\xampp\htdocs\AMS`
2. Start Apache and MySQL from XAMPP Control Panel
3. Access: `http://localhost/AMS`

#### For Production Server
1. Upload files to your web root directory
2. Ensure proper file permissions (755 for directories, 644 for files)
3. Update the base URL in configuration if needed

### Step 4: Access the System

Navigate to your installation URL:
```
http://localhost/AMS/login.php
```

## Default Login Credentials

### Admin Account
- **Username**: `admin`
- **Password**: `admin123`

### Lecturer Account (Sample)
- **Username**: `lecturer1`
- **Password**: `admin123`

**Important**: Change these passwords immediately after first login!

## Project Structure

```
AMS/
├── admin/                  # Admin pages
│   ├── dashboard.php
│   ├── groups.php
│   ├── lecturers.php
│   ├── students.php
│   ├── partitions.php
│   └── attendance.php
├── lecturer/                # Lecturer pages
│   ├── dashboard.php
│   ├── mark_attendance.php
│   ├── students.php
│   └── reports.php
├── includes/               # Core files
│   ├── config.php         # Database configuration
│   ├── functions.php      # Utility functions
│   ├── header.php         # Common header
│   └── footer.php         # Common footer
├── css/                    # Stylesheets
│   └── style.css
├── js/                     # JavaScript files
│   ├── main.js
│   ├── partitions.js
│   └── attendance.js
├── database_setup.sql      # Database schema
├── login.php              # Login page
├── logout.php             # Logout script
├── index.php              # Entry point
└── README.md              # This file
```

## Usage Guide

### For Administrators

1. **Create Groups**
   - Navigate to Groups page
   - Click "Create Group"
   - Enter group name (e.g., "Class 10A")

2. **Add Lecturers**
   - Navigate to Lecturers page
   - Click "Add Lecturer"
   - Fill in lecturer details
   - System generates username and password
   - Provide credentials to lecturer

3. **Add Students**
   - Navigate to Students page
   - Click "Add Student"
   - Fill in student details and select group

4. **Assign Lecturers to Groups**
   - Navigate to Groups page
   - Click "View" on a group
   - Select lecturer from dropdown and click "Assign Lecturer"

5. **Create Partitions**
   - Navigate to Partitions page
   - Click "Create Partitions"
   - Select group
   - Add multiple partitions with:
     - Name (e.g., "Morning Class")
     - Start time
     - End time
     - Day of week

### For Lecturers

1. **Mark Attendance**
   - Navigate to "Mark Attendance"
   - Select group, partition, and date
   - Mark each student as Present/Absent/Late
   - Add optional notes
   - Click "Save Attendance"

2. **View Students**
   - Navigate to "Students"
   - Filter by group or view all
   - Click "View Details" to see attendance history

3. **Generate Reports**
   - Navigate to "Reports"
   - Select group and date range
   - Click "Generate Report"
   - View statistics or export to CSV

## Database Schema

### Tables
- **users**: Admin and lecturer accounts
- **lecturers**: Lecturer profile information
- **groups**: Class/group definitions
- **students**: Student information
- **group_assignments**: Lecturer-to-group assignments
- **partitions**: Class session schedules
- **attendance**: Attendance records

## Security Features

- Password hashing using PHP's `password_hash()`
- SQL injection prevention using PDO prepared statements
- Session-based authentication
- Role-based access control (RBAC)
- Input sanitization and validation
- Session timeout (30 minutes)
- CSRF protection through form validation

## Browser Compatibility

- Chrome (recommended)
- Firefox
- Safari
- Edge
- Opera

## Troubleshooting

### Database Connection Error
- Check database credentials in `includes/config.php`
- Ensure MySQL service is running
- Verify database exists

### Login Issues
- Clear browser cache and cookies
- Check if session support is enabled in PHP
- Verify user credentials in database

### Permission Errors
- Ensure proper file permissions
- Check PHP error logs
- Verify database user has proper privileges

## Development

### Adding New Features
1. Follow the existing code structure
2. Use PDO for database operations
3. Implement proper error handling
4. Add appropriate access controls
5. Update documentation

### Code Standards
- Use meaningful variable names
- Comment complex logic
- Sanitize all user inputs
- Use prepared statements for queries

## Support

For issues or questions:
1. Check the troubleshooting section
2. Review PHP error logs
3. Verify database schema is correct
4. Ensure all files are properly uploaded

## License

This project is open-source and available for educational purposes.

## Credits

Developed as a comprehensive student attendance management solution.

## Version History

### Version 1.1.0 (Current)
- Refactored 'Teacher' to 'Lecturer' across the entire system
- Updated database schema, file structure, and documentation
- Consistent terminology in UI and backend

### Version 1.0.0
- Initial release
- Admin and Teacher roles
- Complete CRUD operations
- Attendance marking
- Report generation
- CSV export
- Responsive design

## Future Enhancements

Potential features for future versions:
- Email notifications
- SMS integration
- Mobile app
- Biometric integration
- Advanced analytics
- Parent portal
- Multi-language support
- Dark mode
- API for third-party integrations
"# AMS" 
