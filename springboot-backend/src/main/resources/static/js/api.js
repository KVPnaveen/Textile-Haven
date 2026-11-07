/**
 * Textile Haven - Frontend API Client & Component Renderer
 * Connects frontend directly to Spring Boot REST backend (port 8080).
 */

const API_CONFIG = {
    // If frontend is served directly by Spring Boot, use relative '/api', else absolute URL
    baseUrl: window.location.origin.includes(':8080') ? '/api' : 'http://localhost:8080/api',
    imageBaseUrl: window.location.origin.includes(':8080') ? '' : 'http://localhost:8080'
};

const Auth = {
    getUser() {
        const data = localStorage.getItem('th_user');
        return data ? JSON.parse(data) : null;
    },
    setUser(user) {
        if (user) {
            localStorage.setItem('th_user', JSON.stringify(user));
        } else {
            localStorage.removeItem('th_user');
        }
        window.dispatchEvent(new CustomEvent('auth-changed', { detail: user }));
    },
    isLoggedIn() {
        return !!this.getUser();
    },
    isAdmin() {
        const u = this.getUser();
        return !!(u && u.isAdmin);
    },
    getHeaders() {
        const headers = { 'Content-Type': 'application/json' };
        const user = this.getUser();
        if (user && user.id) {
            headers['X-User-Id'] = user.id;
        }
        return headers;
    },
    async login(identifier, password) {
        const res = await fetch(`${API_CONFIG.baseUrl}/auth/login`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email: identifier, password: password })
        });
        const json = await res.json();
        if (json.success && json.data) {
            this.setUser(json.data);
        }
        return json;
    },
    async register(username, email, password) {
        const res = await fetch(`${API_CONFIG.baseUrl}/auth/register`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username, email, password })
        });
        return await res.json();
    },
    async logout() {
        try {
            await fetch(`${API_CONFIG.baseUrl}/auth/logout`, {
                method: 'POST',
                headers: this.getHeaders()
            });
        } catch (e) {
            console.error('Logout error:', e);
        }
        this.setUser(null);
        window.location.href = 'index.html';
    }
};

const ProductAPI = {
    formatImageUrl(imagePath) {
        if (!imagePath) return 'assets/textile-haven-LOGO.png';
        if (imagePath.startsWith('http://') || imagePath.startsWith('https://')) return imagePath;
        const clean = imagePath.startsWith('/') ? imagePath : '/' + imagePath;
        return `${API_CONFIG.imageBaseUrl}${clean}`;
    },
    formatPrice(price) {
        const num = parseFloat(price) || 0;
        return '$' + num.toFixed(2);
    },
    async getProducts(params = {}) {
        const query = new URLSearchParams(params).toString();
        const res = await fetch(`${API_CONFIG.baseUrl}/products?${query}`);
        return await res.json();
    },
    async getFeatured(limit = 6) {
        const res = await fetch(`${API_CONFIG.baseUrl}/products/featured?limit=${limit}`);
        return await res.json();
    },
    async getById(id) {
        const res = await fetch(`${API_CONFIG.baseUrl}/products/${id}`);
        return await res.json();
    },
    async create(formData) {
        const user = Auth.getUser();
        const headers = {};
        if (user && user.id) headers['X-User-Id'] = user.id;

        const res = await fetch(`${API_CONFIG.baseUrl}/products`, {
            method: 'POST',
            headers: headers,
            body: formData
        });
        return await res.json();
    },
    async update(id, formData) {
        const user = Auth.getUser();
        const headers = {};
        if (user && user.id) headers['X-User-Id'] = user.id;

        const res = await fetch(`${API_CONFIG.baseUrl}/products/${id}`, {
            method: 'PUT',
            headers: headers,
            body: formData
        });
        return await res.json();
    },
    async delete(id) {
        const user = Auth.getUser();
        const headers = { 'Content-Type': 'application/json' };
        if (user && user.id) headers['X-User-Id'] = user.id;

        const res = await fetch(`${API_CONFIG.baseUrl}/products/${id}`, {
            method: 'DELETE',
            headers: headers
        });
        return await res.json();
    }
};

const SizeAPI = {
    async getAll() {
        const res = await fetch(`${API_CONFIG.baseUrl}/sizes`);
        return await res.json();
    },
    async getByProduct(productId) {
        const res = await fetch(`${API_CONFIG.baseUrl}/sizes/product/${productId}`);
        return await res.json();
    }
};

