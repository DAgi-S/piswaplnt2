/**
 * Get production orders with optional filters
 * 
 * @param array $filters Optional filters (status, date_range, product_id, search)
 * @param int $limit Number of records to return
 * @param int $offset Offset for pagination
 * @return array List of production orders
 */
public function getProductionOrders($filters = array(), $limit = 10, $offset = 0) {
    try {
        $sql = "SELECT po.*, p.product_name, p.product_code, p.product_image, 
                u.username as created_by_username
                FROM production_orders po
                LEFT JOIN production_products p ON po.product_id = p.id
                LEFT JOIN users u ON po.created_by = u.user_id";
        
        $where = array();
        $params = array();
        
        // Apply filters
        if (isset($filters['status']) && !empty($filters['status'])) {
            $where[] = "po.status = ?";
            $params[] = $filters['status'];
        }
        
        if (isset($filters['start_date']) && !empty($filters['start_date'])) {
            $where[] = "po.date_created >= ?";
            $params[] = $filters['start_date'] . ' 00:00:00';
        }
        
        if (isset($filters['end_date']) && !empty($filters['end_date'])) {
            $where[] = "po.date_created <= ?";
            $params[] = $filters['end_date'] . ' 23:59:59';
        }
        
        if (isset($filters['product_id']) && !empty($filters['product_id'])) {
            $where[] = "po.product_id = ?";
            $params[] = $filters['product_id'];
        }
        
        if (isset($filters['search']) && !empty($filters['search'])) {
            $where[] = "(po.order_number LIKE ? OR p.product_name LIKE ? OR p.product_code LIKE ?)";
            $searchTerm = "%" . $filters['search'] . "%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        // Combine WHERE clauses
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        
        // Add ordering
        $sql .= " ORDER BY po.date_created DESC";
        
        // Add limit and offset
        $sql .= " LIMIT ?, ?";
        $params[] = (int)$offset;
        $params[] = (int)$limit;
        
        $stmt = $this->conn->prepare($sql);
        
        if ($stmt === false) {
            throw new Exception("Failed to prepare statement: " . $this->conn->error);
        }
        
        // Bind parameters dynamically
        if (!empty($params)) {
            $types = str_repeat('s', count($params));
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $orders = array();
        while ($row = $result->fetch_assoc()) {
            // Calculate completion percentage
            $completionPercentage = 0;
            if ($row['target_quantity'] > 0) {
                $completionPercentage = round(($row['completed_quantity'] / $row['target_quantity']) * 100);
            }
            $row['completion_percentage'] = $completionPercentage;
            
            // Format dates for readability
            if (isset($row['date_created'])) {
                $row['formatted_date_created'] = date('M d, Y', strtotime($row['date_created']));
            }
            if (isset($row['target_completion_date'])) {
                $row['formatted_target_date'] = date('M d, Y', strtotime($row['target_completion_date']));
            }
            
            $orders[] = $row;
        }
        
        return $orders;
    } catch (Exception $e) {
        throw new Exception("Failed to get production orders: " . $e->getMessage());
    }
}

/**
 * Get total count of production orders with filters
 * 
 * @param array $filters Optional filters
 * @return int Total count of matching production orders
 */
public function getProductionOrdersCount($filters = array()) {
    try {
        $sql = "SELECT COUNT(*) as total FROM production_orders po
                LEFT JOIN production_products p ON po.product_id = p.id";
        
        $where = array();
        $params = array();
        
        // Apply filters
        if (isset($filters['status']) && !empty($filters['status'])) {
            $where[] = "po.status = ?";
            $params[] = $filters['status'];
        }
        
        if (isset($filters['start_date']) && !empty($filters['start_date'])) {
            $where[] = "po.date_created >= ?";
            $params[] = $filters['start_date'] . ' 00:00:00';
        }
        
        if (isset($filters['end_date']) && !empty($filters['end_date'])) {
            $where[] = "po.date_created <= ?";
            $params[] = $filters['end_date'] . ' 23:59:59';
        }
        
        if (isset($filters['product_id']) && !empty($filters['product_id'])) {
            $where[] = "po.product_id = ?";
            $params[] = $filters['product_id'];
        }
        
        if (isset($filters['search']) && !empty($filters['search'])) {
            $where[] = "(po.order_number LIKE ? OR p.product_name LIKE ? OR p.product_code LIKE ?)";
            $searchTerm = "%" . $filters['search'] . "%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        // Combine WHERE clauses
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        
        $stmt = $this->conn->prepare($sql);
        
        if ($stmt === false) {
            throw new Exception("Failed to prepare statement: " . $this->conn->error);
        }
        
        // Bind parameters dynamically
        if (!empty($params)) {
            $types = str_repeat('s', count($params));
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return (int)$row['total'];
    } catch (Exception $e) {
        throw new Exception("Failed to count production orders: " . $e->getMessage());
    }
} 