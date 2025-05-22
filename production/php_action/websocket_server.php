<?php
require_once dirname(__DIR__) . '/vendor/autoload.php';
$connect = require_once 'websocket_config.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Factory;
use React\Socket\SecureServer;
use React\Socket\Server;

class ProductionWebSocket implements MessageComponentInterface {
    protected $clients;
    protected $subscriptions;
    protected $connect;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->subscriptions = [];
        global $connect;
        $this->connect = $connect;
        echo "WebSocket server initialized\n";
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        try {
            $data = json_decode($msg, true);
            
            if (!isset($data['action'])) {
                return;
            }

            switch ($data['action']) {
                case 'subscribe':
                    if (isset($data['orderId'])) {
                        $this->subscriptions[$from->resourceId] = $data['orderId'];
                        $this->sendProductionUpdate($from, $data['orderId']);
                        echo "Client {$from->resourceId} subscribed to order {$data['orderId']}\n";
                    }
                    break;

                case 'update':
                    if (isset($data['orderId'])) {
                        $this->broadcastProductionUpdate($data['orderId']);
                        echo "Broadcasting update for order {$data['orderId']}\n";
                    }
                    break;
            }
        } catch (\Exception $e) {
            echo "Error processing message: {$e->getMessage()}\n";
        }
    }

    protected function sendProductionUpdate($client, $orderId) {
        try {
            $query = "SELECT 
                po.*,
                pp.name as product_name,
                u.username as updated_by
            FROM production_orders po
            LEFT JOIN production_products pp ON po.product_id = pp.id
            LEFT JOIN users u ON po.updated_by = u.user_id
            WHERE po.id = ?";

            $stmt = $this->connect->prepare($query);
            $stmt->bind_param('i', $orderId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                $update = [
                    'type' => 'production_update',
                    'data' => [
                        'id' => $row['id'],
                        'status' => $row['status'],
                        'completed_quantity' => $row['completed_quantity'],
                        'target_quantity' => $row['target_quantity'],
                        'product_name' => $row['product_name'],
                        'updated_by' => $row['updated_by'],
                        'last_update' => date('Y-m-d H:i:s')
                    ]
                ];
                $client->send(json_encode($update));
                echo "Sent update to client {$client->resourceId}\n";
            }
        } catch (\Exception $e) {
            echo "Error sending update: {$e->getMessage()}\n";
        }
    }

    protected function broadcastProductionUpdate($orderId) {
        foreach ($this->clients as $client) {
            if (isset($this->subscriptions[$client->resourceId]) 
                && $this->subscriptions[$client->resourceId] == $orderId) {
                $this->sendProductionUpdate($client, $orderId);
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        unset($this->subscriptions[$conn->resourceId]);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    echo "Starting WebSocket server...\n";
    
    // Create event loop and socket server
    $loop = Factory::create();
    $socket = new Server('0.0.0.0:8080', $loop);

    // Create WebSocket server
    $webSocket = new WsServer(new ProductionWebSocket());
    $webSocket->enableKeepAlive($loop);

    // Create HTTP server to handle WebSocket handshake
    $server = new IoServer(
        new HttpServer($webSocket),
        $socket,
        $loop
    );

    echo "WebSocket server started on port 8080\n";
    $server->run();
} catch (\Exception $e) {
    echo "Fatal error: {$e->getMessage()}\n";
    exit(1);
} 