const CartAPI = {
    async getCart() {
        const user = Auth.getUser();
        if (!user) return { success: false, data: { items: [], count: 0, total: 0 } };
        const res = await fetch(`${API_CONFIG.baseUrl}/cart`, {
            headers: Auth.getHeaders()
        });
        return await res.json();
    },
    async addToCart(productId, sizeId, quantity = 1) {
        const user = Auth.getUser();
        if (!user) {
            openAuthModal('login');
            return { success: false, message: 'Please login to add to cart' };
        }
        const res = await fetch(`${API_CONFIG.baseUrl}/cart`, {
            method: 'POST',
            headers: Auth.getHeaders(),
            body: JSON.stringify({ productId, sizeId, quantity })
        });
        const data = await res.json();
        if (data.success) {
            updateCartBadge();
        }
        return data;
    },
    async updateQuantity(cartId, quantity) {
        const res = await fetch(`${API_CONFIG.baseUrl}/cart/${cartId}`, {
            method: 'PUT',
            headers: Auth.getHeaders(),
            body: JSON.stringify({ quantity })
        });
        const data = await res.json();
        if (data.success) updateCartBadge();
        return data;
    },
    async removeFromCart(cartId) {
        const res = await fetch(`${API_CONFIG.baseUrl}/cart/${cartId}`, {
            method: 'DELETE',
            headers: Auth.getHeaders()
        });
        const data = await res.json();
        if (data.success) updateCartBadge();
        return data;
    },
    async clearCart() {
        const res = await fetch(`${API_CONFIG.baseUrl}/cart`, {
            method: 'DELETE',
            headers: Auth.getHeaders()
        });
        const data = await res.json();
        if (data.success) updateCartBadge();
        return data;
    }
};

const OrderAPI = {
    async checkout(checkoutData) {
        const res = await fetch(`${API_CONFIG.baseUrl}/checkout`, {
            method: 'POST',
            headers: Auth.getHeaders(),
            body: JSON.stringify(checkoutData)
        });
        const data = await res.json();
        if (data.success) updateCartBadge();
        return data;
    }
};

async function updateCartBadge() {
    const badge = document.getElementById('nav-cart-count');
    const mobileBadge = document.getElementById('nav-cart-count-mobile');
    if (!Auth.isLoggedIn()) {
        if (badge) badge.classList.add('hidden');
        if (mobileBadge) mobileBadge.classList.add('hidden');
        return;
    }
    try {
        const res = await CartAPI.getCart();
        const count = res.data && res.data.count ? res.data.count : 0;
        [badge, mobileBadge].forEach(el => {
            if (!el) return;
            if (count > 0) {
                el.textContent = count;
                el.classList.remove('hidden');
            } else {
                el.classList.add('hidden');
            }
        });
    } catch (e) {
        console.error('Failed to update cart badge:', e);
    }
}

function renderNavbar() {
    const container = document.getElementById('navbar-container');
    if (!container) return;

    const user = Auth.getUser();
    const isAdmin = Auth.isAdmin();

    container.innerHTML = `
    <nav class="bg-white shadow-lg sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="index.html" class="flex-shrink-0 flex items-center space-x-2">
                        <span class="text-2xl font-bold text-purple-600">Textile Haven</span>
                    </a>
                </div>

                <div class="hidden md:flex items-center space-x-8">
                    <a href="index.html" class="text-gray-700 hover:text-purple-600 px-3 py-2 rounded-md text-sm font-medium">Home</a>
                    <a href="products.html" class="text-gray-700 hover:text-purple-600 px-3 py-2 rounded-md text-sm font-medium">Shop</a>
                    <a href="cart.html" class="text-gray-700 hover:text-purple-600 px-3 py-2 rounded-md text-sm font-medium relative">
                        Cart
                        <span id="nav-cart-count" class="hidden absolute -top-1 -right-2 bg-red-500 text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center">0</span>
                    </a>
                    ${user ? `
                        <span class="text-gray-700 px-3 py-2 text-sm font-medium">Welcome, ${escapeHtml(user.username)}</span>
                        ${isAdmin ? `
                            <a href="admin.html" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-md text-sm font-medium transition">Admin Panel</a>
                        ` : ''}
                        <button onclick="Auth.logout()" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium transition">Logout</button>
                    ` : `
                        <button onclick="openAuthModal('login')" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-md text-sm font-medium transition">Login</button>
                    `}
                </div>

                <!-- Mobile menu button -->
                <div class="md:hidden flex items-center">
                    <button type="button" id="mobile-menu-btn" class="bg-gray-100 p-2 rounded-md text-gray-500 hover:text-gray-700 focus:outline-none">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile menu -->
        <div id="mobile-menu" class="hidden md:hidden px-4 pt-2 pb-4 space-y-2 border-t">
            <a href="index.html" class="block text-gray-700 hover:text-purple-600 px-3 py-2 rounded-md font-medium">Home</a>
            <a href="products.html" class="block text-gray-700 hover:text-purple-600 px-3 py-2 rounded-md font-medium">Shop</a>
            <a href="cart.html" class="block text-gray-700 hover:text-purple-600 px-3 py-2 rounded-md font-medium relative">
                Cart
                <span id="nav-cart-count-mobile" class="hidden ml-2 bg-red-500 text-white text-xs px-2 py-0.5 rounded-full">0</span>
            </a>
            ${user ? `
                <div class="pt-2 border-t text-sm text-gray-600 px-3">Signed in as <strong>${escapeHtml(user.username)}</strong></div>
                ${isAdmin ? `
                    <a href="admin.html" class="block bg-yellow-500 text-white px-3 py-2 rounded-md font-medium">Admin Panel</a>
                ` : ''}
                <button onclick="Auth.logout()" class="w-full text-left bg-red-500 text-white px-3 py-2 rounded-md font-medium">Logout</button>
            ` : `
                <button onclick="openAuthModal('login')" class="w-full text-left bg-purple-600 text-white px-3 py-2 rounded-md font-medium">Login / Sign Up</button>
            `}
        </div>
    </nav>
    `;

    document.getElementById('mobile-menu-btn')?.addEventListener('click', () => {
        document.getElementById('mobile-menu')?.classList.toggle('hidden');
    });

    updateCartBadge();
}

