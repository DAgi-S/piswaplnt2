# Production Mechanism

## 1. Overview

The Production Management System is a comprehensive solution for managing manufacturing operations, inventory control, and production workflows. It enables the creation, monitoring, and execution of production orders, tracking material usage, and documenting production progress.

## 2. Key Components

### 2.1 Production Orders Management

Production orders represent manufacturing instructions to produce a specific quantity of a finished product. The system handles the complete lifecycle of production orders:

- **Creation**: Users can create new production orders specifying the product, quantity, and dates
- **Material Requirements**: The system automatically calculates required materials based on bill of materials
- **Status Tracking**: Orders progress through various statuses (draft, confirmed, in progress, completed, cancelled)
- **Progress Monitoring**: Real-time tracking of completion percentage and production progress
- **Material Consumption**: Recording of materials used in the production process
- **Quality Control**: Integration with quality checks during and after production

### 2.2 Bill of Materials Management

The Bill of Materials (BOM) defines the components required to produce each product:

- **Component Definition**: Specifies raw materials and quantities needed for each product
- **Wastage Calculation**: Includes wastage percentages to account for material loss during production
- **Cost Calculation**: Provides the basis for production cost calculations
- **Variance Analysis**: Enables comparison between planned and actual material consumption

### 2.3 Inventory Integration

The production system integrates closely with inventory management:

- **Stock Verification**: Checks material availability before starting production
- **Reservation System**: Reserves materials for planned production orders
- **Consumption Tracking**: Records actual material usage during production
- **Finished Goods Update**: Automatically updates finished goods inventory upon completion

## 3. Detailed Production Workflow

### 3.1 Order Creation Process

1. **Product Selection Phase**
   - User navigates to the production orders management interface
   - User clicks "New Production Order" button to open creation modal
   - System loads available products from the `production_products` table
   - User selects a product from the dropdown menu
   - System triggers an AJAX call to retrieve the bill of materials for the selected product
   - The BOM data is used to populate the materials requirements table

2. **Order Configuration Phase**
   - User inputs target quantity for production
   - System calculates required material quantities based on BOM and target quantity
   - User sets start date and expected completion date
   - System validates dates (start date must be current or future, completion date must be after start date)
   - User can optionally input order number or leave blank for auto-generation
   - User can add notes or special instructions for the production order

3. **Material Availability Check**
   - System automatically checks material availability against current inventory
   - For each material, system displays:
     - Required quantity
     - Available quantity in stock
     - Status indicator (sufficient/insufficient)
   - If materials are insufficient, user is notified but can still create the order

4. **Order Submission**
   - User reviews all information and submits the form
   - System validates all inputs
   - System generates a unique order number if one wasn't provided
   - System creates a new record in `production_orders` table
   - System creates material requirement records in `production_order_materials` table
   - Order is created with 'draft' status
   - User is redirected to the production orders list with success message

### 3.2 Status Progression Flow

1. **Draft → Confirmed**
   - User views order details and clicks "Confirm Order" button
   - System prompts for confirmation
   - Upon confirmation:
     - System updates order status to "confirmed"
     - System creates material reservations in `material_reservations` table
     - System updates reserved_quantity in `raw_materials` table
     - System logs the status change
     - Notification is sent to relevant personnel

2. **Confirmed → In Progress**
   - User clicks "Start Production" button when ready to begin manufacturing
   - System checks if start date has been reached
   - Upon confirmation:
     - System updates order status to "inprogress"
     - System updates reservation status to "released" in material reservations
     - System logs the status change
     - Production start is timestamped

3. **In Progress → Production Progress Recording**
   - User clicks "Record Progress" button
   - System displays progress recording form with:
     - Current production quantity and target
     - Material consumption tracking
     - Quality notes
   - User enters quantity produced in this session
   - User records actual materials consumed
   - System validates inputs (cannot exceed remaining quantity)
   - Upon submission:
     - System creates a new record in `production_progress` table
     - System updates consumed quantities in `production_order_materials`
     - System updates completed_quantity in `production_orders`
     - System calculates and displays completion percentage

4. **In Progress → Completed**
   - System checks if completed quantity has reached or exceeded target quantity
   - User can manually trigger completion if quality and quantity requirements are met
   - Upon completion:
     - System updates order status to "completed"
     - System sets actual_completion_date to current date
     - System finalizes all material consumption
     - System adds produced quantity to finished goods inventory
     - System creates warehouse stock movement record
     - System generates completion report
     - System calculates final variances and costs

