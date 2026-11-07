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
$featuredProducts = $product->getFeaturedProducts(6);
?>

<!-- Hero Section -->
<section class="relative bg-gradient-to-r from-pink-400 to-purple-500 text-white py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl md:text-6xl font-bold mb-6">
            Discover Your Perfect Style
        </h1>
        <p class="text-xl md:text-2xl mb-8 text-pink-100">
            Elegant women's fashion for every occasion
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="products.php" class="bg-white text-purple-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition duration-300">
                Shop Now
            </a>
            <a href="#featured" class="border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-purple-600 transition duration-300">
                View Collection
            </a>
        </div>
    </div>
</section>

<!-- Featured Products Section -->
<section id="featured" class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Featured Collection</h2>
            <p class="text-lg text-gray-600">Discover our latest arrivals and trending styles</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($featuredProducts as $productItem): ?>
                <div class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition duration-300">
                    <div class="relative aspect-[2/3] bg-gradient-to-br from-gray-100 to-gray-200 overflow-hidden product-image-container">
                        <?php
                        $mainImage = $product->getMainImage($productItem);
                        $secondaryImage = $product->getSecondaryImage($productItem);
                        $hasMultipleImages = $mainImage && $secondaryImage && $product->imageExists($mainImage) && $product->imageExists($secondaryImage);
                        ?>

                        <?php if ($mainImage && $product->imageExists($mainImage)): ?>
                            <!-- Main Image (always visible) -->
                            <img src="<?php echo htmlspecialchars($mainImage); ?>"
                                alt="<?php echo htmlspecialchars($productItem['name']); ?>"
                                class="w-full h-full object-cover transition-all duration-300 ease-in-out product-image loaded"
                                loading="lazy">

                            <?php if ($hasMultipleImages): ?>
                                <!-- Secondary Image (visible on hover) -->
                                <img src="<?php echo htmlspecialchars($secondaryImage); ?>"
                                    alt="<?php echo htmlspecialchars($productItem['name']); ?> (Alternate view)"
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
                        <h3 class="text-lg font-semibold text-gray-900 mb-2"><?php echo htmlspecialchars($productItem['name']); ?></h3>
                        <p class="text-gray-600 text-sm mb-3"><?php echo htmlspecialchars($productItem['description']); ?></p>
                        <div class="flex justify-between items-center mb-3">
                            <span class="text-2xl font-bold text-primary"><?php echo $product->formatPrice($productItem['price']); ?></span>
                            <?php
                            $hasStock = $size->hasStock($productItem['id']);
                            $availableSizes = $size->getAvailableSizes($productItem['id']);
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
                            <a href="product-details.php?id=<?php echo $productItem['id']; ?>"
                                class="flex-1 bg-primary text-white py-2 px-4 rounded-lg hover:bg-purple-700 transition duration-300 text-center">
                                <?php echo $hasStock ? 'Select Size' : 'View Details'; ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-12">
            <a href="products.php" class="inline-block bg-primary text-white px-8 py-3 rounded-lg font-semibold hover:bg-purple-700 transition duration-300">
                View All Products
            </a>
        </div>
    </div>
</section>

<!-- Categories Section -->
<section class="py-16 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Shop by Category</h2>
            <p class="text-lg text-gray-600">Find your perfect style in our curated collections</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Dresses -->
            <div class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition duration-300">
                <div class="aspect-[2/3] bg-gradient-to-br from-pink-100 to-pink-200 flex items-center justify-center">
                    <div class="text-center">
                        <div class="w-24 h-24 bg-pink-500 rounded-full mx-auto mb-4 flex items-center justify-center">
                            <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-800">Dresses</h3>
                    </div>
                </div>
                <div class="p-6">
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Elegant Dresses</h3>
                    <p class="text-gray-600 mb-4">From casual day dresses to stunning evening gowns, find the perfect dress for any occasion.</p>
                    <a href="products.php" class="inline-block bg-pink-500 text-white px-6 py-2 rounded-lg hover:bg-pink-600 transition duration-300">
                        Shop Dresses
                    </a>
                </div>
            </div>

            <!-- Tops -->
            <div class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition duration-300">
                <div class="aspect-[2/3] bg-gradient-to-br from-blue-100 to-blue-200 flex items-center justify-center">
                    <div class="text-center">
                        <div class="w-24 h-24 bg-blue-500 rounded-full mx-auto mb-4 flex items-center justify-center">
                            <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-800">Tops</h3>
                    </div>
                </div>
                <div class="p-6">
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Stylish Tops</h3>
                    <p class="text-gray-600 mb-4">Blouses, t-shirts, and elegant tops to complete your everyday and special occasion looks.</p>
                    <a href="products.php" class="inline-block bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition duration-300">
                        Shop Tops
                    </a>
                </div>
            </div>

            <!-- Bottoms -->
            <div class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition duration-300">
                <div class="aspect-[2/3] bg-gradient-to-br from-green-100 to-green-200 flex items-center justify-center">
                    <div class="text-center">
                        <div class="w-24 h-24 bg-green-500 rounded-full mx-auto mb-4 flex items-center justify-center">
                            <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-800">Bottoms</h3>
                    </div>
                </div>
                <div class="p-6">
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Trendy Bottoms</h3>
                    <p class="text-gray-600 mb-4">Skirts, pants, and jeans designed for comfort and style in every season.</p>
                    <a href="products.php" class="inline-block bg-green-500 text-white px-6 py-2 rounded-lg hover:bg-green-600 transition duration-300">
                        Shop Bottoms
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Why Choose Textile Haven?</h2>
            <p class="text-lg text-gray-600">We provide the best quality fashion with exceptional service</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="text-center">
                <div class="w-16 h-16 bg-primary rounded-full mx-auto mb-4 flex items-center justify-center">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Premium Quality</h3>
                <p class="text-gray-600">All our clothing is carefully selected and tested for quality assurance.</p>
            </div>

            <div class="text-center">
                <div class="w-16 h-16 bg-secondary rounded-full mx-auto mb-4 flex items-center justify-center">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Fast Delivery</h3>
                <p class="text-gray-600">Quick and reliable shipping to get your fashion items to you as soon as possible.</p>
            </div>

            <div class="text-center">
                <div class="w-16 h-16 bg-green-500 rounded-full mx-auto mb-4 flex items-center justify-center">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192L5.636 18.364M12 2.25a9.75 9.75 0 100 19.5 9.75 9.75 0 000-19.5z" />
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Style Expert</h3>
                <p class="text-gray-600">Our team of fashion experts is here to help you choose the perfect outfit.</p>
            </div>
        </div>
    </div>
</section>

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