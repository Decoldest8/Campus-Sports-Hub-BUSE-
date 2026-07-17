<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

start_session();

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// Handle team creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_team'])) {
    $team_name = sanitize($_POST['team_name']);
    $sport_id = intval($_POST['sport_id']);
    
    if (empty($team_name) || empty($sport_id)) {
        $message = 'Please fill in all fields.';
        $message_type = 'danger';
    } else {
        // Check if user already has a team as captain
        $stmt = $pdo->prepare("SELECT team_id FROM teams WHERE captain_id = ?");
        $stmt->execute([$user_id]);
        if ($stmt->fetch()) {
            $message = 'You already have a team. You can only captain one team.';
            $message_type = 'warning';
        } else {
            // Create team
            $stmt = $pdo->prepare("INSERT INTO teams (team_name, captain_id, sport_id) VALUES (?, ?, ?)");
            if ($stmt->execute([$team_name, $user_id, $sport_id])) {
                $team_id = $pdo->lastInsertId();
                // Add captain as team member
                $stmt = $pdo->prepare("INSERT INTO team_members (team_id, user_id, status) VALUES (?, ?, 'accepted')");
                $stmt->execute([$team_id, $user_id]);
                $message = 'Team created successfully!';
                $message_type = 'success';
            } else {
                $message = 'Failed to create team. Please try again.';
                $message_type = 'danger';
            }
        }
    }
}

// Handle player invitation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['invite_player'])) {
    $team_id = intval($_POST['team_id']);
    $invite_username = sanitize($_POST['invite_username']);
    
    if (empty($invite_username)) {
        $message = 'Please enter a username.';
        $message_type = 'danger';
    } else {
        // Find user by username
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
        $stmt->execute([$invite_username]);
        $invited_user = $stmt->fetch();
        
        if (!$invited_user) {
            $message = 'User not found.';
            $message_type = 'danger';
        } elseif ($invited_user['user_id'] == $user_id) {
            $message = 'You cannot invite yourself.';
            $message_type = 'warning';
        } else {
            // Check if already invited or member
            $stmt = $pdo->prepare("SELECT member_id FROM team_members WHERE team_id = ? AND user_id = ?");
            $stmt->execute([$team_id, $invited_user['user_id']]);
            if ($stmt->fetch()) {
                $message = 'This user is already a member or has a pending invitation.';
                $message_type = 'warning';
            } else {
                // Send invitation
                $stmt = $pdo->prepare("INSERT INTO team_members (team_id, user_id, status) VALUES (?, ?, 'pending')");
                if ($stmt->execute([$team_id, $invited_user['user_id']])) {
                    $message = 'Invitation sent successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Failed to send invitation.';
                    $message_type = 'danger';
                }
            }
        }
    }
}

// Handle join request response (accept/reject)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['handle_request'])) {
    $member_id = intval($_POST['member_id']);
    $action = sanitize($_POST['action']);
    
    if ($action == 'accept') {
        $stmt = $pdo->prepare("UPDATE team_members SET status = 'accepted' WHERE member_id = ?");
        $stmt->execute([$member_id]);
        $message = 'Player accepted to team.';
        $message_type = 'success';
    } elseif ($action == 'reject') {
        $stmt = $pdo->prepare("UPDATE team_members SET status = 'rejected' WHERE member_id = ?");
        $stmt->execute([$member_id]);
        $message = 'Join request rejected.';
        $message_type = 'success';
    }
}

// Handle league entry request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_league'])) {
    $team_id = intval($_POST['team_id']);
    $league_id = intval($_POST['league_id']);
    
    // Check if already requested
    $stmt = $pdo->prepare("SELECT league_team_id FROM league_teams WHERE team_id = ? AND league_id = ?");
    $stmt->execute([$team_id, $league_id]);
    if ($stmt->fetch()) {
        $message = 'You have already requested entry to this league.';
        $message_type = 'warning';
    } else {
        $stmt = $pdo->prepare("INSERT INTO league_teams (league_id, team_id, status) VALUES (?, ?, 'pending')");
        if ($stmt->execute([$league_id, $team_id])) {
            $message = 'League entry request sent. Waiting for admin approval.';
            $message_type = 'success';
        } else {
            $message = 'Failed to send league entry request.';
            $message_type = 'danger';
        }
    }
}

// Get user's team (as captain)
$stmt = $pdo->prepare("SELECT * FROM teams WHERE captain_id = ?");
$stmt->execute([$user_id]);
$team = $stmt->fetch();