5. **Any Status → Cancelled**
   - User can cancel an order by clicking "Cancel Order" button
   - System requests cancellation reason
   - Upon confirmation:
     - System updates order status to "cancelled"
     - System releases any reserved materials back to available inventory
     - System logs cancellation reason and timestamp
     - System notifies relevant stakeholders

### 3.3 Detailed Production Progress Recording

1. **Incremental Production Updates**
   - System allows multiple progress entries throughout production
   - Each entry captures:
     - Date and time of entry
     - Quantity produced in this increment
     - Operator/user making the entry
     - Notes about production quality or issues
   - Progress is cumulative, building toward target quantity
   - System maintains complete history of all progress entries

2. **Material Consumption Tracking**
   - For each progress entry, system prompts for material consumption
   - User enters actual quantities consumed for each material
   - System validates inputs against:
     - Required quantities based on BOM
     - Previously consumed quantities
     - Available quantities in stock
   - System calculates and displays:
     - Consumption variance vs. standard
     - Material usage efficiency
     - Cost implications of variances

3. **Quality Control Integration**
   - During progress recording, user can add quality control checks
   - Quality checks include:
     - Quantity inspected
     - Pass/fail/partial status
     - Defect types and quantities
     - Inspector identification
   - Quality data is stored in `production_quality_checks` table
   - System calculates and displays:
     - First pass yield
     - Defect rates by type
     - Quality trends across production run

4. **Waste and Scrap Tracking**
   - System allows recording of material waste
   - For each waste entry, system captures:
     - Material type
     - Quantity wasted
     - Reason for waste (categorized)
     - Waste date and time
     - Notes for future prevention
   - Waste data is stored in `production_waste_logs` table
   - System analyzes waste patterns for improvement opportunities

## 4. Material Management

### 4.1 Material Reservation

- Materials are reserved when a production order is confirmed
- Reservation prevents the same materials from being allocated to multiple orders
- Reserved quantities are subtracted from available stock but not from total stock
- Reservation status can be: pending, reserved, released, consumed

### 4.2 Material Consumption

- Actual consumption is recorded during production
- Consumption can be recorded in increments as production progresses
- System supports batch and partial consumption
- Variance between planned and actual consumption is tracked

## 5. Technical Implementation

### 5.1 Security & Permissions

The system implements role-based access control:

- **View Permission**: Ability to view production orders and details
- **Create Permission**: Ability to create new production orders
- **Edit Permission**: Ability to update order details and record progress
- **Delete Permission**: Ability to cancel or delete orders (with restrictions)

### 5.2 User Interface Components

- **Production Orders Dashboard**: Main interface showing all orders with filtering options
- **Creation Modal**: Form for creating new production orders
- **Detail View**: Comprehensive view of a single production order with all related information
- **Progress Recording**: Interface for recording production progress and material consumption

### 5.3 Key Files and Modules

- `production_orders.php`: Main interface for managing production orders
- `production_middleware.php`: Core logic for permissions and business rules
- `js/modules/production-order.js`: Client-side functionality for order management
- `js/modules/status-handler.js`: Handles status transitions and validations
- Various PHP action files handling specific operations (create, update, etc.)

### 5.4 PHP Action Files For Production Process

The production system relies on the following PHP action files in the `/php_action/` directory:

#### Production Order Management
- `createProductionOrder.php`: Handles creation of new production orders
  - Validates input data from the form
  - Generates unique order number if not provided
  - Creates order record and associated material requirements
  - Returns success/error response to the client

- `fetchProductionOrders.php`: Retrieves production orders for the DataTable
  - Supports filtering, sorting, and pagination
  - Joins with related tables to fetch product names and other details
  - Formats data for display in the UI

- `fetchSingleProductionOrder.php`: Gets detailed information about a specific order
  - Retrieves order details, materials, progress history
  - Used for display in the edit/view modal
  - Formats dates and calculates completion percentages

- `changeProductionOrderStatus.php`: Manages production order status transitions
  - Validates permission for requested status change
  - Implements business logic for each status transition
  - Handles material reservations and stock updates
  - Creates appropriate log entries
  - Returns success/error response with guidance messages

#### Production Materials Management
- `fetchProductBOM.php`: Retrieves bill of materials for a selected product
  - Used during order creation to show required materials
  - Returns material details including quantities and wastage percentages

