// PDF Generator for Campus Sports Hub - BUSE
// Uses jsPDF library for client-side PDF generation

function generateLeaguePDF(leagueName, season, sportName, standingsData, matchesData) {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    
    let yPosition = 20;
    const pageWidth = doc.internal.pageSize.getWidth();
    const margin = 20;
    
    // Title
    doc.setFontSize(18);
    doc.setFont("helvetica", "bold");
    doc.text("League Report - " + leagueName, pageWidth / 2, yPosition, { align: "center" });
    yPosition += 10;
    
    // Subtitle
    doc.setFontSize(12);
    doc.setFont("helvetica", "normal");
    doc.text("Campus Sports Hub - Bindura University of Science and Sports (BUSE)", pageWidth / 2, yPosition, { align: "center" });
    yPosition += 7;
    
    // Date
    const today = new Date().toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
    doc.text("Generated on: " + today, pageWidth / 2, yPosition, { align: "center" });
    yPosition += 15;
    
    // League info
    doc.setFontSize(10);
    doc.text("Sport: " + sportName, margin, yPosition);
    yPosition += 6;
    doc.text("Season: " + season, margin, yPosition);
    yPosition += 15;
    
    // Standings Section
    doc.setFontSize(14);
    doc.setFont("helvetica", "bold");
    doc.text("Final Standings", margin, yPosition);
    yPosition += 10;
    
    // Standings table
    doc.setFontSize(10);
    doc.setFont("helvetica", "normal");
    
    const standingsHeaders = ["Team", "P", "W", "D", "L", "GF", "GA", "GD", "Pts"];
    const colWidths = [50, 15, 15, 15, 15, 15, 15, 15, 20];
    
    // Header row
    doc.setFillColor(200, 200, 200);
    doc.rect(margin, yPosition - 5, pageWidth - 2 * margin, 8, 'F');
    doc.setFont("helvetica", "bold");
    
    let xPos = margin;
    standingsHeaders.forEach((header, index) => {
        doc.text(header, xPos, yPosition);
        xPos += colWidths[index];
    });
    
    yPosition += 10;
    doc.setFont("helvetica", "normal");
    
    // Data rows
    standingsData.forEach((row, index) => {
        // Check if we need a new page
        if (yPosition > 250) {
            doc.addPage();
            yPosition = 20;
        }
        
        xPos = margin;
        row.forEach((cell, cellIndex) => {
            doc.text(String(cell), xPos, yPosition);
            xPos += colWidths[cellIndex];
        });
        yPosition += 8;
    });
    
    yPosition += 15;
    
    // Check if we need a new page for matches
    if (yPosition > 200) {
        doc.addPage();
        yPosition = 20;
    }
    
    // Matches Section
    doc.setFontSize(14);
    doc.setFont("helvetica", "bold");
    doc.text("Match Results", margin, yPosition);
    yPosition += 10;
    
    // Matches table
    doc.setFontSize(10);
    doc.setFont("helvetica", "normal");
    
    const matchesHeaders = ["Date", "Home Team", "Away Team", "Score", "Status"];
    const matchColWidths = [35, 50, 50, 25, 30];
    
    // Header row
    doc.setFillColor(200, 200, 200);
    doc.rect(margin, yPosition - 5, pageWidth - 2 * margin, 8, 'F');
    doc.setFont("helvetica", "bold");
    
    xPos = margin;
    matchesHeaders.forEach((header, index) => {
        doc.text(header, xPos, yPosition);
        xPos += matchColWidths[index];
    });
    
    yPosition += 10;
    doc.setFont("helvetica", "normal");
    
    // Data rows
    matchesData.forEach((row, index) => {
        // Check if we need a new page
        if (yPosition > 250) {
            doc.addPage();
            yPosition = 20;
        }
        
        xPos = margin;
        row.forEach((cell, cellIndex) => {
            doc.text(String(cell), xPos, yPosition);
            xPos += matchColWidths[cellIndex];
        });
        yPosition += 8;
    });
    
    // Footer
    yPosition += 15;
    doc.setFontSize(10);
    doc.setFont("helvetica", "italic");
    doc.text("End of Report", pageWidth / 2, yPosition, { align: "center" });
    
    // Save the PDF
    const fileName = leagueName.replace(/\s+/g, '_') + '_Report.pdf';
    doc.save(fileName);
}

function extractStandingsData() {
    const table = document.querySelector('.standings-table');
    if (!table) return [];
    
    const data = [];
    const rows = table.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        const rowData = [];
        const cells = row.querySelectorAll('td');
        cells.forEach(cell => {
            rowData.push(cell.textContent.trim());
        });
        data.push(rowData);
    });
    
    return data;
}

function extractMatchesData() {
    const matchCards = document.querySelectorAll('.match-card');
    if (matchCards.length === 0) return [];
    
    const data = [];
    
    matchCards.forEach(card => {
        const rowData = [];
        
        // Extract date
        const dateElement = card.querySelector('.match-date');
        if (dateElement) {
            const dateText = dateElement.textContent.trim();
            // Extract just the date part
            const dateMatch = dateText.match(/(\w+ \d+, \d{4})/);
            rowData.push(dateMatch ? dateMatch[1] : dateText.split(' at')[0]);
        } else {
            rowData.push('');
        }
        
        // Extract teams and score
        const teamsDiv = card.querySelector('.match-teams');
        if (teamsDiv) {
            const teamElements = teamsDiv.querySelectorAll('div');
            if (teamElements.length >= 3) {
                rowData.push(teamElements[0].textContent.trim()); // Home team
                rowData.push(teamElements[2].textContent.trim()); // Away team
            }
        }
        
        // Extract score
        const scoreElement = card.querySelector('.match-score');
        if (scoreElement) {
            const scoreText = scoreElement.textContent.trim();
            if (scoreText === 'vs') {
                rowData.push('-');
            } else {
                rowData.push(scoreText);
            }
        } else {
            rowData.push('-');
        }
        
        // Extract status
        const statusBadge = card.querySelector('.badge');
        if (statusBadge) {
            rowData.push(statusBadge.textContent.trim());
        } else {
            rowData.push('');
        }
        
        data.push(rowData);
    });
    
    return data;
}
