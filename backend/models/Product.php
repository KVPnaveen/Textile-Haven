<?php
require_once __DIR__ . '/../includes/db.php';

class Product
{
    private $pdo;
    private $uploadDir;
    private $maxFileSize = 2097152; // 2MB in bytes
    private $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        // Set upload directory relative to project root
        $this->uploadDir = dirname(__DIR__) . '/admin/assets/images/';
    }

    /**
     * Get all products
     */
    public function getAllProducts($limit = null)
    {
        try {
            $sql = "SELECT p.*, COALESCE(SUM(ps.stock_quantity), 0) as total_stock FROM products p LEFT JOIN product_sizes ps ON p.id = ps.product_id GROUP BY p.id ORDER BY p.created_at DESC";
            if ($limit) {
                $sql .= " LIMIT " . (int)$limit;
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Get product by ID
     */
    public function getProductById($id)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Get featured products
     */
    public function getFeaturedProducts($limit = 6)
    {
        try {
            // Use direct query instead of prepared statement for LIMIT
            $sql = "SELECT p.*, COALESCE(SUM(ps.stock_quantity), 0) as total_stock FROM products p LEFT JOIN product_sizes ps ON p.id = ps.product_id GROUP BY p.id ORDER BY p.created_at DESC LIMIT " . (int)$limit;
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Search products
     */
    public function searchProducts($query)
    {
        try {
            $searchTerm = '%' . $query . '%';
            $stmt = $this->pdo->prepare("SELECT p.*, COALESCE(SUM(ps.stock_quantity), 0) as total_stock FROM products p LEFT JOIN product_sizes ps ON p.id = ps.product_id WHERE p.name LIKE ? OR p.description LIKE ? GROUP BY p.id ORDER BY p.created_at DESC");
            $stmt->execute([$searchTerm, $searchTerm]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Validate uploaded image
     */
    private function validateImage($file)
    {
        // Use the Security class for validation
        require_once __DIR__ . '/../includes/security.php';
        $security = Security::getInstance();

        $errors = $security->validateFileUpload($file, $this->allowedTypes, $this->maxFileSize);

        if (!empty($errors)) {
            return ['valid' => false, 'message' => implode(', ', $errors)];
        }

        return ['valid' => true, 'message' => 'File is valid.'];
    }

    /**
     * Upload image file
     */
    private function uploadImage($file, $productId = null)
    {
        $validation = $this->validateImage($file);
        if (!$validation['valid']) {
            return ['success' => false, 'message' => $validation['message']];
        }

        // Use original filename with timestamp to avoid conflicts
        $originalName = pathinfo($file['name'], PATHINFO_FILENAME);
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);

        // Clean the filename (remove special characters, keep only alphanumeric, spaces, and hyphens)
        $cleanName = preg_replace('/[^a-zA-Z0-9\s\-]/', '', $originalName);
        $cleanName = trim($cleanName);
        $cleanName = preg_replace('/\s+/', '_', $cleanName); // Replace spaces with underscores

        // Use only original name
        $filename = $cleanName . '.' . $extension;

        $filepath = $this->uploadDir . $filename;

        // Ensure upload directory exists
        if (!is_dir($this->uploadDir)) {
            if (!mkdir($this->uploadDir, 0755, true)) {
                return ['success' => false, 'message' => 'Failed to create upload directory.'];
            }
        }

        // Check if directory is writable
        if (!is_writable($this->uploadDir)) {
            return ['success' => false, 'message' => 'Upload directory is not writable.'];
        }

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            // Return relative path for database storage
            $relativePath = 'assets/images/' . $filename;
            return ['success' => true, 'filename' => $relativePath];
        } else {
            return ['success' => false, 'message' => 'Failed to move uploaded file.'];
        }
    }

    /**
     * Delete image file
     */
    private function deleteImage($imagePath)
    {
        if ($imagePath) {
            // Convert relative path to absolute path for file operations
            $absolutePath = dirname(__DIR__) . '/' . $imagePath;
            if (file_exists($absolutePath)) {
                unlink($absolutePath);
            }
        }
    }

    /**
     * Add new product (Admin only)
     */
    public function addProduct($name, $description, $price, $image1 = null, $image2 = null, $sizes = [])
    {
        try {
            // First, create the product without images
            $stmt = $this->pdo->prepare("INSERT INTO products (name, description, price, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$name, $description, $price]);

            $productId = $this->pdo->lastInsertId();

            $image1Path = null;
            $image2Path = null;

            // Handle image1 upload (optional)
            if ($image1 && isset($image1['tmp_name']) && is_uploaded_file($image1['tmp_name'])) {
                $uploadResult = $this->uploadImage($image1, $productId);
                if ($uploadResult['success']) {
                    $image1Path = $uploadResult['filename'];
                } else {
                    return ['success' => false, 'message' => 'Image1: ' . $uploadResult['message']];
                }
            }

            // Handle image2 upload (optional)
            if ($image2 && isset($image2['tmp_name']) && is_uploaded_file($image2['tmp_name'])) {
                $uploadResult = $this->uploadImage($image2, $productId);
                if ($uploadResult['success']) {
                    $image2Path = $uploadResult['filename'];
                } else {
                    return ['success' => false, 'message' => 'Image2: ' . $uploadResult['message']];
                }
            }

            // Update product with image paths
            if ($image1Path || $image2Path) {
                $stmt = $this->pdo->prepare("UPDATE products SET image1 = ?, image2 = ? WHERE id = ?");
                $stmt->execute([$image1Path, $image2Path, $productId]);
            }

            // Handle sizes and stock
            if (!empty($sizes)) {
                require_once __DIR__ . '/Size.php';
                $sizeModel = new Size($this->pdo);

                foreach ($sizes as $sizeId => $quantity) {
                    $sizeModel->updateStockQuantity($productId, $sizeId, $quantity);
                }
            }

            return ['success' => true, 'message' => 'Product added successfully!', 'id' => $productId];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to add product.'];
        }
    }

    /**
     * Update product (Admin only)
     */
    public function updateProduct($id, $name, $description, $price, $image1 = null, $image2 = null, $sizes = [])
    {
        try {
            // Get current product data
            $currentProduct = $this->getProductById($id);
            if (!$currentProduct) {
                return ['success' => false, 'message' => 'Product not found.'];
            }

            $image1Path = $currentProduct['image1'];
            $image2Path = $currentProduct['image2'];

            // Handle image1 upload
            if ($image1 && isset($image1['tmp_name']) && is_uploaded_file($image1['tmp_name'])) {
                $uploadResult = $this->uploadImage($image1, $id);
                if ($uploadResult['success']) {
                    // Delete old image1 if exists
                    if ($image1Path) {
                        $this->deleteImage($image1Path);
                    }
                    $image1Path = $uploadResult['filename'];
                } else {
                    return ['success' => false, 'message' => 'Image1: ' . $uploadResult['message']];
                }
            }

            // Handle image2 upload
            if ($image2 && isset($image2['tmp_name']) && is_uploaded_file($image2['tmp_name'])) {
                $uploadResult = $this->uploadImage($image2, $id);
                if ($uploadResult['success']) {
                    // Delete old image2 if exists
                    if ($image2Path) {
                        $this->deleteImage($image2Path);
                    }
                    $image2Path = $uploadResult['filename'];
                } else {
                    return ['success' => false, 'message' => 'Image2: ' . $uploadResult['message']];
                }
            }

            $stmt = $this->pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, image1 = ?, image2 = ? WHERE id = ?");
            $stmt->execute([$name, $description, $price, $image1Path, $image2Path, $id]);

            // Handle sizes and stock
            if (!empty($sizes)) {
                require_once __DIR__ . '/Size.php';
                $sizeModel = new Size($this->pdo);

                // Clear existing sizes
                $sizeModel->deleteProductSizes($id);

                // Add new sizes (including 0 quantities to set out of stock)
                foreach ($sizes as $sizeId => $quantity) {
                    $sizeModel->updateStockQuantity($id, $sizeId, $quantity);
                }
            }

            return ['success' => true, 'message' => 'Product updated successfully!'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to update product.'];
        }
    }

    /**
     * Delete product (Admin only)
     */
    public function deleteProduct($id)
    {
        try {
            // Get product images before deletion
            $product = $this->getProductById($id);
            if ($product) {
                // Delete associated images
                if ($product['image1']) {
                    $this->deleteImage($product['image1']);
                }
                if ($product['image2']) {
                    $this->deleteImage($product['image2']);
                }
            }

            $stmt = $this->pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$id]);

            return ['success' => true, 'message' => 'Product deleted successfully!'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to delete product.'];
        }
    }

    /**
     * Update product stock (deprecated - use Size model for size-specific stock)
     */
    public function updateStock($id, $quantity)
    {
        try {
            // This method is deprecated since we now use size-specific stock
            // Use Size model methods instead
            require_once __DIR__ . '/Size.php';
            $sizeModel = new Size($this->pdo);

            // Get total stock across all sizes
            $totalStock = $sizeModel->getTotalStock($id);
            if ($totalStock >= $quantity) {
                // This is a simplified approach - in practice, you'd want to specify which size
                return true;
            }
            return false;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Check if product is in stock (deprecated - use Size model for size-specific stock)
     */
    public function isInStock($id, $quantity = 1)
    {
        try {
            // This method is deprecated since we now use size-specific stock
            // Use Size model methods instead
            require_once __DIR__ . '/Size.php';
            $sizeModel = new Size($this->pdo);

            $totalStock = $sizeModel->getTotalStock($id);
            return $totalStock >= $quantity;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Get products with pagination
     */
    public function getProductsWithPagination($page = 1, $perPage = 12)
    {
        try {
            $offset = ($page - 1) * $perPage;

            // Get total count
            $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM products");
            $countStmt->execute();
            $total = $countStmt->fetchColumn();

            // Get products for current page - use direct query for LIMIT and OFFSET
            $sql = "SELECT p.*, COALESCE(SUM(ps.stock_quantity), 0) as total_stock FROM products p LEFT JOIN product_sizes ps ON p.id = ps.product_id GROUP BY p.id ORDER BY p.created_at DESC LIMIT " . (int)$perPage . " OFFSET " . (int)$offset;
            $stmt = $this->pdo->query($sql);
            $products = $stmt->fetchAll();

            return [
                'products' => $products,
                'total' => $total,
                'pages' => ceil($total / $perPage),
                'current_page' => $page
            ];
        } catch (PDOException $e) {
            return [
                'products' => [],
                'total' => 0,
                'pages' => 0,
                'current_page' => $page
            ];
        }
    }

    /**
     * Get product categories (for future use)
     */
    public function getCategories()
    {
        return [
            'dresses' => 'Dresses',
            'tops' => 'Tops',
            'skirts' => 'Skirts',
            'pants' => 'Pants',
            'outerwear' => 'Outerwear',
            'accessories' => 'Accessories'
        ];
    }

    /**
     * Format price for display
     */
    public function formatPrice($price)
    {
        return '$' . number_format($price, 2);
    }

    /**
     * Get main image for product (image1 or fallback to image)
     */
    public function getMainImage($product)
    {
        $image = $product['image1'] ?? $product['image'] ?? null;
        return $this->formatImagePath($image);
    }

    /**
     * Get secondary image for product (image2)
     */
    public function getSecondaryImage($product)
    {
        $image = $product['image2'] ?? null;
        return $this->formatImagePath($image);
    }

    /**
     * Format image path for admin panel (relative to admin directory)
     */
    public function formatImagePathForAdmin($imagePath)
    {
        if (!$imagePath) {
            return null;
        }

        // If it's just a filename, add the admin-relative path
        if (strpos($imagePath, '/') === false) {
            return 'assets/images/' . $imagePath;
        }

        // If it's a relative path without the assets prefix, add it
        if (strpos($imagePath, 'assets/images/') === 0) {
            return $imagePath;
        }

        // For any other case, just use the filename with the admin path
        return 'assets/images/' . basename($imagePath);
    }

    /**
     * Format image path to ensure it starts with the correct relative path from frontend
     */
    public function formatImagePath($imagePath)
    {
        if (!$imagePath) {
            return null;
        }

        // If path already starts with the correct relative path, return as is
        if (strpos($imagePath, '../../backend/admin/assets/images/') === 0) {
            return $imagePath;
        }

        // If it's just a filename, add the full path
        if (strpos($imagePath, '/') === false) {
            return '../../backend/admin/assets/images/' . $imagePath;
        }

        // If it's a relative path without the correct prefix, add it
        if (strpos($imagePath, 'assets/images/') === 0) {
            return '../../backend/admin/' . $imagePath;
        }

        // For any other case, just use the filename with the correct path
        return '../../backend/admin/assets/images/' . basename($imagePath);
    }

    /**
     * Check if image file exists (handles relative paths)
     */
    public function imageExists($imagePath)
    {
        if (!$imagePath) {
            return false;
        }

        // If it's already a formatted path, extract the filename
        if (strpos($imagePath, '../../backend/admin/assets/images/') === 0) {
            $imagePath = basename($imagePath);
        }

        // If it's an admin formatted path, extract the filename
        if (strpos($imagePath, 'assets/images/') === 0) {
            $imagePath = basename($imagePath);
        }

        // Convert relative path to absolute path
        $absolutePath = $this->uploadDir . $imagePath;
        return file_exists($absolutePath);
    }

    /**
     * Get image filename from path
     */
    public function getImageFilename($imagePath)
    {
        if (!$imagePath) {
            return null;
        }

        return basename($imagePath);
    }
}