- `createBOM.php`: Manages the creation and update of bill of materials
  - Handles material associations with products
  - Validates material quantities and wastage percentages
  - Supports multiple materials per product

- `fetchRawMaterialsForSelect.php`: Provides material list for dropdowns
  - Used in BOM management and material consumption forms
  - Returns active materials with their units and current stock

#### Production Progress Tracking
- `recordProductionProgress.php`: Handles incremental progress updates
  - Validates input quantities against remaining target
  - Updates production order completion percentage
  - Records material consumption
  - Creates progress history entry

- `getProductionOrderDetails.php`: Fetches comprehensive order information
  - Includes progress history, material consumption, quality checks
  - Used for detailed view and reports
  - Calculates variances and efficiencies

- `fetchProductionOrdersReport.php`: Generates production performance reports
  - Aggregates data across multiple orders
  - Supports filtering by date range, product, status
  - Calculates efficiency and variance metrics

#### Quality Control Integration
- `createQC.php`: Records quality control checks for production orders
  - Stores inspection results
  - Links quality data to specific production orders
  - Tracks quantities inspected and pass/fail rates

- `fetchQC.php`: Retrieves quality control data
  - Used for quality dashboards and reports
  - Supports filtering and aggregation

#### Production Middleware and Security
- `production_middleware.php`: Implements security and business logic
  - Validates user permissions for production operations
  - Provides helper methods for common operations
  - Enforces business rules and data validation
  - Manages CSRF protection for production forms

- `add_production_permissions.php`: Sets up role-based permissions
  - Creates required permission entries in the database
  - Associates permissions with user roles

- `check_permission.php`: Validates user authorization
  - Checks if current user has required permissions
  - Used for UI element visibility and action authorization

## 6. Integration Points

### 6.1 Inventory System

- Stock level verification before and during production
- Material reservation and consumption
- Finished goods inventory updates

### 6.2 Quality Management

- Quality checks during production
- Final product quality verification
- Defect and rework tracking

### 6.3 Cost Accounting

- Material cost calculation
- Labor cost tracking
- Variance analysis
- Production cost reporting

## 7. Business Rules

### 7.1 Order Validation

- Target quantity must be greater than zero
- Start date must be current or future date
- Expected completion date must be after start date
- Required materials must be available or substitutable

### 7.2 Status Transition Rules

- Draft → Confirmed: Requires all materials to be available
- Confirmed → In Progress: Requires scheduled start date to be reached
- In Progress → Completed: Requires target quantity to be produced
- Any Status → Cancelled: Requires appropriate permissions

### 7.3 Material Consumption Rules

- Cannot consume more than reserved quantity without override
- Consumption updates reduce available inventory
- Material substitution requires approval based on configuration
- Wastage thresholds trigger alerts when exceeded

## 8. Data Flow Diagram

```
┌───────────────┐       ┌─────────────────┐       ┌───────────────────┐
│ Product       │       │ Bill of         │       │ Raw Material      │
│ Selection     ├──────►│ Materials       ├──────►│ Availability      │
└───────┬───────┘       └─────────────────┘       └─────────┬─────────┘
        │                                                   │
        ▼                                                   ▼
┌───────────────┐       ┌─────────────────┐       ┌───────────────────┐
│ Production    │       │ Material        │       │ Inventory         │
│ Order Creation├──────►│ Reservation     ├──────►│ Update            │
└───────┬───────┘       └─────────────────┘       └─────────┬─────────┘
        │                                                   │
        ▼                                                   ▼
┌───────────────┐       ┌─────────────────┐       ┌───────────────────┐
│ Production    │       │ Progress        │       │ Material          │
│ Execution     ├──────►│ Recording       ├──────►│ Consumption       │
└───────┬───────┘       └─────────────────┘       └─────────┬─────────┘
        │                                                   │
        ▼                                                   ▼
┌───────────────┐       ┌─────────────────┐       ┌───────────────────┐
│ Quality       │       │ Production      │       │ Finished Goods    │
│ Control       ├──────►│ Completion      ├──────►│ Inventory Update  │
└───────┬───────┘       └─────────────────┘       └─────────┬─────────┘
        │                                                   │
        ▼                                                   ▼
┌───────────────┐       ┌─────────────────┐       ┌───────────────────┐
│ Cost          │       │ Variance        │       │ Production        │
│ Calculation   ├──────►│ Analysis        ├──────►│ Reporting         │
└───────────────┘       └─────────────────┘       └───────────────────┘
``` 