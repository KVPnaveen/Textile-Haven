<?php
require_once '../includes/db.php';
require_once '../models/User.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$user = new User($pdo);

$identifier = trim($_POST['email'] ?? ''); // The field is still named 'email' but can contain username
$password = $_POST['password'] ?? '';

if (empty($identifier) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all fields']);
    exit;
}

$result = $user->login($identifier, $password);
echo json_encode($result);
