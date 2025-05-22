<?php
/**
 * Base Model for Expense Module
 * Provides common functionality for all expense-related models
 */
class BaseModel {
    protected $db;
    protected $table;
    protected $primaryKey;
    protected $softDelete = true;

    public function __construct() {
        global $db;
        $this->db = $db;
    }

    /**
     * Get a single record by ID
     */
    public function find($id) {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
        if ($this->softDelete) {
            $sql .= " AND deleted = 0";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get all records with optional conditions
     */
    public function findAll($conditions = [], $orderBy = null, $limit = null) {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];

        if ($this->softDelete) {
            $sql .= " WHERE deleted = 0";
        }

        if (!empty($conditions)) {
            $where = $this->softDelete ? " AND " : " WHERE ";
            $sql .= $where . implode(" AND ", array_map(function($key) {
                return "$key = ?";
            }, array_keys($conditions)));
            $params = array_values($conditions);
        }

        if ($orderBy) {
            $sql .= " ORDER BY " . $orderBy;
        }

        if ($limit) {
            $sql .= " LIMIT " . $limit;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Create a new record
     */
    public function create($data) {
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');
        
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($data));
        
        return $this->db->lastInsertId();
    }

    /**
     * Update an existing record
     */
    public function update($id, $data) {
        $fields = array_map(function($field) {
            return "$field = ?";
        }, array_keys($data));
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . 
               " WHERE {$this->primaryKey} = ?";
        
        $params = array_values($data);
        $params[] = $id;
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Soft delete a record
     */
    public function delete($id) {
        if ($this->softDelete) {
            return $this->update($id, ['deleted' => 1]);
        }
        
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Log audit trail
     */
    protected function logAudit($action, $recordId, $oldValue = null, $newValue = null) {
        $sql = "INSERT INTO expense_audit_log (expense_id, user_id, action, old_value, new_value, ip_address) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $recordId,
            $_SESSION['user_id'],
            $action,
            $oldValue ? json_encode($oldValue) : null,
            $newValue ? json_encode($newValue) : null,
            $_SERVER['REMOTE_ADDR']
        ]);
    }
} 