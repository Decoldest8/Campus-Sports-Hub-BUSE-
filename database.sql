-- Campus Sports Hub Database Schema for BUSE
-- Bindura University of Science and Sports

CREATE DATABASE IF NOT EXISTS campus_sports_hub;
USE campus_sports_hub;

-- Users table
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('student', 'captain', 'admin') NOT NULL DEFAULT 'student',
    student_id VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Sports table
CREATE TABLE sports (
    sport_id INT AUTO_INCREMENT PRIMARY KEY,
    sport_name VARCHAR(50) NOT NULL,
    description TEXT
);

-- Teams table
CREATE TABLE teams (
    team_id INT AUTO_INCREMENT PRIMARY KEY,
    team_name VARCHAR(100) NOT NULL,
    captain_id INT NOT NULL,
    sport_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (captain_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (sport_id) REFERENCES sports(sport_id) ON DELETE CASCADE
);

-- Team members table
CREATE TABLE team_members (
    member_id INT AUTO_INCREMENT PRIMARY KEY,
    team_id INT NOT NULL,
    user_id INT NOT NULL,
    status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (team_id) REFERENCES teams(team_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_team_user (team_id, user_id)
);

-- Leagues table
CREATE TABLE leagues (
    league_id INT AUTO_INCREMENT PRIMARY KEY,
    league_name VARCHAR(100) NOT NULL,
    sport_id INT NOT NULL,
    season VARCHAR(50) NOT NULL,
    description TEXT,
    status ENUM('upcoming', 'active', 'completed') DEFAULT 'upcoming',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sport_id) REFERENCES sports(sport_id) ON DELETE CASCADE
);

-- League teams table (teams approved for a league)
CREATE TABLE league_teams (
    league_team_id INT AUTO_INCREMENT PRIMARY KEY,
    league_id INT NOT NULL,
    team_id INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_at TIMESTAMP NULL,
    FOREIGN KEY (league_id) REFERENCES leagues(league_id) ON DELETE CASCADE,
    FOREIGN KEY (team_id) REFERENCES teams(team_id) ON DELETE CASCADE,
    UNIQUE KEY unique_league_team (league_id, team_id)
);

-- Matches table
CREATE TABLE matches (
    match_id INT AUTO_INCREMENT PRIMARY KEY,
    league_id INT NOT NULL,
    home_team_id INT NOT NULL,
    away_team_id INT NOT NULL,
    match_date DATE NOT NULL,
    match_time TIME,
    venue VARCHAR(100),
    home_score INT NULL,
    away_score INT NULL,
    status ENUM('scheduled', 'completed', 'cancelled') DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (league_id) REFERENCES leagues(league_id) ON DELETE CASCADE,
    FOREIGN KEY (home_team_id) REFERENCES teams(team_id) ON DELETE CASCADE,
    FOREIGN KEY (away_team_id) REFERENCES teams(team_id) ON DELETE CASCADE
);

-- Standings table
CREATE TABLE standings (
    standing_id INT AUTO_INCREMENT PRIMARY KEY,
    league_id INT NOT NULL,
    team_id INT NOT NULL,
    played INT DEFAULT 0,
    won INT DEFAULT 0,
    drawn INT DEFAULT 0,
    lost INT DEFAULT 0,
    goals_for INT DEFAULT 0,
    goals_against INT DEFAULT 0,
    goal_difference INT DEFAULT 0,
    points INT DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (league_id) REFERENCES leagues(league_id) ON DELETE CASCADE,
    FOREIGN KEY (team_id) REFERENCES teams(team_id) ON DELETE CASCADE,
    UNIQUE KEY unique_league_team_standing (league_id, team_id)
);

-- Insert default admin user (password: admin123 - should be changed in production)
INSERT INTO users (username, email, password, full_name, role, student_id) 
VALUES ('admin', 'admin@buse.ac.zw', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin', 'ADMIN001');

-- Insert default sports
INSERT INTO sports (sport_name, description) VALUES 
('Football', 'Association Football'),
('Basketball', 'Basketball'),
('Volleyball', 'Volleyball'),
('Netball', 'Netball'),
('Rugby', 'Rugby'),
('Tennis', 'Tennis');
