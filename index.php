<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Redirect to appropriate dashboard based on role
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('admin/dashboard.php');
    } else {
        redirect('teacher/dashboard.php');
    }
} else {
    redirect('login.php');
}
?>
