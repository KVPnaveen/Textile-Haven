<?php
session_start();
require_once '../../backend/includes/db.php';
require_once '../../backend/includes/security.php';

// Initialize security for existing session
$security = Security::getInstanceForExistingSession();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../backend/auth/login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed - Textile Haven</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#8B5CF6'
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-gray-50">
    <?php include '../components/header.php'; ?>

    <div class="max-w-2xl mx-auto px-4 py-16">
        <div class="text-center">
            <!-- Success Icon -->
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-6">
                <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>

            <!-- Success Message -->
            <h1 class="text-3xl font-bold text-gray-900 mb-4">Order Confirmed!</h1>
            <p class="text-lg text-gray-600 mb-8">
                Thank you for your purchase. Your order has been successfully processed and stock quantities have been updated.
            </p>

            <!-- Order Details -->
            <div class="bg-white shadow rounded-lg p-6 mb-8">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Order Details</h2>
                <div class="space-y-3 text-left">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Order Number:</span>
                        <span class="font-medium text-gray-900">ORD-<?php echo date('Ymd'); ?>-<?php echo strtoupper(substr(uniqid(), -6)); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Order Date:</span>
                        <span class="font-medium text-gray-900"><?php echo date('F j, Y'); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Status:</span>
                        <span class="font-medium text-green-600">Confirmed</span>
                    </div>
                </div>
            </div>

            <!-- What Happens Next -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">
                <h3 class="text-lg font-semibold text-blue-900 mb-3">What Happens Next?</h3>
                <div class="space-y-2 text-left text-blue-800">
                    <div class="flex items-start">
                        <span class="flex-shrink-0 w-6 h-6 bg-blue-200 rounded-full flex items-center justify-center text-blue-600 text-sm font-medium mr-3 mt-0.5">1</span>
                        <span>Your order has been processed and stock quantities have been updated in our database.</span>
                    </div>
                    <div class="flex items-start">
                        <span class="flex-shrink-0 w-6 h-6 bg-blue-200 rounded-full flex items-center justify-center text-blue-600 text-sm font-medium mr-3 mt-0.5">2</span>
                        <span>In a real e-commerce system, you would receive an order confirmation email.</span>
                    </div>
                    <div class="flex items-start">
                        <span class="flex-shrink-0 w-6 h-6 bg-blue-200 rounded-full flex items-center justify-center text-blue-600 text-sm font-medium mr-3 mt-0.5">3</span>
                        <span>Your items would be prepared for shipping and tracking information would be provided.</span>
                    </div>
                </div>
            </div>

            <!-- Demo Notice -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-8">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">Demo Notice</h3>
                        <div class="mt-2 text-sm text-yellow-700">
                            <p>This is a demonstration checkout system. No real payment was processed, but the product stock quantities have been updated in the database. You can verify this by checking the admin panel.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="products.php" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-primary hover:bg-purple-700 transition duration-200">
                    Continue Shopping
                </a>
                <a href="cart.php" class="inline-flex items-center px-6 py-3 border border-gray-300 text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition duration-200">
                    View Cart
                </a>
            </div>
        </div>
    </div>

    <?php include '../components/footer.php'; ?>
</body>

</html>