function renderFooter() {
    const container = document.getElementById('footer-container');
    if (!container) return;

    container.innerHTML = `
    <footer class="bg-gray-900 text-white mt-20">
        <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div class="col-span-1 md:col-span-2">
                    <h3 class="text-2xl font-bold text-purple-400 mb-4">Textile Haven</h3>
                    <p class="text-gray-300 mb-4">Your premier destination for elegant women's fashion and fine fabrics. Powered by Spring Boot backend.</p>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4 text-purple-300">Quick Links</h3>
                    <ul class="space-y-2">
                        <li><a href="index.html" class="text-gray-400 hover:text-white transition">Home</a></li>
                        <li><a href="products.html" class="text-gray-400 hover:text-white transition">Shop Products</a></li>
                        <li><a href="cart.html" class="text-gray-400 hover:text-white transition">My Cart</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4 text-purple-300">Contact Us</h3>
                    <div class="space-y-2 text-gray-400 text-sm">
                        <p>📍 123 Fashion Blvd, Suite 100</p>
                        <p>📞 +1 (555) 123-4567</p>
                        <p>✉️ support@textilehaven.com</p>
                        <p>🕒 Mon-Fri: 9:00 AM - 6:00 PM</p>
                    </div>
                </div>
            </div>
            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-sm text-gray-500">
                &copy; 2026 Textile Haven. Powered by Spring Boot.
            </div>
        </div>
    </footer>
    `;
}

