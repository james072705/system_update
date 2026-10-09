-- ============================================================
--  KIOSK SYSTEM DATABASE - ERD FOR MYSQL WORKBENCH
--  Database: kiosk_system
--  This file is formatted for MySQL Workbench EER Diagram import
-- ============================================================

-- ============================================================
-- TABLE: users
-- Primary Key: user_id
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
  user_id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Primary Key - User ID',
  fullname VARCHAR(100) NOT NULL COMMENT 'User full name',
  email VARCHAR(150) NOT NULL UNIQUE COMMENT 'User email address',
  username VARCHAR(50) NOT NULL UNIQUE COMMENT 'Login username',
  password VARCHAR(255) NOT NULL COMMENT 'Hashed password',
  role ENUM('admin','staff','kitchen') NOT NULL DEFAULT 'staff' COMMENT 'User role',
  status ENUM('active','inactive') NOT NULL DEFAULT 'active' COMMENT 'Account status',
  failed_attempts INT NOT NULL DEFAULT 0 COMMENT 'Login failed attempts',
  locked_until DATETIME NULL COMMENT 'Account lockout expiry',
  is_verified TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Email verification status',
  otp VARCHAR(6) NULL COMMENT 'One-time password',
  otp_expiry DATETIME NULL COMMENT 'OTP expiry time',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Account creation time'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='System users table';

