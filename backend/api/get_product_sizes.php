<?php
// Start output buffering to prevent header issues
ob_start();

require_once '../includes/db.php';
require_once '../models/Size.php';

// Check if product_id is provided
if (!isset($_GET['product_id']) || !is_numeric($_GET['product_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid product ID']);
    exit;
}

$productId = intval($_GET['product_id']);

try {
    $size = new Size($pdo);
    $productSizes = $size->getProductSizes($productId);

    // Return all sizes with current stock data from database
    // This ensures real-time availability information
    header('Content-Type: application/json');
    echo json_encode($productSizes);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
