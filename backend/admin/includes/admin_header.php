<?php
require_once __DIR__ . '/admin_auth.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Textile Haven</title>
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
    <!-- Admin Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="index.php" class="flex-shrink-0 flex items-center">
                        <span class="text-2xl font-bold text-primary">Admin Dashboard</span>
                    </a>
                </div>

                <div class="hidden md:flex items-center space-x-8">
                    <a href="index.php" class="text-gray-700 hover:text-primary px-3 py-2 rounded-md text-sm font-medium">Dashboard</a>
                    <a href="products.php" class="text-gray-700 hover:text-primary px-3 py-2 rounded-md text-sm font-medium">Products</a>
                    <span class="text-gray-700 px-3 py-2 text-sm">Admin: <?php echo htmlspecialchars($currentUser['username']); ?></span>
                    <a href="../auth/logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium">Logout</a>
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
                <a href="index.php" class="text-gray-700 hover:text-primary block px-3 py-2 rounded-md text-base font-medium">Dashboard</a>
                <a href="products.php" class="text-gray-700 hover:text-primary block px-3 py-2 rounded-md text-base font-medium">Products</a>
                <span class="text-gray-700 block px-3 py-2 text-base">Admin: <?php echo htmlspecialchars($currentUser['username']); ?></span>
                <a href="../auth/logout.php" class="bg-red-500 hover:bg-red-600 text-white block px-3 py-2 rounded-md text-base font-medium">Logout</a>
            </div>
        </div>
    </nav>

    <script>
        // Mobile menu toggle
        document.querySelector('.mobile-menu-button').addEventListener('click', function() {
            document.querySelector('.mobile-menu').classList.toggle('hidden');
        });

        // Login form handler for admin area
        document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.getElementById('loginForm');
            if (loginForm) {
                loginForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const formData = new FormData(this);

                    fetch('../auth/login.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Show success message
                                if (typeof showAlert === 'function') {
                                    showAlert(data.message, 'success');
                                } else {
                                    alert(data.message);
                                }

                                setTimeout(() => {
                                    // Redirect based on user type
                                    if (data.redirect_url) {
                                        window.location.href = data.redirect_url;
                                    } else {
                                        window.location.reload();
                                    }
                                }, 1000);
                            } else {
                                if (typeof showAlert === 'function') {
                                    showAlert(data.message, 'error');
                                } else {
                                    alert(data.message);
                                }
                            }
                        })
                        .catch(error => {
                            if (typeof showAlert === 'function') {
                                showAlert('An error occurred. Please try again.', 'error');
                            } else {
                                alert('An error occurred. Please try again.');
                            }
                        });
                });
            }
        });
    </script>