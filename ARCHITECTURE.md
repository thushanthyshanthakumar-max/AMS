# System Architecture & Flow Documentation

## System Overview

The Student Attendance Management System is a role-based web application with two primary user types: **Admin** and **Teacher**.

## Architecture Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                    USER INTERFACE (Browser)                  │
│  ┌──────────────┐              ┌──────────────┐            │
│  │  Admin Panel │              │ Teacher Panel│            │
│  └──────────────┘              └──────────────┘            │
└─────────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│                  APPLICATION LAYER (PHP)                     │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  Authentication & Session Management                  │  │
│  └──────────────────────────────────────────────────────┘  │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  Business Logic (CRUD Operations)                     │  │
│  │  - Groups  - Teachers  - Students  - Partitions      │  │
│  │  - Attendance  - Reports  - Access Control           │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│                   DATA LAYER (MySQL)                         │
│  ┌──────┐  ┌──────┐  ┌──────┐  ┌──────┐  ┌──────┐         │
│  │Users │  │Groups│  │Stdnts│  │Partns│  │Attndc│         │
│  └──────┘  └──────┘  └──────┘  └──────┘  └──────┘         │
└─────────────────────────────────────────────────────────────┘
```

## User Flow Diagrams

### Admin Workflow

```
Login → Dashboard → Choose Action
                      │
        ┌─────────────┼─────────────┬─────────────┬──────────────┐
        ▼             ▼             ▼             ▼              ▼
   Create Group  Add Teacher  Add Student  Set Partitions  View Reports
        │             │             │             │              │
        ▼             ▼             ▼             ▼              ▼
   Assign        Create User   Assign to    Define Times   Filter & View
   Teachers      Account       Group        & Days         Attendance
```

### Teacher Workflow

```
Login → Dashboard → Choose Action
                      │
        ┌─────────────┼─────────────┬──────────────┐
        ▼             ▼             ▼              ▼
   Mark          View           Generate      View
   Attendance    Students       Reports       Schedule
        │             │             │              │
        ▼             ▼             ▼              ▼
   Select        See Details   Select Range   See Upcoming
   Group/Date    & History     & Export       Partitions
```

## Database Relationships

```
users (1) ──────────── (1) teachers
  │                           │
  │                           │
  │                      (M) group_assignments (M)
  │                           │
  │                           │
  └──────────────────────── groups (1)
                              │
                              │
                         (M) students (1)
                              │         │
                              │         │
                         (M) partitions │
                              │         │
                              └────┬────┘
                                   │
                                   ▼
                              attendance
```

### Relationship Details:
- **1:1** - users ↔ teachers (One user account per teacher)
- **M:M** - teachers ↔ groups (via group_assignments)
- **1:M** - groups → students (One group has many students)
- **1:M** - groups → partitions (One group has many partitions)
- **M:1** - attendance → students (Many records per student)
- **M:1** - attendance → partitions (Many records per partition)

## File Structure & Responsibilities

### Core Files
```
includes/
├── config.php          → Database connection & constants
├── functions.php       → Utility functions & helpers
├── header.php          → Common navigation & HTML head
└── footer.php          → Common footer & scripts
```

### Admin Module
```
admin/
├── dashboard.php       → Statistics & overview
├── groups.php          → Group CRUD & teacher assignment
├── teachers.php        → Teacher CRUD & user creation
├── students.php        → Student CRUD & group assignment
├── partitions.php      → Partition CRUD with dynamic forms
└── attendance.php      → View all attendance records
```

### Teacher Module
```
teacher/
├── dashboard.php       → Personal statistics & schedule
├── mark_attendance.php → Mark student attendance
├── students.php        → View assigned students
└── reports.php         → Generate & export reports
```

### Assets
```
css/
└── style.css          → All styling (responsive, modern UI)

js/
├── main.js            → Common utilities & interactions
├── partitions.js      → Dynamic partition field management
└── attendance.js      → Attendance marking helpers
```

## Security Layers

```
┌─────────────────────────────────────────┐
│  1. Input Validation (Client-side JS)   │
└─────────────────────────────────────────┘
                  ▼
┌─────────────────────────────────────────┐
│  2. Session Authentication               │
└─────────────────────────────────────────┘
                  ▼
┌─────────────────────────────────────────┐
│  3. Role-Based Access Control            │
└─────────────────────────────────────────┘
                  ▼
