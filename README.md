# 🛍️ Textile Haven - Women's Fashion E-commerce (Spring Boot + Modern Frontend)

A modern, responsive e-commerce web application for women's clothing powered by a **Spring Boot 4 / Java 21 REST API** backend, **MySQL**, and a client-side **Tailwind CSS + JavaScript** frontend. The legacy PHP backend has been successfully replaced.

---

## ✨ Features

- **Modern Architecture**: Spring Boot REST API backend + decoupled modern HTML5/JS frontend
- **User Authentication**: Secure BCrypt authentication with login, registration, and admin authorization
- **Product Catalog**: Paginated product listings, real-time keyword search, and featured collections
- **Size & Inventory Management**: Multi-size inventory support (XS, S, M, L, XL, XXL) with live stock tracking
- **Shopping Cart**: Real-time quantity adjustment, stock limit enforcement, cart badges, and subtotal calculation
- **Checkout & Order Processing**: Complete checkout flow that validates stock, deducts inventory, and generates order IDs
- **Admin Dashboard**: Web dashboard to add, edit, and delete products, manage size-level stock, and upload images
- **Dual Serving**: Frontend can be accessed directly through Spring Boot (`http://localhost:8080/`) or standalone with CORS support

---

## 🚀 Quick Start

### Prerequisites
- **Java 21+** (JDK 21)
- **MySQL 5.7+ / 8.0+ / MariaDB** (via XAMPP or standalone on port 3306)

### 1. Database Setup
Ensure MySQL is running on `localhost:3306` with database `textile_haven`:
- If you already imported the database, your data is preserved.
- To set up fresh, run `springboot-backend/src/main/resources/schema.sql` in MySQL / phpMyAdmin.
- Default database configuration (`springboot-backend/src/main/resources/application.properties`):
  ```properties
  spring.datasource.url=jdbc:mysql://localhost:3306/textile_haven?useSSL=false&serverTimezone=UTC&allowPublicKeyRetrieval=true
  spring.datasource.username=root
  spring.datasource.password=
  ```

### 2. Run the Spring Boot Backend
Open a terminal in the `springboot-backend` directory and execute:

```powershell
# Windows PowerShell
$env:JAVA_HOME = "C:\Program Files\Java\jdk-21"
cd springboot-backend
.\mvnw.cmd spring-boot:run
```

Or run the pre-built JAR:
```powershell
java -jar target/springboot-backend-0.0.1-SNAPSHOT.jar
```

The server starts on: **`http://localhost:8080/`**

### 3. Open the Frontend
You can access the website in two ways:
1. **Directly via Spring Boot** (Recommended):
   - Visit: [http://localhost:8080/](http://localhost:8080/)
2. **Via any browser / Live Server / Apache**:
   - Open `frontend/index.html` (e.g. `http://localhost/textile-haven-main/frontend/index.html`)

---

## 🔑 Default Credentials

- **Admin Account**:
  - **Username / Email**: `admin` or `admin@textilehaven.com`
  - **Password**: `admin123`
  - **Admin Dashboard**: [http://localhost:8080/admin.html](http://localhost:8080/admin.html)

---

## 📁 Project Structure

```
textile-haven-main/
├── frontend/                     # Modern Frontend (HTML5 + Tailwind CSS + Vanilla JS)
│   ├── js/
│   │   └── api.js                # Centralized REST API client & components
│   ├── assets/                   # Images and branding assets
│   ├── index.html                # Home page with hero and featured collection
│   ├── products.html             # Product catalog with search and pagination
│   ├── product-details.html      # Product detail view with size and stock selection
│   ├── cart.html                 # Shopping cart management
│   ├── checkout.html             # Shipping details and order placement
│   ├── checkout-success.html     # Order confirmation receipt
│   └── admin.html                # Admin product & inventory management
│
├── springboot-backend/           # Spring Boot REST API Backend
│   ├── src/main/java/springboot_backend/
│   │   ├── config/               # WebConfig (CORS, Static/Image Resources), PasswordEncoder
│   │   ├── controller/           # Auth, Product, Size, Cart, and Order REST Controllers
│   │   ├── dto/                  # Request and Response transfer models
│   │   ├── entity/               # JPA Entities (User, Product, Size, ProductSize, CartItem)
│   │   ├── repository/           # Spring Data JPA Repositories
│   │   └── service/              # Business logic & file upload storage
│   ├── src/main/resources/
│   │   ├── application.properties
│   │   ├── schema.sql            # Database schema
│   │   └── static/               # Bundled frontend assets served by Spring Boot
│   └── pom.xml
│
└── backend_php_backup/           # Archived legacy PHP backend (inactive)
```

---

## 🔌 REST API Endpoints

| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/api/auth/login` | Authenticate user (email/username + password) |
| `POST` | `/api/auth/register` | Register new user account |
| `GET` | `/api/auth/me` | Get currently authenticated user profile |
| `POST` | `/api/auth/logout` | Terminate session |
| `GET` | `/api/products` | Retrieve products (supports `search`, `page`, `size`, `all`) |
| `GET` | `/api/products/featured` | Retrieve top featured products |
| `GET` | `/api/products/{id}` | Get detailed product information with size stock |
| `POST` | `/api/products` | Add new product with image uploads (Admin) |
| `PUT` | `/api/products/{id}` | Update product & inventory (Admin) |
| `DELETE` | `/api/products/{id}` | Delete product (Admin) |
| `GET` | `/api/sizes` | Get all available clothing sizes |
| `GET` | `/api/sizes/product/{id}`| Get stock breakdown per size for a product |
| `GET` | `/api/cart` | Get current user's cart items and total |
| `POST` | `/api/cart` | Add item to cart with size and quantity |
| `PUT` | `/api/cart/{id}` | Update item quantity in cart |
| `DELETE` | `/api/cart/{id}` | Remove specific item from cart |
| `DELETE` | `/api/cart` | Clear entire cart |
| `POST` | `/api/checkout` | Process order, reduce stock, clear cart |
