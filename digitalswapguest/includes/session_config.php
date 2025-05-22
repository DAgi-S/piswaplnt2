<?php
/**
 * Session Configuration
 * This file must be included before session_start()
 */

// Session Configuration
define('SESSION_NAME', 'guest_portal');
define('SESSION_LIFETIME', 7200); // 2 hours
define('SESSION_PATH', '/');
define('SESSION_DOMAIN', '');
define('SESSION_SECURE', false);
define('SESSION_HTTPONLY', true);

// Initialize session settings
ini_set('session.name', SESSION_NAME);
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
ini_set('session.cookie_lifetime', SESSION_LIFETIME);
ini_set('session.cookie_path', SESSION_PATH);
if (SESSION_DOMAIN) {
    ini_set('session.cookie_domain', SESSION_DOMAIN);
}
ini_set('session.cookie_secure', SESSION_SECURE);
ini_set('session.cookie_httponly', SESSION_HTTPONLY);
ini_set('session.use_strict_mode', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.use_trans_sid', 0);
ini_set('session.cache_limiter', 'nocache');
?> 