<?php
require_once __DIR__ . '/../includes/db.php';

class Size
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Get all sizes ordered by sort order
     */
    public function getAllSizes()
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM sizes ORDER BY sort_order ASC");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Get size by ID
     */
    public function getSizeById($id)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM sizes WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Get size by name
     */
    public function getSizeByName($name)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM sizes WHERE name = ?");
            $stmt->execute([$name]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Get product sizes with stock information
     */
    public function getProductSizes($productId)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    s.id,
                    s.name,
                    s.display_name,
                    s.sort_order,
                    COALESCE(ps.stock_quantity, 0) as stock_quantity
                FROM sizes s
                LEFT JOIN product_sizes ps ON s.id = ps.size_id AND ps.product_id = ?
                ORDER BY s.sort_order ASC
            ");
            $stmt->execute([$productId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Get available sizes for a product (sizes with stock > 0)
     */
    public function getAvailableSizes($productId)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    s.id,
                    s.name,
                    s.display_name,
                    s.sort_order,
                    ps.stock_quantity
                FROM sizes s
                INNER JOIN product_sizes ps ON s.id = ps.size_id
                WHERE ps.product_id = ? AND ps.stock_quantity > 0
                ORDER BY s.sort_order ASC
            ");
            $stmt->execute([$productId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Check if a product has any stock in any size
     */
    public function hasStock($productId)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count
                FROM product_sizes 
                WHERE product_id = ? AND stock_quantity > 0
            ");
            $stmt->execute([$productId]);
            $result = $stmt->fetch();
            return $result && $result['count'] > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Get stock quantity for a specific product and size
     */
    public function getStockQuantity($productId, $sizeId)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT stock_quantity 
                FROM product_sizes 
                WHERE product_id = ? AND size_id = ?
            ");
            $stmt->execute([$productId, $sizeId]);
            $result = $stmt->fetch();
            return $result ? $result['stock_quantity'] : 0;
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * Update stock quantity for a product size
     */
    public function updateStockQuantity($productId, $sizeId, $quantity)
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO product_sizes (product_id, size_id, stock_quantity) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE stock_quantity = VALUES(stock_quantity)
            ");
            return $stmt->execute([$productId, $sizeId, $quantity]);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Decrease stock quantity (for purchases)
     */
    public function decreaseStock($productId, $sizeId, $quantity)
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE product_sizes 
                SET stock_quantity = stock_quantity - ? 
                WHERE product_id = ? AND size_id = ? AND stock_quantity >= ?
            ");
            return $stmt->execute([$quantity, $productId, $sizeId, $quantity]);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Increase stock quantity (for returns/cancellations)
     */
    public function increaseStock($productId, $sizeId, $quantity)
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE product_sizes 
                SET stock_quantity = stock_quantity + ? 
                WHERE product_id = ? AND size_id = ?
            ");
            return $stmt->execute([$quantity, $productId, $sizeId]);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Delete all size entries for a product
     */
    public function deleteProductSizes($productId)
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM product_sizes WHERE product_id = ?");
            return $stmt->execute([$productId]);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Get total stock across all sizes for a product
     */
    public function getTotalStock($productId)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COALESCE(SUM(stock_quantity), 0) as total_stock
                FROM product_sizes 
                WHERE product_id = ?
            ");
            $stmt->execute([$productId]);
            $result = $stmt->fetch();
            return $result ? $result['total_stock'] : 0;
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * Validate size ID exists
     */
    public function isValidSize($sizeId)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM sizes WHERE id = ?");
            $stmt->execute([$sizeId]);
            $result = $stmt->fetch();
            return $result && $result['count'] > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
}
