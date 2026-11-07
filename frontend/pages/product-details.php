<?php
include '../components/header.php';
require_once '../../backend/models/Product.php';
require_once '../../backend/models/Size.php';
require_once '../../backend/models/Cart.php';
require_once '../../backend/models/User.php';

$user = new User($pdo);
$currentUser = $user->getCurrentUser();

// Check if user is admin and redirect to admin dashboard (only if not coming from admin area)
if ($currentUser && $user->isAdmin() && !isset($_GET['from_admin'])) {
    header('Location: ../../backend/admin/');
    exit;
}

$product = new Product($pdo);
$size = new Size($pdo);

// Get product ID from URL
$productId = intval($_GET['id'] ?? 0);

if (!$productId) {
    header('Location: products.php');
    exit;
}

// Get product details
$productItem = $product->getProductById($productId);

if (!$productItem) {
    header('Location: products.php');
    exit;
}

$mainImage = $product->getMainImage($productItem);
$secondaryImage = $product->getSecondaryImage($productItem);
?>

<!-- Page Header -->
<div class="bg-white shadow">
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <div class="flex items-center space-x-4">
            <a href="products.php<?php echo isset($_GET['from_admin']) ? '?from_admin=1' : ''; ?>" class="text-primary hover:text-purple-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <h1 class="text-3xl font-bold text-gray-900">Product Details</h1>
        </div>
    </div>
</div>

