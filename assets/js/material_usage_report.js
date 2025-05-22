/**
 * Material Usage Report Export Functions
 * Created: 2024-03-14
 * Description: Functions to export material usage report to PDF and Excel
 */

// Function to export table data to PDF
function exportToPDF() {
    const doc = new jsPDF();
    
    // Add title
    doc.setFontSize(16);
    doc.text('Material Usage Report', 14, 15);
    
    // Add timestamp
    doc.setFontSize(10);
    doc.text('Generated on: ' + new Date().toLocaleString(), 14, 25);
    
    // Export table using autotable
    doc.autoTable({
        html: '#materialUsageTable',
        startY: 30,
        styles: { fontSize: 8 },
        columnStyles: { 0: { cellWidth: 20 } },
        headStyles: { fillColor: [41, 128, 185], textColor: 255 },
        alternateRowStyles: { fillColor: [245, 245, 245] }
    });
    
    // Save the PDF
    doc.save('material_usage_report.pdf');
}

// Function to export table data to Excel
function exportToExcel() {
    // Get the table element
    const table = document.getElementById('materialUsageTable');
    
    // Convert table to worksheet
    const ws = XLSX.utils.table_to_sheet(table);
    
    // Create workbook and add worksheet
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Material Usage');
    
    // Save the file
    XLSX.writeFile(wb, 'material_usage_report.xlsx');
}

// Initialize DataTable when document is ready
$(document).ready(function() {
    $('#materialUsageTable').DataTable({
        "pageLength": 25,
        "order": [[0, "desc"]],
        "responsive": true
    });
});

// Add event listeners when document is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('Material Usage Report JS loaded');
}); 