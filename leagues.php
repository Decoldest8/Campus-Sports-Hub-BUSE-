<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

start_session();

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php');
}

// Get all leagues
$stmt = $pdo->query("
    SELECT l.*, s.sport_name, 
           (SELECT COUNT(*) FROM league_teams WHERE league_id = l.league_id AND status = 'approved') as team_count
    FROM leagues l 
    JOIN sports s ON l.sport_id = s.sport_id 
    ORDER BY l.status DESC, l.created_at DESC
");
$leagues = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leagues - Campus Sports Hub BUSE</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo">Campus Sports Hub <span>BUSE</span></div>
            <nav>
                <ul>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="leagues.php" style="color: #ffd700;">Leagues</a></li>
                    <?php if (has_role('admin')): ?>
                        <li><a href="admin_panel.php">Admin Panel</a></li>
                    <?php endif; ?>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2>All Leagues</h2>
            </div>
            <p>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
        </div>

        <?php if (empty($leagues)): ?>
            <div class="card">
                <div class="empty-state">
                    <p>No leagues have been created yet.</p>
                    <?php if (has_role('admin')): ?>
                        <p style="margin-top: 1rem;"><a href="admin_panel.php" class="btn btn-primary">Create a League</a></p>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <table>
                    <thead>
                        <tr>
                            <th>League Name</th>
                            <th>Sport</th>
                            <th>Season</th>
                            <th>Status</th>
                            <th>Teams</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($leagues as $league): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($league['league_name']); ?></td>
                                <td><?php echo htmlspecialchars($league['sport_name']); ?></td>
                                <td><?php echo htmlspecialchars($league['season']); ?></td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo $league['status'] == 'active' ? 'success' : 
                                             ($league['status'] == 'completed' ? 'info' : 'warning'); 
                                    ?>">
                                        <?php echo ucfirst($league['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo $league['team_count']; ?></td>
                                <td>
                                    <a href="league_view.php?id=<?php echo $league['league_id']; ?>" class="btn btn-sm btn-primary">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h2>League Status Guide</h2>
            </div>
            <ul style="list-style-position: inside; line-height: 2;">
                <li><span class="badge badge-warning">Upcoming</span> - League is scheduled but not yet started</li>
                <li><span class="badge badge-success">Active</span> - League is currently in progress</li>
                <li><span class="badge badge-info">Completed</span> - League has finished (archived)</li>
            </ul>
        </div>
    </div>

    <footer>
        <p>&copy; 2026 Campus Sports Hub - Bindura University of Science and Sports</p>
    </footer>
</body>
</html>
