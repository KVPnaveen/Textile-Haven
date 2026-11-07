# 🛍️ Textile Haven - Women's Fashion E-commerce

A modern, responsive e-commerce website for women's clothing built with PHP, MySQL, and Tailwind CSS.

## ✨ Features

- **Modern UI**: Beautiful, responsive design with Tailwind CSS
- **Product Management**: Admin dashboard for CRUD operations
- **Shopping Cart**: Add, remove, and manage cart items
- **User Authentication**: Login/signup system with admin roles
- **Product Search**: Search functionality across products
- **Mobile Responsive**: Works perfectly on all devices

## 🚀 Quick Start

### Prerequisites

- XAMPP/WAMP (Apache + MySQL + PHP)
- PHP 7.4+ with PDO MySQL extension

### Installation

1. **Clone/Download** the project to your web server directory

   ```
   C:\xampp\htdocs\textile-haven\
   ```

2. **Create Database**

   - Open phpMyAdmin: `http://localhost/phpmyadmin`
   - Create database: `textile_haven`
   - Import: `database/schema.sql`

3. **Configure Database**

   - Edit `includes/db.php` if needed
   - Default: `localhost`, `root`, no password

4. **Start Server**
   - Start Apache and MySQL in XAMPP
   - Visit: `http://localhost/textile-haven/`

## 📁 Project Structure

```
textile-haven/
├── admin/
│   └── products.php          # Admin dashboard
├── auth/
│   ├── login.php            # Login handler
│   ├── register.php         # Registration handler
│   └── logout.php           # Logout handler
├── includes/
│   ├── db.php              # Database connection
│   ├── header.php          # Common header
│   └── footer.php          # Common footer
├── models/
│   ├── User.php            # User management
│   ├── Product.php         # Product management
│   └── Cart.php            # Shopping cart
├── database/
│   └── schema.sql          # Database schema
├── index.php               # Home page
├── products.php            # Products listing
└── cart.php                # Shopping cart
```

## 🔑 Admin Access

- **URL**: `http://localhost/textile-haven/admin/products.php`
- **Email**: `admin@textilehaven.com`
- **Password**: `admin123`

## 🎨 Pages

1. **Home Page** (`index.php`)

   - Hero section with call-to-action
   - Featured products (6 items)
   - Category sections
   - Features section

2. **Products Page** (`products.php`)

   - All products with search
   - Pagination (12 per page)
   - Add to cart functionality

3. **Shopping Cart** (`cart.php`)

   - View cart items
   - Update quantities
   - Remove items

4. **Admin Dashboard** (`admin/products.php`)
   - Add new products
   - Edit existing products
   - Delete products
   - Manage inventory

## 🛠️ Technologies Used

- **Backend**: PHP 7.4+ with OOP
- **Database**: MySQL with PDO
- **Frontend**: HTML5, Tailwind CSS, JavaScript
- **Server**: Apache (XAMPP)

## 📊 Database Schema

- **users**: User accounts and authentication
- **products**: Product catalog with inventory
- **cart**: Shopping cart items

## 🎯 OOP Implementation

- **User Class**: Authentication, registration, admin checks
- **Product Class**: CRUD operations, search, pagination
- **Cart Class**: Add, remove, update cart items

## 🔧 Troubleshooting

### Common Issues

1. **Products not showing**

   - Check PDO MySQL extension is loaded
   - Restart Apache after php.ini changes

2. **Admin can't login**

   - Verify admin user exists in database
   - Check password hash is correct

3. **Database connection failed**
   - Ensure MySQL is running
   - Check database credentials in `includes/db.php`

### Quick Fixes

- **Clear browser cache**: Ctrl+F5
- **Check browser console**: F12 for errors
- **Restart Apache**: In XAMPP Control Panel

## 📝 License

This project is for educational purposes. Feel free to modify and use as needed.

---

**Textile Haven** - Your perfect fashion destination! 🎉
