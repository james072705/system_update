# ============================================================
#  Reorder Optimization Script
#  Generates stock reorder recommendations based on forecast
# ============================================================

import os
import sys

BASE_DIR = os.path.dirname(__file__)
if BASE_DIR not in sys.path:
    sys.path.insert(0, BASE_DIR)

from database import (
    fetch_product_sales,
    get_current_stock,
    get_reorder_level,
    save_forecast
)
from datetime import datetime, timedelta


def generate_reorder_recommendations(days=30):
    """Generate reorder recommendations for all products."""
    print("=" * 50)
    print("  STOCK REORDER OPTIMIZATION")
    print("=" * 50)

    product_sales = fetch_product_sales(days=days)
    if not product_sales:
        print("[WARN] No sales data found.")
        return

    print(f"\n[INFO] Analyzing {len(product_sales)} products...")
    recommendations = []

    for p in product_sales:
        pid = p['product_id']
        name = p['product_name']
        avg_daily = p['total_qty'] / max(days, 1)
        predicted_7d = round(float(avg_daily) * 7)

        current_stock = get_current_stock(pid)
        reorder_level = get_reorder_level(pid)

        # Reorder quantity: predicted demand minus current stock, at least reorder level
        target = max(reorder_level, predicted_7d)
        reorder_qty = max(0, int(target - current_stock))

        priority = 'High' if reorder_qty > 0 else 'OK'
        recommendations.append({
            'product': name,
            'current_stock': current_stock,
            'predicted': predicted_7d,
            'reorder': reorder_qty,
            'priority': priority
        })

        # Save forecast
        forecast_date = (datetime.now() + timedelta(days=7)).strftime('%Y-%m-%d')
        save_forecast(pid, forecast_date, predicted_7d, reorder_qty)

        print(f"  - {name}: stock={current_stock}, predicted={predicted_7d}, reorder={reorder_qty} [{priority}]")

    # Sort by reorder quantity desc
    recommendations.sort(key=lambda x: x['reorder'], reverse=True)

    print("\n[SUMMARY] Products to reorder:")
    for r in recommendations:
        if r['reorder'] > 0:
            print(f"  - {r['product']}: order {r['reorder']} units [{r['priority']}]")

    print("=" * 50)


if __name__ == '__main__':
    days = 30
    if len(sys.argv) > 1:
        try:
            days = int(sys.argv[1])
        except ValueError:
            pass
    generate_reorder_recommendations(days=days)
