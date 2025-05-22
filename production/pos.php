<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/header.php';
require_once 'php_action/core.php';

// Check if user is logged in
if(!isset($_SESSION['userId'])) {
    header('location: ../index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS System - Lebawi Net Trading PLC</title>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- jQuery UI -->
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    
    <!-- Select2 -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    
    <!-- DataTables -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.css">
    <script type="text/javascript" src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.js"></script>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="../assests/font-awesome/css/font-awesome.min.css">

    <script>
        tailwind.config = {
            theme: {
                extend: {}
            }
        }
    </script>

    <style>
        /* Override Select2 styles to match Tailwind */
        .select2-container--default .select2-selection--single {
            height: 38px;
            padding: 5px;
            border-color: #D1D5DB;
            border-radius: 0.5rem;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }
        .select2-dropdown {
            border-color: #D1D5DB;
            border-radius: 0.5rem;
        }

        /* DataTables Tailwind Integration */
        .dataTables_wrapper .dataTables_length select,
        .dataTables_wrapper .dataTables_filter input {
            border-radius: 0.5rem;
            border-color: #D1D5DB;
            padding: 0.375rem 0.75rem;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #2563eb !important;
            color: white !important;
            border: none;
            border-radius: 0.375rem;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #3b82f6 !important;
            color: white !important;
            border: none;
        }
    </style>
</head>
<body class="bg-gray-100">

<div class="max-w-7xl mx-auto px-4 py-6">
    <!-- Header -->
    

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column: Products -->
        <div class="lg:col-span-2 space-y-4">
            <!-- Search and Categories -->
            <div class="flex gap-4">
                <input type="text" id="searchProduct" placeholder="Search Products" 
                       class="flex-1 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                <select id="categoryFilter" 
                        class="w-1/3 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    <option value="">All Categories</option>
                </select>
            </div>

            <!-- Products Grid -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div id="productGrid" class="grid grid-cols-2 md:grid-cols-3 gap-4 p-4 max-h-[calc(100vh-20rem)] overflow-y-auto">
                    <!-- Products will be populated here -->
                </div>
            </div>
        </div>

        <!-- Right Column: Cart -->
        <div class="space-y-4 pb-[80px] lg:pb-0">
            <!-- Client Selection -->
            <div class="flex gap-2">
                <select id="clientSelect" 
                        class="flex-1 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    <option value="">Select Client</option>
                </select>
                <button class="quick-add-btn px-4 py-2 text-green-600 border border-green-600 rounded-lg hover:bg-green-50 flex items-center gap-2">
                    <span class="text-xl">+</span>
                    <span class="hidden sm:inline">Quick Add</span>
                </button>
            </div>

            <!-- Cart -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-4 space-y-4">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-sm text-gray-600">
                                <th class="pb-2">Item</th>
                                <th class="pb-2">Qty</th>
                                <th class="pb-2">Price</th>
                                <th class="pb-2 w-10"></th>
                            </tr>
                        </thead>
                        <tbody id="cartItems" class="divide-y divide-gray-100">
                            <!-- Cart items will be populated here -->
                        </tbody>
                    </table>
                </div>

                <!-- Payment Section -->
                <div class="border-t border-gray-100 p-4 space-y-4">
                    <select id="paymentMethod" 
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                        <option value="">Payment Method</option>
                        <option value="cash">Cash</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="check">Check</option>
                    </select>

                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Sub total</span>
                            <span id="subtotal" class="font-medium">br 0.00</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Discount</span>
                            <input type="number" id="discount" value="0" min="0" step="0.01"
                                   class="w-24 text-right rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">VAT (15%)</span>
                            <span id="tax" class="font-medium">br 0.00</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <div class="flex items-center gap-2">
                                <input type="checkbox" id="applyWithholding" class="rounded border-gray-300">
                                <span class="text-gray-600">Withholding (2%)</span>
                            </div>
                            <span id="withholding" class="font-medium">br 0.00</span>
                        </div>
                        <div class="flex justify-between items-center pt-2 border-t border-gray-100">
                            <span class="font-medium">Grand Total</span>
                            <span id="total" class="font-bold text-lg">br 0.00</span>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="fixed lg:relative bottom-0 left-0 right-0 p-4 bg-white border-t border-gray-200 shadow-lg lg:shadow-none lg:border-0 lg:p-4 lg:bg-gray-50 flex gap-2 z-50">
                    <button type="button" id="saleButton" class="sale-button flex-1 bg-green-600 text-white py-3 px-6 rounded-lg hover:bg-green-700 font-medium">
                        SALE
                    </button>
                    <button type="button" id="clearButton" class="clear-button flex-1 bg-red-600 text-white py-3 px-6 rounded-lg hover:bg-red-700 font-medium">
                        CLEAR
                    </button>
                    <button type="button" id="homeButton" class="home-button flex-1 bg-cyan-600 text-white py-3 px-6 rounded-lg hover:bg-cyan-700 font-medium">
                        <i class="fa fa-home mr-2"></i>
                        HOME
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Add Client Modal -->
<div id="quickAddClientModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4">
        <div class="p-6">
            <h3 class="text-lg font-medium mb-4">Quick Add Client</h3>
            <form id="quickAddClientForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Company Name</label>
                    <input type="text" id="clientName" required
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                    <input type="tel" id="clientPhone"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" id="clientEmail"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>
                <div class="flex justify-end gap-2 mt-6">
                    <button type="button" onclick="closeQuickAddModal()"
                            class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                        Save Client
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="custom/js/common.js"></script>
<script src="custom/js/pos_print.js"></script>
<script src="custom/js/pos_cart.js"></script>
<script src="custom/js/pos.js"></script>

<script>
// Quick Add Client Modal Functions
function openQuickAddModal() {
    document.getElementById('quickAddClientModal').classList.remove('hidden');
}

function closeQuickAddModal() {
    document.getElementById('quickAddClientModal').classList.add('hidden');
}

// Add event listener to Quick Add Client button
document.querySelector('.quick-add-btn').addEventListener('click', openQuickAddModal);

// Handle form submission
document.getElementById('quickAddClientForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = {
        company_name: document.getElementById('clientName').value,
        phone: document.getElementById('clientPhone').value,
        email: document.getElementById('clientEmail').value
    };

    try {
        const response = await fetch('php_action/createClient.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        });

        const result = await response.json();
        if (result.success) {
            // Add new client to select dropdown
            const select = document.getElementById('clientSelect');
            const option = document.createElement('option');
            option.value = result.client_id;
            option.textContent = formData.company_name;
            select.appendChild(option);
            select.value = result.client_id;

            // Close modal and reset form
            closeQuickAddModal();
            e.target.reset();
        } else {
            alert('Error adding client: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error adding client. Please try again.');
    }
});

// Initialize POS system when the page loads
document.addEventListener('DOMContentLoaded', function() {
    POS.init().catch(error => {
        console.error('Failed to initialize POS:', error);
    });
});
</script>

</body>
</html>

<?php require_once 'includes/footer.php'; ?> 