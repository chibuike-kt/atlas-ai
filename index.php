<?php
/**
 * Atlas AI Assistant - Main Index
 * This file handles routing based on user authentication status
 */

// Include necessary files
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

// Check if user is logged in
if (isLoggedIn()) {
    // User is logged in, redirect to dashboard
    header('Location: ' . BASE_URL . '/dashboard/orb.php');
    exit();
} else {
    // User is not logged in, redirect to login page
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit();
}
?>