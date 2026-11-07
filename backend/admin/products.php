<?php
// Start output buffering to prevent header issues
ob_start();

include 'includes/admin_header.php';
require_once '../models/Product.php';
require_once '../models/Size.php';

$product = new Product($pdo);
$size = new Size($pdo);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    global $security;

    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !$security->validateCSRFToken($_POST['csrf_token'])) {
        $security->logSecurityEvent('csrf_token_invalid', ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
        $message = 'Security validation failed. Please try again.';
        $messageType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'add':
                $name = $security->sanitizeInput($_POST['name'] ?? '');
                $description = $security->sanitizeInput($_POST['description'] ?? '');
                $price = floatval($_POST['price'] ?? 0);

                // Validate input
                $validationRules = [
                    'name' => ['required' => true, 'min_length' => 2, 'max_length' => 255],
                    'description' => ['required' => true, 'min_length' => 10, 'max_length' => 1000],
                    'price' => ['required' => true, 'numeric' => true, 'min' => 0.01, 'max' => 999999.99]
                ];

                $input = ['name' => $name, 'description' => $description, 'price' => $price];
                $errors = $security->validateInput($input, $validationRules);

                if (!empty($errors)) {
                    $message = implode(' ', $errors);
                    $messageType = 'error';
                } elseif ($name && $price > 0) {
                    $image1 = isset($_FILES['image1']) ? $_FILES['image1'] : null;
                    $image2 = isset($_FILES['image2']) ? $_FILES['image2'] : null;

                    // Process sizes
                    $sizes = [];
                    $allSizes = $size->getAllSizes();
                    foreach ($allSizes as $sizeItem) {
                        $sizeKey = 'size_' . $sizeItem['id'];
                        if (isset($_POST[$sizeKey]) && $_POST[$sizeKey] > 0) {
                            $sizes[$sizeItem['id']] = intval($_POST[$sizeKey]);
                        }
                    }

                    $result = $product->addProduct($name, $description, $price, $image1, $image2, $sizes);
                    $message = $result['message'];
                    $messageType = $result['success'] ? 'success' : 'error';

                    if ($result['success']) {
                        $security->logSecurityEvent('product_added', ['name' => $name, 'admin_id' => $currentUser['id']]);
                    }
                } else {
                    $message = 'Please fill in all required fields correctly.';
                    $messageType = 'error';
                }
                break;

            case 'update':
                $id = intval($_POST['id'] ?? 0);
                $name = $security->sanitizeInput($_POST['name'] ?? '');
                $description = $security->sanitizeInput($_POST['description'] ?? '');
                $price = floatval($_POST['price'] ?? 0);

                // Validate input
                $validationRules = [
                    'name' => ['required' => true, 'min_length' => 2, 'max_length' => 255],
                    'description' => ['required' => true, 'min_length' => 10, 'max_length' => 1000],
                    'price' => ['required' => true, 'numeric' => true, 'min' => 0.01, 'max' => 999999.99]
                ];

                $input = ['name' => $name, 'description' => $description, 'price' => $price];
                $errors = $security->validateInput($input, $validationRules);

                if (!empty($errors)) {
                    $message = implode(' ', $errors);
                    $messageType = 'error';
                } elseif ($id && $name && $price > 0) {
                    $image1 = isset($_FILES['image1']) ? $_FILES['image1'] : null;
                    $image2 = isset($_FILES['image2']) ? $_FILES['image2'] : null;

                    // Process sizes
                    $sizes = [];
                    $allSizes = $size->getAllSizes();
                    foreach ($allSizes as $sizeItem) {
                        $sizeKey = 'size_' . $sizeItem['id'];
                        if (isset($_POST[$sizeKey]) && $_POST[$sizeKey] > 0) {
                            $sizes[$sizeItem['id']] = intval($_POST[$sizeKey]);
                        }
                    }

                    $result = $product->updateProduct($id, $name, $description, $price, $image1, $image2, $sizes);
                    $message = $result['message'];
                    $messageType = $result['success'] ? 'success' : 'error';

                    if ($result['success']) {
                        $security->logSecurityEvent('product_updated', ['id' => $id, 'name' => $name, 'admin_id' => $currentUser['id']]);
                    }
                } else {
                    $message = 'Please fill in all required fields correctly.';
                    $messageType = 'error';
                }
                break;

            case 'delete':
                $id = intval($_POST['id'] ?? 0);
                if ($id) {
                    $result = $product->deleteProduct($id);
                    $message = $result['message'];
                    $messageType = $result['success'] ? 'success' : 'error';

                    if ($result['success']) {
                        $security->logSecurityEvent('product_deleted', ['id' => $id, 'admin_id' => $currentUser['id']]);
                    }
                } else {
                    $message = 'Invalid product ID.';
                    $messageType = 'error';
                }
                break;
        }
    }
}

