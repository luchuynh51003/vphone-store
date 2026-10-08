-- =========================================================================
-- CƠ SỞ DỮ LIỆU ĐỒ ÁN V-PHONE STORE (ĐẦY ĐỦ 100+ MÁY & MÀU SẮC THỰC TẾ)
-- =========================================================================

CREATE DATABASE IF NOT EXISTS db_phone_store CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_phone_store;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS order_details;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS vouchers;
DROP TABLE IF EXISTS articles;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS brands;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE brands (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(50) NOT NULL);

CREATE TABLE users (id INT AUTO_INCREMENT PRIMARY KEY, fullname VARCHAR(100) NOT NULL, email VARCHAR(100) UNIQUE NOT NULL, phone VARCHAR(20), address VARCHAR(255), password VARCHAR(255) NOT NULL, role TINYINT DEFAULT 0, cart_data LONGTEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);

CREATE TABLE products (id INT AUTO_INCREMENT PRIMARY KEY, brand_id INT, name VARCHAR(150) NOT NULL, price DECIMAL(12,0) NOT NULL, sale_price DECIMAL(12,0) DEFAULT 0, image VARCHAR(255) NOT NULL, screen VARCHAR(60), cpu VARCHAR(100), ram VARCHAR(20), rom VARCHAR(20), battery VARCHAR(50), quantity INT DEFAULT 20, is_featured TINYINT(1) DEFAULT 0, is_used TINYINT(1) DEFAULT 0, condition_desc VARCHAR(100) DEFAULT 'Mới 100%', colors VARCHAR(255) DEFAULT 'Đen, Trắng', storage_options LONGTEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE SET NULL);

CREATE TABLE orders (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, fullname VARCHAR(100) NOT NULL, phone VARCHAR(20) NOT NULL, address VARCHAR(255) NOT NULL, note TEXT, total_money DECIMAL(12,0) NOT NULL, payment_method VARCHAR(50) DEFAULT 'COD', status VARCHAR(50) DEFAULT 'Chờ xử lý', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL);

