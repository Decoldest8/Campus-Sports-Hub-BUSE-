<?php
// Common functions for Campus Sports Hub - BUSE

// Start session if not already started
function start_session() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
}

// Check if user is logged in
function is_logged_in() {
    start_session();
    return isset($_SESSION['user_id']);
}

// Check if user has specific role
function has_role($role) {
    start_session();
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

// Redirect to page
function redirect($url) {
    header("Location: $url");
    exit();
}

// Sanitize input
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Get current user info
function get_logged_in_user() {
    start_session();
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// Check if user is captain of a team
function is_captain($team_id) {
    start_session();
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    global $pdo;
    $stmt = $pdo->prepare("SELECT captain_id FROM teams WHERE team_id = ?");
    $stmt->execute([$team_id]);
    $team = $stmt->fetch();
    return $team && $team['captain_id'] == $_SESSION['user_id'];
}

// Update standings after a match result
function update_standings($league_id, $team_id, $goals_for, $goals_against) {
    global $pdo;
    
    // Get current standings
    $stmt = $pdo->prepare("SELECT * FROM standings WHERE league_id = ? AND team_id = ?");
    $stmt->execute([$league_id, $team_id]);
    $standing = $stmt->fetch();
    
    if (!$standing) {
        // Create new standing entry
        $stmt = $pdo->prepare("INSERT INTO standings (league_id, team_id, played, won, drawn, lost, goals_for, goals_against, goal_difference, points) VALUES (?, ?, 1, ?, ?, ?, ?, ?, ?, ?)");
        
        if ($goals_for > $goals_against) {
            $stmt->execute([$league_id, $team_id, 1, 0, 0, $goals_for, $goals_against, $goals_for - $goals_against, 3]);
        } elseif ($goals_for < $goals_against) {
            $stmt->execute([$league_id, $team_id, 0, 0, 1, $goals_for, $goals_against, $goals_for - $goals_against, 0]);
        } else {
            $stmt->execute([$league_id, $team_id, 0, 1, 0, $goals_for, $goals_against, 0, 1]);
        }
    } else {
        // Update existing standing
        $played = $standing['played'] + 1;
        $won = $standing['won'];
        $drawn = $standing['drawn'];
        $lost = $standing['lost'];
        $gf = $standing['goals_for'] + $goals_for;
        $ga = $standing['goals_against'] + $goals_against;
        $gd = $gf - $ga;
        $points = $standing['points'];
        
        if ($goals_for > $goals_against) {
            $won++;
            $points += 3;
        } elseif ($goals_for < $goals_against) {
            $lost++;
        } else {
            $drawn++;
            $points += 1;
        }
        
        $stmt = $pdo->prepare("UPDATE standings SET played = ?, won = ?, drawn = ?, lost = ?, goals_for = ?, goals_against = ?, goal_difference = ?, points = ? WHERE league_id = ? AND team_id = ?");
        $stmt->execute([$played, $won, $drawn, $lost, $gf, $ga, $gd, $points, $league_id, $team_id]);
    }
}

// Initialize standings for a league
function initialize_league_standings($league_id) {
    global $pdo;
    
    // Get all approved teams for the league
    $stmt = $pdo->prepare("SELECT team_id FROM league_teams WHERE league_id = ? AND status = 'approved'");
    $stmt->execute([$league_id]);
    $teams = $stmt->fetchAll();
    
    foreach ($teams as $team) {
        // Check if standing already exists
        $check = $pdo->prepare("SELECT standing_id FROM standings WHERE league_id = ? AND team_id = ?");
        $check->execute([$league_id, $team['team_id']]);
        
        if (!$check->fetch()) {
            // Create standing entry
            $insert = $pdo->prepare("INSERT INTO standings (league_id, team_id) VALUES (?, ?)");
            $insert->execute([$league_id, $team['team_id']]);
        }
    }
}

// Format date for display
function format_date($date) {
    return date('F j, Y', strtotime($date));
}

// Format time for display
function format_time($time) {
    return date('g:i A', strtotime($time));
}
?>
