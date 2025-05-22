<?php
require_once 'php_action/db_connect.php';

try {
    // Start transaction
    $connect->begin_transaction();

    // Sample zones for Main Warehouse
    $mainWarehouseZones = [
        ['Raw Materials Zone A', 'RMZ-A', 'Raw materials storage area A', 1000],
        ['Raw Materials Zone B', 'RMZ-B', 'Raw materials storage area B', 1500],
        ['Bulk Storage Zone', 'BSZ-1', 'Bulk storage area', 2000],
        ['Temperature Controlled Zone', 'TCZ-1', 'Climate controlled storage', 800],
    ];

    // Get Main Warehouse ID
    $sql = "SELECT id FROM warehouses WHERE name = 'Main Warehouse' LIMIT 1";
    $result = $connect->query($sql);
    if (!$result || $result->num_rows === 0) {
        // Create Main Warehouse if it doesn't exist
        $sql = "INSERT INTO warehouses (name, code, location, description, status) 
                VALUES ('Main Warehouse', 'MWH', 'Main Location', 'Primary warehouse facility', 'active')";
        if (!$connect->query($sql)) {
            throw new Exception("Error creating main warehouse: " . $connect->error);
        }
        $warehouseId = $connect->insert_id;
    } else {
        $row = $result->fetch_assoc();
        $warehouseId = $row['id'];
    }

    // Add zones
    $zoneIds = [];
    foreach ($mainWarehouseZones as $zone) {
        $sql = "INSERT INTO warehouse_zones (
                    warehouse_id, name, zone_code, description, capacity, 
                    temperature_min, temperature_max, humidity_min, humidity_max, 
                    status, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', 1)";
        
        $stmt = $connect->prepare($sql);
        $temp_min = ($zone[0] === 'Temperature Controlled Zone') ? 18 : null;
        $temp_max = ($zone[0] === 'Temperature Controlled Zone') ? 24 : null;
        $humidity_min = ($zone[0] === 'Temperature Controlled Zone') ? 45 : null;
        $humidity_max = ($zone[0] === 'Temperature Controlled Zone') ? 55 : null;
        
        $stmt->bind_param("isssidddd", 
            $warehouseId, $zone[0], $zone[1], $zone[2], $zone[3],
            $temp_min, $temp_max, $humidity_min, $humidity_max
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Error creating zone {$zone[0]}: " . $stmt->error);
        }
        $zoneIds[$zone[1]] = $connect->insert_id;
        echo "Created zone: {$zone[0]}\n";
    }

    // Sample storage locations for each zone
    foreach ($zoneIds as $zoneCode => $zoneId) {
        $numRacks = ($zoneCode === 'BSZ-1') ? 6 : 4;
        $numShelves = 4;
        $numBins = 6;
        
        for ($rack = 1; $rack <= $numRacks; $rack++) {
            for ($shelf = 1; $shelf <= $numShelves; $shelf++) {
                for ($bin = 1; $bin <= $numBins; $bin++) {
                    $locationCode = sprintf("%s-R%02d-S%02d-B%02d", $zoneCode, $rack, $shelf, $bin);
                    $capacity = ($zoneCode === 'BSZ-1') ? 200 : 100;
                    
                    $sql = "INSERT INTO storage_locations (
                        zone_id, location_code, rack_number, shelf_number, bin_number,
                        capacity, current_utilization, status, item_type, created_by
                    ) VALUES (?, ?, ?, ?, ?, ?, 0, 'empty', 'raw_material', 1)";
                    
                    $stmt = $connect->prepare($sql);
                    $rackNum = sprintf("R%02d", $rack);
                    $shelfNum = sprintf("S%02d", $shelf);
                    $binNum = sprintf("B%02d", $bin);
                    
                    $stmt->bind_param("issssd", 
                        $zoneId, $locationCode, $rackNum, $shelfNum, $binNum, $capacity
                    );
                    
                    if (!$stmt->execute()) {
                        throw new Exception("Error creating location {$locationCode}: " . $stmt->error);
                    }
                    echo "Created location: {$locationCode}\n";
                }
            }
        }
    }

    // Commit transaction
    $connect->commit();
    echo "\nSuccessfully added sample warehouse zones and storage locations!";

} catch (Exception $e) {
    // Rollback transaction on error
    $connect->rollback();
    echo "Error: " . $e->getMessage() . "\n";
}

$connect->close();
?> 