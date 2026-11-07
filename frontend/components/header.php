<?php
require_once '../../backend/includes/db.php';
require_once '../../backend/models/User.php';
require_once '../../backend/models/Cart.php';

$user = new User($pdo);
$cart = new Cart($pdo);

$currentUser = $user->getCurrentUser();
$cartCount = $currentUser ? $cart->getCartItemCount($currentUser['id']) : 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Textile Haven - Women's Fashion</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#8B5CF6',
                        secondary: '#F59E0B',
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="index.php" class="flex-shrink-0 flex items-center">
                        <span class="text-2xl font-bold text-primary">Textile Haven</span>
                    </a>
                </div>

                <div class="hidden md:flex items-center space-x-8">
                    <a href="index.php<?php echo isset($_GET['from_admin']) ? '?from_admin=1' : ''; ?>" class="text-gray-700 hover:text-primary px-3 py-2 rounded-md text-sm font-medium">Home</a>
                    <a href="products.php<?php echo isset($_GET['from_admin']) ? '?from_admin=1' : ''; ?>" class="text-gray-700 hover:text-primary px-3 py-2 rounded-md text-sm font-medium">Shop</a>
                    <a href="cart.php<?php echo isset($_GET['from_admin']) ? '?from_admin=1' : ''; ?>" class="text-gray-700 hover:text-primary px-3 py-2 rounded-md text-sm font-medium relative">
                        Cart
                        <?php if ($cartCount > 0): ?>
                            <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                                <?php echo $cartCount; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    <?php if ($currentUser): ?>
                        <span class="text-gray-700 px-3 py-2 text-sm">Welcome, <?php echo htmlspecialchars($currentUser['username']); ?></span>
                        <?php if ($user->isAdmin()): ?>
                            <?php if (isset($_GET['from_admin'])): ?>
                                <a href="../../backend/admin/" class="bg-secondary hover:bg-yellow-600 text-white px-4 py-2 rounded-md text-sm font-medium">Back to Admin</a>
                            <?php else: ?>
                                <a href="../../backend/admin/" class="bg-secondary hover:bg-yellow-600 text-white px-4 py-2 rounded-md text-sm font-medium">Admin</a>
                            <?php endif; ?>
                        <?php endif; ?>
                        <a href="../../backend/auth/logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium">Logout</a>
                    <?php else: ?>
                        <button onclick="openModal('loginModal')" class="bg-primary hover:bg-purple-700 text-white px-4 py-2 rounded-md text-sm font-medium">Login</button>
                    <?php endif; ?>
                </div>

                <!-- Mobile menu button -->
                <div class="md:hidden flex items-center">
                    <button type="button" class="mobile-menu-button bg-gray-100 inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary">
                        <span class="sr-only">Open main menu</span>
                        <svg class="block h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile menu -->
        <div class="mobile-menu hidden md:hidden">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
                <a href="index.php<?php echo isset($_GET['from_admin']) ? '?from_admin=1' : ''; ?>" class="text-gray-700 hover:text-primary block px-3 py-2 rounded-md text-base font-medium">Home</a>
                <a href="products.php<?php echo isset($_GET['from_admin']) ? '?from_admin=1' : ''; ?>" class="text-gray-700 hover:text-primary block px-3 py-2 rounded-md text-base font-medium">Shop</a>
                <a href="cart.php<?php echo isset($_GET['from_admin']) ? '?from_admin=1' : ''; ?>" class="text-gray-700 hover:text-primary block px-3 py-2 rounded-md text-base font-medium">Cart</a>
                <?php if ($currentUser): ?>
                    <span class="text-gray-700 block px-3 py-2 text-base">Welcome, <?php echo htmlspecialchars($currentUser['username']); ?></span>
                    <?php if ($user->isAdmin()): ?>
                        <?php if (isset($_GET['from_admin'])): ?>
                            <a href="../../backend/admin/" class="bg-secondary hover:bg-yellow-600 text-white block px-3 py-2 rounded-md text-base font-medium">Back to Admin</a>
                        <?php else: ?>
                            <a href="../../backend/admin/" class="bg-secondary hover:bg-yellow-600 text-white block px-3 py-2 rounded-md text-base font-medium">Admin</a>
                        <?php endif; ?>
                    <?php endif; ?>
                    <a href="../../backend/auth/logout.php" class="bg-red-500 hover:bg-red-600 text-white block px-3 py-2 rounded-md text-base font-medium">Logout</a>
                <?php else: ?>
                    <button onclick="openModal('loginModal')" class="bg-primary hover:bg-purple-700 text-white block px-3 py-2 rounded-md text-base font-medium">Login</button>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Login/Signup Modal -->
    <div id="loginModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <!-- Modal Header -->
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900" id="modalTitle">Login</h3>
                    <button onclick="closeModal('loginModal')" class="text-gray-400 hover:text-gray-600">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Tab Buttons -->
                <div class="flex border-b mb-4">
                    <button onclick="switchTab('login')" class="tab-btn active px-4 py-2 text-sm font-medium border-b-2 border-primary text-primary">
                        Login
                    </button>
                    <button onclick="switchTab('signup')" class="tab-btn px-4 py-2 text-sm font-medium text-gray-500 hover:text-gray-700">
                        Sign Up
                    </button>
                </div>

                <!-- Login Form -->
                <form id="loginForm" class="space-y-4">
                    <div>
                        <label for="loginEmail" class="block text-sm font-medium text-gray-700">Email or Username</label>
                        <input type="text" id="loginEmail" name="email" required placeholder="Enter email or username"
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary">
                    </div>
                    <div>
                        <label for="loginPassword" class="block text-sm font-medium text-gray-700">Password</label>
                        <input type="password" id="loginPassword" name="password" required
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary">
                    </div>
                    <div>
                        <button type="submit"
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                            Login
                        </button>
                    </div>
                </form>

                <!-- Signup Form -->
                <form id="signupForm" class="space-y-4 hidden">
                    <div>
                        <label for="signupUsername" class="block text-sm font-medium text-gray-700">Username</label>
                        <input type="text" id="signupUsername" name="username" required
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary">
                    </div>
                    <div>
                        <label for="signupEmail" class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" id="signupEmail" name="email" required
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary">
                    </div>
                    <div>
                        <label for="signupPassword" class="block text-sm font-medium text-gray-700">Password</label>
                        <input type="password" id="signupPassword" name="password" required
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary">
                    </div>
                    <div>
                        <label for="signupConfirmPassword" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                        <input type="password" id="signupConfirmPassword" name="confirm_password" required
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary">
                    </div>
                    <div>
                        <button type="submit"
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                            Sign Up
                        </button>
                    </div>
                </form>

                <!-- Alert Messages -->
                <div id="alertMessage" class="mt-4 hidden">
                    <div id="alertContent" class="p-3 rounded-md"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Modal functions
        function openModal(modalId) {
            document.getElementById(modalId).classList.remove('hidden');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
            hideAlert();
        }

        function switchTab(tab) {
            const loginForm = document.getElementById('loginForm');
            const signupForm = document.getElementById('signupForm');
            const tabBtns = document.querySelectorAll('.tab-btn');
            const modalTitle = document.getElementById('modalTitle');

            if (tab === 'login') {
                loginForm.classList.remove('hidden');
                signupForm.classList.add('hidden');
                modalTitle.textContent = 'Login';
                tabBtns[0].classList.add('active', 'border-primary', 'text-primary');
                tabBtns[0].classList.remove('text-gray-500');
                tabBtns[1].classList.remove('active', 'border-primary', 'text-primary');
                tabBtns[1].classList.add('text-gray-500');
            } else {
                loginForm.classList.add('hidden');
                signupForm.classList.remove('hidden');
                modalTitle.textContent = 'Sign Up';
                tabBtns[1].classList.add('active', 'border-primary', 'text-primary');
                tabBtns[1].classList.remove('text-gray-500');
                tabBtns[0].classList.remove('active', 'border-primary', 'text-primary');
                tabBtns[0].classList.add('text-gray-500');
            }
            hideAlert();
        }

        function showAlert(message, type = 'success') {
            const alertDiv = document.getElementById('alertMessage');
            const alertContent = document.getElementById('alertContent');

            alertContent.textContent = message;
            alertContent.className = `p-3 rounded-md ${type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`;
            alertDiv.classList.remove('hidden');
        }

        function hideAlert() {
            document.getElementById('alertMessage').classList.add('hidden');
        }

        // Form submissions
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            fetch('../../backend/auth/login.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert(data.message, 'success');
                        setTimeout(() => {
                            // Redirect based on user type
                            if (data.redirect_url) {
                                window.location.href = data.redirect_url;
                            } else {
                                window.location.reload();
                            }
                        }, 1000);
                    } else {
                        showAlert(data.message, 'error');
                    }
                })
                .catch(error => {
                    showAlert('An error occurred. Please try again.', 'error');
                });
        });

        document.getElementById('signupForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const password = document.getElementById('signupPassword').value;
            const confirmPassword = document.getElementById('signupConfirmPassword').value;

            if (password !== confirmPassword) {
                showAlert('Passwords do not match.', 'error');
                return;
            }

            const formData = new FormData(this);

            fetch('../../backend/auth/register.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert(data.message, 'success');
                        setTimeout(() => {
                            switchTab('login');
                        }, 1000);
                    } else {
                        showAlert(data.message, 'error');
                    }
                })
                .catch(error => {
                    showAlert('An error occurred. Please try again.', 'error');
                });
        });

        // Mobile menu toggle
        document.querySelector('.mobile-menu-button').addEventListener('click', function() {
            document.querySelector('.mobile-menu').classList.toggle('hidden');
        });
    </script>