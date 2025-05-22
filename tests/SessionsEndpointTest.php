<?php
/**
 * Sessions Endpoint Test
 * Tests the session management API endpoints
 */

class SessionsEndpointTest extends TestCase {
    private $testUser;
    private $accessToken;
    private $sessionManager;
    private $testSessionId;

    protected function setUp(): void {
        parent::setUp();
        
        // Create test user
        $this->testUser = [
            'id' => 1,
            'email' => 'test@example.com',
            'role' => 'user'
        ];
        
        // Generate access token
        $jwtHandler = JWTHandler::getInstance();
        $this->accessToken = $jwtHandler->generateAccessToken($this->testUser);
        
        // Initialize session manager
        $this->sessionManager = SessionManager::getInstance();
        
        // Create test session
        $refreshToken = $jwtHandler->generateRefreshToken($this->testUser['id']);
        $this->testSessionId = $this->sessionManager->createSession(
            $this->testUser['id'],
            $refreshToken
        );
    }

    /**
     * Test getting active sessions
     */
    public function testGetActiveSessions() {
        $this->createRequest('GET', '/api/v2/auth/sessions', [
            'session_id' => $this->testSessionId
        ]);
        
        // Set authorization header
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $this->accessToken;

        ob_start();
        include __DIR__ . '/../api/v2/auth/sessions.php';
        $response = $this->getJsonResponse();

        $this->assertResponseStatus(200);
        $this->assertJsonStructure([
            'status',
            'sessions' => [
                '*' => [
                    'session_id',
                    'ip_address',
                    'user_agent',
                    'last_activity',
                    'expires_at',
                    'is_current'
                ]
            ]
        ], $response);

        $this->assertEquals('success', $response['status']);
        $this->assertNotEmpty($response['sessions']);
        
        // Verify test session is in response
        $found = false;
        foreach ($response['sessions'] as $session) {
            if ($session['session_id'] === $this->testSessionId) {
                $found = true;
                $this->assertTrue($session['is_current']);
                break;
            }
        }
        $this->assertTrue($found, 'Test session not found in response');
    }

    /**
     * Test terminating a session
     */
    public function testTerminateSession() {
        // Create another session to terminate
        $jwtHandler = JWTHandler::getInstance();
        $refreshToken = $jwtHandler->generateRefreshToken($this->testUser['id']);
        $sessionToTerminate = $this->sessionManager->createSession(
            $this->testUser['id'],
            $refreshToken
        );

        $this->createRequest('DELETE', '/api/v2/auth/sessions', [
            'session_id' => $sessionToTerminate,
            'current_session_id' => $this->testSessionId
        ]);
        
        // Set authorization header
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $this->accessToken;

        ob_start();
        include __DIR__ . '/../api/v2/auth/sessions.php';
        $response = $this->getJsonResponse();

        $this->assertResponseStatus(200);
        $this->assertJsonStructure([
            'status',
            'message'
        ], $response);

        $this->assertEquals('success', $response['status']);
        $this->assertEquals('Session terminated successfully', $response['message']);

        // Verify session was actually terminated
        $sessions = $this->sessionManager->getActiveSessions($this->testUser['id']);
        $found = false;
        foreach ($sessions as $session) {
            if ($session['session_id'] === $sessionToTerminate) {
                $found = true;
                break;
            }
        }
        $this->assertFalse($found, 'Terminated session still active');
    }

    /**
     * Test attempting to terminate current session
     */
    public function testTerminateCurrentSession() {
        $this->createRequest('DELETE', '/api/v2/auth/sessions', [
            'session_id' => $this->testSessionId,
            'current_session_id' => $this->testSessionId
        ]);
        
        // Set authorization header
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $this->accessToken;

        ob_start();
        include __DIR__ . '/../api/v2/auth/sessions.php';
        $response = $this->getJsonResponse();

        $this->assertResponseStatus(401);
        $this->assertEquals('error', $response['status']);
        $this->assertEquals('Cannot terminate current session', $response['message']);

        // Verify session is still active
        $sessions = $this->sessionManager->getActiveSessions($this->testUser['id']);
        $found = false;
        foreach ($sessions as $session) {
            if ($session['session_id'] === $this->testSessionId) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Current session was terminated');
    }

    /**
     * Test unauthorized access
     */
    public function testUnauthorizedAccess() {
        $this->createRequest('GET', '/api/v2/auth/sessions');
        
        ob_start();
        include __DIR__ . '/../api/v2/auth/sessions.php';
        $response = $this->getJsonResponse();

        $this->assertResponseStatus(401);
        $this->assertEquals('error', $response['status']);
        $this->assertEquals('No access token provided', $response['message']);
    }

    protected function tearDown(): void {
        // Clean up test sessions
        if (isset($this->testUser)) {
            $this->sessionManager->invalidateAllSessions($this->testUser['id']);
        }
        
        parent::tearDown();
    }
} 