<?php
session_start();
require_once '../../backend/includes/db.php';
require_once '../../backend/includes/security.php';
require_once '../../backend/models/Cart.php';
require_once '../../backend/models/Product.php';
require_once '../../backend/models/Size.php';

// Initialize security for existing session
$security = Security::getInstanceForExistingSession();
$cart = new Cart($pdo);
$product = new Product($pdo);
$size = new Size($pdo);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../backend/auth/login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Handle checkout process
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'process_checkout') {
        // Validate CSRF token
        if (!$security->validateCSRFToken($_POST['csrf_token'])) {
            $error = 'Invalid request. Please try again.';
        } else {
            // Get cart items
            $cartItems = $cart->getCartItems($userId);

            if (empty($cartItems)) {
                $error = 'Your cart is empty.';
            } else {
                // Validate stock availability
                $validationErrors = $cart->validateCartItems($cartItems);

                if (!empty($validationErrors)) {
                    $error = 'Some items in your cart are no longer available: ' . implode(', ', $validationErrors);
                } else {
                    // Process the order (dummy checkout)
                    $orderSuccess = processOrder($cartItems, $userId, $pdo, $size);

                    if ($orderSuccess) {
                        // Clear the cart after successful order
                        $cart->clearCart($userId);

                        // Redirect to success page
                        header('Location: checkout-success.php');
                        exit;
                    } else {
                        $error = 'Failed to process your order. Please try again.';
                    }
                }
            }
        }
    }
}

// Get cart items for display
$cartItems = $cart->getCartItems($userId);
$totalAmount = 0;

foreach ($cartItems as $item) {
    $totalAmount += $item['price'] * $item['quantity'];
}

function processOrder($cartItems, $userId, $pdo, $size)
{
    try {
        $pdo->beginTransaction();

        // Create order record (dummy - you can expand this later)
        $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

        // Update stock quantities for each item
        foreach ($cartItems as $item) {
            if ($item['size_id']) {
                // Decrease stock for specific size
                $success = $size->decreaseStock($item['product_id'], $item['size_id'], $item['quantity']);
                if (!$success) {
                    throw new Exception("Failed to update stock for product {$item['name']}");
                }
            } else {
                // For items without size, decrease from any available size
                $availableSizes = $size->getAvailableSizes($item['product_id']);
                $remainingQuantity = $item['quantity'];

                foreach ($availableSizes as $availableSize) {
                    if ($remainingQuantity <= 0) break;

                    $stockToUse = min($availableSize['stock_quantity'], $remainingQuantity);
                    $success = $size->decreaseStock($item['product_id'], $availableSize['id'], $stockToUse);
                    if (!$success) {
                        throw new Exception("Failed to update stock for product {$item['name']}");
                    }
                    $remainingQuantity -= $stockToUse;
                }

                if ($remainingQuantity > 0) {
                    throw new Exception("Insufficient stock for product {$item['name']}");
                }
            }
        }

        // Log the order (you can expand this to save to a real orders table)
        $security = Security::getInstanceForExistingSession();
        $security->logSecurityEvent('order_processed', [
            'user_id' => $userId,
            'order_number' => $orderNumber,
            'items_count' => count($cartItems),
            'total_amount' => array_sum(array_map(function ($item) {
                return $item['price'] * $item['quantity'];
            }, $cartItems))
        ]);

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Order processing failed: " . $e->getMessage());
        return false;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Textile Haven</title>
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

    <div class="max-w-4xl mx-auto px-4 py-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Checkout</h1>
            <p class="text-gray-600 mt-2">Complete your purchase</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-md">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($cartItems)): ?>
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">Your cart is empty</h3>
                <p class="mt-1 text-sm text-gray-500">Add some products to your cart to continue.</p>
                <div class="mt-6">
                    <a href="products.php" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary hover:bg-purple-700">
                        Continue Shopping
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Order Summary -->
                <div class="lg:col-span-2">
                    <div class="bg-white shadow rounded-lg p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Order Summary</h2>

                        <div class="space-y-4">
                            <?php foreach ($cartItems as $item): ?>
                                <div class="flex items-center space-x-4 py-4 border-b border-gray-200 last:border-b-0">
                                    <div class="flex-shrink-0">
                                        <?php if ($item['image1']): ?>
                                            <img class="h-16 w-16 rounded-lg object-cover" src="<?php echo htmlspecialchars($product->formatImagePath($item['image1'])); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                        <?php else: ?>
                                            <div class="h-16 w-16 rounded-lg bg-gray-200 flex items-center justify-center">
                                                <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                                </svg>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="flex-1">
                                        <h3 class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item['name']); ?></h3>
                                        <?php if ($item['size_display_name']): ?>
                                            <p class="text-sm text-gray-500">Size: <?php echo htmlspecialchars($item['size_display_name']); ?></p>
                                        <?php endif; ?>
                                        <p class="text-sm text-gray-500">Quantity: <?php echo $item['quantity']; ?></p>
                                    </div>

                                    <div class="text-right">
                                        <p class="text-sm font-medium text-gray-900">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></p>
                                        <p class="text-sm text-gray-500">$<?php echo number_format($item['price'], 2); ?> each</p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Payment Form -->
                <div class="lg:col-span-1">
                    <div class="bg-white shadow rounded-lg p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Payment Information</h2>

                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="action" value="process_checkout">
                            <input type="hidden" name="csrf_token" value="<?php echo $security->generateCSRFToken(); ?>">

                            <!-- Dummy Payment Fields -->
                            <div>
                                <label for="card_number" class="block text-sm font-medium text-gray-700">Card Number</label>
                                <input type="text" id="card_number" name="card_number" value="4242 4242 4242 4242" required
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary">
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="expiry" class="block text-sm font-medium text-gray-700">Expiry Date</label>
                                    <input type="text" id="expiry" name="expiry" value="12/25" placeholder="MM/YY" required
                                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary">
                                </div>

                                <div>
                                    <label for="cvv" class="block text-sm font-medium text-gray-700">CVV</label>
                                    <input type="text" id="cvv" name="cvv" value="123" required
                                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary">
                                </div>
                            </div>

                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700">Cardholder Name</label>
                                <input type="text" id="name" name="name" value="John Doe" required
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary">
                            </div>

                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                                <input type="email" id="email" name="email" value="john@example.com" required
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary">
                            </div>

                            <!-- Order Total -->
                            <div class="border-t border-gray-200 pt-4">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Subtotal:</span>
                                    <span class="text-gray-900">$<?php echo number_format($totalAmount, 2); ?></span>
                                </div>
                                <div class="flex justify-between text-sm mt-1">
                                    <span class="text-gray-600">Shipping:</span>
                                    <span class="text-gray-900">$0.00</span>
                                </div>
                                <div class="flex justify-between text-sm mt-1">
                                    <span class="text-gray-600">Tax:</span>
                                    <span class="text-gray-900">$0.00</span>
                                </div>
                                <div class="flex justify-between text-lg font-semibold mt-3 pt-3 border-t border-gray-200">
                                    <span class="text-gray-900">Total:</span>
                                    <span class="text-gray-900">$<?php echo number_format($totalAmount, 2); ?></span>
                                </div>
                            </div>

                            <button type="submit"
                                class="w-full bg-primary hover:bg-purple-700 text-white font-medium py-3 px-4 rounded-md transition duration-200">
                                Complete Purchase
                            </button>
                        </form>

                        <div class="mt-4 text-xs text-gray-500 text-center">
                            <p>This is a demo checkout. No real payment will be processed.</p>
                            <p>Stock quantities will be updated in the database.</p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include '../components/footer.php'; ?>
</body>

</html>