CREATE TABLE vouchers (
	id INT AUTO_INCREMENT PRIMARY KEY,
	code VARCHAR(40) NOT NULL UNIQUE,
	discount_type ENUM('percent', 'fixed') NOT NULL,
	discount_value DECIMAL(12,0) NOT NULL,
	minimum_order DECIMAL(12,0) NOT NULL DEFAULT 0,
	max_discount DECIMAL(12,0) DEFAULT NULL,
	starts_at DATETIME DEFAULT NULL,
	expires_at DATETIME DEFAULT NULL,
	usage_limit INT DEFAULT NULL,
	used_count INT NOT NULL DEFAULT 0,
	is_active TINYINT(1) NOT NULL DEFAULT 1,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO vouchers (code, discount_type, discount_value, minimum_order, max_discount)
VALUES
	('VPHONE5', 'percent', 5, 500000, 250000),
	('VPHONE10', 'percent', 10, 1000000, 500000),
	('VPHONE15', 'percent', 15, 5000000, 1000000),
	('FLASH20', 'percent', 20, 10000000, 2000000),
	('WELCOME50', 'fixed', 50000, 500000, NULL),
	('GIAM100K', 'fixed', 100000, 1000000, NULL),
	('GIAM200K', 'fixed', 200000, 2000000, NULL),
	('GIAM500K', 'fixed', 500000, 5000000, NULL);

CREATE TABLE articles (
	id INT AUTO_INCREMENT PRIMARY KEY,
	title VARCHAR(255) NOT NULL,
	badge VARCHAR(80) NOT NULL DEFAULT 'TIN MỚI',
	badge_class VARCHAR(40) NOT NULL DEFAULT 'bg-primary',
	article_date VARCHAR(30) NOT NULL,
	read_time VARCHAR(30) NOT NULL DEFAULT '5 phút đọc',
	image VARCHAR(255) NOT NULL,
	summary TEXT NOT NULL,
	content LONGTEXT NOT NULL,
	is_published TINYINT(1) NOT NULL DEFAULT 1,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE order_details (id INT AUTO_INCREMENT PRIMARY KEY, order_id INT, product_id INT, product_name VARCHAR(150), price DECIMAL(12,0), quantity INT, total_price DECIMAL(12,0), FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE, FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL);

INSERT INTO brands (id, name) VALUES (1, 'Apple'), (2, 'Samsung'), (3, 'Xiaomi'), (4, 'OPPO'), (5, 'Vivo'), (6, 'Google Pixel'), (7, 'ASUS ROG'), (8, 'Sony'), (9, 'Huawei & Honor');

INSERT INTO products (id, brand_id, name, price, sale_price, image, screen, cpu, ram, rom, battery, quantity, is_featured, is_used, condition_desc, created_at, colors) VALUES
(1, 1, 'iPhone 18 Pro Max (Khung Nhôm)', 36990000, 33990000, 'assets/images/products/iphone-18-promax.png', '6.9" 144Hz', 'Apple A20 Pro (2nm)', '16 GB', '256 GB', '5100 mAh', 25, 1, 0, 'Mới 100%', '2026-09-25 08:55:29', 'Đỏ Burgundy, Xanh Glacier Blue, Đen, Bạc'),
(2, 1, 'iPhone 18 Pro (Khung Nhôm)', 36990000, 33990000, 'assets/images/products/iphone-18-promax.png', '6.3" 144Hz', 'Apple A20 Pro (2nm)', '16 GB', '256 GB', '3900 mAh', 20, 1, 0, 'Mới 100%', '2026-09-25 08:55:29', 'Đỏ Burgundy, Xanh Glacier Blue, Đen, Bạc'),
(3, 1, 'iPhone Duo (Màn Gập Apple)', 69990000, 64990000, 'assets/images/products/iphone-18-duo.png', '7.9" Gập', 'Apple A20 Pro Bionic', '16 GB', '256 GB', '5200 mAh', 12, 1, 0, 'Mới 100%', '2026-09-25 08:55:29', 'Trắng Ánh Sao, Trời Đêm'),
(4, 1, 'iPhone 18 Standard', 27990000, 24990000, 'assets/images/products/iphone-16.png', '6.1" OLED', 'Apple A20 Bionic', '12 GB', '128 GB', '4100 mAh', 30, 1, 0, 'Mới 100%', '2026-09-25 08:55:29', 'Xanh Băng, Hồng, Bạc, Đen'),
(5, 2, 'Galaxy Z Tri-Fold (Gập 3 Màn)', 62990000, 56990000, 'assets/images/products/samsung-tri-fold.png', '10.2" Gập 3', 'Snapdragon 8 Gen 5 AI', '24 GB', '256 GB', '6200 mAh', 8, 1, 0, 'Mới 100%', '2026-09-25 08:55:29', 'Vàng Gold, Xám Titan, Đen Phantom'),
(6, 2, 'Galaxy S26 Ultra (Titan AI)', 38990000, 34990000, 'assets/images/products/samsung-s26-ultra.png', '6.8" 165Hz', 'Snapdragon 8 Gen 5 for Galaxy', '16 GB', '256 GB', '5800 mAh', 30, 1, 0, 'Mới 100%', '2026-09-25 08:55:29', 'Xám Titan, Đen Titan, Tím Titan, Vàng Titan'),
(7, 9, 'Huawei Mate XT (Gập 3 Màn)', 79990000, 74990000, 'assets/images/products/huawei-mate-xt.png', '10.2" Gập 3', 'Kirin 9010 5G Flagship', '16 GB', '256 GB', '5600 mAh', 5, 1, 0, 'Mới 100%', '2026-09-25 08:55:29', 'Đỏ Huyền Thoại, Đen Tinh Tế'),
(8, 3, 'Xiaomi 16 Ultra (Leica Edition)', 35990000, 32490000, 'assets/images/products/xiaomi-16-ultra.png', '6.78" 2K+', 'Snapdragon 8 Gen 5', '24 GB', '256 GB', '5800 mAh', 15, 1, 0, 'Mới 100%', '2026-09-25 08:55:29', 'Đen Da Thuần, Trắng Da Gốm'),
(9, 4, 'OPPO Find N5 Duo (Hologram)', 46990000, 42990000, 'assets/images/products/oppo-find-n5.png', '7.9" Gập', 'Dimensity 9500 Ultra', '16 GB', '256 GB', '5250 mAh', 10, 1, 0, 'Mới 100%', '2026-09-25 08:55:29', 'Vàng Hoàng Kim, Bạc Ánh Sao'),
(10, 1, 'iPhone 17 Pro Max', 36990000, 32990000, 'assets/images/products/iphone-17-promax.png', '6.9" 120Hz', 'Apple A19 Pro Bionic', '12 GB', '256 GB', '4850 mAh', 20, 0, 0, 'Mới 100%', '2026-09-25 08:55:29', 'Titan Tự Nhiên, Titan Xanh Đậm, Titan Bạc'),
(11, 1, 'iPhone 17 Pro', 30990000, 28490000, 'assets/images/products/iphone-16-pro.png', '6.3" 120Hz', 'Apple A19 Pro Bionic', '12 GB', '128 GB', '3600 mAh', 15, 0, 0, 'Mới 100%', '2026-09-25 08:55:29', 'Titan Tự Nhiên, Titan Bạc, Titan Đen'),
(12, 1, 'iPhone 17 Air (Siêu Mỏng)', 27990000, 25990000, 'assets/images/products/iphone-16.png', '6.6" 120Hz', 'Apple A19', '8 GB', '256 GB', '3800 mAh', 20, 0, 0, 'Mới 100%', '2026-09-25 08:55:29', 'Bạc Ánh Kim, Xanh Băng, Đen'),
(13, 1, 'iPhone 17 Standard', 23990000, 21990000, 'assets/images/products/iphone-16.png', '6.1" OLED', 'Apple A19', '8 GB', '128 GB', '3700 mAh', 25, 0, 0, 'Mới 100%', '2026-09-25 08:55:29', 'Xanh, Hồng, Trắng, Đen'),
(14, 1, 'iPhone 16 Pro Max', 28990000, 27990000, 'assets/images/products/iphone-16-promax.png', '6.9" 120Hz', 'Apple A18 Pro', '8 GB', '256 GB', '4685 mAh', 35, 0, 0, 'Mới 100%', '2026-09-25 08:55:29', 'Titan Sa Mạc, Titan Tự Nhiên, Titan Đen, Titan Trắng'),
(15, 1, 'iPhone 16 Pro', 28990000, 27990000, 'assets/images/products/iphone-16-pro.png', '6.3" 120Hz', 'Apple A18 Pro', '8 GB', '128 GB', '3582 mAh', 25, 0, 0, 'Mới 100%', '2026-09-25 08:55:29', 'Titan Sa Mạc, Titan Tự Nhiên, Titan Đen, Titan Trắng'),
(16, 1, 'iPhone 16 Plus', 25990000, 24990000, 'assets/images/products/iphone-16-plus.png', '6.7" OLED', 'Apple A18', '8 GB', '128 GB', '4674 mAh', 20, 0, 0, 'Mới 100%', '2026-09-25 08:55:29', 'Xanh Lưu Ly, Hồng, Xanh Mòng Két, Trắng, Đen'),
(17, 1, 'iPhone 16 Standard', 22990000, 21490000, 'assets/images/products/iphone-16.png', '6.1" OLED', 'Apple A18', '8 GB', '128 GB', '3561 mAh', 30, 0, 0, 'Mới 100%', '2026-09-25 08:55:29', 'Hồng, Xanh Mòng Két, Xanh Lưu Ly, Trắng, Đen'),
(18, 1, 'iPhone 15 Pro Max', 31990000, 28490000, 'assets/images/products/iphone-15-promax.png', '6.7" 120Hz', 'Apple A17 Pro', '8 GB', '256 GB', '4422 mAh', 25, 0, 0, 'Mới 100%', '2026-09-25 08:55:29', 'Titan Tự Nhiên, Titan Xanh, Titan Đen, Titan Trắng'),
(19, 1, 'iPhone 15 Standard', 21990000, 18490000, 'assets/images/products/iphone-15.png', '6.1" OLED', 'Apple A16 Bionic', '6 GB', '128 GB', '3349 mAh', 30, 0, 0, 'Mới 100%', '2026-09-25 08:55:29', 'Hồng, Vàng, Xanh Mint, Xanh Lam, Đen'),
(20, 1, 'iPhone 14 Pro Max', 27990000, 24490000, 'assets/images/products/iphone-14-promax.png', '6.7" 120Hz', 'Apple A16 Bionic', '6 GB', '128 GB', '4323 mAh', 20, 0, 0, 'Mới 100%', '2026-09-25 08:55:29', 'Tím Deep Purple, Vàng Gold, Bạc Silver, Đen Space'),
(21, 1, 'iPhone 14 Standard', 19990000, 16490000, 'assets/images/products/iphone-14.jpg', '6.1" OLED', 'Apple A15 Bionic', '6 GB', '128 GB', '3279 mAh', 22, 0, 0, 'Mới 100%', '2026-09-25 08:55:29', 'Xanh Dương, Tím, Vàng, Trắng Starlight, Đen Midnight, Đỏ'),
(22, 1, 'iPhone 13 Standard', 16990000, 13490000, 'assets/images/products/iphone-13.png', '6.1" OLED', 'Apple A15 Bionic', '4 GB', '128 GB', '3240 mAh', 35, 0, 0, 'Mới 100%', '2026-09-25 08:55:29', 'Trắng Starlight, Đen Midnight, Hồng, Xanh Lá, Đỏ, Xanh Dương'),
(23, 1, 'iPhone 12 Standard', 13990000, 11490000, 'assets/images/products/iphone-12.jpg', '6.1" OLED', 'Apple A14 Bionic', '4 GB', '64 GB', '2815 mAh', 18, 0, 0, 'Mới 100%', '2026-09-25 08:55:29', 'Tím, Xanh Lam, Xanh Lục, Đỏ, Trắng, Đen'),
(24, 1, 'iPhone 11 64GB', 11990000, 8490000, 'assets/images/products/iphone-11.png', '6.1" LCD', 'Apple A13 Bionic', '4 GB', '64 GB', '3110 mAh', 40, 0, 0, 'Mới 100%', '2026-09-25 08:55:29', 'Đen, Trắng, Tím, Vàng, Xanh Lục, Đỏ'),
(25, 1, 'iPhone SE 2022', 10990000, 8990000, 'assets/images/products/iphone-se-2022.jpg', '4.7" Retina', 'Apple A15 Bionic', '4 GB', '64 GB', '2018 mAh', 15, 0, 0, 'Mới 100%', '2026-09-25 08:55:29', 'Đen Midnight, Trắng Starlight, Đỏ Product Red'),
(26, 2, 'Galaxy S24 Ultra 5G', 33990000, 26490000, 'assets/images/products/samsung-s24-ultra.png', '6.8" 120Hz 2K+', 'Snapdragon 8 Gen 3 for Galaxy', '12 GB', '256 GB', '5000 mAh', 30, 0, 0, 'Mới 100%', '2026-09-25 08:55:29', 'Xám Titan, Đen Titan, Tím Titan, Vàng Titan'),
(27, 2, 'Galaxy S24+ 5G', 26990000, 21490000, 'assets/images/products/samsung-s24-plus.jpg', '6.7" 120Hz 2K+', 'Exynos 2400', '12 GB', '256 GB', '4900 mAh', 20, 0, 0, 'Mới 100%', '2026-09-25 08:55:29', 'Tím Cobalt, Vàng Amber, Đen Onyx, Xám Marble'),
(28, 2, 'Galaxy S24 5G', 22990000, 17490000, 'assets/images/products/samsung-s24.jpg', '6.2" 120Hz', 'Exynos 2400', '8 GB', '256 GB', '4000 mAh', 15, 0, 0, 'Mới 100%', '2026-09-25 08:55:29', 'Vàng Amber, Xám Marble, Đen Onyx, Tím Cobalt'),
(29, 2, 'Galaxy S24 FE 5G', 16990000, 14990000, 'assets/images/products/samsung-s24-fe.jpg', '6.7" 120Hz', 'Exynos 2400e', '8 GB', '128 GB', '4700 mAh', 18, 0, 0, 'Mới 100%', '2026-09-25 08:55:29', 'Xanh Topaz, Xám Graphite, Vàng Chanh, Xanh Mint'),
(30, 2, 'Galaxy Z Fold6 5G', 43990000, 39490000, 'assets/images/products/samsung-z-fold6.png', '7.6" Gập 120Hz', 'Snapdragon 8 Gen 3 for Galaxy', '12 GB', '256 GB', '4400 mAh', 18, 0, 0, 'Mới 100%', '2026-09-25 08:55:29', 'Xám Metal, Hồng Petal, Xanh Navy'),
(31, 2, 'Galaxy Z Flip6 5G', 28990000, 24490000, 'assets/images/products/samsung-z-flip6.jpg', '6.7" Gập 120Hz', 'Snapdragon 8 Gen 3 for Galaxy', '12 GB', '256 GB', '4000 mAh', 22, 0, 0, 'Mới 100%', '2026-09-25 08:55:29', 'Xanh Maya, Vàng Solar, Bạc Shadow, Xanh Mint'),
(32, 2, 'Galaxy Z Fold5 5G', 34990000, 28990000, 'assets/images/products/samsung-z-fold5.jpg', '7.6" Gập', 'Snapdragon 8 Gen 2', '12 GB', '256 GB', '4400 mAh', 10, 0, 0, 'Mới 100%', '2026-09-25 08:55:29', 'Xanh Icy Blue, Kem Ivory, Đen Phantom'),
(33, 2, 'Galaxy S23 Ultra 5G', 26990000, 20490000, 'assets/images/products/samsung-s23-ultra.png', '6.8" 120Hz', 'Snapdragon 8 Gen 2', '8 GB', '256 GB', '5000 mAh', 20, 0, 0, 'Mới 100%', '2026-09-25 08:55:29', 'Đen Phantom, Xanh Botanic, Kem Cotton, Tím Lilac'),
(34, 2, 'Galaxy A55 5G', 9990000, 8690000, 'assets/images/products/samsung-a55.png', '6.6" 120Hz', 'Exynos 1480', '8 GB', '128 GB', '5000 mAh', 40, 0, 0, 'Mới 100%', '2026-09-25 08:55:29', 'Xanh Iceblue, Xanh Navy, Tím Lilac'),
(35, 2, 'Galaxy A35 5G', 7990000, 6990000, 'assets/images/products/samsung-a35.jpg', '6.6" 120Hz', 'Exynos 1380', '8 GB', '128 GB', '5000 mAh', 30, 0, 0, 'Mới 100%', '2026-09-25 08:55:29', 'Xanh Iceblue, Vàng Chanh, Xanh Navy'),
(36, 2, 'Galaxy A15 5G', 4990000, 4190000, 'assets/images/products/samsung-a15.jpg', '6.5" 90Hz', 'Dimensity 6100+', '8 GB', '128 GB', '5000 mAh', 35, 0, 0, 'Mới 100%', '2026-09-25 08:55:29', 'Xanh Dương, Đen, Vàng Khúc Chiết'),
(37, 2, 'Galaxy A05s', 3990000, 3390000, 'assets/images/products/samsung-a05s.jpg', '6.7" 90Hz', 'Snapdragon 680', '4 GB', '128 GB', '5000 mAh', 40, 0, 0, 'Mới 100%', '2026-09-25 08:55:29', 'Đen, Bạc Ánh Kim, Xanh Lá Khói'),
(38, 3, 'Xiaomi 14 Ultra 5G', 32990000, 28990000, 'assets/images/products/xiaomi-14-ultra.png', '6.73" 2K+ LTPO', 'Snapdragon 8 Gen 3', '16 GB', '512 GB', '5000 mAh', 15, 0, 0, 'Mới 100%', '2026-09-25 08:55:29', 'Đen Da Thuần, Trắng Da Thuần'),
(39, 3, 'Xiaomi 14 5G', 22990000, 18490000, 'assets/images/products/xiaomi-14.jpg', '6.36" 1.5K', 'Snapdragon 8 Gen 3', '12 GB', '256 GB', '4610 mAh', 20, 0, 0, 'Mới 100%', '2026-09-25 08:55:29', 'Trắng Ngọc, Đen Cổ Điển, Xanh Ngọc Bích'),
(40, 3, 'Redmi Note 13 Pro+ 5G', 10990000, 9190000, 'assets/images/products/redmi-note-13-pro-plus.jpg', '6.67" 1.5K Cong', 'Dimensity 7200 Ultra', '8 GB', '256 GB', '5000 mAh', 30, 0, 0, 'Mới 100%', '2026-09-25 08:55:29', 'Đen Midnight, Tím Cực Quang, Trắng Ánh Trăng'),
(41, 3, 'Redmi 13', 4290000, 3790000, 'assets/images/products/redmi-13.jpg', '6.79" 90Hz', 'Helio G91 Ultra', '6 GB', '128 GB', '5030 mAh', 45, 0, 0, 'Mới 100%', '2026-09-25 08:55:29', 'Xanh Biển, Vàng Cát, Đen'),
(42, 3, 'POCO F6 Pro 5G', 14990000, 12990000, 'assets/images/products/poco-f6-pro.jpg', '6.67" 2K 120Hz', 'Snapdragon 8 Gen 2', '12 GB', '256 GB', '5000 mAh', 15, 0, 0, 'Mới 100%', '2026-09-25 08:55:29', 'Đen Vũ Trụ, Trắng Sương Mai'),
(43, 3, 'POCO X6 Pro 5G', 9990000, 8490000, 'assets/images/products/poco-x6-pro.jpg', '6.67" 1.5K', 'Dimensity 8300-Ultra', '8 GB', '256 GB', '5000 mAh', 20, 0, 0, 'Mới 100%', '2026-09-25 08:55:29', 'Vàng Da Thuần, Đen, Xám'),
(44, 4, 'OPPO Find N3 5G', 44990000, 39490000, 'assets/images/products/oppo-find-n3.png', '7.82" Gập 120Hz', 'Snapdragon 8 Gen 2', '16 GB', '512 GB', '4805 mAh', 12, 0, 0, 'Mới 100%', '2026-09-25 08:55:29', 'Vàng Hoàng Kim, Đen Cổ Điển Da'),
(45, 4, 'OPPO Find N3 Flip', 22990000, 17990000, 'assets/images/products/oppo-find-n3-flip.jpg', '6.8" Gập', 'Dimensity 9200', '12 GB', '256 GB', '4300 mAh', 15, 0, 0, 'Mới 100%', '2026-09-25 08:55:29', 'Vàng Thạch Anh, Hồng Phấn, Đen Kính'),
(46, 4, 'OPPO Reno12 Pro 5G', 18990000, 16490000, 'assets/images/products/oppo-reno12-pro.jpg', '6.7" 120Hz Cong', 'Dimensity 7300-Energy', '12 GB', '512 GB', '5000 mAh', 20, 0, 0, 'Mới 100%', '2026-09-25 08:55:29', 'Bạc Tinh Thể, Nâu Không Gian'),
(47, 4, 'OPPO Reno11 F 5G', 8990000, 7690000, 'assets/images/products/oppo-reno11-f.jpg', '6.7" 120Hz', 'Dimensity 7050', '8 GB', '256 GB', '5000 mAh', 30, 0, 0, 'Mới 100%', '2026-09-25 08:55:29', 'Xanh Cọ, Xanh Đại Dương, Tím Thạch Anh'),
(48, 5, 'Vivo X100 Pro 5G', 22990000, 19490000, 'assets/images/products/vivo-x100-pro.jpg', '6.78" 1.5K LTPO', 'Dimensity 9300', '16 GB', '512 GB', '5400 mAh', 10, 0, 0, 'Mới 100%', '2026-09-25 08:55:29', 'Cam Nắng Hoàng Hôn, Xanh Tinh Tú, Đen'),
(49, 5, 'Vivo Y28', 5790000, 4990000, 'assets/images/products/vivo-y28.jpg', '6.68" 90Hz', 'Helio G85', '8 GB', '128 GB', '6000 mAh', 35, 0, 0, 'Mới 100%', '2026-09-25 08:55:29', 'Cam Pháo Hoa, Xanh Ánh Kim'),
(50, 6, 'Google Pixel 9 Pro XL', 29990000, 26490000, 'assets/images/products/google-pixel-9.png', '6.8" 120Hz LTPO', 'Google Tensor G4', '16 GB', '256 GB', '5060 mAh', 10, 0, 0, 'Mới 100%', '2026-09-25 08:55:29', 'Đá Obsidian, Sứ Porcelain, Hạt Phỉ Hazel, Hồng Rose'),
(51, 7, 'ASUS ROG Phone 8 Pro', 31990000, 27490000, 'assets/images/products/asus-rog-phone-8.jpg', '6.78" 165Hz LTPO', 'Snapdragon 8 Gen 3', '16 GB', '512 GB', '5500 mAh', 8, 0, 0, 'Mới 100%', '2026-09-25 08:55:29', 'Đen Phantom Matrix, Xám Nổi Loạn'),
(52, 8, 'Sony Xperia 1 VI', 34990000, 30490000, 'assets/images/products/sony-xperia-1-vi.png', '6.5" 120Hz LTPO', 'Snapdragon 8 Gen 3', '12 GB', '256 GB', '5000 mAh', 8, 0, 0, 'Mới 100%', '2026-09-25 08:55:29', 'Bạc Bạch Kim, Đen Cổ Điển, Xanh Rêu Khaki'),
(53, 1, '[Cũ 99%] iPhone 15 Pro Max 256GB', 28990000, 21990000, 'assets/images/products/iphone-15-promax.png', '6.7" 120Hz', 'Apple A17 Pro', '8 GB', '256 GB', '4422 mAh', 7, 0, 1, 'Đẹp 99% - Pin 96%', '2026-09-25 08:55:29', 'Titan Tự Nhiên, Titan Xanh, Titan Đen'),
(54, 1, '[Cũ 99%] iPhone 14 Pro Max 128GB', 24990000, 17990000, 'assets/images/products/iphone-14-promax.png', '6.7" 120Hz', 'Apple A16 Bionic', '6 GB', '128 GB', '4323 mAh', 5, 0, 1, 'Đẹp 99% - Pin 93%', '2026-09-25 08:55:29', 'Tím Deep Purple, Vàng Gold, Đen Space'),
(55, 1, '[Cũ 99%] iPhone 13 128GB', 14990000, 10490000, 'assets/images/products/iphone-13.png', '6.1" OLED', 'Apple A15 Bionic', '4 GB', '128 GB', '3240 mAh', 8, 0, 1, 'Đẹp 98% - Pin 90%', '2026-09-25 08:55:29', 'Trắng Ánh Sao, Đen Midnight, Hồng'),
(56, 1, '[Cũ 99%] iPhone 11 64GB', 8990000, 5490000, 'assets/images/products/iphone-11.png', '6.1" LCD', 'Apple A13 Bionic', '4 GB', '64 GB', '3110 mAh', 15, 0, 1, 'Đẹp 98% - Pin 89%', '2026-09-25 08:55:29', 'Đen, Trắng, Đỏ'),
(57, 2, '[Cũ 99%] Samsung Galaxy S23 Ultra 256GB', 22990000, 15990000, 'assets/images/products/samsung-s23-ultra.png', '6.8" 120Hz', 'Snapdragon 8 Gen 2', '8 GB', '256 GB', '5000 mAh', 4, 0, 1, 'Đẹp Like New 99%', '2026-09-25 08:55:29', 'Đen Phantom, Kem Cotton, Xanh Botanic'),
(58, 2, '[Cũ 99%] Samsung Galaxy Z Fold5 5G', 32990000, 21990000, 'assets/images/products/samsung-z-fold5.jpg', '7.6" Gập', 'Snapdragon 8 Gen 2', '12 GB', '256 GB', '4400 mAh', 3, 0, 1, 'Bản gập nguyên zin 99%', '2026-09-25 08:55:29', 'Xanh Icy Blue, Đen Phantom');

INSERT INTO users (id, fullname, email, phone, address, password, role) VALUES (1, 'Võ Minh Hiếu', 'hieu@vphone.vn', '0901234567', 'Quận 1, TP.HCM', '$2y$10$kZ76ftCCfjpFJkWv8aOyFuYo2fmvz5loyyGZyQzqRvajascv78iKu', 0), (2, 'Huỳnh Bá Lực', 'luc@vphone.vn', '0909888999', 'Quận 7, TP.HCM', '$2y$10$kZ76ftCCfjpFJkWv8aOyFuYo2fmvz5loyyGZyQzqRvajascv78iKu', 0), (3, 'Quản Trị Viên V-Phone', 'admin@vphone.vn', '18006868', 'Trụ sở V-Phone', '$2y$10$kZ76ftCCfjpFJkWv8aOyFuYo2fmvz5loyyGZyQzqRvajascv78iKu', 1);

