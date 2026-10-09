-- ============================================================
--  KIOSK SALES & INVENTORY MANAGEMENT SYSTEM
--  Database Schema - kiosk_system
--  For: Restobar Kiosk System (BSIT Capstone)
--  Engine: MySQL 5.7+ / MariaDB (XAMPP)
-- ============================================================

CREATE DATABASE IF NOT EXISTS kiosk_system
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE kiosk_system;

SET FOREIGN_KEY_CHECKS = 0;

-- ============ DROP EXISTING TABLES (clean import) ============
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS forecasts;
DROP TABLE IF EXISTS stock_movements;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS sales_items;
DROP TABLE IF EXISTS sales;
DROP TABLE IF EXISTS inventory;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- TABLE: users
-- ============================================================
CREATE TABLE users (
  user_id    INT AUTO_INCREMENT PRIMARY KEY,
  fullname   VARCHAR(100) NOT NULL,
  email      VARCHAR(150) NOT NULL UNIQUE,
  username   VARCHAR(50)  NOT NULL UNIQUE,
  password   VARCHAR(255) NOT NULL,
  role       ENUM('admin','staff','kitchen') NOT NULL DEFAULT 'staff',
  status     ENUM('active','inactive') NOT NULL DEFAULT 'active',
  failed_attempts INT NOT NULL DEFAULT 0,
  locked_until    DATETIME NULL,
  is_verified TINYINT(1) NOT NULL DEFAULT 0,
  otp        VARCHAR(6)  NULL,
  otp_expiry DATETIME    NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: categories
-- ============================================================
CREATE TABLE categories (
  category_id   INT AUTO_INCREMENT PRIMARY KEY,
  category_name VARCHAR(100) NOT NULL,
  icon          VARCHAR(50)  DEFAULT 'bi-tag',
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: products
-- ============================================================
CREATE TABLE products (
  product_id      INT AUTO_INCREMENT PRIMARY KEY,
  category_id     INT NOT NULL,
  product_name    VARCHAR(150) NOT NULL,
  description     TEXT NULL,
  image           VARCHAR(255) NULL,
  price           DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  reorder_level   INT NOT NULL DEFAULT 5,
  expiration_date DATE NULL,
  status          ENUM('active','inactive') NOT NULL DEFAULT 'active',
  is_fast_moving  TINYINT(1) NOT NULL DEFAULT 0,
  created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_products_category FOREIGN KEY (category_id)
    REFERENCES categories(category_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: inventory
-- ============================================================
CREATE TABLE inventory (
  inventory_id INT AUTO_INCREMENT PRIMARY KEY,
  product_id   INT NOT NULL UNIQUE,
  quantity     INT NOT NULL DEFAULT 0,
  updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_inventory_product FOREIGN KEY (product_id)
    REFERENCES products(product_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: sales
-- ============================================================
CREATE TABLE sales (
  sale_id        INT AUTO_INCREMENT PRIMARY KEY,
  invoice_number VARCHAR(50) NOT NULL UNIQUE,
  total_amount   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  cash           DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  change_amount  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  sale_date      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  cashier        VARCHAR(100) NULL,
  payment_method VARCHAR(50)  NOT NULL DEFAULT 'Cash',
  order_type     ENUM('dine_in','take_out') NOT NULL DEFAULT 'dine_in'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: sales_items
-- ============================================================
CREATE TABLE sales_items (
  item_id    INT AUTO_INCREMENT PRIMARY KEY,
  sale_id    INT NOT NULL,
  product_id INT NOT NULL,
  quantity   INT NOT NULL,
  price      DECIMAL(10,2) NOT NULL,
  subtotal   DECIMAL(10,2) NOT NULL,
  CONSTRAINT fk_si_sale    FOREIGN KEY (sale_id)
    REFERENCES sales(sale_id) ON DELETE CASCADE,
  CONSTRAINT fk_si_product FOREIGN KEY (product_id)
    REFERENCES products(product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: orders  (Kitchen orders coming from kiosk)
-- ============================================================
CREATE TABLE orders (
  order_id      INT AUTO_INCREMENT PRIMARY KEY,
  order_number  VARCHAR(50) NOT NULL,
  customer_name VARCHAR(100) NOT NULL DEFAULT 'Guest',
  status        ENUM('pending','preparing','ready','completed','cancelled')
                NOT NULL DEFAULT 'pending',
total_amount  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  order_type    ENUM('dine_in','take_out') NOT NULL DEFAULT 'dine_in',
  payment_method VARCHAR(50) NOT NULL DEFAULT 'Cash',
  payment_status ENUM('unpaid','paid') NOT NULL DEFAULT 'unpaid',
  paid_at       DATETIME NULL,
  notes         TEXT NULL,
  order_date    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: order_items
-- ============================================================
CREATE TABLE order_items (
  item_id    INT AUTO_INCREMENT PRIMARY KEY,
  order_id   INT NOT NULL,
  product_id INT NOT NULL,
  quantity   INT NOT NULL,
  price      DECIMAL(10,2) NOT NULL,
  subtotal   DECIMAL(10,2) NOT NULL,
  CONSTRAINT fk_oi_order   FOREIGN KEY (order_id)
    REFERENCES orders(order_id) ON DELETE CASCADE,
  CONSTRAINT fk_oi_product FOREIGN KEY (product_id)
    REFERENCES products(product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: stock_movements
-- ============================================================
CREATE TABLE stock_movements (
  movement_id   INT AUTO_INCREMENT PRIMARY KEY,
  product_id    INT NOT NULL,
  movement_type ENUM('in','out','sale','adjustment') NOT NULL DEFAULT 'in',
  quantity      INT NOT NULL DEFAULT 0,
  remarks       VARCHAR(255) NULL,
  user_id       INT NULL,
  date          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_sm_product FOREIGN KEY (product_id)
    REFERENCES products(product_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: forecasts  (ML prediction results)
-- ============================================================
CREATE TABLE forecasts (
  forecast_id      INT AUTO_INCREMENT PRIMARY KEY,
  product_id       INT NOT NULL,
  forecast_date    DATE NOT NULL,
  predicted_sales  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  reorder_quantity INT NOT NULL DEFAULT 0,
  created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_fc_product FOREIGN KEY (product_id)
    REFERENCES products(product_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: notifications
-- ============================================================
CREATE TABLE notifications (
  notification_id INT AUTO_INCREMENT PRIMARY KEY,
  type            VARCHAR(50) NOT NULL DEFAULT 'info',
  message         TEXT NOT NULL,
  status          ENUM('unread','read') NOT NULL DEFAULT 'unread',
  created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: audit_logs
-- ============================================================
CREATE TABLE audit_logs (
  log_id    INT AUTO_INCREMENT PRIMARY KEY,
  user_id   INT NULL,
  action    VARCHAR(255) NOT NULL,
  details   TEXT NULL,
  ip_address VARCHAR(45) NULL,
  log_date  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

