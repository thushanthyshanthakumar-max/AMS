# 🎓 Student Attendance Management System - Quick Start Guide

## 📋 Overview

A complete attendance management system for the University of Jaffna Pre-Semester Programme with **459 students** across **10 groups**.

## 🚀 Installation (3 Simple Steps)

### Step 1: Create Database
Open your terminal/command prompt and run:

```bash
cd C:\xampp\htdocs\AMS
mysql -u root -p < database_setup.sql
```

Or use phpMyAdmin:
1. Open `http://localhost/phpmyadmin`
2. Click "Import"
3. Select `database_setup.sql`
4. Click "Go"

### Step 2: Import Student Data
Choose your preferred method:

**Method A: Web Interface** (Easiest)
1. Open `http://localhost/AMS`
2. Login: `admin` / `admin123`
3. Go to `http://localhost/AMS/admin/import_students.php`
4. Click "Import All Students"
5. Done! ✅

**Method B: Command Line**
```bash
mysql -u root -p attendance_system < students_data.sql
```

**Method C: phpMyAdmin**
1. Select `attendance_system` database
2. Import → Choose `students_data.sql` → Go

### Step 3: Access the System
Open your browser and go to:
```
http://localhost/AMS
```

## 🔑 Login Credentials

### Admin Account
- **URL**: `http://localhost/AMS/login.php`
- **Username**: `admin`
- **Password**: `admin123`

### Teacher Accounts (10 available)
- **Usernames**: `teacher1`, `teacher2`, ... `teacher10`
- **Password**: `admin123` (same for all)

## 📊 What's Included

### ✅ Pre-configured Data

**10 Teachers**
- Each assigned to one group
- Ready to mark attendance

**10 Groups**
1. GROUP-01 (1P Physics) - 46 students
2. GROUP-02 (2P Physics) - 46 students
3. GROUP-03 (3P Physics) - 46 students
4. GROUP-04 (4P Physics) - 46 students
5. GROUP-05 (SWC) - 46 students
6. GROUP-06 (1B Botany) - 46 students
7. GROUP-07 (2B Botany) - 46 students
8. GROUP-08 (4M Mathematics) - 46 students
9. GROUP-09 (FSL Fisheries) - 46 students
10. GROUP-10 (CSH Computer Science) - 45 students

**459 Students**
- All with registration numbers (e.g., 2024/CSC/001)
- Titles (MR/MISS/MRS)
- Names with initials
- Group assignments

## 🎯 Main Features

### Admin Module
- 📊 Dashboard with statistics
- 👥 Manage Teachers
- 📚 Manage Groups
- 🎓 Manage Students
- ⏰ Manage Class Partitions
- 📈 View Reports
- 📥 Bulk Import Students

### Teacher Module
- 📊 Dashboard
- ✅ Mark Attendance
- 👨‍🎓 View Students
- 📊 Generate Reports

## 💡 How to Use

### For Admins

1. **View Dashboard**
   - See total students, teachers, groups
   - Quick statistics

2. **Manage Students**
   - Add/Edit/Delete students
   - Search and filter
   - View by group

3. **Import Students**
   - Go to Import Students page
   - Click import button
   - All 459 students added instantly

4. **Setup Partitions**
   - Create class schedules
   - Assign time slots
   - Set days of week

### For Teachers

1. **Mark Attendance**
   - Select your group
   - Choose partition (class session)
   - Select date
   - Mark Present/Absent/Late

2. **View Students**
   - See all students in your group
   - Search by name or reg number

3. **Generate Reports**
   - Filter by date range
   - Export attendance data

## 🎨 UI Features

### Modern Design
- Purple gradient background
- Clean white cards
- Smooth animations
- Responsive layout

### Student Display
- **Registration Number**: Prominently displayed
- **Title Badges**: 
  - MR → Blue badge
  - MISS → Pink badge
  - MRS → Pink badge
- **Group Info**: Clear group assignment
- **Actions**: Edit and Delete buttons

### Forms
- Clean, intuitive inputs
- Dropdown for titles
- Group selection
- Form validation

## 🔧 Troubleshooting

### "Database connection failed"
**Solution**: Check if MySQL is running in XAMPP

### "Table doesn't exist"
**Solution**: Run `database_setup.sql` first

### "Can't login"
**Solution**: 
- Username: `admin` (lowercase)
- Password: `admin123`
- Clear browser cache

### "Import failed"
**Solution**:
- Make sure groups are created first
- Check file permissions
- Use phpMyAdmin as alternative

## 📁 File Structure

```
AMS/
├── admin/                  # Admin pages
│   ├── dashboard.php
│   ├── students.php       # ✨ Updated
│   ├── import_students.php # ✨ New
│   ├── teachers.php
│   ├── groups.php
│   └── partitions.php
├── teacher/               # Teacher pages
│   ├── dashboard.php
│   ├── mark_attendance.php
│   └── reports.php
├── includes/              # Shared files
│   ├── config.php
│   ├── functions.php
│   └── header.php
├── css/
│   └── style.css         # ✨ Updated
├── database_setup.sql    # ✨ Updated schema
├── students_data.sql     # ✨ Complete dataset
└── index.php
```

## 📞 Support

### Common Issues

**Q: Where is the import page?**
A: `http://localhost/AMS/admin/import_students.php`

**Q: How to reset password?**
A: Update in database or recreate user

**Q: Can I add more students?**
A: Yes! Use the "Add Student" button

**Q: How to backup data?**
A: Export database from phpMyAdmin

## ✅ Checklist

Before using the system:

- [ ] XAMPP is running (Apache + MySQL)
- [ ] Database created (`database_setup.sql` imported)
- [ ] Students imported (`students_data.sql` imported)
- [ ] Can access `http://localhost/AMS`
- [ ] Can login as admin
- [ ] Can see students in the list

## 🎉 You're Ready!

The system is now fully configured and ready to use. You have:

✅ 10 Teachers
✅ 10 Groups  
✅ 459 Students
✅ Modern UI
✅ Complete functionality

Start by logging in as admin and exploring the dashboard!

---

**Need Help?** Check the `SETUP_COMPLETE.md` file for detailed documentation.

**System Version**: 2.0
**Last Updated**: December 2024
**Status**: 🟢 Production Ready
