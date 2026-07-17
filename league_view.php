<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

start_session();

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php');
}

// Get league ID
$league_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$league_id) {
    redirect('leagues.php');
}

// Get league details
$stmt = $pdo->prepare("
    SELECT l.*, s.sport_name 
    FROM leagues l 
    JOIN sports s ON l.sport_id = s.sport_id 
    WHERE l.league_id = ?
");
$stmt->execute([$league_id]);
$league = $stmt->fetch();

if (!$league) {
    redirect('leagues.php');
}

// Get standings
$stmt = $pdo->prepare("
    SELECT s.*, t.team_name 
    FROM standings s 
    JOIN teams t ON s.team_id = t.team_id 
    WHERE s.league_id = ? 
    ORDER BY s.points DESC, s.goal_difference DESC, s.goals_for DESC
");
$stmt->execute([$league_id]);
$standings = $stmt->fetchAll();

// Get matches
$stmt = $pdo->prepare("
    SELECT m.*, 
           ht.team_name as home_team_name, 
           at.team_name as away_team_name 
    FROM matches m 
    JOIN teams ht ON m.home_team_id = ht.team_id 
    JOIN teams at ON m.away_team_id = at.team_id 
    WHERE m.league_id = ? 
    ORDER BY m.match_date DESC, m.match_time DESC
");
$stmt->execute([$league_id]);
$matches = $stmt->fetchAll();

// Get teams in league
$stmt = $pdo->prepare("
    SELECT t.*, lt.status as team_status 
    FROM league_teams lt 
    JOIN teams t ON lt.team_id = t.team_id 
    WHERE lt.league_id = ? AND lt.status = 'approved'
");
$stmt->execute([$league_id]);
$teams = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($league['league_name']); ?> - Campus Sports Hub BUSE</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="js/pdf-generator.js"></script>
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo">Campus Sports Hub <span>BUSE</span></div>
            <nav>
                <ul>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="leagues.php">Leagues</a></li>
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
                <h2><?php echo htmlspecialchars($league['league_name']); ?></h2>
            </div>
            <p><strong>Sport:</strong> <?php echo htmlspecialchars($league['sport_name']); ?></p>
            <p><strong>Season:</strong> <?php echo htmlspecialchars($league['season']); ?></p>
            <p><strong>Status:</strong> 
                <span class="badge badge-<?php 
                    echo $league['status'] == 'active' ? 'success' : 
                         ($league['status'] == 'completed' ? 'info' : 'warning'); 
                ?>">
                    <?php echo ucfirst($league['status']); ?>
                </span>
            </p>
            <?php if ($league['description']): ?>
                <p><strong>Description:</strong> <?php echo htmlspecialchars($league['description']); ?></p>
            <?php endif; ?>
            <button onclick="generatePDF()" class="btn btn-primary" style="margin-top: 1rem;">Generate PDF Report</button>
        </div>

        <!-- Standings Table -->
        <div class="card">
            <div class="card-header">
                <h2>League Standings</h2>
            </div>
            <?php if (empty($standings)): ?>
                <div class="empty-state">
                    <p>No standings available yet. Matches need to be completed.</p>
                </div>
            <?php else: ?>
                <table class="standings-table">
                    <thead>
                        <tr>
                            <th>Team</th>
                            <th>P</th>
                            <th>W</th>
                            <th>D</th>
                            <th>L</th>
                            <th>GF</th>
                            <th>GA</th>
                            <th>GD</th>
                            <th>Pts</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($standings as $standing): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($standing['team_name']); ?></td>
                                <td><?php echo $standing['played']; ?></td>
                                <td><?php echo $standing['won']; ?></td>
                                <td><?php echo $standing['drawn']; ?></td>
                                <td><?php echo $standing['lost']; ?></td>
                                <td><?php echo $standing['goals_for']; ?></td>
                                <td><?php echo $standing['goals_against']; ?></td>
                                <td><?php echo $standing['goal_difference']; ?></td>
                                <td><strong><?php echo $standing['points']; ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p style="margin-top: 1rem; font-size: 0.875rem; color: var(--gray);">
                    <strong>P</strong> = Played, <strong>W</strong> = Won, <strong>D</strong> = Drawn, <strong>L</strong> = Lost, 
                    <strong>GF</strong> = Goals For, <strong>GA</strong> = Goals Against, <strong>GD</strong> = Goal Difference, <strong>Pts</strong> = Points
                </p>
            <?php endif; ?>
        </div>

        <!-- Matches -->
        <div class="card">
            <div class="card-header">
                <h2>Matches</h2>
            </div>
            <?php if (empty($matches)): ?>
                <div class="empty-state">
                    <p>No matches scheduled yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($matches as $match): ?>
                    <div class="match-card">
                        <div class="match-teams">
                            <div>
                                <strong><?php echo htmlspecialchars($match['home_team_name']); ?></strong>
                            </div>
                            <div class="match-score">
                                <?php if ($match['status'] == 'completed'): ?>
                                    <?php echo $match['home_score']; ?> - <?php echo $match['away_score']; ?>
                                <?php else: ?>
                                    vs
                                <?php endif; ?>
                            </div>
                            <div>
                                <strong><?php echo htmlspecialchars($match['away_team_name']); ?></strong>
                            </div>
                        </div>
                        <div class="match-date">
                            <?php echo format_date($match['match_date']); ?>
                            <?php if ($match['match_time']): ?>
                                at <?php echo format_time($match['match_time']); ?>
                            <?php endif; ?>
                            <?php if ($match['venue']): ?>
                                | <?php echo htmlspecialchars($match['venue']); ?>
                            <?php endif; ?>
                            <br>
                            <span class="badge badge-<?php 
                                echo $match['status'] == 'completed' ? 'success' : 
                                     ($match['status'] == 'cancelled' ? 'danger' : 'warning'); 
                            ?>">
                                <?php echo ucfirst($match['status']); ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Participating Teams -->
        <div class="card">
            <div class="card-header">
                <h2>Participating Teams</h2>
            </div>
            <?php if (empty($teams)): ?>
                <div class="empty-state">
                    <p>No teams approved for this league yet.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Team Name</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($teams as $team): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($team['team_name']); ?></td>
                                <td>
                                    <span class="badge badge-success">Approved</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div style="margin-top: 1rem;">
            <a href="leagues.php" class="btn btn-secondary">Back to Leagues</a>
        </div>
    </div>

    <footer>
        <p>&copy; 2026 Campus Sports Hub - Bindura University of Science and Sports</p>
    </footer>

    <script>
        function generatePDF() {
            const leagueName = "<?php echo addslashes($league['league_name']); ?>";
            const season = "<?php echo addslashes($league['season']); ?>";
            const sportName = "<?php echo addslashes($league['sport_name']); ?>";
            
            const standingsData = extractStandingsData();
            const matchesData = extractMatchesData();
            
            if (standingsData.length === 0 && matchesData.length === 0) {
                alert('No data available to generate PDF. Please ensure the league has standings or matches.');
                return;
            }
            
            try {
                generateLeaguePDF(leagueName, season, sportName, standingsData, matchesData);
            } catch (error) {
                console.error('PDF generation error:', error);
                alert('Error generating PDF. Please try again.');
            }
        }
    </script>
</body>
</html>
