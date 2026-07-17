<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

start_session();

// Check if user is logged in and is admin
if (!is_logged_in() || !has_role('admin')) {
    redirect('dashboard.php');
}

$message = '';
$message_type = '';
$active_tab = isset($_GET['tab']) ? sanitize($_GET['tab']) : 'leagues';

// Handle league creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_league'])) {
    $league_name = sanitize($_POST['league_name']);
    $sport_id = intval($_POST['sport_id']);
    $season = sanitize($_POST['season']);
    $description = sanitize($_POST['description']);
    
    if (empty($league_name) || empty($sport_id) || empty($season)) {
        $message = 'Please fill in all required fields.';
        $message_type = 'danger';
    } else {
        $stmt = $pdo->prepare("INSERT INTO leagues (league_name, sport_id, season, description, status) VALUES (?, ?, ?, ?, 'upcoming')");
        if ($stmt->execute([$league_name, $sport_id, $season, $description])) {
            $message = 'League created successfully!';
            $message_type = 'success';
        } else {
            $message = 'Failed to create league.';
            $message_type = 'danger';
        }
    }
}

// Handle team approval/rejection
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['handle_team_request'])) {
    $league_team_id = intval($_POST['league_team_id']);
    $action = sanitize($_POST['action']);
    
    if ($action == 'approve') {
        $stmt = $pdo->prepare("UPDATE league_teams SET status = 'approved', approved_at = NOW() WHERE league_team_id = ?");
        $stmt->execute([$league_team_id]);
        
        // Initialize standings for this team in the league
        $stmt = $pdo->prepare("SELECT league_id, team_id FROM league_teams WHERE league_team_id = ?");
        $stmt->execute([$league_team_id]);
        $league_team = $stmt->fetch();
        
        if ($league_team) {
            initialize_league_standings($league_team['league_id']);
        }
        
        $message = 'Team approved for league.';
        $message_type = 'success';
    } elseif ($action == 'reject') {
        $stmt = $pdo->prepare("UPDATE league_teams SET status = 'rejected' WHERE league_team_id = ?");
        $stmt->execute([$league_team_id]);
        $message = 'Team request rejected.';
        $message_type = 'success';
    }
}

// Handle fixture creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_fixture'])) {
    $league_id = intval($_POST['league_id']);
    $home_team_id = intval($_POST['home_team_id']);
    $away_team_id = intval($_POST['away_team_id']);
    $match_date = sanitize($_POST['match_date']);
    $match_time = sanitize($_POST['match_time']);
    $venue = sanitize($_POST['venue']);
    
    if (empty($league_id) || empty($home_team_id) || empty($away_team_id) || empty($match_date)) {
        $message = 'Please fill in all required fields.';
        $message_type = 'danger';
    } elseif ($home_team_id == $away_team_id) {
        $message = 'Home and away teams cannot be the same.';
        $message_type = 'danger';
    } else {
        $stmt = $pdo->prepare("INSERT INTO matches (league_id, home_team_id, away_team_id, match_date, match_time, venue, status) VALUES (?, ?, ?, ?, ?, ?, 'scheduled')");
        if ($stmt->execute([$league_id, $home_team_id, $away_team_id, $match_date, $match_time, $venue])) {
            $message = 'Fixture created successfully!';
            $message_type = 'success';
        } else {
            $message = 'Failed to create fixture.';
            $message_type = 'danger';
        }
    }
}

// Handle result entry
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['enter_result'])) {
    $match_id = intval($_POST['match_id']);
    $home_score = intval($_POST['home_score']);
    $away_score = intval($_POST['away_score']);
    
    // Get match details
    $stmt = $pdo->prepare("SELECT * FROM matches WHERE match_id = ?");
    $stmt->execute([$match_id]);
    $match = $stmt->fetch();
    
    if (!$match) {
        $message = 'Match not found.';
        $message_type = 'danger';
    } else {
        // Update match result
        $stmt = $pdo->prepare("UPDATE matches SET home_score = ?, away_score = ?, status = 'completed' WHERE match_id = ?");
        if ($stmt->execute([$home_score, $away_score, $match_id])) {
            // Update standings for both teams
            update_standings($match['league_id'], $match['home_team_id'], $home_score, $away_score);
            update_standings($match['league_id'], $match['away_team_id'], $away_score, $home_score);
            
            $message = 'Result entered and standings updated!';
            $message_type = 'success';
        } else {
            $message = 'Failed to enter result.';
            $message_type = 'danger';
        }
    }
}

// Handle league status change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_league_status'])) {
    $league_id = intval($_POST['league_id']);
    $status = sanitize($_POST['status']);
    
    $stmt = $pdo->prepare("UPDATE leagues SET status = ? WHERE league_id = ?");
    if ($stmt->execute([$status, $league_id])) {
        $message = 'League status updated.';
        $message_type = 'success';
    } else {
        $message = 'Failed to update league status.';
        $message_type = 'danger';
    }
}

