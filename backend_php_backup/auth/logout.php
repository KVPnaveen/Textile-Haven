<?php
require_once '../includes/db.php';

// Destroy the session
session_destroy();

// Redirect to home page
header('Location: ../../frontend/pages/index.php');
exit();
