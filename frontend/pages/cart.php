<?php
require_once '../../backend/models/Cart.php';
require_once '../../backend/models/Product.php';
require_once '../../backend/models/Size.php';
require_once '../../backend/models/User.php';

$cart = new Cart($pdo);
$product = new Product($pdo);
$user = new User($pdo);
$size = new Size($pdo);
$currentUser = $user->getCurrentUser();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (!$currentUser) {
        echo json_encode(['success' => false, 'message' => 'Please login to manage cart']);
        exit;
    }

    $action = $_POST['action'] ?? '';
    $userId = $currentUser['id'];

    switch ($action) {
        case 'add':
            $productId = intval($_POST['product_id'] ?? 0);
            $quantity = intval($_POST['quantity'] ?? 1);
            $sizeId = intval($_POST['size_id'] ?? 0);

            if ($productId && $quantity > 0) {
                $result = $cart->addToCart($userId, $productId, $quantity, $sizeId);
                echo json_encode($result);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid product or quantity']);
            }
            break;

        case 'update':
            $cartId = intval($_POST['cart_id'] ?? 0);
            $quantity = intval($_POST['quantity'] ?? 0);

            if ($cartId && $quantity >= 0) {
                $result = $cart->updateQuantity($cartId, $quantity);
                echo json_encode($result);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid cart item or quantity']);
            }
            break;

        case 'remove':
            $cartId = intval($_POST['cart_id'] ?? 0);

            if ($cartId) {
                $result = $cart->removeFromCart($cartId);
                echo json_encode($result);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid cart item']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    exit;
}

// Include header only for non-AJAX requests
include '../components/header.php';

// Check if user is admin and redirect to admin dashboard (only if not coming from admin area)
if ($currentUser && $user->isAdmin() && !isset($_GET['from_admin'])) {
    header('Location: ../../backend/admin/');
    exit;
}

// Get cart items for display
$cartItems = [];
$cartTotal = 0;

if ($currentUser) {
    $cartItems = $cart->getCartItems($currentUser['id']);
    $cartTotal = $cart->getCartTotal($currentUser['id']);
}
?>

<!-- Page Header -->
<div class="bg-white shadow">
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-gray-900">Shopping Cart</h1>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <?php if (!$currentUser): ?>
        <!-- Not Logged In -->
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Please login to view your cart</h3>
            <p class="mt-1 text-sm text-gray-500">Sign in to manage your shopping cart and checkout.</p>
            <div class="mt-6">
                <button onclick="openModal('loginModal')" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary hover:bg-purple-700">
                    Login
                </button>
            </div>
        </div>
    <?php elseif (empty($cartItems)): ?>
        <!-- Empty Cart -->
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.5 5M7 13l2.5 5m6-5v6a2 2 0 01-2 2H9a2 2 0 01-2-2v-6m8 0V9a2 2 0 00-2-2H9a2 2 0 00-2 2v4.01" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Your cart is empty</h3>
            <p class="mt-1 text-sm text-gray-500">Start shopping to add items to your cart.</p>
            <div class="mt-6">
                <a href="products.php" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary hover:bg-purple-700">
                    Continue Shopping
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Cart Items -->
            <div class="lg:w-2/3">
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Cart Items (<?php echo count($cartItems); ?>)</h2>
                    </div>
                    <div class="divide-y divide-gray-200">
                        <?php foreach ($cartItems as $item): ?>
                            <div class="p-6 flex items-center space-x-4">
                                <div class="flex-shrink-0">
                                    <?php if ($item['image1']): ?>
                                        <img src="<?php echo htmlspecialchars($product->formatImagePath($item['image1'])); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="w-20 h-20 object-cover rounded-lg">
                                    <?php else: ?>
                                        <div class="w-20 h-20 bg-gradient-to-br from-gray-100 to-gray-200 rounded-lg flex items-center justify-center">
                                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                            </svg>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($item['name']); ?></h3>
                                    <p class="text-gray-500">Price: <?php echo $cart->formatPrice($item['price']); ?></p>
                                    <?php if ($item['size_display_name']): ?>
                                        <p class="text-gray-500">Size: <?php echo htmlspecialchars($item['size_display_name']); ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($item)); ?>)"
                                        class="w-8 h-8 bg-blue-500 text-white rounded-full flex items-center justify-center hover:bg-blue-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <span class="w-12 text-center text-gray-900"><?php echo $item['quantity']; ?></span>
                                </div>
                                <div class="text-right">
                                    <p class="text-lg font-semibold text-gray-900"><?php echo $cart->formatPrice($item['price'] * $item['quantity']); ?></p>
                                    <button onclick="removeItem(<?php echo $item['id']; ?>)"
                                        class="text-red-500 hover:text-red-700 text-sm">
                                        Remove
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="lg:w-1/3">
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Order Summary</h2>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Subtotal</span>
                            <span class="text-gray-900"><?php echo $cart->formatPrice($cartTotal); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Shipping</span>
                            <span class="text-gray-900">$5.99</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Tax</span>
                            <span class="text-gray-900"><?php echo $cart->formatPrice($cartTotal * 0.08); ?></span>
                        </div>
                        <hr class="border-gray-200">
                        <div class="flex justify-between">
                            <span class="text-lg font-semibold text-gray-900">Total</span>
                            <span class="text-lg font-semibold text-gray-900"><?php echo $cart->formatPrice($cartTotal + 5.99 + ($cartTotal * 0.08)); ?></span>
                        </div>

                        <div class="mt-6">
                            <button onclick="checkout()"
                                class="w-full bg-primary text-white py-3 px-4 rounded-lg hover:bg-purple-700 transition duration-300 font-semibold">
                                Proceed to Checkout
                            </button>
                        </div>

                        <div class="text-center">
                            <a href="products.php" class="text-primary hover:text-purple-500 text-sm">
                                Continue Shopping
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Shipping Info -->
                <div class="bg-white rounded-lg shadow mt-6">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Shipping Information</h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-3">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="text-sm text-gray-600">Free shipping on orders over $50</span>
                            </div>
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="text-sm text-gray-600">Estimated delivery: 3-5 business days</span>
                            </div>
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="text-sm text-gray-600">30-day return policy</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    function updateQuantity(cartId, change) {
        const currentQuantity = parseInt(document.querySelector(`[onclick="updateQuantity(${cartId}, -1)"]`).nextElementSibling.textContent);
        const newQuantity = currentQuantity + change;

        if (newQuantity <= 0) {
            removeItem(cartId);
            return;
        }

        fetch('cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=update&cart_id=${cartId}&quantity=${newQuantity}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Failed to update cart: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
    }

    function removeItem(cartId) {
        if (confirm('Are you sure you want to remove this item from your cart?')) {
            fetch('cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=remove&cart_id=${cartId}`
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Failed to remove item: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                });
        }
    }

    function checkout() {
        window.location.href = 'checkout.php';
    }
