# ============================================================
#  Fast/Slow Moving Products Analysis
#  Identifies fast-moving and slow-moving products
# ============================================================

import os
import sys

BASE_DIR = os.path.dirname(__file__)
if BASE_DIR not in sys.path:
    sys.path.insert(0, BASE_DIR)

from database import fetch_product_sales


def analyze(days=30):
    """Identify fast and slow moving products."""
    print("=" * 50)
    print("  FAST / SLOW MOVING PRODUCT ANALYSIS")
    print("=" * 50)

    product_sales = fetch_product_sales(days=days)
    if not product_sales:
        print("[WARN] No sales data found.")
        return

    # Sort by quantity
    sales = sorted(product_sales, key=lambda x: x['total_qty'], reverse=True)
    total = len(sales)

    print(f"\n[INFO] Analyzing {total} products over the last {days} days.\n")

    # Fast movers: top 25%
    fast_count = max(1, int(total * 0.25))
    print("[FAST MOVING PRODUCTS]")
    for p in sales[:fast_count]:
        print(f"  - {p['product_name']}: {p['total_qty']} units sold")

    # Slow movers: bottom 25%
    slow_count = max(1, int(total * 0.25))
    print("\n[SLOW MOVING PRODUCTS]")
    for p in sales[-slow_count:]:
        print(f"  - {p['product_name']}: {p['total_qty']} units sold")

    print("=" * 50)


if __name__ == '__main__':
    days = 30
    if len(sys.argv) > 1:
        try:
            days = int(sys.argv[1])
        except ValueError:
            pass
    analyze(days=days)
