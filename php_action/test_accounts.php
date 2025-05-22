<?php
header('Content-Type: application/json');

// Sample test data
$data = [
    [
        'account_owner' => 'Test Owner',
        'platform' => 'Test Platform',
        'currency' => 'USD',
        'balance' => '$100.00',
        'transactions' => 5,
        'created_at' => date('d M Y H:i'),
        'action' => '<button>Test</button>'
    ]
];

echo json_encode(['data' => $data]); 