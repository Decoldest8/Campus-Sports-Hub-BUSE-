<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

start_session();

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php');
}

// Role-based routing
$role = $_SESSION['role'];

if ($role == 'admin') {
    redirect('admin_panel.php');
} elseif ($role == 'captain') {
    redirect('team_view.php');
} else {
    redirect('student_home.php');
}
?>
