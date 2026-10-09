# ============================================================
#  Forecast Script - Wrapper for the admin "Run Forecast" button
#  Calls prediction.py main logic
# ============================================================

import os
import sys

# Ensure we can import from this directory
BASE_DIR = os.path.dirname(__file__)
if BASE_DIR not in sys.path:
    sys.path.insert(0, BASE_DIR)

from prediction import run_forecast

if __name__ == '__main__':
    # Run with default 30 days when called from PHP
    days = 30
    if len(sys.argv) > 1:
        try:
            days = int(sys.argv[1])
        except ValueError:
            pass
    run_forecast(days=days)
