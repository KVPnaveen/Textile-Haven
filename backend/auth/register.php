<?php
require_once '../includes/db.php';
require_once '../models/User.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$user = new User($pdo);

$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all fields']);
    exit;
}

if ($password !== $confirm_password) {
    echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
    exit;
}

if (strlen($password) < 8) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long']);
    exit;
}

// Additional password strength validation
if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $password)) {
    echo json_encode(['success' => false, 'message' => 'Password must contain at least one lowercase letter, one uppercase letter, and one number']);
    exit;
}

$result = $user->register($username, $email, $password);
echo json_encode($result);
