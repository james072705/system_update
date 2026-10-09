# KIOSK Sales & Inventory Management System
# Machine Learning Module

## Overview
This module uses **Python** and **Linear Regression** to:
- Read historical sales data from the MySQL database
- Predict future sales demand
- Recommend reorder quantities
- Identify fast-moving and slow-moving products
- Save forecast results back into the database for the admin dashboard

## Setup

### 1. Install Python dependencies
```bash
pip install -r requirements.txt
```

### 2. Configure database
Edit `database.py` and update the `DB_CONFIG` dictionary with your MySQL credentials:
```python
DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
'password': '',
    'database': 'kiosk_system',
}
```

### 3. Train the model (first time)
```bash
python train_model.py
```
This trains a Linear Regression model on historical sales and saves it to `model/sales_model.pkl`.

### 4. Run the forecast
```bash
python forecast.py
```
or
```bash
python prediction.py --days 30
```
This reads sales from the database, predicts future demand, and saves results to the `forecasts` table.

## Scripts

| Script | Purpose |
|--------|---------|
| `train_model.py` | Trains the Linear Regression model |
| `prediction.py` | Main forecast engine (reads DB, predicts, saves) |
| `forecast.py` | Wrapper script called by the PHP admin dashboard |
| `reorder.py` | Generates stock reorder recommendations |
| `fast_slow_products.py` | Identifies fast/slow moving products |
| `database.py` | MySQL database connection and helpers |

## Integration with PHP
The admin dashboard's "Run Forecast" button calls:
```bash
python forecast.py
```
PHP then simply displays the results from the `forecasts` table via Chart.js.

## Note
If you have no sales data, run the sample data first via `database/sample_data.sql`.
