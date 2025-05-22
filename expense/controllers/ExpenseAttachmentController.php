<?php
/**
 * Expense Attachment Controller
 * Handles operations related to expense attachments and file management
 */
class ExpenseAttachmentController {
    private $model;
    private $permission;

    public function __construct() {
        $this->model = new ExpenseAttachment();
        $this->permission = new Permission();
    }

    /**
     * Upload a new attachment for an expense
     */
    public function upload($expenseId, $file) {
        // Check permission
        if (!$this->permission->hasPermission('expense_edit')) {
            return ['error' => 'Access denied'];
        }

        try {
            // Validate expense exists
            $expense = (new Expense())->find($expenseId);
            if (!$expense) {
                return ['error' => 'Expense not found'];
            }

            // Validate file
            if (empty($file) || !isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
                return ['error' => 'Invalid file upload'];
            }

            // Upload file
            $attachmentId = $this->model->uploadAttachment($expenseId, $file);
            return ['success' => true, 'attachment_id' => $attachmentId];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Delete an attachment
     */
    public function delete($id) {
        // Check permission
        if (!$this->permission->hasPermission('expense_edit')) {
            return ['error' => 'Access denied'];
        }

        try {
            // Validate attachment exists
            $attachment = $this->model->find($id);
            if (!$attachment) {
                return ['error' => 'Attachment not found'];
            }

            // Delete attachment
            $result = $this->model->deleteAttachment($id);
            return ['success' => true, 'deleted' => $result];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get all attachments for an expense
     */
    public function getExpenseAttachments($expenseId) {
        // Check permission
        if (!$this->permission->hasPermission('expense_view')) {
            return ['error' => 'Access denied'];
        }

        try {
            // Validate expense exists
            $expense = (new Expense())->find($expenseId);
            if (!$expense) {
                return ['error' => 'Expense not found'];
            }

            // Get attachments
            $attachments = $this->model->getExpenseAttachments($expenseId);
            return ['success' => true, 'data' => $attachments];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Download an attachment
     */
    public function download($id) {
        // Check permission
        if (!$this->permission->hasPermission('expense_view')) {
            return ['error' => 'Access denied'];
        }

        try {
            // Validate attachment exists
            $attachment = $this->model->find($id);
            if (!$attachment) {
                return ['error' => 'Attachment not found'];
            }

            // Download file
            $result = $this->model->downloadAttachment($id);
            return ['success' => true, 'data' => $result];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
} 