// Get data for dropdowns
$sports = $pdo->query("SELECT * FROM sports ORDER BY sport_name")->fetchAll();
$leagues = $pdo->query("SELECT l.*, s.sport_name FROM leagues l JOIN sports s ON l.sport_id = s.sport_id ORDER BY l.created_at DESC")->fetchAll();
$teams = $pdo->query("SELECT t.*, s.sport_name FROM teams t JOIN sports s ON t.sport_id = s.sport_id ORDER BY t.team_name")->fetchAll();

// Get pending team requests
$pending_requests = $pdo->query("
    SELECT lt.*, t.team_name, l.league_name, l.season 
    FROM league_teams lt 
    JOIN teams t ON lt.team_id = t.team_id 
    JOIN leagues l ON lt.league_id = l.league_id 
    WHERE lt.status = 'pending'
")->fetchAll();

// Get scheduled matches
$scheduled_matches = $pdo->query("
    SELECT m.*, 
           ht.team_name as home_team_name, 
           at.team_name as away_team_name,
           l.league_name 
    FROM matches m 
    JOIN teams ht ON m.home_team_id = ht.team_id 
    JOIN teams at ON m.away_team_id = at.team_id 
    JOIN leagues l ON m.league_id = l.league_id 
    WHERE m.status = 'scheduled'
    ORDER BY m.match_date ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Campus Sports Hub BUSE</title>
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
                    <li><a href="admin_panel.php" style="color: #ffd700;">Admin Panel</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2>Admin Panel - <?php echo htmlspecialchars($_SESSION['full_name']); ?></h2>
            </div>
            <p>You are logged in as <strong>Administrator</strong></p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
        <?php endif; ?>

        <!-- Tab Navigation -->
        <div style="margin-bottom: 2rem;">
            <button class="btn btn-<?php echo $active_tab == 'leagues' ? 'primary' : 'secondary'; ?>" onclick="location.href='admin_panel.php?tab=leagues'">Leagues</button>
            <button class="btn btn-<?php echo $active_tab == 'teams' ? 'primary' : 'secondary'; ?>" onclick="location.href='admin_panel.php?tab=teams'">Team Requests</button>
            <button class="btn btn-<?php echo $active_tab == 'fixtures' ? 'primary' : 'secondary'; ?>" onclick="location.href='admin_panel.php?tab=fixtures'">Fixtures</button>
            <button class="btn btn-<?php echo $active_tab == 'results' ? 'primary' : 'secondary'; ?>" onclick="location.href='admin_panel.php?tab=results'">Results</button>
        </div>

        <?php if ($active_tab == 'leagues'): ?>
            <!-- Create League -->
            <div class="card">
                <div class="card-header">
                    <h2>Create New League</h2>
                </div>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="league_name">League Name</label>
                        <input type="text" id="league_name" name="league_name" required placeholder="e.g., 2026 Semester 1 Football">
                    </div>
                    
                    <div class="form-group">
                        <label for="sport_id">Sport</label>
                        <select id="sport_id" name="sport_id" required>
                            <?php foreach ($sports as $sport): ?>
                                <option value="<?php echo $sport['sport_id']; ?>"><?php echo htmlspecialchars($sport['sport_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="season">Season</label>
                        <input type="text" id="season" name="season" required placeholder="e.g., 2026 Semester 1">
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description (Optional)</label>
                        <textarea id="description" name="description" rows="3"></textarea>
                    </div>
                    
                    <button type="submit" name="create_league" class="btn btn-success">Create League</button>
                </form>
            </div>

            <!-- Manage Leagues -->
            <div class="card">
                <div class="card-header">
                    <h2>Manage Leagues</h2>
                </div>
                <?php if (empty($leagues)): ?>
                    <div class="empty-state">
                        <p>No leagues created yet.</p>
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
                                        <span class="badge badge-<?php 
                                            echo $league['status'] == 'active' ? 'success' : 
                                                 ($league['status'] == 'completed' ? 'info' : 'warning'); 
                                        ?>">
                                            <?php echo ucfirst($league['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" action="" style="display: inline;">
                                            <input type="hidden" name="league_id" value="<?php echo $league['league_id']; ?>">
                                            <select name="status" style="width: auto; padding: 0.5rem;">
                                                <option value="upcoming" <?php echo $league['status'] == 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                                                <option value="active" <?php echo $league['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                                <option value="completed" <?php echo $league['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                            </select>
                                            <button type="submit" name="update_league_status" class="btn btn-sm btn-primary">Update</button>
                                        </form>
                                        <a href="league_view.php?id=<?php echo $league['league_id']; ?>" class="btn btn-sm btn-secondary">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

        <?php elseif ($active_tab == 'teams'): ?>
            <!-- Team Requests -->
            <div class="card">
                <div class="card-header">
                    <h2>Pending Team Requests</h2>
                </div>
                <?php if (empty($pending_requests)): ?>
                    <div class="empty-state">
                        <p>No pending team requests.</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Team</th>
                                <th>League</th>
                                <th>Season</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_requests as $request): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($request['team_name']); ?></td>
                                    <td><?php echo htmlspecialchars($request['league_name']); ?></td>
                                    <td><?php echo htmlspecialchars($request['season']); ?></td>
                                    <td>
                                        <form method="POST" action="" style="display: inline;">
                                            <input type="hidden" name="league_team_id" value="<?php echo $request['league_team_id']; ?>">
                                            <input type="hidden" name="handle_team_request" value="1">
                                            <button type="submit" name="action" value="approve" class="btn btn-sm btn-success">Approve</button>
                                            <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger">Reject</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- All Teams -->
            <div class="card">
                <div class="card-header">
                    <h2>All Teams</h2>
                </div>
                <?php if (empty($teams)): ?>
                    <div class="empty-state">
                        <p>No teams created yet.</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Team Name</th>
                                <th>Sport</th>
                                <th>Captain</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($teams as $team): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($team['team_name']); ?></td>
                                    <td><?php echo htmlspecialchars($team['sport_name']); ?></td>
                                    <td>
                                        <?php 
                                            $stmt = $pdo->prepare("SELECT username FROM users WHERE user_id = ?");
                                            $stmt->execute([$team['captain_id']]);
                                            $captain = $stmt->fetch();
                                            echo htmlspecialchars($captain['username']);
                                        ?>
                                    </td>
                                    <td><?php echo format_date($team['created_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

        <?php elseif ($active_tab == 'fixtures'): ?>
            <!-- Create Fixture -->
            <div class="card">
                <div class="card-header">
                    <h2>Create Fixture</h2>
                </div>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="league_id">League</label>
                        <select id="league_id" name="league_id" required>
                            <?php foreach ($leagues as $league): ?>
                                <option value="<?php echo $league['league_id']; ?>"><?php echo htmlspecialchars($league['league_name'] . ' - ' . $league['season']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="home_team_id">Home Team</label>
                        <select id="home_team_id" name="home_team_id" required>
                            <?php foreach ($teams as $team): ?>
                                <option value="<?php echo $team['team_id']; ?>"><?php echo htmlspecialchars($team['team_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="away_team_id">Away Team</label>
                        <select id="away_team_id" name="away_team_id" required>
                            <?php foreach ($teams as $team): ?>
                                <option value="<?php echo $team['team_id']; ?>"><?php echo htmlspecialchars($team['team_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="match_date">Match Date</label>
                        <input type="date" id="match_date" name="match_date" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="match_time">Match Time (Optional)</label>
                        <input type="time" id="match_time" name="match_time">
                    </div>
                    
                    <div class="form-group">
                        <label for="venue">Venue (Optional)</label>
                        <input type="text" id="venue" name="venue" placeholder="e.g., Main Stadium">
                    </div>
                    
                    <button type="submit" name="create_fixture" class="btn btn-success">Create Fixture</button>
                </form>
            </div>

            <!-- Scheduled Matches -->
            <div class="card">
                <div class="card-header">
                    <h2>Scheduled Matches</h2>
                </div>
                <?php if (empty($scheduled_matches)): ?>
                    <div class="empty-state">
                        <p>No scheduled matches.</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>League</th>
                                <th>Home Team</th>
                                <th>Away Team</th>
                                <th>Venue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($scheduled_matches as $match): ?>
                                <tr>
                                    <td><?php echo format_date($match['match_date']); ?></td>
                                    <td><?php echo htmlspecialchars($match['league_name']); ?></td>
                                    <td><?php echo htmlspecialchars($match['home_team_name']); ?></td>
                                    <td><?php echo htmlspecialchars($match['away_team_name']); ?></td>
                                    <td><?php echo htmlspecialchars($match['venue'] ?: 'TBD'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

        <?php elseif ($active_tab == 'results'): ?>
            <!-- Enter Results -->
            <div class="card">
                <div class="card-header">
                    <h2>Enter Match Results</h2>
                </div>
                <?php if (empty($scheduled_matches)): ?>
                    <div class="empty-state">
                        <p>No scheduled matches to enter results for.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($scheduled_matches as $match): ?>
                        <div class="match-card">
                            <div class="match-teams">
                                <div>
                                    <strong><?php echo htmlspecialchars($match['home_team_name']); ?></strong>
                                </div>
                                <div>vs</div>
                                <div>
                                    <strong><?php echo htmlspecialchars($match['away_team_name']); ?></strong>
                                </div>
                            </div>
                            <div class="match-date">
                                <?php echo format_date($match['match_date']); ?>
                                <?php if ($match['match_time']): ?>
                                    at <?php echo format_time($match['match_time']); ?>
                                <?php endif; ?>
                            </div>
                            <form method="POST" action="" style="margin-top: 1rem;">
                                <input type="hidden" name="match_id" value="<?php echo $match['match_id']; ?>">
                                <input type="hidden" name="enter_result" value="1">
                                <div style="display: flex; gap: 1rem; align-items: center;">
                                    <input type="number" name="home_score" min="0" placeholder="Home Score" required style="width: 100px;">
                                    <span>-</span>
                                    <input type="number" name="away_score" min="0" placeholder="Away Score" required style="width: 100px;">
                                    <button type="submit" class="btn btn-sm btn-success">Submit Result</button>
                                </div>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        <?php endif; ?>
    </div>

    <footer>
        <p>&copy; 2026 Campus Sports Hub - Bindura University of Science and Sports</p>
    </footer>
</body>
</html>
