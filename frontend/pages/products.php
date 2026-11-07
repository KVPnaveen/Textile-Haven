<?php
include '../components/header.php';
require_once '../../backend/models/Product.php';
require_once '../../backend/models/Size.php';

// Check if user is admin and redirect to admin dashboard (only if not coming from admin area)
if ($currentUser && $user->isAdmin() && !isset($_GET['from_admin'])) {
    header('Location: ../../backend/admin/');
    exit;
}

$product = new Product($pdo);
$size = new Size($pdo);

// Get search query
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));

// Get products based on search or all products
if ($search) {
    $products = $product->searchProducts($search);
    $total = count($products);
    $pages = 1;
} else {
    $result = $product->getProductsWithPagination($page, 12);
    $products = $result['products'];
    $total = $result['total'];
    $pages = $result['pages'];
    $current_page = $result['current_page'];
}
?>

<!-- Page Header -->
<div class="bg-white shadow">
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center">
            <h1 class="text-3xl font-bold text-gray-900">
                <?php echo $search ? 'Search Results for "' . htmlspecialchars($search) . '"' : 'Women\'s Fashion Collection'; ?>
            </h1>
            <div class="flex items-center space-x-4">
                <span class="text-gray-600"><?php echo $total; ?> products</span>
            </div>
        </div>
    </div>
</div>

<!-- Search and Products -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Search Bar -->
    <div class="mb-8">
        <form method="GET" class="flex gap-4">
            <?php if (isset($_GET['from_admin'])): ?>
                <input type="hidden" name="from_admin" value="1">
            <?php endif; ?>
            <div class="flex-1">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                    placeholder="Search for dresses, tops, skirts..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
            </div>
            <button type="submit"
                class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-purple-700 transition duration-300">
                Search
            </button>
            <?php if ($search): ?>
                <a href="products.php<?php echo isset($_GET['from_admin']) ? '?from_admin=1' : ''; ?>"
                    class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition duration-300">
                    Clear
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Products Grid -->
    <?php if (empty($products)): ?>
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No products found</h3>
            <p class="mt-1 text-sm text-gray-500">Try adjusting your search terms or browse all products.</p>
            <div class="mt-6">
                <a href="products.php" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary hover:bg-purple-700">
                    View All Products
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php foreach ($products as $item): ?>
                <?php
                $mainImage = $product->getMainImage($item);
                $secondaryImage = $product->getSecondaryImage($item);
                $hasMultipleImages = $mainImage && $secondaryImage && $product->imageExists($mainImage) && $product->imageExists($secondaryImage);
                ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition duration-300">
                    <div class="relative aspect-[2/3] bg-gray-100 overflow-hidden product-image-container">
                        <?php if ($mainImage && $product->imageExists($mainImage)): ?>
                            <!-- Main Image (always visible) -->
                            <img src="<?php echo htmlspecialchars($mainImage); ?>"
                                alt="<?php echo htmlspecialchars($item['name']); ?>"
                                class="w-full h-full object-cover transition-all duration-300 ease-in-out product-image loaded"
                                loading="lazy">

                            <?php if ($hasMultipleImages): ?>
                                <!-- Secondary Image (visible on hover) -->
                                <img src="<?php echo htmlspecialchars($secondaryImage); ?>"
                                    alt="<?php echo htmlspecialchars($item['name']); ?> (Alternate view)"
                                    class="absolute inset-0 w-full h-full object-cover opacity-0 transition-all duration-300 ease-in-out product-image"
                                    loading="lazy">
                            <?php endif; ?>
                        <?php else: ?>
                            <!-- Fallback placeholder -->
                            <div class="w-full h-full flex items-center justify-center">
                                <div class="text-center">
                                    <div class="w-16 h-16 bg-primary rounded-full mx-auto mb-2 flex items-center justify-center">
                                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                        </svg>
                                    </div>
                                    <span class="text-sm text-gray-500">Fashion Item</span>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Image indicator for multiple images -->
                        <?php if ($hasMultipleImages): ?>
                            <div class="absolute top-2 right-2 bg-black bg-opacity-50 text-white text-xs px-2 py-1 rounded-full">
                                2 Images
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="p-4">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2"><?php echo htmlspecialchars($item['name']); ?></h3>
                        <p class="text-gray-600 text-sm mb-3"><?php echo htmlspecialchars($item['description']); ?></p>
                        <div class="flex justify-between items-center mb-3">
                            <span class="text-2xl font-bold text-primary"><?php echo $product->formatPrice($item['price']); ?></span>
                            <?php
                            $hasStock = $size->hasStock($item['id']);
                            $availableSizes = $size->getAvailableSizes($item['id']);
                            ?>
                            <span class="text-sm <?php echo $hasStock ? 'text-green-600' : 'text-red-600'; ?>">
                                <?php if ($hasStock): ?>
                                    <?php echo count($availableSizes); ?> sizes available
                                <?php else: ?>
                                    Out of stock
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="flex space-x-2">
                            <a href="product-details.php?id=<?php echo $item['id']; ?><?php echo isset($_GET['from_admin']) ? '&from_admin=1' : ''; ?>"
                                class="flex-1 bg-primary text-white py-2 px-4 rounded-lg hover:bg-purple-700 transition duration-300 text-center">
                                <?php echo $hasStock ? 'Select Size' : 'View Details'; ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if (!$search && $pages > 1): ?>
            <div class="mt-12 flex justify-center">
                <nav class="flex items-center space-x-2">
                    <?php if ($current_page > 1): ?>
                        <a href="?page=<?php echo $current_page - 1; ?><?php echo isset($_GET['from_admin']) ? '&from_admin=1' : ''; ?>"
                            class="px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Previous
                        </a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $current_page - 2); $i <= min($pages, $current_page + 2); $i++): ?>
                        <a href="?page=<?php echo $i; ?><?php echo isset($_GET['from_admin']) ? '&from_admin=1' : ''; ?>"
                            class="px-3 py-2 border border-gray-300 rounded-md text-sm font-medium <?php echo $i == $current_page ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-50'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($current_page < $pages): ?>
                        <a href="?page=<?php echo $current_page + 1; ?><?php echo isset($_GET['from_admin']) ? '&from_admin=1' : ''; ?>"
                            class="px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Next
                        </a>
                    <?php endif; ?>
                </nav>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<style>
    /* Enhanced image hover effects */
    .product-image-container {
        position: relative;
        overflow: hidden;
    }

    .product-image-container img {
        transition: all 0.3s ease-in-out;
    }

    /* Hover effect for multiple images */
    .product-image-container:hover img:first-child {
        opacity: 0;
        transform: scale(1.05);
    }

    .product-image-container:hover img:nth-child(2) {
        opacity: 1;
        transform: scale(1.05);
    }

    /* Single image hover effect */
    .product-image-container:has(img:only-child):hover img {
        transform: scale(1.05);
    }

    /* Smooth loading for images */
    .product-image {
        opacity: 0;
        transition: all 0.3s ease-in-out;
    }

    .product-image.loaded {
        opacity: 1;
    }

    /* Fallback for browsers that don't support :has() */
    .product-image-container img:only-child:hover {
        transform: scale(1.05);
    }