// Get all products for display
$products = $product->getAllProducts();
?>

<!-- Page Header -->
<div class="bg-white shadow">
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center">
            <h1 class="text-3xl font-bold text-gray-900">Admin Dashboard - Products</h1>
            <button onclick="openAddModal()" class="bg-primary hover:bg-purple-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                Add New Product
            </button>
        </div>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <?php if (isset($message)): ?>
        <div class="mb-4 p-4 rounded-md <?php echo $messageType === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Products Table -->
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
        <div class="px-4 py-5 sm:px-6 flex justify-between items-center">
            <div>
                <h3 class="text-lg leading-6 font-medium text-gray-900">Women's Clothing Products</h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">Manage your product inventory</p>
            </div>
            <div class="flex space-x-3">
                <button onclick="refreshStockData()" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Refresh Stock
                </button>
                <button onclick="openAddModal()" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary hover:bg-purple-700">
                    Add Product
                </button>
            </div>
        </div>

        <?php if (empty($products)): ?>
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No products found</h3>
                <p class="mt-1 text-sm text-gray-500">Get started by adding your first product.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Images</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock by Size</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($products as $item): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <?php
                                            $mainImage = $product->formatImagePathForAdmin($item['image1']);
                                            if ($mainImage && $product->imageExists($mainImage)):
                                            ?>
                                                <img class="h-10 w-10 rounded-full object-cover" src="<?php echo htmlspecialchars($mainImage); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                            <?php else: ?>
                                                <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                                                    <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                                    </svg>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item['name']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars(substr($item['description'], 0, 50)) . (strlen($item['description']) > 50 ? '...' : ''); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex space-x-2">
                                        <?php
                                        $mainImage = $product->formatImagePathForAdmin($item['image1']);
                                        $secondaryImage = $product->formatImagePathForAdmin($item['image2']);

                                        if ($mainImage && $product->imageExists($mainImage)):
                                        ?>
                                            <div class="relative">
                                                <img class="h-8 w-8 rounded object-cover" src="<?php echo htmlspecialchars($mainImage); ?>" alt="Main">
                                                <span class="absolute -top-1 -right-1 bg-blue-500 text-white text-xs rounded-full h-4 w-4 flex items-center justify-center">1</span>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($secondaryImage && $product->imageExists($secondaryImage)): ?>
                                            <div class="relative">
                                                <img class="h-8 w-8 rounded object-cover" src="<?php echo htmlspecialchars($secondaryImage); ?>" alt="Secondary">
                                                <span class="absolute -top-1 -right-1 bg-green-500 text-white text-xs rounded-full h-4 w-4 flex items-center justify-center">2</span>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!$mainImage && !$secondaryImage): ?>
                                            <span class="text-xs text-gray-400">No images</span>
                                        <?php endif; ?>

                                        <!-- Image Filenames -->
                                        <div class="mt-2 text-xs text-gray-500">
                                            <?php if ($mainImage): ?>
                                                <div>Main: <?php echo $product->getImageFilename($mainImage); ?></div>
                                            <?php endif; ?>
                                            <?php if ($secondaryImage): ?>
                                                <div>Secondary: <?php echo $product->getImageFilename($secondaryImage); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-primary">
                                    <?php echo $product->formatPrice($item['price']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php
                                    // Get size-specific stock information
                                    $productSizes = $size->getProductSizes($item['id']);
                                    $hasStock = false;
                                    $sizeStockInfo = [];
                                    $totalStock = 0;

                                    foreach ($productSizes as $sizeInfo) {
                                        if ($sizeInfo['stock_quantity'] > 0) {
                                            $hasStock = true;
                                            $totalStock += $sizeInfo['stock_quantity'];
                                            $sizeStockInfo[] = $sizeInfo['display_name'] . ': ' . $sizeInfo['stock_quantity'];
                                        }
                                    }

                                    if ($hasStock) {
                                        if (count($sizeStockInfo) <= 3) {
                                            // Show all sizes if 3 or fewer
                                            echo '<div class="text-xs space-y-1">';
                                            foreach ($sizeStockInfo as $info) {
                                                echo '<div class="text-green-600">' . htmlspecialchars($info) . '</div>';
                                            }
                                            echo '</div>';
                                        } else {
                                            // Show summary for many sizes
                                            echo '<div class="text-xs">';
                                            echo '<div class="text-green-600 font-medium">' . count($sizeStockInfo) . ' sizes available</div>';
                                            echo '<div class="text-gray-500">Total: ' . $totalStock . ' units</div>';
                                            echo '</div>';
                                        }
                                    } else {
                                        echo '<span class="text-red-600 font-medium">Out of Stock</span>';
                                    }
                                    ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('M j, Y', strtotime($item['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($item)); ?>)" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</button>
                                    <button onclick="deleteProduct(<?php echo $item['id']; ?>)" class="text-red-600 hover:text-red-900">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Product Modal -->
<div id="addModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Add New Product</h3>
                <button onclick="closeModal('addModal')" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="csrf_token" value="<?php echo $security->generateCSRFToken(); ?>">

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Product Name *</label>
                    <input type="text" id="name" name="name" required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary">
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea id="description" name="description" rows="3"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary"></textarea>
                </div>

                <div>
                    <label for="price" class="block text-sm font-medium text-gray-700">Price *</label>
                    <input type="number" id="price" name="price" step="0.01" min="0" required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary">
                </div>

                <!-- Size-Specific Stock Management -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Stock by Size</label>
                    <div class="grid grid-cols-2 gap-3">
                        <?php foreach ($size->getAllSizes() as $sizeItem): ?>
                            <div>
                                <label for="size_<?php echo $sizeItem['id']; ?>" class="block text-xs text-gray-600 mb-1">
                                    <?php echo htmlspecialchars($sizeItem['display_name']); ?>
                                </label>
                                <input type="number" id="size_<?php echo $sizeItem['id']; ?>"
                                    name="size_<?php echo $sizeItem['id']; ?>" min="0" value="0"
                                    class="block w-full px-2 py-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"
                                    placeholder="Qty">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-2 flex justify-between items-center">
                        <p class="text-xs text-gray-500">Set stock quantity for each size. Products without stock in any size will show as "Out of Stock".</p>
                        <button type="button" onclick="setAllSizesToZero()" class="text-xs bg-red-100 hover:bg-red-200 text-red-700 px-2 py-1 rounded">
                            Set Out of Stock
                        </button>
                    </div>
                </div>

                <div>
                    <label for="image1" class="block text-sm font-medium text-gray-700">Main Image (Optional)</label>
                    <input type="file" id="image1" name="image1" accept="image/*"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary">
                    <p class="mt-1 text-xs text-gray-500">Max size: 2MB. Allowed: JPG, PNG, GIF. Images will be saved in assets/images/ folder.</p>
                </div>

                <div>
                    <label for="image2" class="block text-sm font-medium text-gray-700">Secondary Image (Optional)</label>
                    <input type="file" id="image2" name="image2" accept="image/*"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary">
                    <p class="mt-1 text-xs text-gray-500">Max size: 2MB. Allowed: JPG, PNG, GIF. Images will be saved in assets/images/ folder.</p>
                </div>

                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeModal('addModal')"
                        class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-md text-sm font-medium">
                        Cancel
                    </button>
                    <button type="submit"
                        class="bg-primary hover:bg-purple-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                        Add Product
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Product Modal -->
<div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Edit Product</h3>
                <button onclick="closeModal('editModal')" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="csrf_token" value="<?php echo $security->generateCSRFToken(); ?>">
                <input type="hidden" id="edit_id" name="id">

                <div>
                    <label for="edit_name" class="block text-sm font-medium text-gray-700">Product Name *</label>
                    <input type="text" id="edit_name" name="name" required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary">
                </div>

                <div>
                    <label for="edit_description" class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea id="edit_description" name="description" rows="3"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary"></textarea>
                </div>

                <div>
                    <label for="edit_price" class="block text-sm font-medium text-gray-700">Price *</label>
                    <input type="number" id="edit_price" name="price" step="0.01" min="0" required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary">
                </div>

                <!-- Size-Specific Stock Management -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Stock by Size</label>
                    <div class="grid grid-cols-2 gap-3">
                        <?php foreach ($size->getAllSizes() as $sizeItem): ?>
                            <div>
                                <label for="edit_size_<?php echo $sizeItem['id']; ?>" class="block text-xs text-gray-600 mb-1">
                                    <?php echo htmlspecialchars($sizeItem['display_name']); ?>
                                </label>
                                <input type="number" id="edit_size_<?php echo $sizeItem['id']; ?>"
                                    name="size_<?php echo $sizeItem['id']; ?>" min="0" value="0"
                                    class="block w-full px-2 py-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"
                                    placeholder="Qty">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-2 flex justify-between items-center">
                        <p class="text-xs text-gray-500">Set stock quantity for each size. Products without stock in any size will show as "Out of Stock".</p>
                        <button type="button" onclick="setAllSizesToZero()" class="text-xs bg-red-100 hover:bg-red-200 text-red-700 px-2 py-1 rounded">
                            Set Out of Stock
                        </button>
                    </div>
                </div>

                <!-- Current Images Display -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Current Images</label>
                    <div id="current_images" class="flex space-x-2 mb-3">
                        <!-- Images will be populated by JavaScript -->
                    </div>
                </div>

                <div>
                    <label for="edit_image1" class="block text-sm font-medium text-gray-700">New Main Image</label>
                    <input type="file" id="edit_image1" name="image1" accept="image/*"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary">
                    <p class="mt-1 text-xs text-gray-500">Leave empty to keep current image. Max size: 2MB. Images will be saved in assets/images/ folder.</p>
                </div>

                <div>
                    <label for="edit_image2" class="block text-sm font-medium text-gray-700">New Secondary Image</label>
                    <input type="file" id="edit_image2" name="image2" accept="image/*"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary">
                    <p class="mt-1 text-xs text-gray-500">Leave empty to keep current image. Max size: 2MB. Images will be saved in assets/images/ folder.</p>
                </div>

                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeModal('editModal')"
                        class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-md text-sm font-medium">
                        Cancel
                    </button>
                    <button type="submit"
                        class="bg-primary hover:bg-purple-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                        Update Product
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Confirm Delete</h3>
                <button onclick="closeModal('deleteModal')" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <p class="text-sm text-gray-500 mb-4">Are you sure you want to delete this product? This action cannot be undone.</p>

            <form method="POST" id="deleteForm">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="csrf_token" value="<?php echo $security->generateCSRFToken(); ?>">
                <input type="hidden" id="delete_id" name="id">

                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeModal('deleteModal')"
                        class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-md text-sm font-medium">
                        Cancel
                    </button>
                    <button type="submit"
                        class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                        Delete
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openAddModal() {
        document.getElementById('addModal').classList.remove('hidden');
    }

    function openEditModal(product) {
        document.getElementById('edit_id').value = product.id;
        document.getElementById('edit_name').value = product.name;
        document.getElementById('edit_description').value = product.description;
        document.getElementById('edit_price').value = product.price;

        // Load product sizes with real-time data
        console.log('Loading sizes for product:', product.id);

        // Try public endpoint first
        fetch(`../api/get_product_sizes.php?product_id=${product.id}`)
            .then(response => {
                console.log('Public endpoint response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.json();
            })
            .then(sizes => {
                console.log('Received sizes from public endpoint:', sizes);
                // Clear all size inputs first
                const sizeInputs = document.querySelectorAll('input[name^="size_"]');
                sizeInputs.forEach(input => input.value = '0');

                // Populate with actual data
                sizes.forEach(size => {
                    const input = document.getElementById(`edit_size_${size.id}`);
                    if (input) {
                        input.value = size.stock_quantity;
                        console.log(`Set size ${size.id} to ${size.stock_quantity}`);
                    } else {
                        console.warn(`Input not found for size ${size.id}`);
                    }
                });
            })
            .catch(error => {
                console.error('Error with public endpoint:', error);
                // Fallback: try admin endpoint
                console.log('Trying admin endpoint as fallback...');
                fetch(`get_product_sizes.php?product_id=${product.id}`)
                    .then(response => {
                        console.log('Admin endpoint response status:', response.status);
                        if (!response.ok) {
                            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                        }
                        return response.json();
                    })
                    .then(sizes => {
                        console.log('Received sizes from admin endpoint:', sizes);
                        const sizeInputs = document.querySelectorAll('input[name^="size_"]');
                        sizeInputs.forEach(input => input.value = '0');
                        sizes.forEach(size => {
                            const input = document.getElementById(`edit_size_${size.id}`);
                            if (input) {
                                input.value = size.stock_quantity;
                                console.log(`Set size ${size.id} to ${size.stock_quantity}`);
                            } else {
                                console.warn(`Input not found for size ${size.id}`);
                            }
                        });
                    })
                    .catch(err => {
                        console.error('Error with admin endpoint:', err);
                        // Final fallback: set all to 0
                        const sizeInputs = document.querySelectorAll('input[name^="size_"]');
                        sizeInputs.forEach(input => input.value = '0');
                        console.log('Set all sizes to 0 as fallback');
                    });
            });

        // Display current images
        const currentImagesDiv = document.getElementById('current_images');
        currentImagesDiv.innerHTML = '';

        if (product.image1) {
            const img1 = document.createElement('div');
            img1.className = 'relative';
            img1.innerHTML = `
                <img class="h-12 w-12 rounded object-cover" src="assets/images/${product.image1.split('/').pop()}" alt="Main Image" onerror="this.style.display='none'">
                <span class="absolute -top-1 -right-1 bg-blue-500 text-white text-xs rounded-full h-4 w-4 flex items-center justify-center">1</span>
                <div class="text-xs text-gray-500 mt-1">${product.image1.split('/').pop()}</div>
            `;
            currentImagesDiv.appendChild(img1);
        }

        if (product.image2) {
            const img2 = document.createElement('div');
            img2.className = 'relative';
            img2.innerHTML = `
                <img class="h-12 w-12 rounded object-cover" src="assets/images/${product.image2.split('/').pop()}" alt="Secondary Image" onerror="this.style.display='none'">
                <span class="absolute -top-1 -right-1 bg-green-500 text-white text-xs rounded-full h-4 w-4 flex items-center justify-center">2</span>
                <div class="text-xs text-gray-500 mt-1">${product.image2.split('/').pop()}</div>
            `;
            currentImagesDiv.appendChild(img2);
        }

        if (!product.image1 && !product.image2) {
            currentImagesDiv.innerHTML = '<span class="text-xs text-gray-400">No images uploaded</span>';
        }

        document.getElementById('editModal').classList.remove('hidden');
    }

    function deleteProduct(id) {
        document.getElementById('delete_id').value = id;
        document.getElementById('deleteModal').classList.remove('hidden');
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.add('hidden');
    }

    function refreshStockData() {
        // Reload the page to get fresh stock data
        window.location.reload();
    }

    function setAllSizesToZero() {
        // Set all size inputs to 0
        const sizeInputs = document.querySelectorAll('input[name^="size_"]');
        sizeInputs.forEach(input => {
            input.value = '0';
        });
        console.log('All sizes set to 0 (Out of Stock)');
    }
</script>

<?php include 'includes/admin_footer.php'; ?>