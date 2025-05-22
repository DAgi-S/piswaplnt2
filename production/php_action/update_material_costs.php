<?php
// Include database connection
require_once 'db_connect.php';

// Set error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Check if materials were submitted
$updatesMade = false;
$messages = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    // Process the form submission
    foreach ($_POST['materials'] as $materialId => $data) {
        $cost = floatval($data['cost']);
        
        try {
            if ($connect instanceof PDO) {
                $stmt = $connect->prepare("
                    UPDATE raw_materials 
                    SET cost_per_unit = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$cost, $materialId]);
            } else {
                $stmt = $connect->prepare("
                    UPDATE raw_materials 
                    SET cost_per_unit = ? 
                    WHERE id = ?
                ");
                $stmt->bind_param("di", $cost, $materialId);
                $stmt->execute();
                $stmt->close();
            }
            
            $messages[] = "Updated material ID $materialId with cost $cost";
            $updatesMade = true;
        } catch (Exception $e) {
            $messages[] = "Error updating material ID $materialId: " . $e->getMessage();
        }
    }
}

// Get all materials
if ($connect instanceof PDO) {
    $stmt = $connect->prepare("
        SELECT id, material_code, name, unit, cost_per_unit 
        FROM raw_materials 
        ORDER BY name
    ");
    $stmt->execute();
    $materials = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $connect->prepare("
        SELECT id, material_code, name, unit, cost_per_unit 
        FROM raw_materials 
        ORDER BY name
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    $materials = [];
    while ($row = $result->fetch_assoc()) {
        $materials[] = $row;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Update Material Costs</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1, h2 { color: #333; }
        .messages { margin: 20px 0; padding: 10px; background-color: #f5f5f5; border-left: 4px solid #4CAF50; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        input[type="number"] { width: 100px; padding: 5px; }
        .submit-btn { background-color: #4CAF50; color: white; padding: 10px 15px; border: none; cursor: pointer; margin-top: 20px; }
        .submit-btn:hover { background-color: #45a049; }
    </style>
</head>
<body>
    <h1>Update Material Costs</h1>
    
    <p>Use this page to update the cost_per_unit values for raw materials to test cost calculations.</p>
    
    <?php if (!empty($messages)): ?>
        <div class="messages">
            <h3><?= $updatesMade ? 'Updates Successful' : 'Errors' ?></h3>
            <ul>
                <?php foreach ($messages as $message): ?>
                    <li><?= htmlspecialchars($message) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <form method="post">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Unit</th>
                    <th>Current Cost</th>
                    <th>New Cost</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($materials as $material): ?>
                    <tr>
                        <td><?= htmlspecialchars($material['id']) ?></td>
                        <td><?= htmlspecialchars($material['material_code']) ?></td>
                        <td><?= htmlspecialchars($material['name']) ?></td>
                        <td><?= htmlspecialchars($material['unit']) ?></td>
                        <td><?= number_format($material['cost_per_unit'], 2) ?></td>
                        <td>
                            <input type="number" name="materials[<?= $material['id'] ?>][cost]" value="<?= $material['cost_per_unit'] ?>" step="0.01" min="0">
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <button type="submit" name="update" class="submit-btn">Update Costs</button>
    </form>
    
    <p><a href="test_production_manager.php">Return to Production Manager Test</a></p>
</body>
</html> 