// Get team members if team exists
$team_members = [];
$pending_requests = [];
if ($team) {
    // Get accepted members
    $stmt = $pdo->prepare("
        SELECT tm.*, u.username, u.full_name 
        FROM team_members tm 
        JOIN users u ON tm.user_id = u.user_id 
        WHERE tm.team_id = ? AND tm.status = 'accepted'
    ");
    $stmt->execute([$team['team_id']]);
    $team_members = $stmt->fetchAll();
    
    // Get pending requests
    $stmt = $pdo->prepare("
        SELECT tm.*, u.username, u.full_name 
        FROM team_members tm 
        JOIN users u ON tm.user_id = u.user_id 
        WHERE tm.team_id = ? AND tm.status = 'pending'
    ");
    $stmt->execute([$team['team_id']]);
    $pending_requests = $stmt->fetchAll();
}

// Get available sports
$stmt = $pdo->query("SELECT * FROM sports ORDER BY sport_name");
$sports = $stmt->fetchAll();

// Get available leagues for team's sport
$leagues = [];
if ($team) {
    $stmt = $pdo->prepare("
        SELECT l.*, s.sport_name 
        FROM leagues l 
        JOIN sports s ON l.sport_id = s.sport_id 
        WHERE l.sport_id = ? AND l.status IN ('upcoming', 'active')
        ORDER BY l.created_at DESC
    ");
    $stmt->execute([$team['sport_id']]);
    $leagues = $stmt->fetchAll();
}

// Get league requests for this team
$league_requests = [];
if ($team) {
    $stmt = $pdo->prepare("
        SELECT lt.*, l.league_name, l.season 
        FROM league_teams lt 
        JOIN leagues l ON lt.league_id = l.league_id 
        WHERE lt.team_id = ?
    ");
    $stmt->execute([$team['team_id']]);
    $league_requests = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Management - Campus Sports Hub BUSE</title>
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
                <h2>Team Management - <?php echo htmlspecialchars($_SESSION['full_name']); ?></h2>
            </div>
            <p>You are logged in as a <strong>Team Captain</strong></p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if (!$team): ?>
            <!-- Create Team Form -->
            <div class="card">
                <div class="card-header">
                    <h2>Create Your Team</h2>
                </div>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="team_name">Team Name</label>
                        <input type="text" id="team_name" name="team_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="sport_id">Sport</label>
                        <select id="sport_id" name="sport_id" required>
                            <?php foreach ($sports as $sport): ?>
                                <option value="<?php echo $sport['sport_id']; ?>"><?php echo htmlspecialchars($sport['sport_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" name="create_team" class="btn btn-success">Create Team</button>
                </form>
            </div>
        <?php else: ?>
            <!-- Team Details -->
            <div class="grid">
                <div class="card">
                    <div class="card-header">
                        <h2><?php echo htmlspecialchars($team['team_name']); ?></h2>
                    </div>
                    <p><strong>Sport:</strong> <?php 
                        $stmt = $pdo->prepare("SELECT sport_name FROM sports WHERE sport_id = ?");
                        $stmt->execute([$team['sport_id']]);
                        $sport = $stmt->fetch();
                        echo htmlspecialchars($sport['sport_name']);
                    ?></p>
                    <p><strong>Created:</strong> <?php echo format_date($team['created_at']); ?></p>
                    <p><strong>Members:</strong> <?php echo count($team_members); ?></p>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2>Invite Player</h2>
                    </div>
                    <form method="POST" action="">
                        <input type="hidden" name="team_id" value="<?php echo $team['team_id']; ?>">
                        <div class="form-group">
                            <label for="invite_username">Username</label>
                            <input type="text" id="invite_username" name="invite_username" placeholder="Enter username to invite" required>
                        </div>
                        <button type="submit" name="invite_player" class="btn btn-primary">Send Invitation</button>
                    </form>
                </div>
            </div>

            <!-- Team Members -->
            <div class="card">
                <div class="card-header">
                    <h2>Team Members</h2>
                </div>
                <?php if (empty($team_members)): ?>
                    <div class="empty-state">
                        <p>No team members yet. Invite players to join your team.</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Role</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($team_members as $member): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($member['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($member['username']); ?></td>
                                    <td>
                                        <?php if ($member['user_id'] == $user_id): ?>
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

            <!-- Pending Join Requests -->
            <?php if (!empty($pending_requests)): ?>
                <div class="card">
                    <div class="card-header">
                        <h2>Pending Join Requests</h2>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_requests as $request): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($request['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($request['username']); ?></td>
                                    <td>
                                        <form method="POST" action="" style="display: inline;">
                                            <input type="hidden" name="member_id" value="<?php echo $request['member_id']; ?>">
                                            <input type="hidden" name="handle_request" value="1">
                                            <button type="submit" name="action" value="accept" class="btn btn-sm btn-success">Accept</button>
                                            <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger">Reject</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <!-- League Entry -->
            <div class="card">
                <div class="card-header">
                    <h2>League Entry</h2>
                </div>
                
                <?php if (empty($leagues)): ?>
                    <div class="empty-state">
                        <p>No available leagues for your sport at the moment.</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>League</th>
                                <th>Season</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($leagues as $league): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($league['league_name']); ?></td>
                                    <td><?php echo htmlspecialchars($league['season']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $league['status'] == 'active' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst($league['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" action="" style="display: inline;">
                                            <input type="hidden" name="team_id" value="<?php echo $team['team_id']; ?>">
                                            <input type="hidden" name="league_id" value="<?php echo $league['league_id']; ?>">
                                            <button type="submit" name="request_league" class="btn btn-sm btn-primary">Request Entry</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- League Requests Status -->
            <?php if (!empty($league_requests)): ?>
                <div class="card">
                    <div class="card-header">
                        <h2>League Entry Requests</h2>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>League</th>
                                <th>Season</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($league_requests as $request): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($request['league_name']); ?></td>
                                    <td><?php echo htmlspecialchars($request['season']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo $request['status'] == 'approved' ? 'success' : 
                                                 ($request['status'] == 'rejected' ? 'danger' : 'warning'); 
                                        ?>">
                                            <?php echo ucfirst($request['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <footer>
        <p>&copy; 2026 Campus Sports Hub - Bindura University of Science and Sports</p>
    </footer>
</body>
</html>
