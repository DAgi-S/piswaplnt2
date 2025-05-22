// Cart Management Module
const POSCart = {
    items: [],
    VAT_RATE: 15,
    WITHHOLDING_RATE: 2,

    // Add item to cart
    addItem: function(product) {
        const existingItem = this.items.find(item => item.id === product.id);
        if (existingItem) {
            if (existingItem.quantity < product.stock) {
                existingItem.quantity++;
                return true;
            }
            return false;
        } else {
            this.items.push({
                id: product.id,
                name: product.name,
                price: parseFloat(product.price),
                quantity: 1,
                stock: product.stock
            });
            return true;
        }
    },

    // Remove item from cart
    removeItem: function(index) {
        this.items.splice(index, 1);
    },

    // Update item quantity
    updateQuantity: function(index, newQuantity) {
        const item = this.items[index];
        if (newQuantity > 0 && newQuantity <= item.stock) {
            item.quantity = newQuantity;
            return true;
        }
        return false;
    },

    // Calculate subtotal
    calculateSubtotal: function() {
        return this.items.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    },

    // Calculate tax amount
    calculateTax: function(subtotal) {
        return (subtotal * this.VAT_RATE) / 100;
    },

    // Calculate withholding amount
    calculateWithholding: function(subtotal, applyWithholding = false) {
        return applyWithholding ? (subtotal * this.WITHHOLDING_RATE) / 100 : 0;
    },

    // Calculate total
    calculateTotal: function(discount = 0, applyWithholding = false) {
        const subtotal = this.calculateSubtotal();
        const tax = this.calculateTax(subtotal);
        const withholding = this.calculateWithholding(subtotal, applyWithholding);
        return subtotal - discount + tax - withholding;
    },

    // Get cart summary
    getSummary: function(discount = 0, applyWithholding = false) {
        const subtotal = this.calculateSubtotal();
        const tax = this.calculateTax(subtotal);
        const withholding = this.calculateWithholding(subtotal, applyWithholding);
        const total = this.calculateTotal(discount, applyWithholding);

        return {
            items: this.items,
            subtotal: subtotal,
            tax: tax,
            withholding: withholding,
            discount: discount,
            total: total
        };
    },

    // Clear cart
    clear: function() {
        this.items = [];
    },

    // Check if cart is empty
    isEmpty: function() {
        return this.items.length === 0;
    },

    // Get item count
    getItemCount: function() {
        return this.items.reduce((count, item) => count + item.quantity, 0);
    },

    // Format currency
    formatCurrency: function(amount) {
        return '$' + amount.toFixed(2);
    },

    // Validate cart
    validate: function() {
        return this.items.every(item => item.quantity > 0 && item.price > 0);
    },

    // Get cart data for order creation
    getOrderData: function(clientId, paymentMethod, discount = 0, applyWithholding = false) {
        const summary = this.getSummary(discount, applyWithholding);
        return {
            client_id: clientId,
            items: this.items,
            subtotal: summary.subtotal,
            tax_amount: summary.tax,
            withholding_amount: summary.withholding,
            discount_amount: discount,
            total_amount: summary.total,
            payment_method: paymentMethod
        };
    }
}; 