</script>

<!-- Edit Cart Item Modal -->
<div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-10 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Edit Cart Item</h3>
                <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="flex space-x-4">
                <!-- Product Image -->
                <div class="flex-shrink-0">
                    <img id="edit-product-image" class="w-20 h-20 object-cover rounded-lg" src="" alt="">
                </div>

                <!-- Product Details -->
                <div class="flex-1">
                    <h4 id="edit-product-name" class="text-lg font-semibold text-gray-900"></h4>
                    <p id="edit-product-price" class="text-lg font-bold text-primary"></p>
                    <p id="edit-current-size" class="text-sm text-gray-500"></p>
                </div>
            </div>

            <!-- Size Selection -->
            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Size: <span id="edit-selected-size-text" class="text-primary font-semibold">Select a size</span></label>
                <div class="flex flex-wrap gap-2 mb-2" id="edit-size-options">
                    <!-- Size options will be populated by JavaScript -->
                </div>
                <input type="hidden" id="edit-size" name="size" value="">
                <p class="text-xs text-gray-500">Select your preferred size (shows current availability)</p>
            </div>

            <!-- Quantity Selection -->
            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Quantity:</label>
                <div class="flex items-center space-x-3">
                    <button onclick="decreaseQuantity()" class="w-8 h-8 bg-gray-200 text-gray-600 rounded-full flex items-center justify-center hover:bg-gray-300">
                        -
                    </button>
                    <input type="number" id="edit-quantity" value="1" min="1" max="10"
                        class="w-16 text-center border border-gray-300 rounded-md px-2 py-1">
                    <button onclick="increaseQuantity()" class="w-8 h-8 bg-gray-200 text-gray-600 rounded-full flex items-center justify-center hover:bg-gray-300">
                        +
                    </button>
                </div>
            </div>

            <!-- Stock Alert -->
            <div id="stock-alert" class="mt-3 text-sm text-red-600 hidden">
                <!-- Stock alert will be shown here -->
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end space-x-3 mt-6">
                <button onclick="closeEditModal()"
                    class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-md text-sm font-medium">
                    Cancel
                </button>
                <button onclick="updateCartItem()"
                    class="bg-primary hover:bg-purple-700 text-white px-6 py-2 rounded-md text-sm font-medium">
                    Update Cart
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    let currentCartItem = null;
    let currentProductId = null;

    function openEditModal(cartItem) {
        currentCartItem = cartItem;
        currentProductId = cartItem.product_id;

        // Populate product details
        document.getElementById('edit-product-name').textContent = cartItem.name;
        document.getElementById('edit-product-price').textContent = '$' + parseFloat(cartItem.price).toFixed(2);

        // Use actual product image or show placeholder div like in cart view
        const imageElement = document.getElementById('edit-product-image');
        if (cartItem.image1) {
            // Format the image path to use the correct relative path
            const formattedImagePath = '../../backend/admin/assets/images/' + cartItem.image1.split('/').pop();
            imageElement.src = formattedImagePath;
            imageElement.style.display = 'block';
        } else {
            imageElement.style.display = 'none';
            // Create placeholder div if it doesn't exist
            let placeholder = document.getElementById('edit-product-image-placeholder');
            if (!placeholder) {
                placeholder = document.createElement('div');
                placeholder.id = 'edit-product-image-placeholder';
                placeholder.className = 'w-20 h-20 bg-gradient-to-br from-gray-100 to-gray-200 rounded-lg flex items-center justify-center';
                placeholder.innerHTML = '<svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" /></svg>';
                imageElement.parentNode.appendChild(placeholder);
            }
            placeholder.style.display = 'flex';
        }

        // Load size options with real-time stock data from database
        loadSizeOptions(cartItem.product_id, cartItem.size_id);

        // Set initial quantity after sizes are loaded
        setTimeout(() => {
            const quantityInput = document.getElementById('edit-quantity');
            const currentQuantity = parseInt(cartItem.quantity) || 1;
            const maxQuantity = parseInt(quantityInput.max) || 10;

            // Set quantity within valid range
            if (currentQuantity > maxQuantity) {
                quantityInput.value = maxQuantity;
            } else if (currentQuantity < 1) {
                quantityInput.value = 1;
            } else {
                quantityInput.value = currentQuantity;
            }

            // Add event listener for real-time quantity updates
            quantityInput.addEventListener('input', updateQuantityDisplay);
            updateQuantityDisplay();
        }, 100);

        // Show modal
        document.getElementById('editModal').classList.remove('hidden');
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');

        // Reset image display
        const imageElement = document.getElementById('edit-product-image');
        const placeholder = document.getElementById('edit-product-image-placeholder');
        if (placeholder) {
            placeholder.style.display = 'none';
        }
        imageElement.style.display = 'block';

        currentCartItem = null;
        currentProductId = null;
    }

    function loadSizeOptions(productId, currentSizeId) {
        console.log('Loading sizes for product:', productId);
        fetch(`../../backend/api/get_product_sizes.php?product_id=${productId}`)
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(sizes => {
                console.log('Received sizes data:', sizes);
                const sizeContainer = document.getElementById('edit-size-options');
                sizeContainer.innerHTML = '';

                // Get all sizes
                const allSizes = <?php echo json_encode($size->getAllSizes()); ?>;

                allSizes.forEach(size => {
                    // Check current availability from database - real-time stock data
                    let isAvailable = false;
                    let stockQuantity = 0;

                    // Find the size in the database response
                    const sizeData = sizes.find(s => s.id == size.id);
                    if (sizeData) {
                        stockQuantity = parseInt(sizeData.stock_quantity) || 0;
                        isAvailable = stockQuantity > 0; // Available if stock > 0
                        console.log(`Size ${size.name}: stock=${stockQuantity}, available=${isAvailable}`);
                    } else {
                        console.log(`Size ${size.name}: not found in database response`);
                    }

                    const isCurrentSize = currentSizeId == size.id;

                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = `size-option relative w-12 h-12 border-2 rounded-md text-sm font-medium transition-all duration-200 ${
                        isAvailable ? 'border-gray-300 hover:border-primary text-gray-700 hover:text-primary cursor-pointer' : 'border-gray-200 text-gray-400 cursor-not-allowed'
                    } ${isAvailable ? '' : 'unavailable'} ${isCurrentSize ? 'selected' : ''}`;
                    button.dataset.sizeId = size.id;
                    button.dataset.sizeName = size.display_name;
                    button.dataset.stock = stockQuantity;
                    button.disabled = !isAvailable;
                    button.textContent = size.name;

                    if (!isAvailable) {
                        const overlay = document.createElement('div');
                        overlay.className = 'absolute inset-0 bg-red-100 opacity-50';
                        const slash = document.createElement('div');
                        slash.className = 'absolute inset-0 flex items-center justify-center';
                        slash.innerHTML = '<div class="w-full h-0.5 bg-red-400 transform rotate-45"></div>';
                        button.appendChild(overlay);
                        button.appendChild(slash);
                    }

                    button.addEventListener('click', function() {
                        if (this.disabled) return;

                        // Remove selected class from all options
                        document.querySelectorAll('#edit-size-options .size-option').forEach(opt => {
                            opt.classList.remove('selected');
                        });

                        // Add selected class to clicked option
                        this.classList.add('selected');

                        // Update hidden input and display text
                        const sizeId = this.dataset.sizeId;
                        const sizeName = this.dataset.sizeName;
                        const stock = parseInt(this.dataset.stock);

                        document.getElementById('edit-size').value = sizeId;
                        document.getElementById('edit-selected-size-text').textContent = sizeName;

                        // Update quantity input with real-time stock data
                        const quantityInput = document.getElementById('edit-quantity');
                        const currentQuantity = parseInt(quantityInput.value) || 1;
                        const maxQuantity = Math.min(stock, 10);

                        // Set max quantity
                        quantityInput.max = maxQuantity;

                        // Reset quantity if current quantity exceeds new stock
                        if (currentQuantity > maxQuantity) {
                            quantityInput.value = maxQuantity;
                        } else if (currentQuantity < 1) {
                            quantityInput.value = 1;
                        }

                        // Show stock alert if low
                        if (stock <= 3) {
                            document.getElementById('stock-alert').textContent = `Hurry up! only ${stock} left`;
                            document.getElementById('stock-alert').classList.remove('hidden');
                        } else {
                            document.getElementById('stock-alert').classList.add('hidden');
                        }
                    });

                    sizeContainer.appendChild(button);
                });

                // Set current size as selected
                if (currentSizeId) {
                    document.getElementById('edit-size').value = currentSizeId;
                    const currentSize = allSizes.find(s => s.id == currentSizeId);
                    if (currentSize) {
                        document.getElementById('edit-selected-size-text').textContent = currentSize.display_name;
                    }
                }
            })
            .catch(error => {
                console.error('Error loading sizes:', error);
                console.error('Error details:', error.message);
                // Fallback: create basic size options if fetch fails
                const sizeContainer = document.getElementById('edit-size-options');
                const allSizes = <?php echo json_encode($size->getAllSizes()); ?>;

                allSizes.forEach(size => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = `size-option relative w-12 h-12 border-2 rounded-md text-sm font-medium transition-all duration-200 border-gray-300 hover:border-primary text-gray-700 hover:text-primary cursor-pointer`;
                    button.dataset.sizeId = size.id;
                    button.dataset.sizeName = size.display_name;
                    button.dataset.stock = 10; // Assume some stock for fallback
                    button.textContent = size.name;

                    button.addEventListener('click', function() {
                        document.querySelectorAll('#edit-size-options .size-option').forEach(opt => {
                            opt.classList.remove('selected');
                        });
                        this.classList.add('selected');
                        document.getElementById('edit-size').value = this.dataset.sizeId;
                        document.getElementById('edit-selected-size-text').textContent = this.dataset.sizeName;
                    });

                    sizeContainer.appendChild(button);
                });

                // Show a warning message
                const warning = document.createElement('div');
                warning.className = 'mt-2 text-sm text-orange-600';
                warning.textContent = 'Note: Stock information may not be current due to connection issues.';
                sizeContainer.parentNode.appendChild(warning);
            });
    }

    function decreaseQuantity() {
        const input = document.getElementById('edit-quantity');
        const currentValue = parseInt(input.value);
        if (currentValue > 1) {
            input.value = currentValue - 1;
            updateQuantityDisplay();
        }
    }

    function increaseQuantity() {
        const input = document.getElementById('edit-quantity');
        const currentValue = parseInt(input.value);
        const maxValue = parseInt(input.max);
        if (currentValue < maxValue) {
            input.value = currentValue + 1;
            updateQuantityDisplay();
        }
    }

    function updateQuantityDisplay() {
        const input = document.getElementById('edit-quantity');
        const currentValue = parseInt(input.value) || 1;
        const maxValue = parseInt(input.max) || 10;

        // Update stock alert based on current quantity
        const stockAlert = document.getElementById('stock-alert');
        if (currentValue >= maxValue) {
            stockAlert.textContent = `Maximum quantity (${maxValue}) reached`;
            stockAlert.classList.remove('hidden');
        } else if (maxValue <= 3) {
            stockAlert.textContent = `Hurry up! only ${maxValue} left`;
            stockAlert.classList.remove('hidden');
        } else {
            stockAlert.classList.add('hidden');
        }
    }

    function updateCartItem() {
        const newSizeId = document.getElementById('edit-size').value;
        const newQuantity = document.getElementById('edit-quantity').value;

        if (!newSizeId) {
            alert('Please select a size');
            return;
        }

        if (!currentCartItem) {
            alert('No cart item selected');
            return;
        }

        // Remove old item
        fetch('cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=remove&cart_id=' + currentCartItem.id
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Add new item with updated size/quantity
                    return fetch('cart.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'action=add&product_id=' + currentProductId + '&size_id=' + newSizeId + '&quantity=' + newQuantity
                    });
                } else {
                    throw new Error('Failed to remove old item: ' + (data.message || 'Unknown error'));
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    closeEditModal();
                    location.reload();
                } else {
                    alert('Failed to update cart: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
    }
</script>

<style>
    /* Size selection styling for modal */
    .size-option {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        transition: all 0.2s ease;
    }

    .size-option:hover:not([disabled]) {
        transform: scale(1.05);
    }

    .size-option.unavailable {
        position: relative;
        overflow: hidden;
    }

    .size-option.unavailable::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(45deg, transparent 40%, rgba(239, 68, 68, 0.3) 40%, rgba(239, 68, 68, 0.3) 60%, transparent 60%);
        pointer-events: none;
    }

    .size-option.selected {
        border-color: #8b5cf6 !important;
        background-color: #8b5cf6 !important;
        color: white !important;
    }
</style>

<?php include '../components/footer.php'; ?>