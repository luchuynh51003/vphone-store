-- =========================================================================
-- ĐỒ ÁN CHUYÊN NGÀNH: XÂY DỰNG WEBSITE BÁN ĐIỆN THOẠI THÔNG MINH V-PHONE
-- SINH VIÊN THỰC HIỆN: HUỲNH BÁ LỰC (25002479) - VÕ MINH HIẾU (25002470)
-- GIẢNG VIÊN HƯỚNG DẪN: THẦY MAI CHIẾM TUẤN
-- HỆ QUẢN TRỊ CSDL: MySQL / MariaDB (utf8mb4)
-- =========================================================================

CREATE DATABASE IF NOT EXISTS db_phone_store CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_phone_store;

-- TẮT KIỂM TRA KHÓA NGOẠI KHI KHỞI TẠO LẠI
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS order_details;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS brands;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. BẢNG THƯƠNG HIỆU (BRANDS)
CREATE TABLE brands (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. BẢNG NGƯỜI DÙNG & TÀI KHOẢN (USERS)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    address VARCHAR(255),
    password VARCHAR(255) NOT NULL,
    role TINYINT DEFAULT 0 COMMENT '0: Khách hàng, 1: Quản trị viên (Admin)',
    cart_data LONGTEXT NULL COMMENT 'Lưu giỏ hàng riêng cho từng user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. BẢNG ĐIỆN THOẠI (PRODUCTS)
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    brand_id INT,
    name VARCHAR(150) NOT NULL,
    price DECIMAL(12, 0) NOT NULL,
    sale_price DECIMAL(12, 0) DEFAULT 0,
    image VARCHAR(255) NOT NULL,
    screen VARCHAR(60),
    cpu VARCHAR(100),
    ram VARCHAR(20),
    rom VARCHAR(20),
    battery VARCHAR(50),
    quantity INT DEFAULT 20 COMMENT 'Số lượng tồn kho thực tế',
    is_featured TINYINT(1) DEFAULT 0 COMMENT '1: Flagship 2026',
    is_used TINYINT(1) DEFAULT 0 COMMENT '1: Hàng cũ like new 99%',
    condition_desc VARCHAR(100) DEFAULT 'Mới 100%',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. BẢNG ĐƠN HÀNG (ORDERS)
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    fullname VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address VARCHAR(255) NOT NULL,
    note TEXT,
    total_money DECIMAL(12, 0) NOT NULL,
    payment_method VARCHAR(50) DEFAULT 'COD',
    status VARCHAR(50) DEFAULT 'Chờ xử lý' COMMENT 'Chờ xử lý, Đang giao hàng, Đã giao thành công, Đã hủy',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. BẢNG CHI TIẾT ĐƠN HÀNG (ORDER_DETAILS)
CREATE TABLE order_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    product_id INT,
    product_name VARCHAR(150),
    price DECIMAL(12, 0),
    quantity INT,
    total_price DECIMAL(12, 0),
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================================
-- DỮ LIỆU MẪU CHUẨN XÁC 100%
-- =========================================================================

-- NẠP THƯƠNG HIỆU
INSERT INTO brands (id, name) VALUES 
(1, 'Apple'), 
(2, 'Samsung'), 
(3, 'Xiaomi'), 
(4, 'OPPO'), 
(5, 'Huawei');

-- NẠP TÀI KHOẢN MẪU (MẬT KHẨU MÃ HÓA CỦA 123456)
INSERT INTO users (id, fullname, email, phone, address, password, role) VALUES
(1, 'Võ Minh Hiếu', 'hieu@vphone.vn', '0901234567', 'Quận 1, TP. Hồ Chí Minh', 'yKnbq0ImzZjQS5jaGPJRO.CnkRyCZhj05fJSvzfRUi5MGaZqpi4rO', 0),
(2, 'Huỳnh Bá Lực', 'luc@vphone.vn', '0909888999', 'Quận 7, TP. Hồ Chí Minh', 'yKnbq0ImzZjQS5jaGPJRO.CnkRyCZhj05fJSvzfRUi5MGaZqpi4rO', 0),
(3, 'Quản Trị Viên V-Phone', 'admin@vphone.vn', '18006868', 'Trụ sở V-Phone Store', 'yKnbq0ImzZjQS5jaGPJRO.CnkRyCZhj05fJSvzfRUi5MGaZqpi4rO', 1);

-- NẠP DANH SÁCH 31 SẢN PHẨM ĐIỆN THOẠI (GỒM CẢ MÁY MỚI 2026 & MÁY CŨ 99%)
INSERT INTO products (id, brand_id, name, price, sale_price, image, screen, cpu, ram, rom, battery, quantity, is_featured, is_used, condition_desc) VALUES
-- Dàn Flagship 2026
(1, 1, 'iPhone 18 Pro Max (Titan Space)', 42990000, 39990000, 'assets/images/products/iphone-18-promax.png', '6.9" 144Hz', 'Apple A20 Pro Bionic (2nm)', '16 GB', '256 GB', '5100 mAh', 25, 1, 0, 'Mới 100%'),
(2, 1, 'iPhone 18 Ultra (Tràn Viền)', 49990000, 46990000, 'assets/images/products/iphone-18-ultra.png', '7.0" 120Hz', 'Apple M4 Extreme Mobile', '16 GB', '256 GB', '5400 mAh', 18, 1, 0, 'Mới 100%'),
(3, 1, 'iPhone 18 Duo (Màn Gập Apple)', 54990000, 49990000, 'assets/images/products/iphone-18-duo.png', '7.9" Gập', 'Apple A20 Pro Bionic', '16 GB', '256 GB', '5200 mAh', 12, 1, 0, 'Mới 100%'),
(4, 2, 'Galaxy Z Tri-Fold (Gập 3 Màn)', 62990000, 56990000, 'assets/images/products/samsung-tri-fold.png', '10.2" Gập 3', 'Snapdragon 8 Gen 5 AI', '24 GB', '256 GB', '6200 mAh', 8, 1, 0, 'Mới 100%'),
(5, 2, 'Galaxy S26 Ultra (Titan AI)', 38990000, 34990000, 'assets/images/products/samsung-s26-ultra.png', '6.8" 165Hz', 'Snapdragon 8 Gen 5 for Galaxy', '16 GB', '256 GB', '5800 mAh', 30, 1, 0, 'Mới 100%'),
(6, 5, 'Huawei Mate XT (Gập 3 Đầu Tiên)', 79990000, 74990000, 'assets/images/products/huawei-mate-xt.png', '10.2" Gập 3', 'Kirin 9010 5G Flagship', '16 GB', '256 GB', '5600 mAh', 5, 1, 0, 'Mới 100%'),
(7, 3, 'Xiaomi 16 Ultra (Leica Edition)', 35990000, 32490000, 'assets/images/products/xiaomi-16-ultra.png', '6.78" 2K+', 'Snapdragon 8 Gen 5', '24 GB', '256 GB', '5800 mAh', 15, 1, 0, 'Mới 100%'),
(8, 4, 'OPPO Find N5 Duo (Hologram)', 46990000, 42990000, 'assets/images/products/oppo-find-n5.png', '7.9" Gập', 'Dimensity 9500 Ultra', '16 GB', '256 GB', '5250 mAh', 10, 1, 0, 'Mới 100%'),

-- Dàn Máy Mới Đời Trước Bán Chạy
(9, 1, 'iPhone 17 Pro Max 256GB', 36990000, 32990000, 'assets/images/products/iphone-17-promax.png', '6.9" 120Hz', 'Apple A19 Pro Bionic', '12 GB', '256 GB', '4850 mAh', 20, 0, 0, 'Mới 100%'),
(10, 1, 'iPhone 16 Pro Max (Titan Sa Mạc)', 34990000, 29990000, 'assets/images/products/iphone-16-promax.png', '6.9" 120Hz', 'Apple A18 Pro', '8 GB', '256 GB', '4685 mAh', 35, 0, 0, 'Mới 100%'),
(11, 1, 'iPhone 16 128GB (Hồng Pastel)', 22990000, 21990000, 'assets/images/products/iphone-16.png', '6.1" OLED', 'Apple A18', '8 GB', '128 GB', '3561 mAh', 25, 0, 0, 'Mới 100%'),
(12, 1, 'iPhone 15 Pro Max (Titan Xanh)', 31990000, 27490000, 'assets/images/products/iphone-15-promax.png', '6.7" 120Hz', 'Apple A17 Pro', '8 GB', '256 GB', '4422 mAh', 22, 0, 0, 'Mới 100%'),
(13, 1, 'iPhone 15 128GB (Xanh Dương)', 21990000, 18490000, 'assets/images/products/iphone-15.png', '6.1" OLED', 'Apple A16 Bionic', '6 GB', '128 GB', '3349 mAh', 19, 0, 0, 'Mới 100%'),
(14, 1, 'iPhone 14 Pro Max (Tím Deep)', 27990000, 23990000, 'assets/images/products/iphone-14-promax.png', '6.7" 120Hz', 'Apple A16 Bionic', '6 GB', '128 GB', '4323 mAh', 14, 0, 0, 'Mới 100%'),
(15, 1, 'iPhone 13 128GB (Trắng Ánh Sao)', 16990000, 12990000, 'assets/images/products/iphone-13.png', '6.1" OLED', 'Apple A15 Bionic', '4 GB', '128 GB', '3240 mAh', 30, 0, 0, 'Mới 100%'),
(16, 1, 'iPhone 11 64GB (Đen Classic)', 11990000, 7990000, 'assets/images/products/iphone-11.png', '6.1" LCD', 'Apple A13 Bionic', '4 GB', '64 GB', '3110 mAh', 40, 0, 0, 'Mới 100%'),
(17, 2, 'Galaxy S24 Ultra (Xám Titan)', 33990000, 25990000, 'assets/images/products/samsung-s24-ultra.png', '6.8" 120Hz', 'Snapdragon 8 Gen 3', '12 GB', '256 GB', '5000 mAh', 28, 0, 0, 'Mới 100%'),
(18, 2, 'Galaxy Z Fold6 5G (Xám)', 43990000, 37990000, 'assets/images/products/samsung-z-fold6.png', '7.6" Gập', 'Snapdragon 8 Gen 3', '12 GB', '256 GB', '4400 mAh', 16, 0, 0, 'Mới 100%'),
(19, 2, 'Galaxy S23 Ultra 256GB', 26990000, 19990000, 'assets/images/products/samsung-s23-ultra.png', '6.8" 120Hz', 'Snapdragon 8 Gen 2', '8 GB', '256 GB', '5000 mAh', 12, 0, 0, 'Mới 100%'),
(20, 2, 'Galaxy A55 5G 128GB (Xanh)', 9990000, 8490000, 'assets/images/products/samsung-a55.png', '6.6" 120Hz', 'Exynos 1480', '8 GB', '128 GB', '5000 mAh', 45, 0, 0, 'Mới 100%'),
(21, 3, 'Xiaomi 14 Ultra (Đen Da Thuần)', 32990000, 27990000, 'assets/images/products/xiaomi-14-ultra.png', '6.73" 2K+', 'Snapdragon 8 Gen 3', '16 GB', '512 GB', '5000 mAh', 11, 0, 0, 'Mới 100%'),
(22, 4, 'OPPO Find N3 (Vàng Hoàng Kim)', 44990000, 38990000, 'assets/images/products/oppo-find-n3.png', '7.82" Gập', 'Snapdragon 8 Gen 2', '16 GB', '512 GB', '4805 mAh', 9, 0, 0, 'Mới 100%'),

-- Dàn Kho Máy Cũ Like New 99% (is_used = 1)
(23, 1, '[Cũ 99%] iPhone 15 Pro Max 256GB (Titan Tự Nhiên)', 28990000, 21990000, 'assets/images/products/iphone-15-promax.png', '6.7" 120Hz', 'Apple A17 Pro', '8 GB', '256 GB', '4422 mAh', 7, 0, 1, 'Đẹp 99% - Pin 96%'),
(24, 1, '[Cũ 99%] iPhone 14 Pro Max 128GB (Tím Deep Purple)', 24990000, 17990000, 'assets/images/products/iphone-14-promax.png', '6.7" 120Hz', 'Apple A16 Bionic', '6 GB', '128 GB', '4323 mAh', 5, 0, 1, 'Đẹp 99% - Pin 93%'),
(25, 1, '[Cũ 99%] iPhone 13 128GB (Trắng Ánh Sao)', 14990000, 10490000, 'assets/images/products/iphone-13.png', '6.1" OLED', 'Apple A15 Bionic', '4 GB', '128 GB', '3240 mAh', 8, 0, 1, 'Đẹp 98% - Pin 90%'),
(26, 1, '[Cũ 99%] iPhone 11 64GB (Đen Nguyên Bản)', 8990000, 5490000, 'assets/images/products/iphone-11.png', '6.1" LCD', 'Apple A13 Bionic', '4 GB', '64 GB', '3110 mAh', 15, 0, 1, 'Đẹp 98% - Pin 89% (Quốc Tế)'),
(27, 2, '[Cũ 99%] Samsung Galaxy S23 Ultra 256GB', 22990000, 15990000, 'assets/images/products/samsung-s23-ultra.png', '6.8" 120Hz', 'Snapdragon 8 Gen 2', '8 GB', '256 GB', '5000 mAh', 4, 0, 1, 'Đẹp Like New 99%'),
(28, 2, '[Cũ 99%] Samsung Galaxy Z Fold5 5G 256GB', 32990000, 21990000, 'assets/images/products/samsung-z-fold6.png', '7.6" Gập', 'Snapdragon 8 Gen 2', '12 GB', '256 GB', '4400 mAh', 3, 0, 1, 'Bản gập nguyên zin 99%'),
(29, 2, '[Cũ 99%] Samsung Galaxy A55 5G 128GB', 8490000, 5990000, 'assets/images/products/samsung-a55.png', '6.6" 120Hz', 'Exynos 1480', '8 GB', '128 GB', '5000 mAh', 9, 0, 1, 'Còn bảo hành hãng 6T'),
(30, 3, '[Cũ 99%] Xiaomi 14 Ultra 5G 512GB (Đen Da)', 27990000, 19990000, 'assets/images/products/xiaomi-14-ultra.png', '6.73" 2K+', 'Snapdragon 8 Gen 3', '16 GB', '512 GB', '5000 mAh', 2, 0, 1, 'Fullbox đầy đủ phụ kiện'),
(31, 4, '[Cũ 99%] OPPO Find N3 5G 512GB (Vàng Gold)', 38990000, 26990000, 'assets/images/products/oppo-find-n3.png', '7.82" Gập', 'Snapdragon 8 Gen 2', '16 GB', '512 GB', '4805 mAh', 2, 0, 1, 'Mới 99% - Đẹp keng');

-- NẠP 3 ĐƠN HÀNG MẪU ĐỂ ADMIN CÓ SẴN DOANH THU & ĐƠN HÀNG ĐỂ BÁO CÁO THẦY
INSERT INTO orders (id, user_id, fullname, phone, address, note, total_money, payment_method, status, created_at) VALUES
(1, 1, 'Võ Minh Hiếu', '0901234567', 'Số 12 Lê Lợi, Phường Bến Nghé, Quận 1, TP.HCM', 'Giao trong giờ hành chính', 29990000, 'COD', 'Đã giao thành công', '2026-09-22 09:30:00'),
(2, 2, 'Huỳnh Bá Lực', '0909888999', 'Số 45 Nguyễn Thị Thập, Tân Phú, Quận 7, TP.HCM', 'Gọi trước khi giao 15 phút', 56990000, 'COD', 'Chờ xử lý', '2026-09-24 14:15:00'),
(3, 1, 'Võ Minh Hiếu', '0901234567', 'Số 12 Lê Lợi, Phường Bến Nghé, Quận 1, TP.HCM', 'Kiểm tra máy kỹ trước khi giao', 5490000, 'COD', 'Đang giao hàng', '2026-09-24 16:45:00');

-- NẠP CHI TIẾT ĐƠN HÀNG
INSERT INTO order_details (order_id, product_id, product_name, price, quantity, total_price) VALUES
(1, 10, 'iPhone 16 Pro Max (Titan Sa Mạc)', 29990000, 1, 29990000),
(2, 4, 'Galaxy Z Tri-Fold (Gập 3 Màn)', 56990000, 1, 56990000),
(3, 26, '[Cũ 99%] iPhone 11 64GB (Đen Nguyên Bản)', 5490000, 1, 5490000);
