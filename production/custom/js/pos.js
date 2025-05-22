// POS System Core Functionality
const POS = {
    // State management
    state: {
        cart: [],
        selectedClient: null,
        products: [],
        clients: [],
        categories: [],
        paymentMethod: '',
        tax: 0.15, // 15% VAT
        withholding: 0.02, // 2% Withholding
    },

    // Initialize POS system
    init: async function() {
        try {
            // Wait for DOM to be fully loaded
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => this.initialize());
                } else {
                await this.initialize();
            }
        } catch (error) {
            console.error('Error initializing POS:', error);
            alert('Error initializing POS system. Please refresh the page.');
        }
    },

    // Initialize Select2 dropdowns
    initializeSelect2: function() {
        try {
            if (typeof $.fn.select2 === 'undefined') {
                console.warn('Select2 not loaded, falling back to native dropdowns');
                return;
            }

            // Initialize client select with Select2
            $('#clientSelect').select2({
                placeholder: 'Select Client',
                allowClear: true,
                width: '100%'
            }).on('select2:select', (e) => {
                const clientId = e.params.data.id;
                this.state.selectedClient = clientId;
                console.log('Client selected:', clientId, typeof clientId);
            }).on('select2:unselect', () => {
                this.state.selectedClient = null;
                console.log('Client unselected');
            });

            // Initialize category filter with Select2
            $('#categoryFilter').select2({
                placeholder: 'All Categories',
                allowClear: true,
                width: '100%'
            });

            // Initialize payment method with Select2
            $('#paymentMethod').select2({
                placeholder: 'Select Payment Method',
                allowClear: true,
                width: '100%'
            }).on('select2:select', (e) => {
                this.state.paymentMethod = e.params.data.id;
                console.log('Payment method selected:', this.state.paymentMethod);
            }).on('select2:unselect', () => {
                this.state.paymentMethod = '';
                console.log('Payment method unselected');
            });

        } catch (error) {
            console.warn('Error initializing Select2:', error);
        }
    },

    // Main initialization function
    initialize: async function() {
        try {
            // First check if jQuery is loaded
            if (typeof jQuery === 'undefined') {
                throw new Error('jQuery is required but not loaded');
            }

            // Initialize event listeners first
            this.initializeEventListeners();
            
            // Then load the data
            await this.loadInitialData();
            
            // Setup search functionality
            const searchInput = document.getElementById('searchProduct');
            if (searchInput) {
                searchInput.addEventListener('input', this.handleSearch.bind(this));
            }
            
            // Initialize Select2 dropdowns
            this.initializeSelect2();
            
            // Initial render of products
            this.renderProducts(this.state.products);
            
        } catch (error) {
            console.error('Error in initialization:', error);
            throw error;
        }
    },

    // Initialize all event listeners
    initializeEventListeners: function() {
        try {
            // Ensure all required elements exist
            const elements = {
                searchProduct: document.getElementById('searchProduct'),
                categoryFilter: document.getElementById('categoryFilter'),
                clientSelect: document.getElementById('clientSelect'),
                paymentMethod: document.getElementById('paymentMethod'),
                discount: document.getElementById('discount'),
                saleButton: document.getElementById('saleButton'),
                clearButton: document.getElementById('clearButton'),
                homeButton: document.getElementById('homeButton'),
                quickAddBtn: document.querySelector('.quick-add-btn'),
                quickAddModal: document.getElementById('quickAddClientModal'),
                quickAddForm: document.getElementById('quickAddClientForm'),
                applyWithholding: document.getElementById('applyWithholding')
            };

            // Store elements for later use
            this.elements = elements;

            // Check if all elements exist
            for (const [key, element] of Object.entries(elements)) {
                if (!element) {
                    console.error(`Required element not found: ${key}`);
                }
            }

            // Search and filter
            if (elements.searchProduct) {
                elements.searchProduct.addEventListener('input', this.handleSearch.bind(this));
            }
            if (elements.categoryFilter) {
                elements.categoryFilter.addEventListener('change', this.handleCategoryFilter.bind(this));
            }

            // Discount input
            if (elements.discount) {
                elements.discount.addEventListener('input', this.calculateTotals.bind(this));
            }

            // Action buttons
            if (elements.saleButton) {
                elements.saleButton.addEventListener('click', () => {
                    if (!elements.saleButton.disabled) {
                        this.handleSale();
                    }
                });
            }
            if (elements.clearButton) {
                elements.clearButton.addEventListener('click', this.clearCart.bind(this));
            }
            if (elements.homeButton) {
                elements.homeButton.addEventListener('click', () => window.location.href = 'dashboard.php');
            }

            // Quick add client
            if (elements.quickAddBtn && elements.quickAddModal) {
                elements.quickAddBtn.addEventListener('click', () => {
                    elements.quickAddModal.classList.remove('hidden');
                });
            }

            // Quick add form submission
            if (elements.quickAddForm) {
                elements.quickAddForm.addEventListener('submit', this.handleQuickAddClient.bind(this));
            }

            // Add withholding checkbox listener
            if (elements.applyWithholding) {
                elements.applyWithholding.addEventListener('change', this.calculateTotals.bind(this));
            }

        } catch (error) {
            console.error('Error initializing event listeners:', error);
        }
    },

    // Handle quick add client form submission
    handleQuickAddClient: async function(event) {
        event.preventDefault();
        
        const formData = {
            company_name: document.getElementById('clientName').value,
            phone: document.getElementById('clientPhone').value,
            email: document.getElementById('clientEmail').value
        };

        try {
            const response = await fetch('php_action/createPOSClient.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            });

            const result = await response.json();
            if (result.success) {
                // Add new client to state and select dropdown
                this.state.clients.push(result.client);
                this.populateClients(this.state.clients);
                document.getElementById('clientSelect').value = result.client.id;
                this.state.selectedClient = result.client.id;

                // Close modal and reset form
                document.getElementById('quickAddClientModal').classList.add('hidden');
                event.target.reset();
                    } else {
                throw new Error(result.message);
            }
        } catch (error) {
            console.error('Error adding client:', error);
            alert('Error adding client: ' + error.message);
        }
    },

    // Load initial data
    loadInitialData: async function() {
        try {
            // Load products and clients in parallel
            const [productsResponse, clientsResponse] = await Promise.all([
                fetch('php_action/fetchPOSProducts.php'),
                fetch('php_action/fetchPOSClients.php')
            ]);

            const productsResult = await productsResponse.json();
            const clientsResult = await clientsResponse.json();

            if (!productsResult.success) throw new Error(productsResult.message);
            if (!clientsResult.success) throw new Error(clientsResult.message);

            this.state.products = productsResult.data;
            this.state.clients = clientsResult.data;

            // Extract unique categories from products
            this.state.categories = [...new Set(this.state.products
                .map(p => ({ id: p.category_id, name: p.category_name }))
                .filter(c => c.id && c.name)
            )];

            this.renderProducts(this.state.products);
            this.populateCategories(this.state.categories);
            this.populateClients(this.state.clients);
        } catch (error) {
            console.error('Error loading initial data:', error);
            throw error;
        }
    },

    // Handle search
    handleSearch: function(event) {
        const searchTerm = event.target.value.toLowerCase();
        const filteredProducts = this.state.products.filter(product => 
            product.name.toLowerCase().includes(searchTerm) ||
            product.product_code.toLowerCase().includes(searchTerm)
        );
        this.renderProducts(filteredProducts);
    },

    // Render products to the grid
    renderProducts: function(products) {
        const productGrid = document.getElementById('productGrid');
        if (!productGrid) return;

        productGrid.innerHTML = '';
        products.forEach(product => {
            const productElement = document.createElement('div');
            productElement.className = 'product-card p-4 bg-white rounded-lg shadow-sm hover:shadow-md cursor-pointer transition-shadow';
            productElement.innerHTML = `
                <div class="flex gap-4">
                    <div class="product-info flex-1">
                        <div class="text-sm font-semibold text-gray-800">${product.name}</div>
                        <div class="text-xs text-gray-600">Code: ${product.product_code}</div>
                        <div class="text-sm font-semibold text-green-600">${parseFloat(product.selling_price).toFixed(2)}</div>
                        <div class="text-xs ${parseInt(product.current_stock) > 0 ? 'text-green-600' : 'text-red-600'}">
                            Stock: ${product.current_stock}
                                </div>
                            </div>
                    <div class="product-image w-16 h-16 bg-gray-50 rounded flex items-center justify-center">
                        <img src="${product.product_image}" alt="${product.name}" class="w-full h-full object-contain rounded">
                            </div>
                        </div>
                    `;
            productElement.addEventListener('click', () => {
                if (parseInt(product.current_stock) > 0) {
                    this.addToCart(product);
                } else {
                    alert('This product is out of stock');
                }
            });
            productGrid.appendChild(productElement);
        });
    },

    // Add item to cart
    addToCart: function(productOrId) {
        const product = typeof productOrId === 'object' ? 
            productOrId : 
            this.state.products.find(p => p.product_id === productOrId);

        if (!product) return;

        const existingItem = this.state.cart.find(item => item.product_id === product.product_id);
        if (existingItem) {
            if (existingItem.quantity < parseInt(product.current_stock)) {
                existingItem.quantity++;
            } else {
                alert('Cannot add more units - insufficient stock');
                return;
            }
        } else {
            this.state.cart.push({
                id: product.product_id,
                product_id: product.product_id,
                name: product.name,
                price: parseFloat(product.selling_price),
                quantity: 1,
                max_stock: parseInt(product.current_stock)
            });
        }

        this.renderCart();
        this.calculateTotals();
    },

    // Render cart items
    renderCart: function() {
        const cartBody = document.getElementById('cartItems');
        if (!cartBody) return;

        cartBody.innerHTML = this.state.cart.map(item => `
            <tr class="border-b border-gray-100">
                <td class="py-2">
                    <div class="font-medium">${item.name}</div>
                    <div class="text-xs text-gray-500">${item.price.toFixed(2)} each</div>
                </td>
                <td class="py-2">
                    <div class="flex items-center gap-2">
                        <button type="button" class="w-6 h-6 rounded bg-gray-100 hover:bg-gray-200 flex items-center justify-center"
                                onclick="event.preventDefault(); POS.updateQuantity('${item.product_id}', -1)">-</button>
                        <span class="w-8 text-center">${item.quantity}</span>
                        <button type="button" class="w-6 h-6 rounded bg-gray-100 hover:bg-gray-200 flex items-center justify-center"
                                onclick="event.preventDefault(); POS.updateQuantity('${item.product_id}', 1)">+</button>
                    </div>
                    </td>
                <td class="py-2 text-right">${(item.price * item.quantity).toFixed(2)}</td>
                <td class="py-2 text-center">
                    <button type="button" class="text-red-500 hover:text-red-700" 
                            onclick="event.preventDefault(); POS.removeFromCart('${item.product_id}')">×</button>
                    </td>
                </tr>
        `).join('');

        // Update totals after rendering cart
        this.calculateTotals();
    },

    // Update item quantity
    updateQuantity: function(productId, change) {
        const item = this.state.cart.find(item => item.product_id === productId);
        if (!item) return;

        const newQuantity = item.quantity + change;
        if (newQuantity <= 0) {
            this.removeFromCart(productId);
        } else if (newQuantity <= item.max_stock) {
            item.quantity = newQuantity;
            this.renderCart();
            this.calculateTotals();
        } else {
            alert('Cannot add more units - insufficient stock');
        }
    },

    // Remove item from cart
    removeFromCart: function(productId) {
        this.state.cart = this.state.cart.filter(item => item.product_id !== productId);
        this.renderCart();
        this.calculateTotals();
    },

    // Calculate totals
    calculateTotals: function() {
        const subtotal = this.state.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        const discount = parseFloat(document.getElementById('discount').value || 0);
        const discountedSubtotal = subtotal - discount;
        const vat = discountedSubtotal * this.state.tax;
        const applyWithholding = document.getElementById('applyWithholding').checked;
        const withholding = applyWithholding ? (discountedSubtotal * 0.02) : 0; // 2% withholding
        const total = subtotal - discount + vat - withholding;

        // Update display with proper formatting
        document.getElementById('subtotal').textContent = `${subtotal.toFixed(2)}`;
        document.getElementById('tax').textContent = `${vat.toFixed(2)}`;
        document.getElementById('withholding').textContent = `${withholding.toFixed(2)}`;
        document.getElementById('total').textContent = `${total.toFixed(2)}`;

        // Store calculated values in state for later use
        this.state.currentTotals = {
            subtotal,
            discount,
            vat,
            withholding,
            total
        };
    },

    // Handle sale submission
    handleSale: async function() {
        try {
            // Validate client selection
            if (!this.state.selectedClient) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Please select a client'
                });
                return;
            }

            // Validate cart
            if (this.state.cart.length === 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Cart is empty'
                });
                return;
            }

            // Validate payment method
            if (!this.state.paymentMethod) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Please select a payment method'
                });
                return;
            }

            // Calculate totals using stored values from calculateTotals
            this.calculateTotals(); // Ensure we have the latest totals
            const { subtotal, discount, vat: taxAmount, withholding, total } = this.state.currentTotals;

            // Prepare sale data with clean values
            const saleData = {
                client_id: parseInt(this.state.selectedClient, 10),
                items: this.state.cart.map(item => ({
                    id: parseInt(item.id, 10),
                    quantity: parseInt(item.quantity, 10),
                    price: Number(item.price),
                    tax_rate: 15, // Fixed 15% VAT
                    tax_amount: Number((item.price * item.quantity * 0.15).toFixed(2))
                })),
                payment_method: this.state.paymentMethod,
                subtotal: Number(subtotal.toFixed(2)),
                tax_amount: Number(taxAmount.toFixed(2)),
                discount_amount: Number(discount.toFixed(2)),
                withholding_amount: Number(withholding.toFixed(2)),
                total_amount: Number(total.toFixed(2)),
                paid_amount: Number(total.toFixed(2)) // For POS sales, paid amount equals total amount
            };

            // Debug: Log the data being sent
            console.log('Sale data before stringify:', saleData);
            const jsonString = JSON.stringify(saleData);
            console.log('Stringified sale data:', jsonString);

            // Disable sale button
            const saleButton = document.getElementById('saleButton');
            if (saleButton) {
                saleButton.disabled = true;
            }

            // Submit sale
            const response = await fetch('php_action/createPOSSale.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: jsonString
            });

            // Debug: Log the response
            console.log('Response status:', response.status);
            const responseText = await response.text();
            console.log('Raw response:', responseText);

            // Parse the response
            let result;
            try {
                result = JSON.parse(responseText);
            } catch (parseError) {
                console.error('Error parsing response:', parseError);
                throw new Error('Invalid response from server: ' + responseText);
            }

            if (result.success) {
                await Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: 'Sale completed successfully'
                });

                if (result.sale_id) {
                    try {
                        console.log('Attempting to print receipt for sale ID:', result.sale_id);
                        await window.printReceipt(result.sale_id);
                    } catch (printError) {
                        console.error('Error printing receipt:', printError);
                        Swal.fire({
                            icon: 'warning',
                            title: 'Print Warning',
                            text: 'Sale was successful but there was an error printing the receipt: ' + printError.message
                        });
                    }
                }
                this.clearCart();
            } else {
                throw new Error(Array.isArray(result.messages) ? result.messages.join(' ') : result.messages || 'Error processing sale');
            }
        } catch (error) {
            console.error('Error processing sale:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'Error processing sale'
            });
        } finally {
            const saleButton = document.getElementById('saleButton');
            if (saleButton) {
                saleButton.disabled = false;
            }
        }
    },

    // Calculate subtotal
    calculateSubtotal: function() {
        return this.state.cart.reduce((total, item) => total + (item.price * item.quantity), 0);
    },

    // Reset sale button state
    resetSaleButton: function() {
        const saleButton = this.elements.saleButton;
        if (saleButton) {
            saleButton.disabled = false;
            saleButton.innerHTML = 'SALE';
        }
    },

    // Clear cart
    clearCart: function() {
        this.state.cart = [];
        this.state.selectedClient = null;
        this.state.paymentMethod = '';
        
        // Reset form elements
        document.getElementById('clientSelect').value = '';
        document.getElementById('paymentMethod').value = '';
        document.getElementById('discount').value = '0';
        
        this.renderCart();
        this.calculateTotals();
    },

    // Handle category filter
    handleCategoryFilter: function(event) {
        const categoryId = event.target.value;
        const filteredProducts = categoryId ? 
            this.state.products.filter(product => product.category_id === categoryId) :
            this.state.products;
        this.renderProducts(filteredProducts);
    },

    // Populate categories dropdown
    populateCategories: function(categories) {
        const select = document.getElementById('categoryFilter');
        select.innerHTML = `
            <option value="">All Categories</option>
            ${categories.map(category => 
                `<option value="${category.id}">${category.name}</option>`
            ).join('')}
        `;
    },

    // Populate clients dropdown
    populateClients: function(clients) {
        const select = document.getElementById('clientSelect');
        if (!select) return;

        // Clear existing options
        select.innerHTML = '<option value="">Select Client</option>';
        
        // Add client options
        clients.forEach(client => {
            const option = document.createElement('option');
            option.value = client.id; // Using the correct ID field from clients table
            option.textContent = client.company_name;
            select.appendChild(option);
        });

        // Initialize Select2 if available
        if ($.fn.select2) {
            $(select).select2({
                placeholder: 'Select Client',
                allowClear: true,
                width: '100%'
            });
        }
    },

    // Handle client selection (keeping this for compatibility)
    handleClientSelect: function(event) {
        const selectedValue = event.target.value;
        const clientId = parseInt(selectedValue);
        this.state.selectedClient = clientId || null;
        console.log('Selected client ID:', this.state.selectedClient);
    },

    // Handle payment method change
    handlePaymentMethodChange: function(event) {
        this.state.paymentMethod = event.target.value;
    }
};

// Initialize POS system
POS.init(); 