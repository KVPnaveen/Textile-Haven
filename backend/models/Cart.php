<?php
require_once __DIR__ . '/../includes/db.php';

class Cart
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Add item to cart
     */
    public function addToCart($userId, $productId, $quantity = 1, $sizeId = null)
    {
        try {
            // Check if item already exists in cart with same size
            $stmt = $this->pdo->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ? AND size_id = ?");
            $stmt->execute([$userId, $productId, $sizeId]);
            $existingItem = $stmt->fetch();

            if ($existingItem) {
                // Update quantity
                $newQuantity = $existingItem['quantity'] + $quantity;
                $stmt = $this->pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
                $stmt->execute([$newQuantity, $existingItem['id']]);
            } else {
                // Add new item
                $stmt = $this->pdo->prepare("INSERT INTO cart (user_id, product_id, size_id, quantity) VALUES (?, ?, ?, ?)");
                $stmt->execute([$userId, $productId, $sizeId, $quantity]);
            }

            return ['success' => true, 'message' => 'Item added to cart!'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to add item to cart.'];
        }
    }

    /**
     * Get user's cart items
     */
    public function getCartItems($userId)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT c.id, c.quantity, c.size_id, p.id as product_id, p.name, p.description, p.price, p.image1, p.image2,
                       s.name as size_name, s.display_name as size_display_name
                FROM cart c
                JOIN products p ON c.product_id = p.id
                LEFT JOIN sizes s ON c.size_id = s.id
                WHERE c.user_id = ?
                ORDER BY c.id DESC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Update cart item quantity
     */
    public function updateQuantity($cartId, $quantity)
    {
        try {
            if ($quantity <= 0) {
                // Remove item if quantity is 0 or negative
                return $this->removeFromCart($cartId);
            }

            $stmt = $this->pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
            $stmt->execute([$quantity, $cartId]);

            return ['success' => true, 'message' => 'Cart updated!'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to update cart.'];
        }
    }

    /**
     * Remove item from cart
     */
    public function removeFromCart($cartId)
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM cart WHERE id = ?");
            $stmt->execute([$cartId]);

            return ['success' => true, 'message' => 'Item removed from cart!'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to remove item from cart.'];
        }
    }

    /**
     * Clear user's cart
     */
    public function clearCart($userId)
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$userId]);

            return ['success' => true, 'message' => 'Cart cleared!'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to clear cart.'];
        }
    }

    /**
     * Get cart total
     */
    public function getCartTotal($userId)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT SUM(c.quantity * p.price) as total
                FROM cart c
                JOIN products p ON c.product_id = p.id
                WHERE c.user_id = ?
            ");
            $stmt->execute([$userId]);
            $result = $stmt->fetch();

            return $result['total'] ?? 0;
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * Get cart item count
     */
    public function getCartItemCount($userId)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT SUM(quantity) as count FROM cart WHERE user_id = ?");
            $stmt->execute([$userId]);
            $result = $stmt->fetch();

            return $result['count'] ?? 0;
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * Check if product is in cart
     */
    public function isInCart($userId, $productId, $sizeId = null)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT id FROM cart WHERE user_id = ? AND product_id = ? AND size_id = ?");
            $stmt->execute([$userId, $productId, $sizeId]);

            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Get cart item by ID
     */
    public function getCartItem($cartId)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT c.id, c.quantity, c.size_id, p.id as product_id, p.name, p.description, p.price, p.image1, p.image2,
                       s.name as size_name, s.display_name as size_display_name
                FROM cart c
                JOIN products p ON c.product_id = p.id
                LEFT JOIN sizes s ON c.size_id = s.id
                WHERE c.id = ?
            ");
            $stmt->execute([$cartId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Validate cart items (check stock)
     */
    public function validateCart($userId)
    {
        try {
            $cartItems = $this->getCartItems($userId);
            $errors = [];

            require_once __DIR__ . '/Size.php';
            $sizeModel = new Size($this->pdo);

            foreach ($cartItems as $item) {
                if ($item['size_id']) {
                    // Check size-specific stock
                    $availableStock = $sizeModel->getStockQuantity($item['product_id'], $item['size_id']);
                    if ($item['quantity'] > $availableStock) {
                        $sizeName = $item['size_display_name'] ?? $item['size_name'] ?? 'Unknown Size';
                        $errors[] = "Insufficient stock for {$item['name']} (Size: {$sizeName}). Available: {$availableStock}";
                    }
                } else {
                    // For items without size, check total stock across all sizes
                    $totalStock = $sizeModel->getTotalStock($item['product_id']);
                    if ($item['quantity'] > $totalStock) {
                        $errors[] = "Insufficient stock for {$item['name']}. Available: {$totalStock}";
                    }
                }
            }

            return $errors;
        } catch (PDOException $e) {
            return ['Error validating cart.'];
        }
    }

    /**
     * Validate cart items (check stock) - alias for validateCart
     */
    public function validateCartItems($cartItems)
    {
        try {
            $errors = [];

            require_once __DIR__ . '/Size.php';
            $sizeModel = new Size($this->pdo);

            foreach ($cartItems as $item) {
                if ($item['size_id']) {
                    // Check size-specific stock
                    $availableStock = $sizeModel->getStockQuantity($item['product_id'], $item['size_id']);
                    if ($item['quantity'] > $availableStock) {
                        $sizeName = $item['size_display_name'] ?? $item['size_name'] ?? 'Unknown Size';
                        $errors[] = "Insufficient stock for {$item['name']} (Size: {$sizeName}). Available: {$availableStock}";
                    }
                } else {
                    // For items without size, check total stock across all sizes
                    $totalStock = $sizeModel->getTotalStock($item['product_id']);
                    if ($item['quantity'] > $totalStock) {
                        $errors[] = "Insufficient stock for {$item['name']}. Available: {$totalStock}";
                    }
                }
            }

            return $errors;
        } catch (PDOException $e) {
            return ['Error validating cart items.'];
        }
    }

    /**
     * Format price for display
     */
    public function formatPrice($price)
    {
        return '$' . number_format($price, 2);
    }
}
