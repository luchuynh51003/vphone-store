-- =========================================================================
-- CƠ SỞ DỮ LIỆU ĐỒ ÁN V-PHONE STORE (ĐẦY ĐỦ 100+ MÁY & MÀU SẮC THỰC TẾ)
-- =========================================================================

CREATE DATABASE IF NOT EXISTS db_phone_store CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_phone_store;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS order_details;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS brands;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE brands (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(50) NOT NULL);

CREATE TABLE users (id INT AUTO_INCREMENT PRIMARY KEY, fullname VARCHAR(100) NOT NULL, email VARCHAR(100) UNIQUE NOT NULL, phone VARCHAR(20), address VARCHAR(255), password VARCHAR(255) NOT NULL, role TINYINT DEFAULT 0, cart_data LONGTEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);

CREATE TABLE products (id INT AUTO_INCREMENT PRIMARY KEY, brand_id INT, name VARCHAR(150) NOT NULL, price DECIMAL(12,0) NOT NULL, sale_price DECIMAL(12,0) DEFAULT 0, image VARCHAR(255) NOT NULL, screen VARCHAR(60), cpu VARCHAR(100), ram VARCHAR(20), rom VARCHAR(20), battery VARCHAR(50), quantity INT DEFAULT 20, is_featured TINYINT(1) DEFAULT 0, is_used TINYINT(1) DEFAULT 0, condition_desc VARCHAR(100) DEFAULT 'Mới 100%', colors VARCHAR(255) DEFAULT 'Đen, Trắng', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE SET NULL);

CREATE TABLE orders (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, fullname VARCHAR(100) NOT NULL, phone VARCHAR(20) NOT NULL, address VARCHAR(255) NOT NULL, note TEXT, total_money DECIMAL(12,0) NOT NULL, payment_method VARCHAR(50) DEFAULT 'COD', status VARCHAR(50) DEFAULT 'Chờ xử lý', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL);

CREATE TABLE order_details (id INT AUTO_INCREMENT PRIMARY KEY, order_id INT, product_id INT, product_name VARCHAR(150), price DECIMAL(12,0), quantity INT, total_price DECIMAL(12,0), FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE, FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL);

INSERT INTO brands (id, name) VALUES (1, 'Apple'), (2, 'Samsung'), (3, 'Xiaomi'), (4, 'OPPO'), (5, 'Vivo'), (6, 'Google Pixel'), (7, 'ASUS ROG'), (8, 'Sony'), (9, 'Huawei & Honor');

INSERT INTO users (id, fullname, email, phone, address, password, role) VALUES (1, 'Võ Minh Hiếu', 'hieu@vphone.vn', '0901234567', 'Quận 1, TP.HCM', '$2y$10$kZ76ftCCfjpFJkWv8aOyFuYo2fmvz5loyyGZyQzqRvajascv78iKu', 0), (2, 'Huỳnh Bá Lực', 'luc@vphone.vn', '0909888999', 'Quận 7, TP.HCM', '$2y$10$kZ76ftCCfjpFJkWv8aOyFuYo2fmvz5loyyGZyQzqRvajascv78iKu', 0), (3, 'Quản Trị Viên V-Phone', 'admin@vphone.vn', '18006868', 'Trụ sở V-Phone', '$2y$10$kZ76ftCCfjpFJkWv8aOyFuYo2fmvz5loyyGZyQzqRvajascv78iKu', 1);

