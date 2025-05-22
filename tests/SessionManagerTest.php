<?php
/**
 * Session Manager Test
 * Tests the session management functionality
 */

class SessionManagerTest extends TestCase {
    private $sessionManager;
    private $testUser;
    private $testRefreshToken;
    private $testSessionId;

    protected function setUp(): void {
        parent::setUp();
        
        // Initialize session manager
        $this->sessionManager = SessionManager::getInstance();
        
        // Create test user
        $this->testUser = [
            'id' => 1,
            'email' => 'test@example.com',
            'role' => 'user'
        ];
        
        // Generate test refresh token
        $jwtHandler = JWTHandler::getInstance();
        $this->testRefreshToken = $jwtHandler->generateRefreshToken($this->testUser['id']);
    }

    /**
     * Test session creation
     */
    public function testCreateSession() {
        $sessionId = $this->sessionManager->createSession(
            $this->testUser['id'],
            $this->testRefreshToken
        );

        $this->assertNotFalse($sessionId);
        $this->assertEquals(64, strlen($sessionId)); // 32 bytes = 64 hex chars
        
        $this->testSessionId = $sessionId;
    }

    /**
     * Test session validation
     */
    public function testValidateSession() {
        // Create a session first
        $sessionId = $this->sessionManager->createSession(
            $this->testUser['id'],
            $this->testRefreshToken
        );

        // Test valid session
        $isValid = $this->sessionManager->validateSession(
            $this->testUser['id'],
            $sessionId,
            $this->testRefreshToken
        );
        $this->assertTrue($isValid);

        // Test invalid session ID
        $isValid = $this->sessionManager->validateSession(
            $this->testUser['id'],
            'invalid_session_id',
            $this->testRefreshToken
        );
        $this->assertFalse($isValid);

        // Test invalid refresh token
        $isValid = $this->sessionManager->validateSession(
            $this->testUser['id'],
            $sessionId,
            'invalid_refresh_token'
        );
        $this->assertFalse($isValid);
    }

    /**
     * Test session invalidation
     */
    public function testInvalidateSession() {
        // Create a session
        $sessionId = $this->sessionManager->createSession(
            $this->testUser['id'],
            $this->testRefreshToken
        );

        // Validate session is active
        $isValid = $this->sessionManager->validateSession(
            $this->testUser['id'],
            $sessionId,
            $this->testRefreshToken
        );
        $this->assertTrue($isValid);

        // Invalidate session
        $result = $this->sessionManager->invalidateSession(
            $this->testUser['id'],
            $sessionId
        );
        $this->assertTrue($result);

        // Verify session is no longer valid
        $isValid = $this->sessionManager->validateSession(
            $this->testUser['id'],
            $sessionId,
            $this->testRefreshToken
        );
        $this->assertFalse($isValid);
    }

    /**
     * Test getting active sessions
     */
    public function testGetActiveSessions() {
        // Create multiple sessions
        $sessionId1 = $this->sessionManager->createSession(
            $this->testUser['id'],
            $this->testRefreshToken
        );
        
        $sessionId2 = $this->sessionManager->createSession(
            $this->testUser['id'],
            $jwtHandler->generateRefreshToken($this->testUser['id'])
        );

        // Get active sessions
        $sessions = $this->sessionManager->getActiveSessions($this->testUser['id']);
        
        $this->assertIsArray($sessions);
        $this->assertCount(2, $sessions);
        
        // Verify session data
        $this->assertEquals($sessionId1, $sessions[0]['session_id']);
        $this->assertEquals($_SERVER['REMOTE_ADDR'], $sessions[0]['ip_address']);
        $this->assertEquals($_SERVER['HTTP_USER_AGENT'], $sessions[0]['user_agent']);
    }

    /**
     * Test invalidating all sessions
     */
    public function testInvalidateAllSessions() {
        // Create multiple sessions
        $sessionId1 = $this->sessionManager->createSession(
            $this->testUser['id'],
            $this->testRefreshToken
        );
        
        $sessionId2 = $this->sessionManager->createSession(
            $this->testUser['id'],
            $jwtHandler->generateRefreshToken($this->testUser['id'])
        );

        // Invalidate all sessions except sessionId1
        $result = $this->sessionManager->invalidateAllSessions(
            $this->testUser['id'],
            $sessionId1
        );
        $this->assertTrue($result);

        // Verify only sessionId1 is still valid
        $sessions = $this->sessionManager->getActiveSessions($this->testUser['id']);
        $this->assertCount(1, $sessions);
        $this->assertEquals($sessionId1, $sessions[0]['session_id']);
    }

    /**
     * Test session cleanup
     */
    public function testCleanupExpiredSessions() {
        // Create an expired session by manipulating the database directly
        $database = new PiStockDatabase();
        $db = $database->getConnection();
        
        $sessionId = $this->sessionManager->createSession(
            $this->testUser['id'],
            $this->testRefreshToken
        );

        // Set session to expired
        $query = "UPDATE user_sessions 
                 SET expires_at = DATE_SUB(NOW(), INTERVAL 1 DAY) 
                 WHERE session_id = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param('s', $sessionId);
        $stmt->execute();

        // Create a new active session
        $activeSessionId = $this->sessionManager->createSession(
            $this->testUser['id'],
            $jwtHandler->generateRefreshToken($this->testUser['id'])
        );

        // Get active sessions (should trigger cleanup)
        $sessions = $this->sessionManager->getActiveSessions($this->testUser['id']);
        
        // Verify only active session remains
        $this->assertCount(1, $sessions);
        $this->assertEquals($activeSessionId, $sessions[0]['session_id']);
    }

    protected function tearDown(): void {
        // Clean up test sessions
        if (isset($this->testUser)) {
            $this->sessionManager->invalidateAllSessions($this->testUser['id']);
        }
        
        parent::tearDown();
    }
} 