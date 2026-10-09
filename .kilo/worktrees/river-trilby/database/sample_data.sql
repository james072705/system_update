-- ============================================================
--  SAMPLE DATA - Restobar in Caloocan City
--  Run AFTER kiosk_system.sql
-- ============================================================

USE kiosk_system;

-- ---------- CATEGORIES ----------
INSERT INTO categories (category_name, icon) VALUES
('All Items',        'bi-grid'),
('Beers',            'bi-cup-straw'),
('Cocktails',        'bi-cup'),
('Shots',            'bi-lightning-charge'),
('Wines',            'bi-droplet-half'),
('Foods',            'bi-basket'),
('Softdrinks',       'bi-moisture');

-- ---------- PRODUCTS ----------
-- expiration_date uses DATE_ADD relative to today so the "days left" feature works anytime
INSERT INTO products (category_id, product_name, description, price, reorder_level, expiration_date, status) VALUES
(2, 'San Miguel Pale Pilsen', 'Classic Filipino beer - 330ml bottle', 58.00, 24, NULL, 'active'),
(2, 'San Miguel Light',       'Light beer - 330ml bottle',           62.00, 24, NULL, 'active'),
(2, 'Red Horse Beer',         'Extra strong beer - 500ml bottle',    75.00, 24, NULL, 'active'),
(2, 'San Mig Zero',           'Zero alcohol beer - 330ml',           65.00, 12, NULL, 'active'),
(3, 'Mojito',                 'Fresh mint lime mojito',             150.00, 10, DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'active'),
(3, 'Blue Lagoon',            'Sweet blue cocktail',                145.00, 10, DATE_ADD(CURDATE(), INTERVAL 3 DAY), 'active'),
(3, 'Tequila Sunrise',        'Tequila with orange & grenadine',     160.00, 10, DATE_ADD(CURDATE(), INTERVAL 5 DAY), 'active'),
(3, 'Margarita',              'Classic margarita on the rocks',      155.00, 10, DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'active'),
(4, 'Emperador Light',        'Brandy shot - 350ml',                 90.00, 15, NULL, 'active'),
(4, 'Tanduay Ice',            'RTD rum lime - 330ml',                70.00, 15, NULL, 'active'),
(4, 'Tequila Shot',           'Straight tequila shot',               65.00, 20, NULL, 'active'),
(5, 'Bordeaux Red Wine',      'House red wine - glass',              120.00, 8,  DATE_ADD(CURDATE(), INTERVAL 10 DAY), 'active'),
(5, 'Sauvignon Blanc',        'House white wine - glass',            120.00, 8,  DATE_ADD(CURDATE(), INTERVAL 15 DAY), 'active'),
(6, 'Buffalo Wings (6 pcs)',  'Spicy buffalo wings with dip',        185.00, 5,  DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'active'),
(6, 'Sisig',                  'Sizzling pork sisig',                 165.00, 5,  DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'active'),
(6, 'French Fries',           'Crispy fries with ketchup',            85.00, 8,  DATE_ADD(CURDATE(), INTERVAL 4 DAY), 'active'),
(6, 'Nachos Grande',          'Beef nachos with cheese sauce',       145.00, 5,  DATE_ADD(CURDATE(), INTERVAL 6 DAY), 'active'),
(6, 'Pulchon Sisig Rice Bowl','Sisig with rice bowl',                195.00, 5,  DATE_ADD(CURDATE(), INTERVAL 3 DAY), 'active'),
(7, 'Coca-Cola',              'Soda - 330ml can',                     40.00, 20, NULL, 'active'),
(7, 'Iced Tea',               'Iced tea - 330ml',                     35.00, 20, NULL, 'active'),
(7, 'Mineral Water',          'Mineral water - 500ml',                25.00, 24, NULL, 'active');

-- ---------- INVENTORY ----------
INSERT INTO inventory (product_id, quantity) VALUES
(1, 120), (2, 96), (3, 60), (4, 30), (5, 40), (6, 35), (7, 25), (8, 28),
(9, 60), (10, 40), (11, 80), (12, 20), (13, 18), (14, 20), (15, 15),
(16, 40), (17, 12), (18, 10), (19, 60), (20, 45), (21, 80);

