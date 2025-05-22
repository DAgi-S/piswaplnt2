$(document).ready(function() {
    // Get current page URL
    var url = window.location.href;
    var page = url.split('/').pop();
    
    // Remove any parameters from the page name
    page = page.split('?')[0];
    
    // Handle active menu items
    if (page === 'dashboard.php') {
        $('#navDashboard').addClass('active');
    } else if (page === 'raw_materials.php') {
        $('#navRawMaterials').addClass('active');
        $('#navManageRawMaterials').addClass('active');
    } else if (page === 'raw_material_categories.php') {
        $('#navRawMaterials').addClass('active');
        $('#navManageCategories').addClass('active');
    } else if (page === 'warehouses.php') {
        $('#navRawMaterials').addClass('active');
        $('#navManageWarehouses').addClass('active');
    } else if (page === 'stock_movements.php') {
        $('#navRawMaterials').addClass('active');
        $('#navStockMovements').addClass('active');
    } else if (page === 'low_stock.php') {
        $('#navRawMaterials').addClass('active');
        $('#navLowStock').addClass('active');
    } else if (page === 'production_orders.php') {
        $('#navProduction').addClass('active');
        $('#navProductionOrders').addClass('active');
    } else if (page === 'bill_of_materials.php') {
        $('#navProduction').addClass('active');
        $('#navBOM').addClass('active');
    } else if (page === 'products.php') {
        $('#navProduction').addClass('active');
        $('#navProducts').addClass('active');
    } else if (page === 'quality_control.php') {
        $('#navProduction').addClass('active');
        $('#navQualityControl').addClass('active');
    }
}); 