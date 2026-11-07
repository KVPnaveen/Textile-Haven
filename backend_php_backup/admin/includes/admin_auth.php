<?php

/**
 * Admin Authentication Check
 * Ensures only admin users can access admin pages
 */

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../models/User.php';

$user = new User($pdo);
$currentUser = $user->getCurrentUser();

// Check if user is logged in and is admin
if (!$currentUser || !$user->isAdmin()) {
    // Log unauthorized access attempt
    global $security;
    $security->logSecurityEvent('unauthorized_admin_access', [
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'requested_url' => $_SERVER['REQUEST_URI'] ?? 'unknown'
    ]);

    // Redirect to main site
    header('Location: ../../frontend/pages/index.php');
    exit;
}

// Check session timeout (1 hour)
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > 3600) {
    // Session expired
    $security->logSecurityEvent('admin_session_expired', [
        'user_id' => $currentUser['id'],
        'username' => $currentUser['username']
    ]);

    session_destroy();
    header('Location: ../../auth/login.php?expired=1');
    exit;
}

// Update last activity
$_SESSION['last_activity'] = time();