<!-- Product Details -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="md:flex">
            <!-- Product Images -->
            <div class="md:w-1/2">
                <!-- Main Image Slider -->
                <div class="relative aspect-[2/3] md:aspect-[2/3] bg-gray-100 overflow-hidden">
                    <?php if ($mainImage && $product->imageExists($mainImage)): ?>
                        <!-- Image Container -->
                        <div class="image-slider-container">
                            <!-- Main Image -->
                            <div class="image-slide active">
                                <img src="<?php echo htmlspecialchars($mainImage); ?>"
                                    alt="<?php echo htmlspecialchars($productItem['name']); ?>"
                                    class="w-full h-full object-cover">
                            </div>

                            <?php if ($secondaryImage && $product->imageExists($secondaryImage)): ?>
                                <!-- Secondary Image -->
                                <div class="image-slide">
                                    <img src="<?php echo htmlspecialchars($secondaryImage); ?>"
                                        alt="<?php echo htmlspecialchars($productItem['name']); ?> (Alternate view)"
                                        class="w-full h-full object-cover">
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Navigation Arrows -->
                        <?php if ($secondaryImage && $product->imageExists($secondaryImage)): ?>
                            <button onclick="changeSlide(-1)" class="absolute left-4 top-1/2 transform -translate-y-1/2 bg-black bg-opacity-50 text-white p-2 rounded-full hover:bg-opacity-75 transition-all duration-300">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                            </button>
                            <button onclick="changeSlide(1)" class="absolute right-4 top-1/2 transform -translate-y-1/2 bg-black bg-opacity-50 text-white p-2 rounded-full hover:bg-opacity-75 transition-all duration-300">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        <?php endif; ?>

                        <!-- Image Counter -->
                        <?php if ($secondaryImage && $product->imageExists($secondaryImage)): ?>
                            <div class="absolute top-4 right-4 bg-black bg-opacity-50 text-white px-3 py-1 rounded-full text-sm">
                                <span id="currentSlide">1</span> / <span id="totalSlides">2</span>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <!-- Fallback placeholder -->
                        <div class="w-full h-full flex items-center justify-center">
                            <div class="text-center">
                                <div class="w-24 h-24 bg-primary rounded-full mx-auto mb-4 flex items-center justify-center">
                                    <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                    </svg>
                                </div>
                                <span class="text-lg text-gray-500">No Image Available</span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Image Thumbnails -->
                <?php if ($mainImage && $secondaryImage && $product->imageExists($mainImage) && $product->imageExists($secondaryImage)): ?>
                    <div class="flex space-x-2 p-4">
                        <button onclick="goToSlide(0)"
                            class="w-16 h-16 border-2 border-primary rounded-lg overflow-hidden image-thumb active">
                            <img src="<?php echo htmlspecialchars($mainImage); ?>"
                                alt="Main" class="w-full h-full object-cover">
                        </button>
                        <button onclick="goToSlide(1)"
                            class="w-16 h-16 border-2 border-gray-300 rounded-lg overflow-hidden image-thumb">
                            <img src="<?php echo htmlspecialchars($secondaryImage); ?>"
                                alt="Secondary" class="w-full h-full object-cover">
                        </button>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Product Information -->
            <div class="md:w-1/2 p-6">
                <h2 class="text-3xl font-bold text-gray-900 mb-4"><?php echo htmlspecialchars($productItem['name']); ?></h2>

                <div class="text-3xl font-bold text-primary mb-6">
                    <?php echo $product->formatPrice($productItem['price']); ?>
                </div>

                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Description</h3>
                    <p class="text-gray-600 leading-relaxed">
                        <?php echo nl2br(htmlspecialchars($productItem['description'])); ?>
                    </p>
                </div>

                <?php
                $availableSizes = $size->getAvailableSizes($productId);
                $hasStock = $size->hasStock($productId);
                ?>

                <div class="mb-6">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Availability:</span>
                        <span class="text-sm font-medium <?php echo $hasStock ? 'text-green-600' : 'text-red-600'; ?>">
                            <?php echo $hasStock ? 'In stock' : 'Out of stock'; ?>
                        </span>
                    </div>
                </div>

                <?php if ($hasStock): ?>
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Size: <span id="selected-size-text" class="text-primary font-semibold">Select a size</span></label>
                        <div class="flex flex-wrap gap-2">
                            <?php
                            $allSizes = $size->getAllSizes();
                            foreach ($allSizes as $sizeItem):
                                $isAvailable = false;
                                $stockQuantity = 0;

                                // Check if this size is available
                                foreach ($availableSizes as $availableSize) {
                                    if ($availableSize['id'] == $sizeItem['id']) {
                                        $isAvailable = true;
                                        $stockQuantity = $availableSize['stock_quantity'];
                                        break;
                                    }
                                }
                            ?>
                                <button type="button"
                                    class="size-option relative w-12 h-12 border-2 rounded-md text-sm font-medium transition-all duration-200 <?php echo $isAvailable ? 'border-gray-300 hover:border-primary text-gray-700 hover:text-primary cursor-pointer' : 'border-gray-200 text-gray-400 cursor-not-allowed'; ?> <?php echo $isAvailable ? '' : 'unavailable'; ?>"
                                    data-size-id="<?php echo $sizeItem['id']; ?>"
                                    data-size-name="<?php echo htmlspecialchars($sizeItem['display_name']); ?>"
                                    data-stock="<?php echo $stockQuantity; ?>"
                                    <?php echo $isAvailable ? '' : 'disabled'; ?>>
                                    <?php echo htmlspecialchars($sizeItem['name']); ?>
                                    <?php if (!$isAvailable): ?>
                                        <div class="absolute inset-0 bg-red-100 opacity-50"></div>
                                        <div class="absolute inset-0 flex items-center justify-center">
                                            <div class="w-full h-0.5 bg-red-400 transform rotate-45"></div>
                                        </div>
                                    <?php endif; ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" id="size" name="size" value="">
                        <p class="mt-2 text-xs text-gray-500">Select your preferred size</p>
                    </div>

                    <div class="mb-6">
                        <label for="quantity" class="block text-sm font-medium text-gray-700 mb-2">Quantity:</label>
                        <select id="quantity" class="w-20 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                            <option value="1">1</option>
                        </select>
                    </div>

                    <div class="flex space-x-4">
                        <button onclick="addToCart(<?php echo $productItem['id']; ?>)"
                            class="flex-1 bg-primary text-white py-3 px-6 rounded-lg font-semibold hover:bg-purple-700 transition duration-300">
                            Add to Cart
                        </button>
                        <button onclick="addToWishlist(<?php echo $productItem['id']; ?>)"
                            class="bg-gray-200 text-gray-700 py-3 px-6 rounded-lg font-semibold hover:bg-gray-300 transition duration-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                            </svg>
                        </button>
                    </div>
                <?php else: ?>
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                        <p class="text-red-700">This product is currently out of stock. Please check back later.</p>
                    </div>
                <?php endif; ?>

                <div class="mt-8 pt-6 border-t border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Product Information</h3>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-600">Product ID:</span>
                            <span class="font-medium">#<?php echo $productItem['id']; ?></span>
                        </div>
                        <div>
                            <span class="text-gray-600">Added:</span>
                            <span class="font-medium"><?php echo date('M j, Y', strtotime($productItem['created_at'])); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .image-slider-container {
        position: relative;
        width: 100%;
        height: 100%;
    }

    .image-slide {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        transition: opacity 0.5s ease-in-out;
    }

    .image-slide.active {
        opacity: 1;
    }

    .image-thumb.active {
        border-color: #8b5cf6;
    }

    /* Size selection styling */
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

    .image-thumb:hover {
        border-color: #8b5cf6;
    }

    /* Touch/swipe support for mobile */
    .image-slider-container {
        touch-action: pan-y pinch-zoom;
    }
