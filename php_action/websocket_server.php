<?php
require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/includes/jwt/WebSocketAuthMiddleware.php';
require_once dirname(__DIR__) . '/config/jwt/config.php';
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
    protected $authMiddleware;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->subscriptions = [];
        $this->authMiddleware = new WebSocketAuthMiddleware();
        global $connect;
        $this->connect = $connect;
        echo "WebSocket server initialized\n";
    }

    public function onOpen(ConnectionInterface $conn) {
        // Authenticate the connection
        if (!$this->authMiddleware->handleAuth($conn, $conn->httpRequest)) {
            echo "Authentication failed for connection {$conn->resourceId}\n";
            $conn->send(json_encode([
                'type' => 'error',
                'message' => 'Authentication failed'
            ]));
            $conn->close();
            return;
        }

        // Get authenticated user data
        $userData = $this->authMiddleware->getUser($conn);
        
        $this->clients->attach($conn);
        echo "New authenticated connection! ({$conn->resourceId}) - User: {$userData['email']}\n";
        
        // Send welcome message
        $conn->send(json_encode([
            'type' => 'connection_established',
            'message' => 'Successfully connected and authenticated',
            'user' => [
                'id' => $userData['user_id'],
                'email' => $userData['email'],
                'role' => $userData['role']
            ]
        ]));
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        try {
            // Verify authentication for each message
            if (!$this->authMiddleware->isAuthenticated($from)) {
                $from->send(json_encode([
                    'type' => 'error',
                    'message' => 'Not authenticated'
                ]));
                return;
            }

            $data = json_decode($msg, true);
            
            if (!isset($data['action'])) {
                return;
            }

            switch ($data['action']) {
                case 'subscribe':
                    // Check if user has permission to subscribe
                    if (!$this->authMiddleware->hasPermission($from, 'view_production')) {
                        $from->send(json_encode([
                            'type' => 'error',
                            'message' => 'Permission denied'
                        ]));
                        return;
                    }

                    if (isset($data['orderId'])) {
                        $this->subscriptions[$from->resourceId] = $data['orderId'];
                        $this->sendProductionUpdate($from, $data['orderId']);
                        echo "Client {$from->resourceId} subscribed to order {$data['orderId']}\n";
                    }
                    break;

                case 'update':
                    // Check if user has permission to update
                    if (!$this->authMiddleware->hasPermission($from, 'update_production')) {
                        $from->send(json_encode([
                            'type' => 'error',
                            'message' => 'Permission denied'
                        ]));
                        return;
                    }

                    if (isset($data['orderId'])) {
                        $this->broadcastProductionUpdate($data['orderId']);
                        echo "Broadcasting update for order {$data['orderId']}\n";
                    }
                    break;
            }
        } catch (\Exception $e) {
            echo "Error processing message: {$e->getMessage()}\n";
            $from->send(json_encode([
                'type' => 'error',
                'message' => 'Error processing request'
            ]));
        }
    }

    protected function sendProductionUpdate($client, $orderId) {
        try {
            // Verify authentication before sending update
            if (!$this->authMiddleware->isAuthenticated($client)) {
                return;
            }

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
                && $this->subscriptions[$client->resourceId] == $orderId
                && $this->authMiddleware->isAuthenticated($client)) {
                $this->sendProductionUpdate($client, $orderId);
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        unset($this->subscriptions[$conn->resourceId]);
        $this->authMiddleware->removeAuth($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $this->authMiddleware->removeAuth($conn);
        $conn->close();
    }
}

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    echo "Starting WebSocket server...\n";
    
    // Create event loop
    $loop = Factory::create();

    // Create socket server
    $socket = new Server(WS_HOST . ':' . WS_PORT, $loop);

    // Create secure server if WSS is enabled
    if (WS_SECURE) {
        echo "Setting up secure WebSocket server (WSS)...\n";
        
        if (!file_exists(WS_SSL_CERT) || !file_exists(WS_SSL_KEY)) {
            throw new Exception("SSL certificate files not found");
        }

        $socket = new SecureServer($socket, $loop, [
            'local_cert' => WS_SSL_CERT,
            'local_pk' => WS_SSL_KEY,
            'verify_peer' => false,
            'allow_self_signed' => true
        ]);
    }

    // Create WebSocket server
    $webSocket = new WsServer(new ProductionWebSocket());
    $webSocket->enableKeepAlive($loop);

    // Create HTTP server to handle WebSocket handshake
    $server = new IoServer(
        new HttpServer($webSocket),
        $socket,
        $loop
    );

    echo "WebSocket server started on " . (WS_SECURE ? "wss://" : "ws://") . WS_HOST . ":" . WS_PORT . "\n";
    $server->run();
} catch (\Exception $e) {
    echo "Fatal error: {$e->getMessage()}\n";
    exit(1);
} 