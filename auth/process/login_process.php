<?php
require_once '../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.php');
    exit();
}

// Get and sanitize input
$email = sanitizeInput($_POST['email']);
$password = $_POST['password'];
$remember = isset($_POST['remember']);

// Validation
if (empty($email) || !validateEmail($email)) {
    setFlashMessage("Valid email is required", 'error');
    header('Location: ../login.php');
    exit();
}

if (empty($password)) {
    setFlashMessage("Password is required", 'error');
    header('Location: ../login.php');
    exit();
}

// Get user from database
$user = getUserByEmail($email);

if (!$user || !verifyPassword($password, $user['password'])) {
    setFlashMessage("Invalid email or password", 'error');
    header('Location: ../login.php');
    exit();
}

// Login successful - set session
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_name'] = $user['full_name'];
$_SESSION['user_email'] = $user['email'];

// Update last login
updateLastLogin($user['id']);

// Set remember me cookie if checked (30 days)
if ($remember) {
    $token = bin2hex(random_bytes(32));
    setcookie('remember_token', $token, time() + (86400 * 30), '/');
    // In production, store this token in database
}

// Redirect to dashboard
header('Location: ../../dashboard/orb.php');
exit();
?>