</style>

<script>
    let currentSlideIndex = 0;
    const slides = document.querySelectorAll('.image-slide');
    const thumbs = document.querySelectorAll('.image-thumb');
    const totalSlides = slides.length;

    function showSlide(index) {
        // Hide all slides
        slides.forEach(slide => slide.classList.remove('active'));
        thumbs.forEach(thumb => thumb.classList.remove('active'));

        // Show current slide
        if (slides[index]) {
            slides[index].classList.add('active');
        }
        if (thumbs[index]) {
            thumbs[index].classList.add('active');
        }

        // Update counter
        const currentSlideElement = document.getElementById('currentSlide');
        if (currentSlideElement) {
            currentSlideElement.textContent = index + 1;
        }

        currentSlideIndex = index;
    }

    function changeSlide(direction) {
        let newIndex = currentSlideIndex + direction;

        // Loop around
        if (newIndex >= totalSlides) {
            newIndex = 0;
        } else if (newIndex < 0) {
            newIndex = totalSlides - 1;
        }

        showSlide(newIndex);
    }

    function goToSlide(index) {
        if (index >= 0 && index < totalSlides) {
            showSlide(index);
        }
    }

    // Keyboard navigation
    document.addEventListener('keydown', function(e) {
        if (e.key === 'ArrowLeft') {
            changeSlide(-1);
        } else if (e.key === 'ArrowRight') {
            changeSlide(1);
        }
    });

    // Touch/swipe support
    let touchStartX = 0;
    let touchEndX = 0;

    const sliderContainer = document.querySelector('.image-slider-container');
    if (sliderContainer) {
        sliderContainer.addEventListener('touchstart', function(e) {
            touchStartX = e.changedTouches[0].screenX;
        });

        sliderContainer.addEventListener('touchend', function(e) {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        });
    }

    function handleSwipe() {
        const swipeThreshold = 50;
        const diff = touchStartX - touchEndX;

        if (Math.abs(diff) > swipeThreshold) {
            if (diff > 0) {
                // Swipe left - next slide
                changeSlide(1);
            } else {
                // Swipe right - previous slide
                changeSlide(-1);
            }
        }
    }

    function addToCart(productId) {
        const hiddenSizeInput = document.getElementById('size');
        const quantity = document.getElementById('quantity').value;
        const selectedSize = hiddenSizeInput.value;

        if (!selectedSize) {
            alert('Please select a size before adding to cart.');
            // Focus on the first available size option
            const firstAvailableSize = document.querySelector('.size-option:not([disabled])');
            if (firstAvailableSize) {
                firstAvailableSize.focus();
            }
            return;
        }

        <?php if ($currentUser): ?>
            // User is logged in, add to cart via AJAX
            fetch('../pages/cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=add&product_id=' + productId + '&size_id=' + selectedSize + '&quantity=' + quantity
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

    function addToWishlist(productId) {
        alert('Wishlist functionality coming soon!');
    }

    // Size selection functionality
    document.addEventListener('DOMContentLoaded', function() {
        const sizeOptions = document.querySelectorAll('.size-option');
        const selectedSizeText = document.getElementById('selected-size-text');
        const hiddenSizeInput = document.getElementById('size');
        const quantitySelect = document.getElementById('quantity');

        sizeOptions.forEach(option => {
            option.addEventListener('click', function() {
                if (this.disabled) return; // Don't allow selection of unavailable sizes

                // Remove selected class from all options
                sizeOptions.forEach(opt => {
                    opt.classList.remove('selected');
                });

                // Add selected class to clicked option
                this.classList.add('selected');

                // Update hidden input and display text
                const sizeId = this.dataset.sizeId;
                const sizeName = this.dataset.sizeName;
                const stock = parseInt(this.dataset.stock);

                hiddenSizeInput.value = sizeId;
                selectedSizeText.textContent = sizeName;

                // Update quantity options
                const maxQuantity = Math.min(stock, 10); // Limit to 10 or available stock
                quantitySelect.innerHTML = '';

                for (let i = 1; i <= maxQuantity; i++) {
                    const option = document.createElement('option');
                    option.value = i;
                    option.textContent = i;
                    quantitySelect.appendChild(option);
                }
            });
        });
    });
</script>

<?php include '../components/footer.php'; ?>