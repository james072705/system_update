# ============================================================
#  ML Training Script
#  Trains a Linear Regression model on historical sales data
#  and saves the model to model/sales_model.pkl
# ============================================================

import os
import csv
import pandas as pd
import pickle
from sklearn.linear_model import LinearRegression
from datetime import datetime, timedelta

# Ensure directories exist
BASE_DIR = os.path.dirname(__file__)
os.makedirs(os.path.join(BASE_DIR, 'dataset'), exist_ok=True)
os.makedirs(os.path.join(BASE_DIR, 'model'), exist_ok=True)

SALES_CSV = os.path.join(BASE_DIR, 'dataset', 'sales_history.csv')
MODEL_PATH = os.path.join(BASE_DIR, 'model', 'sales_model.pkl')


def generate_sample_sales_history(days=60):
    """
    If no sales_history.csv exists, generate a realistic sample dataset.
    This simulates daily sales for a restobar.
    """
    if os.path.isfile(SALES_CSV):
        return

    rows = []
    for i in range(days):
        date = datetime.now() - timedelta(days=days - i)
        # Weekly seasonality (higher on weekends)
        weekday = date.weekday()
        base = 2500
        weekend_boost = 800 if weekday >= 5 else 0
        trend = i * 12  # slow upward trend
        noise = ((i * 7) % 50) - 25  # pseudo-random noise
        total = base + weekend_boost + trend + noise
        rows.append([date.strftime('%Y-%m-%d'), round(total, 2)])

    with open(SALES_CSV, 'w', newline='', encoding='utf-8') as f:
        writer = csv.writer(f)
        writer.writerow(['date', 'total_sales'])
        writer.writerows(rows)

    print(f"[INFO] Generated sample sales history: {SALES_CSV}")


def train():
    generate_sample_sales_history()

    if not os.path.isfile(SALES_CSV):
        print("[ERROR] No sales history found.")
        return

    df = pd.read_csv(SALES_CSV)
    # Convert date to a numeric day index
    df['date'] = pd.to_datetime(df['date'])
    df['day_index'] = (df['date'] - df['date'].min()).dt.days
    df['weekday'] = df['date'].dt.weekday

    # Features: day index + weekday (simple)
    X = df[['day_index', 'weekday']].values
    y = df['total_sales'].values

    model = LinearRegression()
    model.fit(X, y)

    with open(MODEL_PATH, 'wb') as f:
        pickle.dump(model, f)

    # Save trained model info
    print(f"[INFO] Model trained on {len(df)} days of data.")
    print(f"[INFO] R^2 score: {model.score(X, y):.4f}")
    print(f"[INFO] Model saved to: {MODEL_PATH}")


if __name__ == '__main__':
    train()
