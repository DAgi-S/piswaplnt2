<?php
// php_action/changelog_api.php
header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';

$logFile = __DIR__ . '/../modules/accounting/php_errors.log';
function logError($msg) {
    global $logFile;
    error_log("[".date('Y-m-d H:i:s')."] CHANGELOG_API: $msg\n", 3, $logFile);
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        // Pagination
        $limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 20;
        $offset = isset($_GET['offset']) ? max(0, intval($_GET['offset'])) : 0;
        $filters = [];
        $where = [];
        $types = '';
        if (!empty($_GET['agent'])) {
            $where[] = 'user_id = ?';
            $filters[] = $_GET['agent'];
            $types .= 'i';
        }
        if (!empty($_GET['module'])) {
            $where[] = 'module = ?';
            $filters[] = $_GET['module'];
            $types .= 's';
        }
        if (!empty($_GET['date'])) {
            $where[] = 'DATE(created_at) = ?';
            $filters[] = $_GET['date'];
            $types .= 's';
        }
        $sql = 'SELECT id, user_id, agent_name, module, action, sql_changes, affected_files, action_data, created_at FROM changelog';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY created_at DESC LIMIT ? OFFSET ?';
        $filters[] = $limit;
        $filters[] = $offset;
        $types .= 'ii';
        $stmt = $connect->prepare($sql);
        if (!$stmt) {
            logError('Prepare failed: ' . $connect->error);
            echo json_encode(['success' => false, 'message' => 'Database error.']);
            exit;
        }
        $stmt->bind_param($types, ...$filters);
        if (!$stmt->execute()) {
            logError('Execute failed: ' . $stmt->error);
            echo json_encode(['success' => false, 'message' => 'Database error.']);
            exit;
        }
        $result = $stmt->get_result();
        $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        // Get total count for pagination
        $countSql = 'SELECT COUNT(*) FROM changelog';
        if ($where) $countSql .= ' WHERE ' . implode(' AND ', $where);
        $countStmt = $connect->prepare($countSql);
        if ($where) {
            $countStmt->bind_param(substr($types, 0, -2), ...array_slice($filters, 0, -2));
        }
        if (!$countStmt->execute()) {
            logError('Count execute failed: ' . $countStmt->error);
            echo json_encode(['success' => false, 'message' => 'Database error.']);
            exit;
        }
        $countResult = $countStmt->get_result();
        $total = $countResult ? $countResult->fetch_row()[0] : 0;
        echo json_encode(['success' => true, 'data' => $rows, 'total' => $total]);
        exit;
    }

    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_POST;
        $user_id = isset($input['agent']) ? $input['agent'] : null;
        $agent_name = isset($input['agent_name']) ? $input['agent_name'] : '';
        $action = isset($input['action']) ? $input['action'] : '';
        $module = isset($input['module']) ? $input['module'] : '';
        $sql_changes = isset($input['sql_changes']) ? $input['sql_changes'] : '';
        $affected_files = isset($input['affected_files']) ? $input['affected_files'] : '';
        $action_data = json_encode([
            'files' => $input['files'] ?? '',
            'details' => $input['details'] ?? ''
        ]);
        $sql = 'INSERT INTO changelog (user_id, agent_name, action, module, sql_changes, affected_files, action_data, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())';
        $stmt = $connect->prepare($sql);
        if (!$stmt) {
            logError('Prepare failed: ' . $connect->error);
            echo json_encode(['success' => false, 'message' => 'Database error.']);
            exit;
        }
        $stmt->bind_param('issssss', $user_id, $agent_name, $action, $module, $sql_changes, $affected_files, $action_data);
        $ok = $stmt->execute();
        if ($ok) {
            echo json_encode(['success' => true, 'message' => 'Changelog entry added.']);
        } else {
            logError('Failed to add changelog entry: ' . json_encode($input));
            echo json_encode(['success' => false, 'message' => 'Failed to add entry.']);
        }
        exit;
    }

    if ($method === 'PUT') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['id'])) {
            logError('PUT missing id or input: ' . json_encode($input));
            echo json_encode(['success' => false, 'message' => 'Missing id for update.']);
            exit;
        }
        $id = $input['id'];
        $user_id = isset($input['agent']) ? $input['agent'] : null;
        $agent_name = isset($input['agent_name']) ? $input['agent_name'] : '';
        $action = isset($input['action']) ? $input['action'] : '';
        $module = isset($input['module']) ? $input['module'] : '';
        $sql_changes = isset($input['sql_changes']) ? $input['sql_changes'] : '';
        $affected_files = isset($input['affected_files']) ? $input['affected_files'] : '';
        $action_data = json_encode([
            'files' => $input['files'] ?? '',
            'details' => $input['details'] ?? ''
        ]);
        $sql = 'UPDATE changelog SET user_id=?, agent_name=?, action=?, module=?, sql_changes=?, affected_files=?, action_data=? WHERE id=?';
        $stmt = $connect->prepare($sql);
        if (!$stmt) {
            logError('Prepare failed: ' . $connect->error);
            echo json_encode(['success' => false, 'message' => 'Database error.']);
            exit;
        }
        $stmt->bind_param('issssssi', $user_id, $agent_name, $action, $module, $sql_changes, $affected_files, $action_data, $id);
        $ok = $stmt->execute();
        if ($ok) {
            echo json_encode(['success' => true, 'message' => 'Changelog entry updated.']);
        } else {
            logError('Failed to update changelog entry: ' . json_encode($input));
            echo json_encode(['success' => false, 'message' => 'Failed to update entry.']);
        }
        exit;
    }

    if ($method === 'DELETE') {
        parse_str(file_get_contents('php://input'), $input);
        if (!isset($input['id'])) {
            logError('DELETE missing id: ' . json_encode($input));
            echo json_encode(['success' => false, 'message' => 'Missing id for delete.']);
            exit;
        }
        $id = $input['id'];
        $sql = 'DELETE FROM changelog WHERE id=?';
        $stmt = $connect->prepare($sql);
        if (!$stmt) {
            logError('Prepare failed: ' . $connect->error);
            echo json_encode(['success' => false, 'message' => 'Database error.']);
            exit;
        }
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        if ($ok) {
            echo json_encode(['success' => true, 'message' => 'Changelog entry deleted.']);
        } else {
            logError('Failed to delete changelog entry: ' . json_encode($input));
            echo json_encode(['success' => false, 'message' => 'Failed to delete entry.']);
        }
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
} catch (Exception $e) {
    logError('Exception: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error.']);
    exit;
} 