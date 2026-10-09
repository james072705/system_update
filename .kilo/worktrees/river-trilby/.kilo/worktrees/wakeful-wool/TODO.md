# Kiosk Sales → Payment → Kitchen → Admin Flow

## Goal
When admin marks a kiosk order as **PAID**:
1. The sale/transaction is **recorded immediately** (appears in admin today's sales).
2. Inventory is deducted automatically.
3. The order is **sent to the kitchen as PENDING**.
4. Kitchen moves it through: **Pending → Preparing → Ready → Completed** (status only, no duplicate sale).

## Status

### Kiosk (customer)
- [x] `kiosk/payment.php` creates an order with `payment_status = 'unpaid'`, status `pending`.
- [x] `kiosk/receipt.php` shows 6-digit order number + "wait to pay" message.

### Admin Settlement
- [x] `admin/payments/settle.php` — "Mark as Paid" button.
- [x] On mark-as-paid: sets `payment_status='paid'`, `paid_at=NOW()`, **status='pending'**.
- [x] On mark-as-paid: **records the sale** in `sales` + `sales_items`, **deducts inventory**, logs stock movement.
- [x] "Payments" link in admin sidebar.

### Kitchen (staff)
- [x] Kitchen queries filter to `payment_status = 'paid'` only (index, pending, preparing, ready, completed, order_details).
- [x] Kitchen header tab counts show paid orders only.
- [x] `api/orders.php` — only updates status (pending→preparing→ready→completed); sale already recorded at payment, so **no duplicate sale**.

### POS (staff)
- [x] `api/pos.php` creates order with `payment_status='paid'` + `paid_at=NOW()` so POS sales also flow to kitchen immediately.

### Admin Dashboard
- [x] Dashboard reads from `sales` table → Today's Sales / Transactions Today update automatically when admin settles payment.

### Migrations
- [x] `database/kiosk_system.sql` includes `payment_status`, `paid_at`, `payment_method` columns on orders.
- [x] `migrate.php` adds these columns to existing DBs and backfills orders as paid.
- [x] `admin/payments/settle.php` and `kiosk/payment.php` auto-migrate on load.

## Verified
- [x] No duplicate sales (invoice number uniqueness check in settle.php).
- [x] POS and kiosk both flow to kitchen with correct payment status.

## Payment Settlement Enhancements
- [x] Search bar for order numbers (admin can search unpaid/paid orders).
- [x] "View Order" button opens a modal with full order details & items.
- [x] "Mark as Paid" opens a confirmation modal with cash received + change.
- [x] After marking paid, redirects to a printable official Ferry's Restobar receipt.
- [x] Official receipt format: Business name, address, contact, cashier, order number, dine-in/take-out, items+qty, total, payment, cash, change, footer message.
- [x] Business info constants added (BUSINESS_NAME, ADDRESS, PHONE, EMAIL).
- [x] `includes/receipt_print.php` reusable printable receipt.
- [x] `admin/payments/receipt.php` standalone receipt page with Print button.
- [x] `api/orders.php` `view` action added (allows admin/staff/kitchen).
