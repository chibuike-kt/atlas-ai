<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Application configuration
define('SITE_NAME', 'Atlas AI Assistant');
define('BASE_URL', 'http://localhost:8080/atlas-ai');

// Security
define('HASH_COST', 12);

// Timezone
date_default_timezone_set('UTC');

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>