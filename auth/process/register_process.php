<?php
require_once '../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../register.php');
    exit();
}

// Get and sanitize input
$full_name = sanitizeInput($_POST['full_name']);
$email = sanitizeInput($_POST['email']);
$password = $_POST['password'];
$confirm_password = $_POST['confirm_password'];

// Validation
$errors = [];

if (empty($full_name)) {
    $errors[] = "Full name is required";
}

if (empty($email) || !validateEmail($email)) {
    $errors[] = "Valid email is required";
}

if (strlen($password) < 8) {
    $errors[] = "Password must be at least 8 characters";
}

if ($password !== $confirm_password) {
    $errors[] = "Passwords do not match";
}

// Check if email already exists
$existingUser = getUserByEmail($email);
if ($existingUser) {
    $errors[] = "Email already registered";
}

if (!empty($errors)) {
    setFlashMessage(implode(', ', $errors), 'error');
    header('Location: ../register.php');
    exit();
}

// Hash password and create user
$hashedPassword = hashPassword($password);
$conn = getDBConnection();

$stmt = $conn->prepare("INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $full_name, $email, $hashedPassword);

if ($stmt->execute()) {
    setFlashMessage("Account created successfully! Please login.", 'success');
    header('Location: ../login.php');
} else {
    setFlashMessage("Registration failed. Please try again.", 'error');
    header('Location: ../register.php');
}

$stmt->close();
$conn->close();
exit();
?>