# 🍻 FERRY'S KIOSK - Sales & Inventory Management System

A **Kiosk-Based Sales and Inventory Management System** for a Restobar in **Caloocan City, Philippines**, using **Machine Learning and Predictive Analytics** for Sales Forecasting and Stock Optimization.

> **BSIT Capstone Project** — Native PHP + MySQL + Python (scikit-learn) + Chart.js

---

## ✨ Features

### 👤 Customer Kiosk (Self-Ordering)
- Touchscreen-friendly, modern self-ordering interface
- Browse products by category with large buttons
- Live search, shopping cart, quantity adjustment
- Checkout with customer name & order type (Dine-in / Take-out)
- Order flows to the Kitchen queue automatically

### 🍳 Kitchen Site
- Real-time order management (Pending → Preparing → Ready → Completed)
- Order cards with item details, customer name, and notes
- One-click status updates

### 🛒 POS (Staff)
- Product grid with stock indicators
- Add to cart, quantity adjust, checkout
- Cash payment with change computation
- Print receipt
- Transaction history

### 🏗️ Admin Dashboard
- **Dashboard cards**: Today's/Weekly/Monthly sales, revenue, transactions, low stock, expiring, pending orders
- **Chart.js analytics**: Sales trend, top selling products, inventory, forecast
- Full product, category, inventory, and staff management
- Low stock & expiry alerts
- Stock movement history
- Audit logs

### 🤖 Machine Learning (Python)
- **Linear Regression** model for sales forecasting
- Reads historical sales from MySQL
- Predicts future sales demand
- Identifies fast-moving / slow-moving products
- Recommends reorder quantities
- Saves results to `forecasts` table for the dashboard

### 📊 Reports
- Daily / Weekly / Monthly / Yearly sales
- Inventory, stock movement, top products, expiry
- Export to **PDF** (print) and **Excel** (CSV)

### 🔐 Security
- PHP Session authentication
- Password hashing (bcrypt)
- Input validation
- SQL prepared statements (PDO)
- Role-based access (Admin / Staff / Kitchen)
- Audit logs

---

## 🛠️ Technology Stack

| Layer      | Technology |
|------------|-----------|
| Frontend   | HTML5, CSS3, JavaScript, Bootstrap Icons, Chart.js |
| Backend    | PHP (Native, PDO) |
| Database   | MySQL (XAMPP / phpMyAdmin) |
| ML         | Python 3, Pandas, scikit-learn (Linear Regression) |
| Server     | Apache (XAMPP) |

---

## 📁 Folder Structure

```
kiosk/
├── assets/          # CSS, JS, images, uploads
├── config/          # Database, session, auth, functions
├── database/        # SQL schema + sample data
├── authentication/  # Login, logout, change password
├── admin/           # Dashboard, products, inventory, reports, forecast
├── kiosk/           # Customer self-ordering kiosk
├── pos/             # Staff point of sale
├── kitchen/         # Kitchen order management
├── api/             # JSON endpoints (cart, orders, sales, etc.)
├── machine_learning/# Python ML module
├── includes/        # Shared headers/footers
├── install.php      # Database installer
└── index.php        # Entry point (role-based redirect)
```

---

## 🚀 Installation (XAMPP)

1. **Copy the project** to `C:\xampp\htdocs\kiosk`
2. **Start Apache & MySQL** in XAMPP Control Panel
3. **Create the database** via the installer:
   - Open `http://localhost/kiosk/install.php`
   - Fill in DB credentials (default: `root` / empty password)
   - Set your admin account
   - Click **Install Now**
4. **Delete `install.php`** after installation (security)
5. **Log in** at `http://localhost/kiosk/authentication/login.php`

### Default Accounts
| Role      | Username | Password    |
|-----------|----------|-------------|
| Admin     | `admin`  | `admin123`  |
| Staff     | `staff`  | `staff123`  |
| Kitchen   | `kitchen`| `kitchen123`|

---

## 🐍 Machine Learning Setup

```bash
cd machine_learning
pip install -r requirements.txt
python train_model.py     # Train the model (first time)
python forecast.py        # Run forecast
```

The **Admin Dashboard → Sales Forecast → Run Forecast** button also triggers the forecast.

---

## 🧭 User Flow

```
Customer → Kiosk → Cart → Checkout → Payment → Save Order
                ↓
        Kitchen Queue → Preparing → Ready → Completed
                ↓
          Save Sales → Inventory Update → Reports
                ↓
        Python Forecast → Forecast Table → Dashboard
```

---

## 📄 License
This project is for academic/educational purposes (BSIT Capstone).

© <?= date('Y') ?> Ferry's Restobar, Caloocan City