-- ---------- SAMPLE SALES (last 30 days) ----------
INSERT INTO sales (invoice_number, total_amount, cash, change_amount, sale_date, cashier, payment_method, order_type) VALUES
('INV-20250110-0001', 580.00, 600.00, 20.00, DATE_SUB(NOW(), INTERVAL 30 DAY), 'Kiosk', 'Cash', 'dine_in'),
('INV-20250112-0002', 415.00, 500.00, 85.00, DATE_SUB(NOW(), INTERVAL 28 DAY), 'Kiosk', 'Cash', 'take_out'),
('INV-20250114-0003', 920.00, 1000.00, 80.00, DATE_SUB(NOW(), INTERVAL 26 DAY), 'Kiosk', 'Cash', 'dine_in'),
('INV-20250116-0004', 300.00, 300.00, 0.00,  DATE_SUB(NOW(), INTERVAL 24 DAY), 'Kiosk', 'Cash', 'dine_in'),
('INV-20250118-0005', 750.00, 800.00, 50.00, DATE_SUB(NOW(), INTERVAL 22 DAY), 'Kiosk', 'Cash', 'take_out'),
('INV-20250120-0006', 1240.00, 1500.00, 260.00, DATE_SUB(NOW(), INTERVAL 20 DAY), 'Kiosk', 'Cash', 'dine_in'),
('INV-20250122-0007', 485.00, 500.00, 15.00, DATE_SUB(NOW(), INTERVAL 18 DAY), 'Kiosk', 'Cash', 'dine_in'),
('INV-20250124-0008', 865.00, 1000.00, 135.00, DATE_SUB(NOW(), INTERVAL 16 DAY), 'Kiosk', 'Cash', 'dine_in'),
('INV-20250126-0009', 320.00, 350.00, 30.00, DATE_SUB(NOW(), INTERVAL 14 DAY), 'Kiosk', 'Cash', 'take_out'),
('INV-20250128-0010', 1010.00, 1100.00, 90.00, DATE_SUB(NOW(), INTERVAL 12 DAY), 'Kiosk', 'Cash', 'dine_in'),
('INV-20250130-0011', 575.00, 600.00, 25.00, DATE_SUB(NOW(), INTERVAL 10 DAY), 'Kiosk', 'Cash', 'dine_in'),
('INV-20250201-0012', 1335.00, 1400.00, 65.00, DATE_SUB(NOW(), INTERVAL 8 DAY), 'Kiosk', 'Cash', 'dine_in'),
('INV-20250203-0013', 440.00, 500.00, 60.00, DATE_SUB(NOW(), INTERVAL 6 DAY), 'Kiosk', 'Cash', 'take_out'),
('INV-20250205-0014', 895.00, 1000.00, 105.00, DATE_SUB(NOW(), INTERVAL 4 DAY), 'Kiosk', 'Cash', 'dine_in'),
('INV-20250207-0015', 650.00, 700.00, 50.00, DATE_SUB(NOW(), INTERVAL 2 DAY), 'Kiosk', 'Cash', 'dine_in'),
('INV-20250208-0016', 1110.00, 1200.00, 90.00, DATE_SUB(NOW(), INTERVAL 1 DAY), 'Kiosk', 'Cash', 'dine_in');

INSERT INTO sales_items (sale_id, product_id, quantity, price, subtotal) VALUES
(1, 1, 4, 58.00, 232.00), (1, 14, 1, 185.00, 185.00), (1, 19, 1, 40.00, 40.00), (1, 15, 1, 165.00, 165.00),
(2, 3, 2, 75.00, 150.00), (2, 6, 1, 145.00, 145.00), (2, 20, 2, 35.00, 70.00), (2, 16, 1, 85.00, 85.00),
(3, 1, 6, 58.00, 348.00), (3, 2, 3, 62.00, 186.00), (3, 17, 1, 145.00, 145.00), (3, 14, 1, 185.00, 185.00),
(4, 7, 1, 160.00, 160.00), (4, 16, 1, 85.00, 85.00), (4, 20, 1, 35.00, 35.00),
(5, 5, 2, 150.00, 300.00), (5, 15, 1, 165.00, 165.00), (5, 18, 1, 195.00, 195.00), (5, 19, 1, 40.00, 40.00),
(6, 1, 8, 58.00, 464.00), (6, 3, 4, 75.00, 300.00), (6, 6, 2, 145.00, 290.00), (6, 14, 2, 185.00, 370.00),
(7, 2, 3, 62.00, 186.00), (7, 4, 2, 65.00, 130.00), (7, 16, 1, 85.00, 85.00), (7, 19, 1, 40.00, 40.00),
(8, 9, 4, 90.00, 360.00), (8, 15, 1, 165.00, 165.00), (8, 17, 1, 145.00, 145.00), (8, 13, 1, 120.00, 120.00),
(9, 20, 2, 35.00, 70.00), (9, 21, 2, 25.00, 50.00), (9, 5, 1, 150.00, 150.00), (9, 16, 1, 85.00, 85.00),
(10, 1, 5, 58.00, 290.00), (10, 3, 3, 75.00, 225.00), (10, 14, 2, 185.00, 370.00), (10, 15, 1, 165.00, 165.00),
(11, 2, 4, 62.00, 248.00), (11, 7, 1, 160.00, 160.00), (11, 16, 1, 85.00, 85.00), (11, 19, 1, 40.00, 40.00),
(12, 1, 7, 58.00, 406.00), (12, 6, 2, 145.00, 290.00), (12, 18, 1, 195.00, 195.00), (12, 5, 2, 150.00, 300.00),
(13, 4, 2, 65.00, 130.00), (13, 11, 2, 65.00, 130.00), (13, 16, 1, 85.00, 85.00), (13, 20, 1, 35.00, 35.00),
(14, 3, 4, 75.00, 300.00), (14, 9, 2, 90.00, 180.00), (14, 17, 1, 145.00, 145.00), (14, 15, 1, 165.00, 165.00),
(15, 1, 4, 58.00, 232.00), (15, 10, 2, 70.00, 140.00), (15, 14, 1, 185.00, 185.00), (15, 19, 1, 40.00, 40.00),
(16, 5, 3, 150.00, 450.00), (16, 6, 2, 145.00, 290.00), (16, 14, 2, 185.00, 370.00);

-- ---------- SAMPLE STOCK MOVEMENTS ----------
INSERT INTO stock_movements (product_id, movement_type, quantity, remarks, user_id) VALUES
(1, 'in', 120, 'Initial stock', NULL),
(2, 'in', 96,  'Initial stock', NULL),
(3, 'in', 60,  'Initial stock', NULL),
(9, 'in', 60,  'Initial stock', NULL),
(15, 'in', 15, 'Initial stock', NULL);

