<?php
require_once 'includes/functions.php';

start_session();

// Redirect if already logged in
if (is_logged_in()) {
    redirect('dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campus Sports Hub - BUSE</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo">Campus Sports Hub <span>BUSE</span></div>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php">Register</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <section class="hero">
        <h1>Welcome to Campus Sports Hub</h1>
        <p>Bindura University of Science and Sports - Official Sports Management System</p>
        <a href="register.php" class="btn btn-primary">Get Started</a>
        <a href="login.php" class="btn btn-secondary" style="margin-left: 1rem;">Login</a>
    </section>

    <div class="container">
        <div class="grid">
            <div class="card">
                <div class="card-header">
                    <h2>For Students</h2>
                </div>
                <p>Register as a student to join teams, view leagues, and track match results. Create your own team and become a captain to lead your squad to victory.</p>
                <br>
                <a href="register.php" class="btn btn-primary">Register Now</a>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>For Team Captains</h2>
                </div>
                <p>Create and manage your team, invite players, request league entry, and track your team's performance throughout the season.</p>
                <br>
                <a href="register.php" class="btn btn-success">Create Team</a>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>For Admins</h2>
                </div>
                <p>Manage leagues, approve team entries, create fixtures, enter match results, and generate reports. Keep the sports program running smoothly.</p>
                <br>
                <a href="login.php" class="btn btn-secondary">Admin Login</a>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Features</h2>
            </div>
            <ul style="list-style-position: inside; line-height: 2;">
                <li><strong>Team Management:</strong> Create teams, invite players, manage rosters</li>
                <li><strong>League System:</strong> Join campus leagues, track standings</li>
                <li><strong>Match Fixtures:</strong> View scheduled matches and results</li>
                <li><strong>Live Standings:</strong> Automatic points calculation and rankings</li>
                <li><strong>History Archive:</strong> Browse past seasons and results</li>
                <li><strong>Role-Based Access:</strong> Student, Captain, and Admin roles</li>
            </ul>
        </div>
    </div>

    <footer>
        <p>&copy; 2026 Campus Sports Hub - Bindura University of Science and Sports</p>
    </footer>
</body>
</html>
