<?php
// php_action/github_push.php
header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';

// --- CONFIG ---
$ALLOWED_USERS = [1]; // TODO: Replace with actual user ID(s) allowed to push
$GIT_REMOTE = 'v2'; // Set to 'origin' or 'v2' as needed
$GIT_BRANCH = 'v2-main'; // Set to your target branch
$logFile = __DIR__ . '/../modules/accounting/php_errors.log';

function logError($msg) {
    global $logFile;
    error_log("[".date('Y-m-d H:i:s')."] GITHUB_PUSH: $msg\n", 3, $logFile);
}

session_start();
$user_id = isset($_SESSION['userId']) ? intval($_SESSION['userId']) : null;
if (!$user_id || !in_array($user_id, $ALLOWED_USERS)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Permission denied.']);
    exit;
}

$allOutput = [];

// Step 1: git add .
$cmd1 = 'git add . 2>&1';
exec($cmd1, $output1, $exit1);
$allOutput[] = "git add .\n" . implode("\n", $output1);

// Step 2: git commit -m "Update: YYYY-MM-DD HH:MM:SS by user $user_id"
$date = date('Y-m-d H:i:s');
$commitMsg = "Update: $date by user $user_id";
$cmd2 = 'git commit -m ' . escapeshellarg($commitMsg) . ' 2>&1';
exec($cmd2, $output2, $exit2);
$allOutput[] = "git commit -m '$commitMsg'\n" . implode("\n", $output2);

// Step 3: git push to configured remote/branch
$cmd3 = 'git push ' . escapeshellarg($GIT_REMOTE) . ' ' . escapeshellarg($GIT_BRANCH) . ' 2>&1';
exec($cmd3, $output3, $exit3);
$allOutput[] = "git push $GIT_REMOTE $GIT_BRANCH\n" . implode("\n", $output3);

$fullOutput = implode("\n\n", $allOutput);

// --- LOG TO CHANGELOG ---
$agent_name = 'agent_gitub';
$action = 'GitHub Push';
$module = 'github_push';
$sql_changes = '';
$affected_files = '';
$details = $fullOutput;
$action_data = json_encode(['details' => $fullOutput]);

$stmt = $connect->prepare('INSERT INTO changelog (user_id, agent_name, action, module, sql_changes, affected_files, details, action_data, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())');
if ($stmt) {
    $stmt->bind_param('issssssss', $user_id, $agent_name, $action, $module, $sql_changes, $affected_files, $details, $action_data);
    $stmt->execute();
}

$success = ($exit3 === 0);
if (strpos(implode("\n", $output2), 'nothing to commit') !== false && $exit3 === 0) {
    $success = true;
}

if ($success) {
    echo json_encode(['success' => true, 'message' => 'Git push completed.', 'output' => $fullOutput]);
} else {
    logError('Git push failed: ' . $fullOutput);
    echo json_encode(['success' => false, 'message' => 'Git push failed.', 'output' => $fullOutput]);
} 