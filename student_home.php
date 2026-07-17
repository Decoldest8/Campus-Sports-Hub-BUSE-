<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

start_session();

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php');
}

// Get user's team memberships
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("
    SELECT tm.*, t.team_name, t.sport_id, s.sport_name, t.captain_id 
    FROM team_members tm 
    JOIN teams t ON tm.team_id = t.team_id 
    JOIN sports s ON t.sport_id = s.sport_id 
    WHERE tm.user_id = ? AND tm.status = 'accepted'
");
$stmt->execute([$user_id]);
$teams = $stmt->fetchAll();

// Get available leagues
$stmt = $pdo->prepare("
    SELECT l.*, s.sport_name 
    FROM leagues l 
    JOIN sports s ON l.sport_id = s.sport_id 
    WHERE l.status IN ('upcoming', 'active')
    ORDER BY l.created_at DESC
");
$stmt->execute();
$leagues = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Home - Campus Sports Hub BUSE</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo">Campus Sports Hub <span>BUSE</span></div>
            <nav>
                <ul>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="leagues.php">Leagues</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></h2>
            </div>
            <p>You are logged in as a <strong>Student</strong></p>
        </div>

        <div class="grid">
            <div class="card">
                <div class="card-header">
                    <h2>My Teams</h2>
                </div>
                <?php if (empty($teams)): ?>
                    <div class="empty-state">
                        <p>You are not a member of any team yet.</p>
                        <p style="margin-top: 1rem;">Ask a team captain to invite you, or register as a captain to create your own team.</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Team Name</th>
                                <th>Sport</th>
                                <th>Role</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($teams as $team): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($team['team_name']); ?></td>
                                    <td><?php echo htmlspecialchars($team['sport_name']); ?></td>
                                    <td>
                                        <?php if ($team['captain_id'] == $user_id): ?>
                                            <span class="badge badge-success">Captain</span>
                                        <?php else: ?>
                                            <span class="badge badge-info">Member</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>Available Leagues</h2>
                </div>
                <?php if (empty($leagues)): ?>
                    <div class="empty-state">
                        <p>No active leagues at the moment.</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>League</th>
                                <th>Sport</th>
                                <th>Season</th>
                                <th>Status</th>
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
                                        <span class="badge badge-<?php echo $league['status'] == 'active' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst($league['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="league_view.php?id=<?php echo $league['league_id']; ?>" class="btn btn-sm btn-primary">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Quick Actions</h2>
            </div>
            <div class="grid">
                <a href="leagues.php" class="btn btn-primary" style="text-align: center;">View All Leagues</a>
                <?php if (empty($teams)): ?>
                    <a href="register.php" class="btn btn-success" style="text-align: center;">Register as Captain</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer>
        <p>&copy; 2026 Campus Sports Hub - Bindura University of Science and Sports</p>
    </footer>
</body>
</html>
