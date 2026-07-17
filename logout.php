<?php
require_once 'includes/functions.php';

start_session();

// Destroy all session data
session_unset();
session_destroy();

// Redirect to home page
redirect('index.php');
?>
