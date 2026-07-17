# Campus Sports Hub - BUSE

**Bindura University of Science and Sports**

A web-based sports management system for university students to create teams, join campus leagues, and track match results.

## Project Overview

Campus Sports Hub digitizes the basics of university sports organization:
- Students form teams and join leagues
- Admin creates fixtures and enters results
- Standings update automatically
- Past seasons remain viewable

## Tech Stack

- **Backend**: PHP, MySQL
- **Frontend**: HTML, CSS, JavaScript
- **Server**: XAMPP (Apache)
- **PDF Generation**: JavaScript with jsPDF library

## Features

### 1. Authentication
- Register / Login system
- Role-based session (Student, Captain, Admin)
- PHP session handling

### 2. Team Management
- Student creates team → becomes Captain
- Invite players by username
- Captain sees pending join requests

### 3. League System
- Admin creates league (e.g., "2026 Semester 1 Football")
- Captain requests team entry
- Admin approves/rejects requests

### 4. Fixtures
- Admin manually enters matches (Team A vs Team B, date)
- Simple list view per league

### 5. Results & Standings
- Admin enters final scores
- PHP auto-calculates and updates standings table:
  - Played, Won, Drawn, Lost, Goals For, Goals Against, Goal Difference, Points
  - 3 points win, 1 draw, 0 loss

### 6. History
- Past seasons remain browseable
- League archive with final tables

## User Roles

| Role | What They Do |
|------|--------------|
| Student | Register, join teams, view leagues and standings |
| Team Captain | Create team, invite players, request league entry |
| Sports Organizer (Admin) | Create leagues, approve teams, set fixtures, enter results |

## Database Schema

The system uses 8 tables:
- `users` - User accounts and authentication
- `sports` - Available sports
- `teams` - Team information
- `team_members` - Team membership and invitations
- `leagues` - League information
- `league_teams` - Team participation in leagues
- `matches` - Match fixtures and results
- `standings` - League standings (auto-calculated)

## Installation Instructions

### Prerequisites
- XAMPP installed (Apache, MySQL, PHP)

### Step 1: Setup XAMPP
1. Install XAMPP if not already installed
2. Start Apache and MySQL services from XAMPP Control Panel

### Step 2: Create Database
1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Create a new database named `campus_sports_hub`
3. Import the `database.sql` file:
   - Click on the `campus_sports_hub` database
   - Click "Import" tab
   - Choose the `database.sql` file
   - Click "Go"

### Step 3: Deploy Web Application
1. Copy the entire project folder to `C:\xampp\htdocs\Campus Sports Hub (BUSE)\`
2. Access the application at: `http://localhost/Campus%20Sports%20Hub%20%28BUSE%29/`

### Step 4: Configure Database Connection
The database connection is configured in `includes/db.php`:
```php
$host = 'localhost';
$dbname = 'campus_sports_hub';
$username = 'root';
$password = '';
```
Update these values if your MySQL credentials differ.

### Step 5: Default Admin Account
A default admin account is created:
- **Username**: admin
- **Password**: admin123
- **Email**: admin@buse.ac.zw

⚠️ **Important**: Change the admin password after first login for security.

## Usage Guide

### For Students
1. Register at `/register.php` with role "Student"
2. Login at `/login.php`
3. View available leagues and standings
4. Ask team captains to invite you to teams

### For Team Captains
1. Register at `/register.php` with role "Team Captain"
2. Login and create your team
3. Invite players by username
4. Request entry to available leagues
5. Wait for admin approval

### For Admins
1. Login with admin credentials
2. Go to Admin Panel
3. **Leagues Tab**: Create new leagues, update league status
4. **Team Requests Tab**: Approve or reject team league entries
5. **Fixtures Tab**: Create match fixtures
6. **Results Tab**: Enter match results (standings auto-update)

## JavaScript PDF Report Generator

A client-side PDF generator built into the web application using the jsPDF library.

### Using the PDF Generator
1. Navigate to any league page (click "View" on a league)
2. Click the "Generate PDF Report" button at the top of the league page
3. The PDF will be automatically downloaded with the league name
4. PDF includes final standings and match results

### Technical Details
- Uses jsPDF library (loaded via CDN)
- Runs entirely in the browser - no server-side processing
- Extracts data directly from the league view page
- Generates professional PDF with BUSE branding

## Project Structure

```
Campus Sports Hub (BUSE)/
├── css/
│   └── style.css              # BUSE blue/white theme
├── js/
│   └── pdf-generator.js       # JavaScript PDF generator
├── includes/
│   ├── db.php                 # Database connection
│   └── functions.php          # Common functions
├── database.sql               # Database schema
├── index.php                  # Landing page
├── login.php                  # Login page
├── register.php               # Registration page
├── dashboard.php              # Role-based routing
├── student_home.php           # Student dashboard
├── team_view.php              # Team management (captain)
├── leagues.php                # League listing
├── league_view.php            # League details & standings
├── admin_panel.php            # Admin control panel
├── logout.php                 # Logout handler
└── README.md                  # This file
```

## Design

- **Colors**: BUSE school colors (blue/white theme)
- **Primary Blue**: #003366
- **Secondary Blue**: #0056b3
- **Accent**: Gold (#ffd700)
- **Responsive**: Works on mobile and desktop
- **Clean Interface**: Simple, professional design

## Testing Checklist

- [ ] Any student can register, create a team, invite friends
- [ ] Captain can request league entry
- [ ] Admin can run a full league cycle: create → approve teams → set fixtures → enter results → view standings
- [ ] Standings update correctly after each result
- [ ] JavaScript PDF generator works and exports a readable PDF
- [ ] Everything shown actually works — no placeholder buttons

## Security Notes

- Passwords are hashed using PHP's `password_hash()`
- Session-based authentication
- Role-based access control
- SQL injection prevention using prepared statements
- XSS prevention using input sanitization

## Troubleshooting

### Database Connection Failed
- Ensure MySQL service is running in XAMPP
- Check database credentials in `includes/db.php`
- Verify database name is `campus_sports_hub`

### PDF Generation Not Working
- Ensure you have an active internet connection (jsPDF loads via CDN)
- Check browser console for JavaScript errors
- Ensure the league has standings or match data to generate a report

### Session Issues
- Ensure PHP session directory is writable
- Check PHP session configuration in php.ini

## Future Enhancements (Out of Scope)

- Real-time notifications
- Auto-scheduling algorithms
- Complex stats (assists, cards, MVP)
- Equipment or venue booking
- Inter-university features
- Multiple sports with different scoring rules

## License

This project is for Bindura University of Science and Sports (BUSE).

## Contact

For support or issues, contact the BUSE IT Department.

---

**© 2026 Campus Sports Hub - Bindura University of Science and Sports**
