# ============================================================
#  Database Helper for the Kiosk ML Module
#  Connects to MySQL and provides data access
#  Uses: pymysql (pure Python, no native deps)
# ============================================================

import pymysql

DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
'password': '',
    'database': 'kiosk_system',
    'charset': 'utf8mb4',
    'cursorclass': pymysql.cursors.DictCursor
}


def get_connection():
    """Return a MySQL connection."""
    return pymysql.connect(**DB_CONFIG)


def fetch_daily_sales(days=30):
    """
    Fetch total daily sales for the last N days.
    Returns list of dicts: {date, total}
    """
    conn = get_connection()
    try:
        with conn.cursor() as cursor:
            cursor.execute("""
                SELECT DATE(sale_date) AS date, SUM(total_amount) AS total
                FROM sales
                WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL %s DAY)
                GROUP BY DATE(sale_date)
                ORDER BY date ASC
            """, (days,))
            return cursor.fetchall()
    finally:
        conn.close()


def fetch_product_sales(days=30):
    """
    Fetch sales per product for the last N days.
    Returns list of dicts: {product_id, product_name, total_qty}
    """
    conn = get_connection()
    try:
        with conn.cursor() as cursor:
            cursor.execute("""
                SELECT si.product_id, p.product_name,
                       SUM(si.quantity) AS total_qty,
                       SUM(si.subtotal) AS total_revenue
                FROM sales_items si
                JOIN sales s ON s.sale_id = si.sale_id
                JOIN products p ON p.product_id = si.product_id
                WHERE s.sale_date >= DATE_SUB(CURDATE(), INTERVAL %s DAY)
                GROUP BY si.product_id, p.product_name
                ORDER BY total_qty DESC
            """, (days,))
            return cursor.fetchall()
    finally:
        conn.close()


def get_current_stock(product_id):
    """Return current stock quantity for a product."""
    conn = get_connection()
    try:
        with conn.cursor() as cursor:
            cursor.execute("SELECT quantity FROM inventory WHERE product_id = %s", (product_id,))
            row = cursor.fetchone()
            return row['quantity'] if row else 0
    finally:
        conn.close()


def get_reorder_level(product_id):
    """Return reorder level for a product."""
    conn = get_connection()
    try:
        with conn.cursor() as cursor:
            cursor.execute("SELECT reorder_level FROM products WHERE product_id = %s", (product_id,))
            row = cursor.fetchone()
            return row['reorder_level'] if row else 5
    finally:
        conn.close()


def save_forecast(product_id, forecast_date, predicted_sales, reorder_quantity):
    """
    Save a forecast prediction into the forecasts table.
    Inserts a new row (or could update if needed).
    """
    conn = get_connection()
    try:
        with conn.cursor() as cursor:
            cursor.execute("""
                INSERT INTO forecasts (product_id, forecast_date, predicted_sales, reorder_quantity)
                VALUES (%s, %s, %s, %s)
            """, (product_id, forecast_date, predicted_sales, reorder_quantity))
        conn.commit()
    finally:
        conn.close()


def save_prediction_csv(product_id, product_name, predicted_sales, reorder_quantity, filepath=None):
    """
    Save prediction results to a CSV file (for record keeping).
    """
    import csv
    import os
    if filepath is None:
        filepath = os.path.join(os.path.dirname(__file__), 'dataset', 'predictions.csv')
    os.makedirs(os.path.dirname(filepath), exist_ok=True)
    file_exists = os.path.isfile(filepath)
    with open(filepath, 'a', newline='', encoding='utf-8') as f:
        writer = csv.writer(f)
        if not file_exists:
            writer.writerow(['product_id', 'product_name', 'predicted_sales', 'reorder_quantity', 'date'])
        writer.writerow([product_id, product_name, predicted_sales, reorder_quantity, __import__('datetime').date.today()])
