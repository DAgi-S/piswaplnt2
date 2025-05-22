<?php
/**
 * Expense Attachment Model
 * Handles database operations for expense attachments
 */
class ExpenseAttachment {
    private $db;
    private $table = 'expense_attachments';
    private $uploadPath = 'uploads/expense_attachments/';

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Find an attachment by ID
     */
    public function find($id) {
        $query = "SELECT * FROM {$this->table} WHERE id = :id AND deleted = 0";
        $params = [':id' => $id];
        return $this->db->query($query, $params)->fetch();
    }

    /**
     * Upload a new attachment
     */
    public function uploadAttachment($expenseId, $file) {
        try {
            // Validate file type and size
            $this->validateFile($file);

            // Generate unique filename
            $filename = $this->generateUniqueFilename($file['name']);
            $filepath = $this->uploadPath . $filename;

            // Create upload directory if it doesn't exist
            if (!file_exists($this->uploadPath)) {
                mkdir($this->uploadPath, 0777, true);
            }

            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                throw new Exception('Failed to move uploaded file');
            }

            // Insert record into database
            $query = "INSERT INTO {$this->table} (expense_id, filename, original_name, file_type, file_size, created_at) 
                     VALUES (:expense_id, :filename, :original_name, :file_type, :file_size, NOW())";
            
            $params = [
                ':expense_id' => $expenseId,
                ':filename' => $filename,
                ':original_name' => $file['name'],
                ':file_type' => $file['type'],
                ':file_size' => $file['size']
            ];

            $this->db->query($query, $params);
            $attachmentId = $this->db->lastInsertId();

            // Log the creation
            $this->logAudit('INSERT', $attachmentId);

            return $attachmentId;
        } catch (Exception $e) {
            // Clean up if file was uploaded but database insert failed
            if (isset($filepath) && file_exists($filepath)) {
                unlink($filepath);
            }
            throw $e;
        }
    }

    /**
     * Delete an attachment
     */
    public function deleteAttachment($id) {
        $attachment = $this->find($id);
        if (!$attachment) {
            return false;
        }

        // Soft delete from database
        $query = "UPDATE {$this->table} SET deleted = 1, updated_at = NOW() WHERE id = :id";
        $params = [':id' => $id];
        $this->db->query($query, $params);

        // Delete physical file
        $filepath = $this->uploadPath . $attachment['filename'];
        if (file_exists($filepath)) {
            unlink($filepath);
        }

        // Log the deletion
        $this->logAudit('DELETE', $id);

        return true;
    }

    /**
     * Get all attachments for an expense
     */
    public function getExpenseAttachments($expenseId) {
        $query = "SELECT * FROM {$this->table} 
                 WHERE expense_id = :expense_id AND deleted = 0 
                 ORDER BY created_at DESC";
        $params = [':expense_id' => $expenseId];
        return $this->db->query($query, $params)->fetchAll();
    }

    /**
     * Download an attachment
     */
    public function downloadAttachment($id) {
        $attachment = $this->find($id);
        if (!$attachment) {
            throw new Exception('Attachment not found');
        }

        $filepath = $this->uploadPath . $attachment['filename'];
        if (!file_exists($filepath)) {
            throw new Exception('File not found');
        }

        // Log the download
        $this->logAudit('DOWNLOAD', $id);

        return [
            'filepath' => $filepath,
            'filename' => $attachment['original_name'],
            'mime_type' => $attachment['file_type']
        ];
    }

    /**
     * Log audit trail
     */
    private function logAudit($action, $recordId) {
        $query = "INSERT INTO audit_log (table_name, record_id, action, user_id, created_at) 
                 VALUES (:table_name, :record_id, :action, :user_id, NOW())";
        
        $params = [
            ':table_name' => $this->table,
            ':record_id' => $recordId,
            ':action' => $action,
            ':user_id' => $_SESSION['user_id'] ?? null
        ];

        $this->db->query($query, $params);
    }

    /**
     * Validate file upload
     */
    private function validateFile($file) {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload error: ' . $file['error']);
        }

        // Check file size (max 10MB)
        $maxSize = 10 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            throw new Exception('File size exceeds maximum limit of 10MB');
        }

        // Check file type
        $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception('Invalid file type. Allowed types: JPG, PNG, PDF, DOC, DOCX');
        }
    }

    /**
     * Generate unique filename
     */
    private function generateUniqueFilename($originalName) {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        return uniqid() . '_' . time() . '.' . $extension;
    }
} 