-- ============================================================
-- TABLE: categories
-- Primary Key: category_id
-- ============================================================
CREATE TABLE IF NOT EXISTS categories (
  category_id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Primary Key - Category ID',
  category_name VARCHAR(100) NOT NULL COMMENT 'Category name',
  icon VARCHAR(50) DEFAULT 'bi-tag' COMMENT 'Bootstrap icon class',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Category creation time'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Product categories';

-- ============================================================
-- TABLE: products
-- Primary Key: product_id
-- Foreign Key: category_id -> categories(category_id)
-- ============================================================
CREATE TABLE IF NOT EXISTS products (
  product_id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Primary Key - Product ID',
  category_id INT NOT NULL COMMENT 'FK - Category ID',
  product_name VARCHAR(150) NOT NULL COMMENT 'Product name',
  description TEXT NULL COMMENT 'Product description',
  image VARCHAR(255) NULL COMMENT 'Product image filename',
  price DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Product price',
  reorder_level INT NOT NULL DEFAULT 5 COMMENT 'Low stock threshold',
  expiration_date DATE NULL COMMENT 'Product expiration date',
  status ENUM('active','inactive') NOT NULL DEFAULT 'active' COMMENT 'Product availability',
  is_fast_moving TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Fast moving flag',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Product creation time',
  CONSTRAINT fk_products_category FOREIGN KEY (category_id)
    REFERENCES categories(category_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Products menu';

-- ============================================================
-- TABLE: inventory
-- Primary Key: inventory_id
-- Foreign Key: product_id -> products(product_id) UNIQUE
-- ============================================================
CREATE TABLE IF NOT EXISTS inventory (
  inventory_id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Primary Key - Inventory ID',
  product_id INT NOT NULL UNIQUE COMMENT 'FK - Product ID (one record per product)',
  quantity INT NOT NULL DEFAULT 0 COMMENT 'Current stock quantity',
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update time',
  CONSTRAINT fk_inventory_product FOREIGN KEY (product_id)
    REFERENCES products(product_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Product inventory tracking';

-- ============================================================
-- TABLE: sales
-- Primary Key: sale_id
-- Unique Key: invoice_number
-- ============================================================
CREATE TABLE IF NOT EXISTS sales (
  sale_id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Primary Key - Sale ID',
  invoice_number VARCHAR(50) NOT NULL UNIQUE COMMENT 'Unique invoice number',
  total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Total sale amount',
  cash DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Cash received',
  change_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Change given',
  sale_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Sale timestamp',
  cashier VARCHAR(100) NULL COMMENT 'Cashier name',
  payment_method VARCHAR(50) NOT NULL DEFAULT 'Cash' COMMENT 'Payment method used',
  order_type ENUM('dine_in','take_out') NOT NULL DEFAULT 'dine_in' COMMENT 'Order type'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Sales transactions';

-- ============================================================
-- TABLE: sales_items
-- Primary Key: item_id
-- Foreign Keys: sale_id -> sales, product_id -> products
-- ============================================================
CREATE TABLE IF NOT EXISTS sales_items (
  item_id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Primary Key - Sales Item ID',
  sale_id INT NOT NULL COMMENT 'FK - Sale ID',
  product_id INT NOT NULL COMMENT 'FK - Product ID',
  quantity INT NOT NULL COMMENT 'Quantity sold',
  price DECIMAL(10,2) NOT NULL COMMENT 'Unit price at time of sale',
  subtotal DECIMAL(10,2) NOT NULL COMMENT 'Line total',
  CONSTRAINT fk_si_sale FOREIGN KEY (sale_id)
    REFERENCES sales(sale_id) ON DELETE CASCADE,
  CONSTRAINT fk_si_product FOREIGN KEY (product_id)
    REFERENCES products(product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Individual items in a sale';

-- ============================================================
-- TABLE: orders
-- Primary Key: order_id
-- ============================================================
CREATE TABLE IF NOT EXISTS orders (
  order_id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Primary Key - Order ID',
  order_number VARCHAR(50) NOT NULL COMMENT 'Short order number (01-100)',
  customer_name VARCHAR(100) NOT NULL DEFAULT 'Guest' COMMENT 'Customer name',
  status ENUM('pending','preparing','ready','completed','cancelled') NOT NULL DEFAULT 'pending' COMMENT 'Order status',
  total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Order total amount',
  order_type ENUM('dine_in','take_out') NOT NULL DEFAULT 'dine_in' COMMENT 'Dine-in or take-out',
  payment_method VARCHAR(50) NOT NULL DEFAULT 'Cash' COMMENT 'Payment method',
  payment_status ENUM('unpaid','paid') NOT NULL DEFAULT 'unpaid' COMMENT 'Payment status',
  paid_at DATETIME NULL COMMENT 'Payment settlement time',
  preparing_at DATETIME NULL COMMENT 'When kitchen started preparing',
  ready_at DATETIME NULL COMMENT 'When order became ready',
  completed_at DATETIME NULL COMMENT 'When order was completed',
  cancelled_at DATETIME NULL COMMENT 'When order was cancelled',
  notes TEXT NULL COMMENT 'Order notes/instructions',
  order_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Order creation timestamp',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation time',
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Record update time'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Kitchen orders from kiosk';

-- ============================================================
-- TABLE: order_items
-- Primary Key: item_id
-- Foreign Keys: order_id -> orders, product_id -> products
-- ============================================================
CREATE TABLE IF NOT EXISTS order_items (
  item_id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Primary Key - Order Item ID',
  order_id INT NOT NULL COMMENT 'FK - Order ID',
  product_id INT NOT NULL COMMENT 'FK - Product ID',
  quantity INT NOT NULL COMMENT 'Quantity ordered',
  price DECIMAL(10,2) NOT NULL COMMENT 'Unit price at time of order',
  subtotal DECIMAL(10,2) NOT NULL COMMENT 'Line total',
  CONSTRAINT fk_oi_order FOREIGN KEY (order_id)
    REFERENCES orders(order_id) ON DELETE CASCADE,
  CONSTRAINT fk_oi_product FOREIGN KEY (product_id)
    REFERENCES products(product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Items within an order';

-- ============================================================
-- TABLE: stock_movements
-- Primary Key: movement_id
-- Foreign Keys: product_id -> products, order_id -> orders, user_id -> users
-- ============================================================
CREATE TABLE IF NOT EXISTS stock_movements (
  movement_id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Primary Key - Movement ID',
  product_id INT NOT NULL COMMENT 'FK - Product ID',
  order_id INT NULL COMMENT 'FK - Order ID (optional)',
  movement_type ENUM('in','out','sale','adjustment') NOT NULL DEFAULT 'in' COMMENT 'Movement type',
  quantity INT NOT NULL DEFAULT 0 COMMENT 'Quantity moved',
  remarks VARCHAR(255) NULL COMMENT 'Movement remarks',
  user_id INT NULL COMMENT 'FK - User who made the movement',
  date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Movement timestamp',
  CONSTRAINT fk_sm_product FOREIGN KEY (product_id)
    REFERENCES products(product_id) ON DELETE CASCADE,
  CONSTRAINT fk_sm_order FOREIGN KEY (order_id)
    REFERENCES orders(order_id) ON DELETE SET NULL,
  CONSTRAINT fk_sm_user FOREIGN KEY (user_id)
    REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stock movement history';

-- Indexes for stock_movements
CREATE INDEX idx_stock_movements_order_id ON stock_movements(order_id) COMMENT 'Index for order_id FK';
CREATE INDEX idx_stock_movements_user_id ON stock_movements(user_id) COMMENT 'Index for user_id FK';

-- ============================================================
-- TABLE: forecasts
-- Primary Key: forecast_id
-- Foreign Key: product_id -> products
-- Unique Key: (product_id, forecast_date)
-- ============================================================
CREATE TABLE IF NOT EXISTS forecasts (
  forecast_id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Primary Key - Forecast ID',
  product_id INT NOT NULL COMMENT 'FK - Product ID',
  forecast_date DATE NOT NULL COMMENT 'Forecast date',
  predicted_sales DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Predicted sales quantity',
  reorder_quantity INT NOT NULL DEFAULT 0 COMMENT 'Suggested reorder quantity',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Forecast creation time',
  CONSTRAINT fk_fc_product FOREIGN KEY (product_id)
    REFERENCES products(product_id) ON DELETE CASCADE,
  CONSTRAINT uq_forecast_product_date UNIQUE (product_id, forecast_date) COMMENT 'One forecast per product per date'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Sales forecast predictions';

-- ============================================================
-- TABLE: notifications
-- Primary Key: notification_id
-- Foreign Key: user_id -> users (optional, for user-specific notifications)
-- ============================================================
CREATE TABLE IF NOT EXISTS notifications (
  notification_id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Primary Key - Notification ID',
  user_id INT NULL COMMENT 'FK - User ID (NULL for global notifications)',
  type VARCHAR(50) NOT NULL DEFAULT 'info' COMMENT 'Notification type',
  message TEXT NOT NULL COMMENT 'Notification message',
  status ENUM('unread','read') NOT NULL DEFAULT 'unread' COMMENT 'Read status',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Notification time',
  CONSTRAINT fk_notif_user FOREIGN KEY (user_id)
    REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='System notifications';

-- Index for notifications
CREATE INDEX idx_notifications_user_id ON notifications(user_id) COMMENT 'Index for user_id FK';

-- ============================================================
-- TABLE: audit_logs
-- Primary Key: log_id
-- Foreign Key: user_id -> users (optional)
-- ============================================================
CREATE TABLE IF NOT EXISTS audit_logs (
  log_id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Primary Key - Log ID',
  user_id INT NULL COMMENT 'FK - User ID (NULL for system actions)',
  action VARCHAR(255) NOT NULL COMMENT 'Action performed',
  details TEXT NULL COMMENT 'Action details',
  ip_address VARCHAR(45) NULL COMMENT 'User IP address',
  log_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Log timestamp',
  CONSTRAINT fk_al_user FOREIGN KEY (user_id)
    REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='System audit log';

-- Index for audit_logs
CREATE INDEX idx_audit_logs_user_id ON audit_logs(user_id) COMMENT 'Index for user_id FK';

-- ============================================================
-- TABLE: order_status_history
-- Primary Key: history_id
-- Foreign Keys: order_id -> orders, changed_by -> users
-- ============================================================
CREATE TABLE IF NOT EXISTS order_status_history (
  history_id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Primary Key - History ID',
  order_id INT NOT NULL COMMENT 'FK - Order ID',
  status VARCHAR(50) NOT NULL COMMENT 'New status',
  changed_by INT NULL COMMENT 'FK - User who changed status',
  changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Change timestamp',
  remarks TEXT NULL COMMENT 'Optional remarks',
  CONSTRAINT fk_osh_order FOREIGN KEY (order_id)
    REFERENCES orders(order_id) ON DELETE CASCADE,
  CONSTRAINT fk_osh_user FOREIGN KEY (changed_by)
    REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Order status change history';

-- Indexes for order_status_history
CREATE INDEX idx_osh_order_id ON order_status_history(order_id) COMMENT 'Index for order_id FK';
CREATE INDEX idx_osh_changed_by ON order_status_history(changed_by) COMMENT 'Index for changed_by FK';

-- ============================================================
-- TABLE: order_announcements
-- Primary Key: announcement_id
-- Foreign Key: order_id -> orders
-- ============================================================
CREATE TABLE IF NOT EXISTS order_announcements (
  announcement_id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Primary Key - Announcement ID',
  order_id INT NOT NULL COMMENT 'FK - Order ID',
  announcement_type VARCHAR(50) NOT NULL DEFAULT 'ready' COMMENT 'Type of announcement',
  announcement_count INT NOT NULL DEFAULT 0 COMMENT 'Number of times announced',
  last_announced_at DATETIME NULL COMMENT 'Last announcement time',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation time',
  CONSTRAINT fk_oa_order FOREIGN KEY (order_id)
    REFERENCES orders(order_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Track order announcements for display';

-- Index for order_announcements
CREATE INDEX idx_oa_order_id ON order_announcements(order_id) COMMENT 'Index for order_id FK';

-- ============================================================
-- ERD RELATIONSHIPS SUMMARY
-- ============================================================
-- 
-- 1. categories (1) ----< products (many)
--    categories.category_id -> products.category_id
--
-- 2. products (1) ----< inventory (1)
--    products.product_id -> inventory.product_id (UNIQUE)
--
-- 3. products (1) ----< forecasts (many)
--    products.product_id -> forecasts.product_id
--    UNIQUE(product_id, forecast_date)
--
-- 4. orders (1) ----< order_items (many)
--    orders.order_id -> order_items.order_id
--
-- 5. products (1) ----< order_items (many)
--    products.product_id -> order_items.product_id
--
-- 6. products (1) ----< stock_movements (many)
--    products.product_id -> stock_movements.product_id
--
-- 7. orders (1) ----< stock_movements (many)
--    orders.order_id -> stock_movements.order_id
--
-- 8. users (1) ----< stock_movements (many)
--    users.user_id -> stock_movements.user_id
--
-- 9. sales (1) ----< sales_items (many)
--    sales.sale_id -> sales_items.sale_id
--
-- 10. products (1) ----< sales_items (many)
--     products.product_id -> sales_items.product_id
--
-- 11. users (1) ----< audit_logs (many)
--     users.user_id -> audit_logs.user_id
--
-- 12. orders (1) ----< order_status_history (many)
--     orders.order_id -> order_status_history.order_id
--
-- 13. users (1) ----< order_status_history (many)
--     users.user_id -> order_status_history.changed_by
--
-- 14. orders (1) ----< order_announcements (many)
--     orders.order_id -> order_announcements.order_id
--
-- 15. users (1) ----< notifications (many) [optional]
--     users.user_id -> notifications.user_id (nullable)
--
-- ============================================================