function renderAuthModal() {
    if (document.getElementById('authModal')) return;

    const modal = document.createElement('div');
    modal.id = 'authModal';
    modal.className = 'fixed inset-0 bg-gray-900 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50 flex items-center justify-center p-4';
    modal.innerHTML = `
        <div class="relative bg-white rounded-xl shadow-2xl max-w-md w-full p-6 transition-all">
            <div class="flex justify-between items-center pb-3 border-b border-gray-100">
                <h3 id="authModalTitle" class="text-2xl font-bold text-gray-800">Login</h3>
                <button onclick="closeAuthModal()" class="text-gray-400 hover:text-gray-600 transition">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="flex border-b border-gray-200 mt-4">
                <button id="tab-login" onclick="switchAuthTab('login')" class="w-1/2 py-2 text-center font-semibold text-purple-600 border-b-2 border-purple-600 focus:outline-none">Login</button>
                <button id="tab-signup" onclick="switchAuthTab('signup')" class="w-1/2 py-2 text-center font-semibold text-gray-500 hover:text-purple-600 focus:outline-none">Sign Up</button>
            </div>

            <div id="authAlert" class="hidden mt-4 p-3 rounded-lg text-sm font-medium"></div>

            <!-- Login Form -->
            <form id="loginForm" class="mt-4 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Email or Username</label>
                    <input type="text" id="loginIdentifier" required placeholder="admin or email@example.com"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Password</label>
                    <input type="password" id="loginPassword" required placeholder="••••••••"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                </div>
                <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-medium py-2 px-4 rounded-lg transition duration-200 shadow">Login</button>
            </form>

            <!-- Signup Form -->
            <form id="signupForm" class="mt-4 space-y-4 hidden">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Username</label>
                    <input type="text" id="signupUsername" required placeholder="janedoe"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" id="signupEmail" required placeholder="jane@example.com"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Password</label>
                    <input type="password" id="signupPassword" required placeholder="At least 6 characters"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Confirm Password</label>
                    <input type="password" id="signupConfirmPassword" required placeholder="Confirm password"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                </div>
                <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-medium py-2 px-4 rounded-lg transition duration-200 shadow">Create Account</button>
            </form>
        </div>
    `;
    document.body.appendChild(modal);

    document.getElementById('loginForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const id = document.getElementById('loginIdentifier').value;
        const pass = document.getElementById('loginPassword').value;
        try {
            const res = await Auth.login(id, pass);
            if (res.success) {
                showAuthAlert('Login successful! Redirecting...', 'success');
                setTimeout(() => {
                    closeAuthModal();
                    if (res.data && res.data.isAdmin) {
                        window.location.href = 'admin.html';
                    } else {
                        window.location.reload();
                    }
                }, 800);
            } else {
                showAuthAlert(res.message || 'Login failed', 'error');
            }
        } catch (err) {
            showAuthAlert('Unable to reach Spring Boot server at ' + API_CONFIG.baseUrl, 'error');
        }
    });

    document.getElementById('signupForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const username = document.getElementById('signupUsername').value;
        const email = document.getElementById('signupEmail').value;
        const pass = document.getElementById('signupPassword').value;
        const confirm = document.getElementById('signupConfirmPassword').value;

        if (pass !== confirm) {
            showAuthAlert('Passwords do not match', 'error');
            return;
        }

        try {
            const res = await Auth.register(username, email, pass);
            if (res.success) {
                showAuthAlert('Account created successfully! Please login.', 'success');
                setTimeout(() => switchAuthTab('login'), 1200);
            } else {
                showAuthAlert(res.message || 'Registration failed', 'error');
            }
        } catch (err) {
            showAuthAlert('Error communicating with Spring Boot server', 'error');
        }
    });
}

function openAuthModal(tab = 'login') {
    renderAuthModal();
    const modal = document.getElementById('authModal');
    if (modal) {
        modal.classList.remove('hidden');
        switchAuthTab(tab);
    }
}

function closeAuthModal() {
    const modal = document.getElementById('authModal');
    if (modal) modal.classList.add('hidden');
    const alert = document.getElementById('authAlert');
    if (alert) alert.classList.add('hidden');
}

function switchAuthTab(tab) {
    const loginForm = document.getElementById('loginForm');
    const signupForm = document.getElementById('signupForm');
    const tabLogin = document.getElementById('tab-login');
    const tabSignup = document.getElementById('tab-signup');
    const title = document.getElementById('authModalTitle');
    const alert = document.getElementById('authAlert');
    if (alert) alert.classList.add('hidden');

    if (tab === 'login') {
        loginForm?.classList.remove('hidden');
        signupForm?.classList.add('hidden');
        tabLogin?.classList.add('text-purple-600', 'border-b-2', 'border-purple-600');
        tabLogin?.classList.remove('text-gray-500');
        tabSignup?.classList.remove('text-purple-600', 'border-b-2', 'border-purple-600');
        tabSignup?.classList.add('text-gray-500');
        if (title) title.textContent = 'Login';
    } else {
        loginForm?.classList.add('hidden');
        signupForm?.classList.remove('hidden');
        tabSignup?.classList.add('text-purple-600', 'border-b-2', 'border-purple-600');
        tabSignup?.classList.remove('text-gray-500');
        tabLogin?.classList.remove('text-purple-600', 'border-b-2', 'border-purple-600');
        tabLogin?.classList.add('text-gray-500');
        if (title) title.textContent = 'Create an Account';
    }
}

function showAuthAlert(msg, type = 'success') {
    const alert = document.getElementById('authAlert');
    if (!alert) return;
    alert.textContent = msg;
    alert.className = `mt-4 p-3 rounded-lg text-sm font-medium ${
        type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
    }`;
    alert.classList.remove('hidden');
}

function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `fixed bottom-5 right-5 z-50 px-5 py-3 rounded-lg shadow-xl text-white font-medium flex items-center space-x-2 transition-transform transform translate-y-0 ${
        type === 'success' ? 'bg-green-600' : 'bg-red-600'
    }`;
    toast.innerHTML = `
        <span>${escapeHtml(message)}</span>
    `;
    document.body.appendChild(toast);
    setTimeout(() => {
        toast.classList.add('opacity-0');
        setTimeout(() => toast.remove(), 400);
    }, 2800);
}

function escapeHtml(text) {
    if (!text) return '';
    return text.toString()
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

document.addEventListener('DOMContentLoaded', () => {
    renderNavbar();
    renderFooter();
    renderAuthModal();
});