</style>

<script>
    // Image loading enhancement
    document.addEventListener('DOMContentLoaded', function() {
        const images = document.querySelectorAll('.product-image-container img');

        images.forEach(img => {
            if (img.complete) {
                img.classList.add('loaded');
            } else {
                img.addEventListener('load', function() {
                    this.classList.add('loaded');
                });

                // Handle image load errors
                img.addEventListener('error', function() {
                    this.style.display = 'none';
                });
            }
        });

        // Enhanced hover effects for better performance
        const productContainers = document.querySelectorAll('.product-image-container');
        productContainers.forEach(container => {
            const images = container.querySelectorAll('img');

            // Only apply hover effects if there are multiple images
            if (images.length > 1) {
                container.addEventListener('mouseenter', function() {
                    if (images[0]) images[0].style.opacity = '0';
                    if (images[1]) images[1].style.opacity = '1';
                });

                container.addEventListener('mouseleave', function() {
                    if (images[0]) images[0].style.opacity = '1';
                    if (images[1]) images[1].style.opacity = '0';
                });
            }
        });
    });

    function addToCart(productId) {
        <?php if ($currentUser): ?>
            // User is logged in, add to cart via AJAX
            fetch('cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=add&product_id=' + productId + '&quantity=1'
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        alert('Product added to cart!');
                        location.reload(); // Refresh to update cart count
                    } else {
                        alert('Failed to add product to cart: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                });
        <?php else: ?>
            // User not logged in, show login modal
            openModal('loginModal');
        <?php endif; ?>
    }
</script>

<?php include '../components/footer.php'; ?>