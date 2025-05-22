// Function to export table to PDF
function exportToPDF() {
    // Get the table element
    var table = document.querySelector('.table-responsive table');
    
    // Create PDF configuration
    var doc = new jsPDF('l', 'pt', 'a4');
    
    // Set title
    doc.setFontSize(18);
    doc.text('Production Cost Analysis Report', 40, 40);
    
    // Get filter values
    var startDate = document.querySelector('input[name="start_date"]').value;
    var endDate = document.querySelector('input[name="end_date"]').value;
    var product = document.querySelector('select[name="product_id"] option:checked').text;
    var category = document.querySelector('select[name="category_id"] option:checked').text;
    
    // Add filter information
    doc.setFontSize(12);
    doc.text('Period: ' + startDate + ' to ' + endDate, 40, 60);
    doc.text('Product: ' + product, 40, 80);
    doc.text('Category: ' + category, 40, 100);
    
    // Add summary statistics
    var totalMaterialCost = document.querySelector('.panel-primary .huge').textContent;
    var totalWastageCost = document.querySelector('.panel-danger .huge').textContent;
    var avgCostPerUnit = document.querySelector('.panel-success .huge').textContent;
    var avgWastageRate = document.querySelector('.panel-warning .huge').textContent;
    
    doc.text('Total Material Cost: ' + totalMaterialCost, 40, 120);
    doc.text('Total Wastage Cost: ' + totalWastageCost, 250, 120);
    doc.text('Avg. Cost per Unit: ' + avgCostPerUnit, 460, 120);
    doc.text('Avg. Wastage Rate: ' + avgWastageRate, 670, 120);
    
    // Convert table to PDF
    doc.autoTable({
        html: table,
        startY: 140,
        styles: {
            fontSize: 8
        },
        columnStyles: {
            6: { // Wastage % column
                cellWidth: 60
            },
            8: { // Status column
                cellWidth: 60
            }
        },
        didDrawCell: function(data) {
            // Handle status labels in PDF
            if ((data.column.index === 6 || data.column.index === 8) && data.cell.section === 'body') {
                var label = data.cell.raw.querySelector('.label');
                if (label) {
                    data.cell.text = [label.textContent];
                }
            }
        }
    });
    
    // Save the PDF
    doc.save('cost_analysis_' + startDate + '_to_' + endDate + '.pdf');
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
    XLSX.utils.book_append_sheet(wb, ws, 'Cost Analysis');
    
    // Generate Excel file and trigger download
    XLSX.writeFile(wb, 'cost_analysis_' + startDate + '_to_' + endDate + '.xlsx');
}

// Initialize DataTable
$(document).ready(function() {
    $('.table-responsive table').DataTable({
        "pageLength": 25,
        "order": [[4, "desc"]], // Sort by wastage cost by default
        "dom": '<"top"f>rt<"bottom"lip><"clear">',
        "language": {
            "search": "Quick Search:"
        }
    });

    // Initialize select2 for better dropdown experience
    $('select[name="product_id"], select[name="category_id"]').select2({
        width: '100%'
    });
}); 