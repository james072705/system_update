# ============================================================
#  Main Prediction Script (Processes Database Sales)
#  Reads historical sales from MySQL, predicts future demand,
#  recommends reorder quantities, and saves results.
#
#  Usage:
#      python prediction.py
#      python prediction.py --days 30
# ============================================================

import os
import sys
import argparse
import pickle
import pandas as pd
from datetime import datetime, timedelta

from database import (
    fetch_daily_sales,
    fetch_product_sales,
    get_current_stock,
    get_reorder_level,
    save_forecast,
    save_prediction_csv
)

BASE_DIR = os.path.dirname(__file__)
MODEL_PATH = os.path.join(BASE_DIR, 'model', 'sales_model.pkl')


def load_model():
    """Load the trained Linear Regression model."""
    if not os.path.isfile(MODEL_PATH):
        print("[ERROR] Model not found. Run train_model.py first.")
        sys.exit(1)
    with open(MODEL_PATH, 'rb') as f:
        return pickle.load(f)


def predict_total_sales(model, days_ahead=7):
    """
    Predict total sales for the next N days using the model.
    """
    daily = fetch_daily_sales(days=60)
    if not daily:
        print("[WARN] No daily sales data found. Using sample data.")
        return None

    df = pd.DataFrame(daily)
    df['date'] = pd.to_datetime(df['date'])
    df['day_index'] = (df['date'] - df['date'].min()).dt.days
    df['weekday'] = df['date'].dt.weekday

    # Predict for next days_ahead
    last_day = df['day_index'].max()
    predictions = []
    future_dates = []
    for i in range(1, days_ahead + 1):
        day_idx = last_day + i
        future_date = datetime.now() + timedelta(days=i)
        weekday = future_date.weekday()
        pred = model.predict([[day_idx, weekday]])[0]
        predictions.append(max(0, round(float(pred), 2)))
        future_dates.append(future_date.strftime('%Y-%m-%d'))

    return future_dates, predictions


def identify_product_status(product_sales):
    """
    Identify fast-moving vs slow-moving products.
    Returns dict of product_id -> status.
    """
    if not product_sales:
        return {}

    # Sort by quantity
    sales = sorted(product_sales, key=lambda x: x['total_qty'], reverse=True)
    total_count = len(sales)
    status = {}
    for i, p in enumerate(sales):
        if i < total_count * 0.25:
            status[p['product_id']] = 'fast'
        elif i >= total_count * 0.75:
            status[p['product_id']] = 'slow'
        else:
            status[p['product_id']] = 'normal'
    return status


def recommend_reorder(product_id, predicted_sales, forecast_days=7):
    """
    Recommend reorder quantity based on predicted demand, current stock,
    and reorder level.
    """
    current_stock = get_current_stock(product_id)
    reorder_level = get_reorder_level(product_id)

    # Predicted units needed for the forecast period
    demand = predicted_sales

    # Safety stock: cover up to reorder level
    target = max(reorder_level, demand)

    # Units to order = target - current stock (if positive)
    reorder_qty = max(0, int(target - current_stock))

    return reorder_qty


def run_forecast(days=30):
    """Main forecast execution."""
    print("=" * 50)
    print("  KIOSK SALES FORECAST ENGINE")
    print("=" * 50)

    # Load model
    model = load_model()
    print(f"[INFO] Model loaded from {MODEL_PATH}")

    # 1. Fetch product sales
    product_sales = fetch_product_sales(days=days)
    if not product_sales:
        print("[WARN] No product sales data found.")
        return

    print(f"[INFO] Found {len(product_sales)} products with sales.")

    # 2. Identify fast/slow moving
    statuses = identify_product_status(product_sales)
    print("\n[INFO] Product status classification:")
    for pid, st in statuses.items():
        name = next((p['product_name'] for p in product_sales if p['product_id'] == pid), 'Unknown')
        print(f"  - {name}: {st}")

    # 3. Predict and save for each product
    future_dates = []
    predictions = []
    print("\n[INFO] Generating forecasts...")
    for p in product_sales:
        pid = p['product_id']
        name = p['product_name']
        avg_daily = p['total_qty'] / max(days, 1)
        # Estimate future demand over 7 days using average daily rate
        predicted_units = round(float(avg_daily) * 7)

        # Optionally use model for daily sales scaling
        if not future_dates:
            result = predict_total_sales(model, days_ahead=7)
            if result:
                future_dates, predictions = result

        # Scale per-product: use product share of total sales
        total_sales = sum(x['total_qty'] for x in product_sales)
        share = (p['total_qty'] / total_sales) if total_sales else 0.05
        weekly_total = sum(predictions) if predictions else predicted_units * 7
        scaled_prediction = round(weekly_total * share)

        # Reorder recommendation
        reorder_qty = recommend_reorder(pid, scaled_prediction)

        # Save to DB
        forecast_date = (datetime.now() + timedelta(days=7)).strftime('%Y-%m-%d')
        save_forecast(pid, forecast_date, scaled_prediction, reorder_qty)
        save_prediction_csv(pid, name, scaled_prediction, reorder_qty)

        print(f"  - {name}: predicted {scaled_prediction} units, reorder {reorder_qty}")

    print("\n[SUCCESS] Forecast completed and saved to database.")
    print("[INFO] Results are displayed in the Admin Dashboard.")
    print("=" * 50)


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description='KIOSK Sales Forecast')
    parser.add_argument('--days', type=int, default=30, help='Number of historical days to analyze')
    args = parser.parse_args()
    run_forecast(days=args.days)
