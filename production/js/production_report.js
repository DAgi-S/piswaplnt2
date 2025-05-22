// Function to export table to PDF
function exportToPDF() {
    // Get the table element
    var table = document.querySelector('.table-responsive table');
    
    // Create PDF configuration
    var doc = new jsPDF('l', 'pt', 'a4');
    
    // Set title
    doc.setFontSize(18);
    doc.text('Production Report', 40, 40);
    
    // Get filter values
    var startDate = document.querySelector('input[name="start_date"]').value;
    var endDate = document.querySelector('input[name="end_date"]').value;
    var status = document.querySelector('select[name="status"]').value;
    
    // Add filter information
    doc.setFontSize(12);
    doc.text('Period: ' + startDate + ' to ' + endDate, 40, 60);
    doc.text('Status: ' + (status === 'all' ? 'All' : status.charAt(0).toUpperCase() + status.slice(1)), 40, 80);
    
    // Convert table to PDF
    doc.autoTable({
        html: table,
        startY: 100,
        styles: {
            fontSize: 8
        },
        columnStyles: {
            4: { // Progress column
                cellWidth: 80
            }
        },
        didDrawCell: function(data) {
            // Replace progress bar with percentage text in PDF
            if (data.column.index === 4 && data.cell.section === 'body') {
                var progressBar = data.cell.raw.querySelector('.progress-bar');
                if (progressBar) {
                    var percentage = progressBar.textContent;
                    data.cell.text = [percentage];
                }
            }
        }
    });
    
    // Save the PDF
    doc.save('production_report_' + startDate + '_to_' + endDate + '.pdf');
}

// Function to export table to Excel
function exportToExcel() {
    // Get the table
    var table = document.querySelector('.table-responsive table');
    
    // Create a workbook
    var wb = XLSX.utils.book_new();
    
    // Get filter values
    var startDate = document.querySelector('input[name="start_date"]').value;
    var endDate = document.querySelector('input[name="end_date"]').value;
    
    // Convert table to worksheet
    var ws = XLSX.utils.table_to_sheet(table, {
        raw: true,
        cellDates: true,
        dateNF: 'dd/mm/yyyy'
    });
    
    // Add the worksheet to workbook
    XLSX.utils.book_append_sheet(wb, ws, 'Production Report');
    
    // Generate Excel file and trigger download
    XLSX.writeFile(wb, 'production_report_' + startDate + '_to_' + endDate + '.xlsx');
}

// Initialize DataTable
$(document).ready(function() {
    $('.table-responsive table').DataTable({
        "pageLength": 25,
        "order": [[5, "desc"]], // Sort by start date by default
        "dom": '<"top"f>rt<"bottom"lip><"clear">',
        "language": {
            "search": "Quick Search:"
        }
    });
}); 