┌─────────────────────────────────────────┐
│  4. Input Sanitization (Server-side)     │
└─────────────────────────────────────────┘
                  ▼
┌─────────────────────────────────────────┐
│  5. Prepared Statements (SQL Injection)  │
└─────────────────────────────────────────┘
                  ▼
┌─────────────────────────────────────────┐
│  6. Password Hashing (bcrypt)            │
└─────────────────────────────────────────┘
```

## Data Flow: Marking Attendance

```
1. Teacher Login
   └─→ Session Created with user_id, role, username

2. Navigate to Mark Attendance
   └─→ Load assigned groups (via group_assignments)

3. Select Group
   └─→ Load partitions for selected group

4. Select Partition & Date
   └─→ Load students in group
   └─→ Load existing attendance (if any)

5. Mark Status (Present/Absent/Late)
   └─→ Add optional notes

6. Submit Form
   └─→ Validate: Teacher has access to group
   └─→ Begin transaction
   └─→ Insert/Update attendance records
   └─→ Commit transaction
   └─→ Show success message

7. View Confirmation
   └─→ Attendance saved with timestamp
```

## Report Generation Flow

```
1. Select Parameters
   ├─→ Group
   ├─→ Start Date
   └─→ End Date

2. Query Database
   ├─→ Get all students in group
   ├─→ Count attendance by status
   └─→ Calculate percentages

3. Display Results
   ├─→ Overall statistics
   ├─→ Student-wise breakdown
   └─→ Attendance percentages

4. Export Option
   └─→ Generate CSV file
       ├─→ Headers: Name, Email, Partition, Date, Status
       └─→ Download to user's device
```

## Session Management

```
Login Success
   └─→ $_SESSION['user_id'] = user ID
   └─→ $_SESSION['username'] = username
   └─→ $_SESSION['role'] = 'admin' or 'teacher'
   └─→ $_SESSION['last_activity'] = current timestamp

Every Page Load
   └─→ Check if session exists
   └─→ Check if timeout (30 minutes)
   └─→ Update last_activity
   └─→ Verify role for page access

Logout
   └─→ session_unset()
   └─→ session_destroy()
   └─→ Redirect to login
```

## Key Features Implementation

### Dynamic Partition Creation
- JavaScript adds/removes partition fields dynamically
- Each partition has: name, start time, end time, day of week
- Multiple partitions can be created in one submission
- Server-side validation ensures data integrity

### Role-Based Access Control
- `requireAdmin()` - Ensures only admins access admin pages
- `requireTeacher()` - Ensures only teachers access teacher pages
- `teacherHasAccessToGroup()` - Verifies teacher assignment
- Automatic redirection on unauthorized access

### Attendance Marking
- AJAX-ready for future enhancements
- Bulk actions (Mark All Present/Absent)
- Visual feedback on changes
- Duplicate prevention (unique constraint)
- Update existing records seamlessly

### Report Export
- CSV format for Excel compatibility
- Customizable date ranges
- Student-wise statistics
- Overall group statistics
- Download with proper headers

## Performance Optimizations

1. **Database Indexes**
   - Primary keys on all tables
   - Foreign key indexes
   - Composite indexes on frequently queried columns

2. **Query Optimization**
   - JOIN operations for related data
   - COUNT aggregations in single queries
   - Prepared statement caching

3. **Frontend**
   - CSS compression ready
   - JavaScript event delegation
   - Minimal DOM manipulation
   - Responsive images

4. **Caching**
   - Browser caching via .htaccess
   - Static asset expiration headers
   - Session data caching

## Error Handling

```
Try-Catch Blocks
   └─→ PDO Exceptions caught
   └─→ Transaction rollback on error
   └─→ User-friendly error messages
   └─→ Logging to error_log.txt
   └─→ No sensitive data exposed
```

## Future Scalability

The system is designed to scale:
- **Horizontal**: Add more teachers, groups, students
- **Vertical**: Add more features (email, SMS, analytics)
- **Integration**: API endpoints can be added
- **Mobile**: Responsive design ready for mobile apps
- **Multi-tenant**: Can be extended for multiple schools

## Maintenance

Regular maintenance tasks:
1. Database backups (weekly recommended)
2. Session cleanup (automatic via PHP)
3. Log file rotation
4. Security updates for PHP/MySQL
5. Password policy enforcement
