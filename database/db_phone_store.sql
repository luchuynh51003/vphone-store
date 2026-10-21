-- =========================================================================
-- CƠ SỞ DỮ LIỆU ĐỒ ÁN V-PHONE STORE (ĐÃ SAO LƯU TỰ ĐỘNG)
-- Thời gian sao lưu: 2026-10-21 23:32:00
-- =========================================================================

CREATE DATABASE IF NOT EXISTS `db_phone_store` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `db_phone_store`;

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+07:00";

DROP TABLE IF EXISTS `articles`;
DROP TABLE IF EXISTS `brands`;
DROP TABLE IF EXISTS `callback_requests`;
DROP TABLE IF EXISTS `order_details`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `password_resets`;
DROP TABLE IF EXISTS `product_reviews`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `user_addresses`;
DROP TABLE IF EXISTS `user_notifications`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `vouchers`;
DROP TABLE IF EXISTS `warranty_claims`;

-- --------------------------------------------------------
-- Cấu trúc bảng `articles`
-- --------------------------------------------------------
CREATE TABLE `articles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `badge` varchar(80) NOT NULL DEFAULT 'TIN MỚI',
  `badge_class` varchar(40) NOT NULL DEFAULT 'bg-primary',
  `article_date` varchar(30) NOT NULL,
  `read_time` varchar(30) NOT NULL DEFAULT '5 phút đọc',
  `image` varchar(255) NOT NULL,
  `summary` text NOT NULL,
  `content` longtext NOT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dữ liệu bảng `articles` (6 dòng)
INSERT INTO `articles` (`id`, `title`, `badge`, `badge_class`, `article_date`, `read_time`, `image`, `summary`, `content`, `is_published`, `created_at`, `updated_at`) VALUES
(1, 'Tổng hợp tin đồn iPhone 18 Pro Max: Chip A20 Pro 2nm và màn hình Dynamic Island thu nhỏ', 'TIN RÒ RỈ 2026', 'bg-danger', '24/09/2026', '5 phút đọc', 'assets/images/products/iphone-18-promax.png', 'Apple được cho là sẽ ra mắt thế hệ iPhone 18 với tiến trình 2nm siêu tiết kiệm pin, khung viền titan siêu nhẹ và camera tiềm vọng zoom quang học 10x hoàn toàn mới.', 'Theo các nguồn tin uy tín từ chuỗi cung ứng, dòng iPhone 18 Pro Max năm 2026 sẽ đánh dấu bước nhảy vọt lớn nhất của Apple. Máy sẽ sử dụng vi xử lý Apple A20 Pro xây dựng trên tiến trình 2nm của TSMC, mang lại hiệu năng đồ họa tăng 35% trong khi tiết kiệm điện năng tới 40%.\n\nNgoài ra, hệ thống camera tele tiềm vọng sẽ nâng cấp lên cảm biến 48MP hỗ trợ zoom quang học 10x và zoom kỹ thuật số lên tới 100x. Thiết kế mặt trước sẽ tiếp tục tối ưu phần viền màn hình chỉ còn 1.0mm, mang lại trải nghiệm thị giác gần như tràn viền tuyệt đối.', 1, '2026-10-08 09:52:37', '2026-10-08 09:52:37'),
(2, 'Đánh giá chi tiết Samsung Galaxy Z Tri-Fold: Kỷ nguyên điện thoại gập ba đã đến!', 'ĐÁNH GIÁ HOT', 'bg-primary', '23/09/2026', '7 phút đọc', 'assets/images/products/samsung-tri-fold.png', 'Chiếc smartphone gập 3 màn hình đầu tiên của Samsung cho phép mở rộng không gian hiển thị lên đến 10.2 inch, biến chiếc điện thoại thành máy tính bảng trong chớp mắt.', 'Galaxy Z Tri-Fold là câu trả lời đanh thép của Samsung dành cho thị trường di động toàn cầu. Với cơ chế gập kép hình chữ Z độc đáo, người dùng có thể sử dụng máy ở dạng màn hình ngoài 6.4 inch như điện thoại thông thường, hoặc mở ra toàn bộ thành màn hình 10.2 inch Dynamic AMOLED 3X.\n\nHệ thống bản lề FlexHinge thế hệ mới được gia cố bằng chất liệu Armor Aluminum siêu bền, cam kết độ bền trên 300.000 lần gập mở. Giao diện One UI 8.0 được tinh chỉnh đặc biệt cho khả năng chạy đa nhiệm cùng lúc 4 ứng dụng trên màn hình lớn.', 1, '2026-10-08 09:52:37', '2026-10-08 09:52:37'),
(3, 'Bí quyết chọn mua iPhone cũ 99%: Cách kiểm tra pin, màn hình và camera tránh bị lừa', 'MẸO CÔNG NGHỆ', 'bg-success', '22/09/2026', '4 phút đọc', 'assets/images/products/iphone-15-promax.png', 'Hướng dẫn chi tiết từng bước kiểm tra từ vỏ máy, màn hình TrueTone, 3uTools cho đến dung lượng pin thực tế khi đi mua iPhone cũ đã qua sử dụng.', 'Khi mua iPhone cũ 99%, điều quan trọng nhất là kiểm tra xem máy đã bị thay màn hình hoặc ép kính hay chưa. Bạn hãy vào Cài đặt -> Màn hình & Độ sáng kiểm tra tính năng TrueTone. Sau đó, vuốt thanh tăng giảm độ sáng để xem cảm ứng có mượt không.\n\nTiếp theo, hãy kiểm tra số lần sạc và độ chai pin trong Cài đặt -> Pin. Đối với máy cũ 99%, pin chuẩn thường dao động từ 88% đến 96%. Tại V-Phone, mọi máy cũ đều được kỹ thuật viên kiểm tra 32 bước nghiêm ngặt và cam kết zin 100% kèm bảo hành 6 tháng 1 đổi 1.', 1, '2026-10-08 09:52:37', '2026-10-08 09:52:37'),
(4, 'So sánh Galaxy S26 Ultra và iPhone 17 Pro Max: Cuộc chiến của các vì sao', 'SO SÁNH', 'bg-info text-dark', '20/09/2026', '6 phút đọc', 'assets/images/products/samsung-s26-ultra.png', 'Nên chọn Galaxy S26 Ultra với bút S-Pen quyền năng và camera 200MP, hay chọn iPhone 17 Pro Max với sự mượt mà và hệ sinh thái iOS bền bỉ?', 'Cả Galaxy S26 Ultra và iPhone 17 Pro Max đều là những siêu phẩm đứng đầu thế giới hiện nay. Trong khi Samsung chiếm ưu thế hoàn toàn về camera siêu zoom và các tính năng Galaxy AI phục vụ công việc hàng ngày, thì Apple lại ghi điểm ở khả năng quay video ProRes đỉnh cao và giữ giá tốt hơn sau nhiều năm sử dụng.\n\nNếu bạn là người thích vọc vạch công nghệ và cần ghi chú nhanh bằng bút thì S26 Ultra là lựa chọn số 1. Còn nếu bạn cần một thiết bị ổn định tuyệt đối, chơi game mát và đồng bộ tốt với iPad/MacBook thì iPhone 17 Pro Max là chân ái.', 1, '2026-10-08 09:52:37', '2026-10-08 09:52:37'),
(5, 'Xiaomi 16 Ultra Leica Edition: Đưa máy ảnh chuyên nghiệp vào trong túi quần bạn', 'TIN MỚI', 'bg-warning text-dark', '18/09/2026', '4 phút đọc', 'assets/images/products/xiaomi-16-ultra.png', 'Cụm camera hợp tác cùng huyền thoại Leica với cảm biến 1 inch thế hệ mới và lớp phủ chống lóa T*, biến Xiaomi 16 Ultra thành quái vật nhiếp ảnh.', 'Xiaomi tiếp tục chứng minh vị thế dẫn đầu về nhiếp ảnh di động khi trang bị cho 16 Ultra cảm biến chính Sony Lytia 1 inch cùng ống kính quang học Leica Summilux. Màu ảnh chụp ra có độ sâu trường ảnh tự nhiên, tương phản cao và tái tạo màu da cực kỳ chân thực, gần như xóa nhòa ranh giới giữa điện thoại và máy ảnh DSLR.', 1, '2026-10-08 09:52:37', '2026-10-08 09:52:37'),
(6, 'Top 4 điện thoại cũ giá dưới 10 triệu đáng mua nhất cho học sinh, sinh viên 2026', 'TƯ VẤN MUA', 'bg-secondary', '15/09/2026', '5 phút đọc', 'assets/images/products/iphone-11.png', 'Với ngân sách dưới 10 triệu, bạn hoàn toàn có thể sở hữu iPhone 11 cũ giá 5tr4, iPhone 13 cũ giá 10tr hay Galaxy A55 5G nguyên zin bảo hành dài hạn.', '1. iPhone 11 64GB cũ (5.490.000 VNĐ): Dù đã ra mắt lâu nhưng chip A13 Bionic vẫn lướt TikTok, Facebook và chơi Liên Quân cực mượt.\n\n2. iPhone 13 128GB cũ (10.490.000 VNĐ): Thiết kế vuông vức thời thượng, màn hình tai thỏ nhỏ gọn và thời lượng pin cực trâu.\n\n3. Samsung Galaxy A55 5G cũ (5.990.000 VNĐ): Màn hình 120Hz siêu mượt, hỗ trợ 5G và pin 5000 mAh dùng thoải mái 2 ngày.', 1, '2026-10-08 09:52:37', '2026-10-17 17:28:17');

-- --------------------------------------------------------
-- Cấu trúc bảng `brands`
-- --------------------------------------------------------
CREATE TABLE `brands` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dữ liệu bảng `brands` (9 dòng)
INSERT INTO `brands` (`id`, `name`) VALUES
(1, 'Apple'),
(2, 'Samsung'),
(3, 'Xiaomi'),
(4, 'OPPO'),
(5, 'Vivo'),
(6, 'Google Pixel'),
(7, 'ASUS ROG'),
(8, 'Sony'),
(9, 'Huawei & Honor');

-- --------------------------------------------------------
-- Cấu trúc bảng `callback_requests`
-- --------------------------------------------------------
CREATE TABLE `callback_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `fullname` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `topic` varchar(100) DEFAULT 'Tư vấn mua máy',
  `department` varchar(50) DEFAULT 'cskh',
  `note` text DEFAULT NULL,
  `reply_message` text DEFAULT NULL,
  `replied_by` int(11) DEFAULT NULL,
  `replied_by_name` varchar(100) DEFAULT NULL,
  `replied_by_title` varchar(100) DEFAULT NULL,
  `replied_at` datetime DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `phone` (`phone`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dữ liệu bảng `callback_requests` (3 dòng)
INSERT INTO `callback_requests` (`id`, `user_id`, `fullname`, `phone`, `email`, `topic`, `department`, `note`, `reply_message`, `replied_by`, `replied_by_name`, `replied_by_title`, `replied_at`, `status`, `created_at`) VALUES
(1, NULL, 'Nguyen Van A', '0987654321', NULL, 'Tu van iPhone 17', 'cskh', 'Goi buoi chieu', NULL, NULL, NULL, NULL, NULL, 'pending', '2026-10-16 15:43:50'),
(3, NULL, 'Lực Huỳnh', '0888443655', 'beluc5102003@gmail.com', 'Hỗ trợ trả góp 0% / Lên đời thu cũ đổi mới', 'sales', 'hihi', 'cc', 3, 'Quản Trị Viên V-Phone', 'Quản Trị Tối Cao', '2026-10-17 13:12:39', 'replied', '2026-10-17 13:12:02'),
(4, NULL, 'Tester', '0912345678', NULL, 'Tu van iPhone', 'sales', '', NULL, NULL, NULL, NULL, NULL, 'pending', '2026-10-19 11:10:16');

-- --------------------------------------------------------
-- Cấu trúc bảng `order_details`
-- --------------------------------------------------------
CREATE TABLE `order_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `product_name` varchar(150) DEFAULT NULL,
  `price` decimal(12,0) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `total_price` decimal(12,0) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `order_details_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_details_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dữ liệu bảng `order_details` (9 dòng)
INSERT INTO `order_details` (`id`, `order_id`, `product_id`, `product_name`, `price`, `quantity`, `total_price`) VALUES
(1, 1, 11, 'iPhone 17 Pro (128 GB - Màu: Titan Tự Nhiên)', '28490000', 1, '28490000'),
(2, 2, 3, 'iPhone Duo (Màn Gập Apple) (256 GB - Màu: Trắng Ánh Sao)', '64990000', 1, '64990000'),
(3, 3, 58, '[Cũ 99%] Samsung Galaxy Z Fold5 5G (512 GB - Màu: Đen Phantom)', '25990000', 1, '25990000'),
(4, 7, 3, 'iPhone Duo (Màn Gập Apple) (256 GB - Màu: Trắng Ánh Sao)', '64990000', 1, '64990000'),
(5, 8, 3, 'iPhone Duo (Màn Gập Apple) (256 GB - Màu: Trắng Ánh Sao)', '64990000', 1, '64990000'),
(7, 11, 7, 'Huawei Mate XT (Gập 3 Màn) (128 GB - Màu: Đỏ Ruihong)', '74990000', 1, '74990000'),
(8, 12, 31, 'Galaxy Z Flip6 5G (128 GB - Màu: Xám Metal)', '24490000', 1, '24490000'),
(9, 13, 5, 'Galaxy Z Tri-Fold (Gập 3 Màn) (256 GB - Màu: Đen Phantom)', '56990000', 1, '56990000'),
(10, 14, 3, 'iPhone Duo (Màn Gập Apple) (256 GB - Màu: Trắng Ánh Sao)', '64990000', 1, '64990000');

-- --------------------------------------------------------
-- Cấu trúc bảng `orders`
-- --------------------------------------------------------
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `fullname` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` varchar(255) NOT NULL,
  `note` text DEFAULT NULL,
  `total_money` decimal(12,0) NOT NULL,
  `payment_method` varchar(50) DEFAULT 'COD',
  `payment_status` varchar(50) NOT NULL DEFAULT 'Chưa thanh toán',
  `transaction_code` varchar(100) DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Chờ xử lý',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dữ liệu bảng `orders` (9 dòng)
INSERT INTO `orders` (`id`, `user_id`, `fullname`, `phone`, `address`, `note`, `total_money`, `payment_method`, `payment_status`, `transaction_code`, `paid_at`, `status`, `created_at`) VALUES
(1, 2, 'Huỳnh Bá Lực', '0909888999', 'Quận 7, TP.HCM', '', '28490000', 'Chuyển khoản QR VietQR', 'Chưa thanh toán', NULL, NULL, 'Đã giao thành công', '2026-10-08 19:06:22'),
(2, 2, 'Huỳnh Bá Lực', '0909888999', 'Quận 7, TP.HCM', '| Voucher: GIAM100K (giảm 100.000 VNĐ)', '64890000', 'COD', 'Chưa thanh toán', NULL, NULL, 'Đã hủy', '2026-10-09 12:00:34'),
(3, 3, 'Quản Trị Viên V-Phone', '18006868', 'Trụ sở V-Phone', '[VietQR Auto-Pay: Đã thanh toán tự động qua mã QR lúc 16/10/2026 03:00:51 - Mã GD: VQR20261016030051869] (Admin xác nhận trực tiếp)', '25990000', 'Chuyển khoản QR VietQR', 'Đã thanh toán', 'VQR20261016030051869', '2026-10-16 03:00:51', 'Đã giao thành công', '2026-10-15 17:21:29'),
(7, 2, 'Huỳnh Bá Lực', '0909888999', 'Quận 7, TP.HCM', '[VietQR Auto-Pay: Đã thanh toán tự động qua mã QR lúc 15/10/2026 12:36:37 - Mã GD: SIM-VQR-123637-3527] (Giả lập khách chuyển khoản thành công)', '64990000', 'Chuyển khoản QR VietQR', 'Đã thanh toán', 'SIM-VQR-123637-3527', '2026-10-15 12:36:37', 'Đang xử lý', '2026-10-15 17:36:30'),
(8, 2, 'Huỳnh Bá Lực', '0909888999', 'Quận 7, TP.HCM', '[VietQR Auto-Pay: Đã thanh toán tự động qua mã QR lúc 15/10/2026 12:38:00 - Mã GD: SIM-VQR-123800-9330] (Giả lập khách chuyển khoản thành công)', '64990000', 'Chuyển khoản QR VietQR', 'Đã thanh toán', 'SIM-VQR-123800-9330', '2026-10-15 12:38:00', 'Đang xử lý', '2026-10-15 17:36:54'),
(11, 1, 'Võ Minh Hiếu', '0901234567', 'Quận 1, TP.HCM', '[MoMo Sandbox: Đã thanh toán qua MoMo lúc 15/10/2026 12:58:58 - Mã GD: MOMO-MOMO_1258588195] [MoMo Sandbox: Đã thanh toán qua MoMo lúc 15/10/2026 12:58:58 - Mã GD: MOMO-MOMO_1258588195]', '74990000', 'Ví điện tử MoMo', 'Đã thanh toán', 'MOMO-MOMO_1258588195', '2026-10-15 12:58:58', 'Đang xử lý', '2026-10-15 17:48:20'),
(12, 1, 'Võ Minh Hiếu', '0901234567', 'Quận 1, TP.HCM', '[VietQR Auto-Pay: Đã thanh toán tự động qua mã QR lúc 15/10/2026 13:03:36 - Mã GD: SIM-VQR-130336-4178] (Giả lập khách chuyển khoản thành công)', '24490000', 'Chuyển khoản QR VietQR', 'Đã thanh toán', 'SIM-VQR-130336-4178', '2026-10-15 13:03:36', 'Đã giao thành công', '2026-10-15 17:59:21'),
(13, 2, 'Huỳnh Bá Lực', '0909888999', 'Quận 7, TP.HCM', '[VietQR Auto-Pay: Đã thanh toán tự động qua mã QR lúc 15/10/2026 13:36:06 - Mã GD: SIM-VQR-133606-7104] (Giả lập khách chuyển khoản thành công)', '56990000', 'Chuyển khoản QR VietQR', 'Đã thanh toán', 'SIM-VQR-133606-7104', '2026-10-15 13:36:06', 'Đang xử lý', '2026-10-15 18:35:57'),
(14, 2, 'Huỳnh Bá Lực', '0909888999', 'Quận 7, TP.HCM', '[VietQR Auto-Pay: Đã thanh toán tự động qua mã QR lúc 15/10/2026 14:04:49 - Mã GD: SIM-VQR-140449-9019] (Giả lập khách chuyển khoản thành công)', '64990000', 'Chuyển khoản QR VietQR', 'Đã thanh toán', 'SIM-VQR-140449-9019', '2026-10-15 14:04:49', 'Đang xử lý', '2026-10-15 19:04:36');

-- --------------------------------------------------------
-- Cấu trúc bảng `password_resets`
-- --------------------------------------------------------
CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `token` varchar(64) NOT NULL,
  `otp` varchar(10) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `email` (`email`),
  KEY `token` (`token`),
  KEY `otp` (`otp`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dữ liệu bảng `password_resets` (1 dòng)
INSERT INTO `password_resets` (`id`, `email`, `token`, `otp`, `expires_at`, `created_at`) VALUES
(2, 'luc@vphone.vn', 'cb2b1b851fa43e1641e0d91b7bb433e9537cefbcec5093cc245a1de7352461ab', '458452', '2026-10-21 23:38:53', '2026-10-21 23:23:53');

-- --------------------------------------------------------
-- Cấu trúc bảng `product_reviews`
-- --------------------------------------------------------
CREATE TABLE `product_reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `rating` tinyint(4) NOT NULL DEFAULT 5,
  `comment` text NOT NULL,
  `is_buyer` tinyint(1) DEFAULT 0,
  `status` tinyint(1) DEFAULT 1,
  `admin_reply` text DEFAULT NULL,
  `admin_reply_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_rating` (`rating`),
  KEY `idx_status` (`status`),
  CONSTRAINT `product_reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dữ liệu bảng `product_reviews` (13 dòng)
INSERT INTO `product_reviews` (`id`, `product_id`, `user_id`, `fullname`, `email`, `phone`, `rating`, `comment`, `is_buyer`, `status`, `admin_reply`, `admin_reply_at`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Võ Minh Hiếu', 'hieu@vphone.vn', '0912345678', 5, 'Máy quá đỉnh! Khung titan nhôm cầm đầm tay, vi xử lý A20 Pro chạy cực mát. Màn hình Promotion siêu mượt, nhận hàng sau 1h tại TP.HCM. Rất hài lòng với V-Phone!', 1, 1, 'Dạ V-Phone cảm ơn anh Hiếu rất nhiều đã luôn tin tưởng và ủng hộ siêu phẩm tại V-Phone. Chúc anh có những trải nghiệm tuyệt vời cùng máy ạ!', '2026-10-14 12:10:39', '2026-10-13 12:10:39', '2026-10-15 17:10:39'),
(2, 1, NULL, 'Trần Hải Đăng', 'dang.tran@gmail.com', '0933123456', 5, 'Camera chụp đêm sắc nét không bị bệt màu, zoom quang học 10x chi tiết rõ mồn một. Shop tư vấn nhiệt tình, đóng gói cẩn thận 2 lớp chống sốc.', 1, 1, 'V-Phone cảm ơn anh Đăng đã đánh giá 5 sao. Bộ quà tặng ốp lưng và dán cường lực VIP đã kích hoạt bảo hành điện tử chính hãng 12 tháng rồi anh nhé!', '2026-10-13 12:10:39', '2026-10-12 12:10:39', '2026-10-15 17:10:39'),
(3, 1, NULL, 'Nguyễn Bích Phương', 'phuong.bich@gmail.com', '0988776655', 5, 'Màu sắc bên ngoài đẹp hơn trong hình nhiều, máy nguyên seal nguyên kiện tem mác đầy đủ. Đổi từ 13 Pro Max lên thấy pin trâu gấp rưỡi.', 1, 1, NULL, NULL, '2026-10-11 12:10:39', '2026-10-15 17:10:39'),
(4, 3, 2, 'Huỳnh Bá Lực', 'luc@vphone.vn', '0909888999', 5, 'Bản gập đầu tiên của Apple thực sự là bước nhảy vọt, bản lề titan gập rất chắc chắn và gần như không thấy nếp nhăn. Cảm giác cầm sang trọng bậc nhất!', 1, 1, 'Cảm ơn anh Lực đã là một trong những khách hàng đầu tiên sở hữu iPhone Duo tại Việt Nam! V-Phone có kỹ thuật viên hỗ trợ tận nơi 24/7 ạ.', '2026-10-14 12:10:39', '2026-10-13 12:10:39', '2026-10-15 17:10:39'),
(5, 3, NULL, 'Lê Quốc Hưng', 'hung.le@fpt.vn', '0977223344', 5, 'Màn hình OLED mở rộng đa nhiệm cực tốt, đọc tài liệu và xem biểu đồ kinh doanh mượt mà. Đáng đồng tiền bát gạo.', 0, 1, NULL, NULL, '2026-10-10 12:10:39', '2026-10-15 17:10:39'),
(6, 5, NULL, 'Hoàng Minh Tuấn', 'tuan.hm@gmail.com', '0918112233', 5, 'Gập 3 màn hình công nghệ tương lai! Khi mở full ra như một chiếc tablet 10.2 inch, làm việc và vẽ đồ họa cùng S-Pen rất tiện lợi. Ship giao hỏa tốc 2 tiếng đúng cam kết.', 1, 1, 'Cảm ơn anh Tuấn! Dòng máy Tri-Fold được tặng kèm gói bảo hiểm rơi vỡ toàn diện 1 năm độc quyền từ V-Phone anh nhé!', '2026-10-13 12:10:39', '2026-10-12 12:10:39', '2026-10-15 17:10:39'),
(7, 6, NULL, 'Phạm Gia Bảo', 'giabao.pham@gmail.com', '0903445566', 5, 'Chip Snapdragon 8 Gen 5 chiến game mượt khỏi bàn, AI dịch thuật trực tiếp trong cuộc gọi siêu tiện. Màn hình phẳng viền siêu mỏng, bút S-Pen viết rất êm tay.', 1, 1, 'Dạ V-Phone cảm ơn anh Bảo đã tin tưởng lựa chọn Galaxy S26 Ultra chính hãng tại V-Phone. Chúc anh có những trải nghiệm công nghệ đỉnh cao!', '2026-10-14 12:10:39', '2026-10-13 12:10:39', '2026-10-15 17:10:39'),
(8, 6, NULL, 'Ngô Thùy Trang', 'thuytrang.ngo@yahoo.com', '0938667788', 4, 'Máy chụp chân dung rất đẹp, pin trâu dùng cả ngày xem phim lướt web còn hơn 40%. Trừ 1 sao vì hộp không kèm củ sạc nhưng được shop tặng kèm sạc nhanh 45W bù lại.', 1, 1, 'Dạ cảm ơn chị Trang nhiều ạ. Do chính sách chung của hãng nên hộp máy không kèm sạc, V-Phone đã tặng ngay củ sạc 45W chính hãng để chị an tâm sử dụng tốt nhất ạ!', '2026-10-13 12:10:39', '2026-10-11 12:10:39', '2026-10-15 17:10:39'),
(9, 8, NULL, 'Đặng Anh Khoa', 'khoa.dang@gmail.com', '0944556677', 5, 'Ống kính Leica chụp ảnh màu sắc cổ điển cực kỳ có hồn, cảm biến 1 inch thu sáng ban đêm xuất sắc. Giao diện mượt mà, sạc 120W chỉ 18 phút là đầy pin!', 1, 1, 'V-Phone cảm ơn anh Khoa! Đội ngũ Leica đã tối ưu thuật toán xử lý ảnh thế hệ mới nhất cho dòng 16 Ultra, chúc anh có thật nhiều bức ảnh đẹp!', '2026-10-12 12:10:39', '2026-10-10 12:10:39', '2026-10-15 17:10:39'),
(10, 10, NULL, 'Nguyễn Minh Khang', 'khang.minh@outlook.com', '0908771122', 5, 'Màu sắc Titan sa mạc quá sang, máy chạy mát lạnh dù quay video 4K liên tục. Nhân viên giao hàng kiểm tra kỹ lưỡng kích hoạt bảo hành tại chỗ.', 1, 1, 'V-Phone xin chân thành cảm ơn anh Khang đã ủng hộ! Nếu cần hỗ trợ chuyển đổi dữ liệu hay phụ kiện anh liên hệ hotline 1800 6868 bất cứ lúc nào nhé.', '2026-10-14 12:10:39', '2026-10-12 12:10:39', '2026-10-15 17:10:39'),
(11, 11, 2, 'Huỳnh Bá Lực', 'luc@vphone.vn', '0909888999', 5, 'Kích thước 6.3 inch cầm vừa tay, Dynamic Island tiện lợi. Đơn hàng trước mình đặt giao rất chuẩn chỉ, máy nguyên seal chưa active.', 1, 1, 'Dạ cảm ơn anh Lực rất nhiều đã quay lại ủng hộ đơn hàng tiếp theo tại hệ thống V-Phone Store!', '2026-10-13 12:10:39', '2026-10-11 12:10:39', '2026-10-15 17:10:39'),
(12, 14, NULL, 'Lý Gia Hân', 'giahan.ly@gmail.com', '0932889900', 5, 'Giá hiện tại quá hời cho một chiếc Flagship toàn diện. Nút Camera Control chụp nhanh rất nhạy, màn hình to 6.9 inch xem phim đã mắt.', 1, 1, 'Cảm ơn chị Hân đã dành thời gian gửi lời đánh giá 5 sao cho V-Phone! Chúc chị có những phút giây trải nghiệm thật vui cùng máy.', '2026-10-12 12:10:39', '2026-10-09 12:10:39', '2026-10-15 17:19:52'),
(13, 26, NULL, 'Bùi Tiến Dũng', 'tiendung.bui@gmail.com', '0917228899', 5, 'Máy dùng rất mượt, viền titan nhám chống bám vân tay tốt. Tính năng khoanh tròn để tìm kiếm bằng AI cực tiện dụng khi lướt web.', 1, 1, NULL, NULL, '2026-10-10 12:10:39', '2026-10-15 17:10:39');

-- --------------------------------------------------------
-- Cấu trúc bảng `products`
-- --------------------------------------------------------
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `brand_id` int(11) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `price` decimal(12,0) NOT NULL,
  `sale_price` decimal(12,0) DEFAULT 0,
  `image` varchar(255) NOT NULL,
  `screen` varchar(60) DEFAULT NULL,
  `cpu` varchar(100) DEFAULT NULL,
  `ram` varchar(20) DEFAULT NULL,
  `rom` varchar(20) DEFAULT NULL,
  `battery` varchar(50) DEFAULT NULL,
  `quantity` int(11) DEFAULT 20,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_used` tinyint(1) DEFAULT 0,
  `condition_desc` varchar(100) DEFAULT 'Mới 100%',
  `colors` varchar(255) DEFAULT 'Đen, Trắng',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `storage_options` longtext DEFAULT NULL,
  `color_quantities` longtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `brand_id` (`brand_id`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=59 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dữ liệu bảng `products` (57 dòng)
INSERT INTO `products` (`id`, `brand_id`, `name`, `price`, `sale_price`, `image`, `screen`, `cpu`, `ram`, `rom`, `battery`, `quantity`, `is_featured`, `is_used`, `condition_desc`, `colors`, `created_at`, `storage_options`, `color_quantities`) VALUES
(1, 1, 'iPhone 18 Pro Max (Khung Nhôm)', '37990000', '34990000', 'assets/images/products/iphone-18-promax.png', '6.9\" 144Hz', 'Apple A20 Pro (2nm)', '16 GB', '256 GB', '5100 mAh', 25, 1, 0, 'Mới 100%', 'Đỏ Burgundy, Xanh Glacier Blue, Đen, Bạc', '2026-09-25 08:55:29', NULL, '[{\"color\":\"Đỏ Burgundy\",\"quantity\":7},{\"color\":\"Xanh Glacier Blue\",\"quantity\":6},{\"color\":\"Đen\",\"quantity\":6},{\"color\":\"Bạc\",\"quantity\":6}]'),
(2, 1, 'iPhone 18 Pro (Khung Nhôm)', '33990000', '30990000', 'assets/images/products/iphone-18-promax.png', '6.3\" 144Hz', 'Apple A20 Pro (2nm)', '16 GB', '256 GB', '3900 mAh', 20, 1, 0, 'Mới 100%', 'Đỏ Burgundy, Xanh Glacier Blue, Đen, Bạc', '2026-09-25 08:55:29', NULL, '[{\"color\":\"Đỏ Burgundy\",\"quantity\":5},{\"color\":\"Xanh Glacier Blue\",\"quantity\":5},{\"color\":\"Đen\",\"quantity\":5},{\"color\":\"Bạc\",\"quantity\":5}]'),
(3, 1, 'iPhone Duo (Màn Gập Apple)', '69990000', '64990000', 'assets/images/products/iphone-18-duo.png', '7.9\" Gập', 'Apple A20 Pro Bionic', '16 GB', '256 GB', '5200 mAh', 8, 1, 0, 'Mới 100%', 'Trắng Ánh Sao, Trời Đêm', '2026-09-25 08:55:29', NULL, '[{\"color\":\"Trắng Ánh Sao\",\"quantity\":2},{\"color\":\"Trời Đêm\",\"quantity\":6}]'),
(5, 2, 'Galaxy Z Tri-Fold (Gập 3 Màn)', '62990000', '56990000', 'assets/images/products/samsung-tri-fold.png', '10.2\" Gập 3', 'Snapdragon 8 Gen 5 AI', '24 GB', '256 GB', '6200 mAh', 7, 1, 0, 'Mới 100%', 'Đen Phantom', '2026-09-25 08:55:29', NULL, '[{\"color\":\"Đen Phantom\",\"quantity\":7}]'),
(6, 2, 'Galaxy S26 Ultra (Titan AI)', '38990000', '34990000', 'assets/images/products/samsung-s26-ultra.png', '6.8\" 165Hz', 'Snapdragon 8 Gen 5 for Galaxy', '16 GB', '256 GB', '5800 mAh', 30, 1, 0, 'Mới 100%', 'Tím Cobalt, Đen Classic, Xanh Sky Blue, Trắng Classic', '2026-09-25 08:55:29', NULL, '[{\"color\":\"Tím Cobalt\",\"quantity\":8},{\"color\":\"Đen Classic\",\"quantity\":8},{\"color\":\"Xanh Sky Blue\",\"quantity\":7},{\"color\":\"Trắng Classic\",\"quantity\":7}]'),
(7, 9, 'Huawei Mate XT (Gập 3 Màn)', '79990000', '74990000', 'assets/images/products/huawei-mate-xt.png', '10.2\" Gập 3', 'Kirin 9010 5G Flagship', '16 GB', '256 GB', '5600 mAh', 6, 1, 0, 'Mới 100%', 'Đỏ Ruihong', '2026-09-25 15:55:29', NULL, '[{\"color\":\"Đỏ Ruihong\",\"quantity\":1},{\"color\":\"Đen Huyền Vũ\",\"quantity\":3}]'),
(8, 3, 'Xiaomi 16 Ultra (Leica Edition)', '35990000', '32490000', 'assets/images/products/xiaomi-16-ultra.png', '6.78\" 2K+', 'Snapdragon 8 Gen 5', '24 GB', '256 GB', '5800 mAh', 15, 1, 0, 'Mới 100%', 'Đen Da Thuần, Xanh Lam', '2026-09-25 08:55:29', NULL, '[{\"color\":\"Đen Da Thuần\",\"quantity\":8},{\"color\":\"Xanh Lam\",\"quantity\":7}]'),
(9, 4, 'OPPO Find N5 Duo (Hologram)', '46990000', '42990000', 'assets/images/products/oppo-find-n5.png', '7.9\" Gập', 'Dimensity 9500 Ultra', '16 GB', '256 GB', '5250 mAh', 10, 1, 0, 'Mới 100%', 'Đen', '2026-09-25 08:55:29', NULL, '[{\"color\":\"Đen\",\"quantity\":10}]'),
(10, 1, 'iPhone 17 Pro Max', '36990000', '32990000', 'assets/images/products/iphone-17-promax.png', '6.9\" 120Hz', 'Apple A19 Pro Bionic', '12 GB', '256 GB', '4850 mAh', 20, 1, 0, 'Mới 100%', 'Bạc, Cam, Xanh', '2026-09-25 08:55:29', NULL, '[{\"color\":\"Bạc\",\"quantity\":20}]'),
(11, 1, 'iPhone 17 Pro', '30990000', '28490000', 'assets/images/products/iphone-17-pro.png', '6.3\" 120Hz', 'Apple A19 Pro Bionic', '12 GB', '128 GB', '3600 mAh', 15, 1, 0, 'Mới 100%', 'Bạc, Cam, Xanh', '2026-09-25 08:55:29', NULL, '[{\"color\":\"Bạc\",\"quantity\":15}]'),
(12, 1, 'iPhone 17 Air (Siêu Mỏng)', '27990000', '25990000', 'assets/images/products/iphone-17-air.png', '6.6\" 120Hz', 'Apple A19', '8 GB', '256 GB', '3800 mAh', 20, 0, 0, 'Mới 100%', 'Đen, Trắng, Xanh', '2026-09-25 08:55:29', NULL, '[{\"color\":\"Bạc Ánh Kim\",\"quantity\":7},{\"color\":\"Xanh Băng\",\"quantity\":7},{\"color\":\"Đen\",\"quantity\":6}]'),
(13, 1, 'iPhone 17 Standard', '23990000', '21990000', 'assets/images/products/iphone-17.png', '6.1\" OLED', 'Apple A19', '8 GB', '128 GB', '3700 mAh', 25, 0, 0, 'Mới 100%', 'Đen, Trắng, Hồng, Xanh, Xanh Lá', '2026-09-25 08:55:29', NULL, '[{\"color\":\"Xanh\",\"quantity\":7},{\"color\":\"Hồng\",\"quantity\":6},{\"color\":\"Trắng\",\"quantity\":6},{\"color\":\"Đen\",\"quantity\":6}]'),
(14, 1, 'iPhone 16 Pro Max', '28990000', '27990000', 'assets/images/products/iphone-16-promax.png', '6.9\" 120Hz', 'Apple A18 Pro', '8 GB', '256 GB', '4685 mAh', 35, 1, 0, 'Mới 100%', 'Titan Sa Mạc, Titan Tự Nhiên, Titan Đen, Titan Trắng', '2026-09-25 15:55:29', NULL, '[{\"color\":\"Titan Sa Mạc\",\"quantity\":9},{\"color\":\"Titan Tự Nhiên\",\"quantity\":9},{\"color\":\"Titan Đen\",\"quantity\":9},{\"color\":\"Titan Trắng\",\"quantity\":8}]'),
(15, 1, 'iPhone 16 Pro', '25990000', '24490000', 'assets/images/products/iphone-16-pro.png', '6.3\" 120Hz', 'Apple A18 Pro', '8 GB', '128 GB', '3582 mAh', 25, 1, 0, 'Mới 100%', 'Titan Sa Mạc, Titan Tự Nhiên, Titan Đen, Titan Trắng', '2026-09-25 15:55:29', NULL, '[{\"color\":\"Titan Sa Mạc\",\"quantity\":7},{\"color\":\"Titan Tự Nhiên\",\"quantity\":6},{\"color\":\"Titan Đen\",\"quantity\":6},{\"color\":\"Titan Trắng\",\"quantity\":6}]'),
(16, 1, 'iPhone 16 Plus', '25990000', '24990000', 'assets/images/products/iphone-16-plus.png', '6.7\" OLED', 'Apple A18', '8 GB', '128 GB', '4674 mAh', 20, 0, 0, 'Mới 100%', 'Đen, Trắng, Hồng, Xanh Mòng Két, Xanh Lưu Ly', '2026-09-25 15:55:29', NULL, '[{\"color\":\"Đen\",\"quantity\":4},{\"color\":\"Trắng\",\"quantity\":4},{\"color\":\"Hồng\",\"quantity\":4},{\"color\":\"Xanh Mòng Két\",\"quantity\":4},{\"color\":\"Xanh Lưu Ly\",\"quantity\":4}]'),
(17, 1, 'iPhone 16 Standard', '22990000', '21490000', 'assets/images/products/iphone-16.png', '6.1\" OLED', 'Apple A18', '8 GB', '128 GB', '3561 mAh', 30, 0, 0, 'Mới 100%', 'Đen, Trắng, Hồng, Xanh Mòng Két, Xanh Lưu Ly', '2026-09-25 15:55:29', NULL, '[{\"color\":\"Đen\",\"quantity\":6},{\"color\":\"Trắng\",\"quantity\":6},{\"color\":\"Hồng\",\"quantity\":6},{\"color\":\"Xanh Mòng Két\",\"quantity\":6},{\"color\":\"Xanh Lưu Ly\",\"quantity\":6}]'),
(18, 1, 'iPhone 15 Pro Max', '31990000', '28490000', 'assets/images/products/iphone-15-promax.png', '6.7\" 120Hz', 'Apple A17 Pro', '8 GB', '256 GB', '4422 mAh', 25, 0, 0, 'Mới 100%', 'Titan Tự Nhiên, Titan Xanh, Titan Đen, Titan Trắng', '2026-09-25 15:55:29', NULL, '[{\"color\":\"Titan Tự Nhiên\",\"quantity\":7},{\"color\":\"Titan Xanh\",\"quantity\":6},{\"color\":\"Titan Đen\",\"quantity\":6},{\"color\":\"Titan Trắng\",\"quantity\":6}]'),
(19, 1, 'iPhone 15 Standard', '21990000', '18490000', 'assets/images/products/iphone-15.png', '6.1\" OLED', 'Apple A16 Bionic', '6 GB', '128 GB', '3349 mAh', 30, 0, 0, 'Mới 100%', 'Đen, Hồng, Vàng, Xanh Lam, Xanh Mint', '2026-09-25 15:55:29', NULL, '[{\"color\":\"Đen\",\"quantity\":6},{\"color\":\"Xanh Lam\",\"quantity\":6},{\"color\":\"Xanh Lá\",\"quantity\":6},{\"color\":\"Vàng\",\"quantity\":6},{\"color\":\"Hồng\",\"quantity\":6}]'),
(20, 1, 'iPhone 14 Pro Max', '27990000', '24490000', 'assets/images/products/iphone-14-promax.png', '6.7\" 120Hz', 'Apple A16 Bionic', '6 GB', '128 GB', '4323 mAh', 20, 0, 0, 'Mới 100%', 'Tím Deep Purple, Vàng Gold, Bạc Silver, Đen Space', '2026-09-25 15:55:29', NULL, '[{\"color\":\"Tím Deep Purple\",\"quantity\":5},{\"color\":\"Vàng Gold\",\"quantity\":5},{\"color\":\"Bạc Silver\",\"quantity\":5},{\"color\":\"Đen Space\",\"quantity\":5}]'),
(21, 1, 'iPhone 14 Standard', '19990000', '16490000', 'assets/images/products/iphone-14.jpg', '6.1\" OLED', 'Apple A15 Bionic', '6 GB', '128 GB', '3279 mAh', 22, 0, 0, 'Mới 100%', 'Xanh Dương, Tím, Vàng, Trắng Starlight, Đen Midnight, Đỏ', '2026-09-25 15:55:29', NULL, '[{\"color\":\"Xanh Dương\",\"quantity\":4},{\"color\":\"Tím\",\"quantity\":4},{\"color\":\"Vàng\",\"quantity\":4},{\"color\":\"Trắng Starlight\",\"quantity\":4},{\"color\":\"Đen Midnight\",\"quantity\":3},{\"color\":\"Đỏ\",\"quantity\":3}]'),
(22, 1, 'iPhone 13 Standard', '16990000', '13490000', 'assets/images/products/iphone-13.png', '6.1\" OLED', 'Apple A15 Bionic', '4 GB', '128 GB', '3240 mAh', 35, 0, 0, 'Mới 100%', 'Trắng Starlight, Đen Midnight, Hồng, Xanh Lá, Đỏ, Xanh Dương', '2026-09-25 15:55:29', NULL, '[{\"color\":\"Trắng Starlight\",\"quantity\":6},{\"color\":\"Đen Midnight\",\"quantity\":6},{\"color\":\"Hồng\",\"quantity\":6},{\"color\":\"Xanh Lá\",\"quantity\":6},{\"color\":\"Đỏ\",\"quantity\":6},{\"color\":\"Xanh Dương\",\"quantity\":5}]'),
(23, 1, 'iPhone 12 Standard', '13990000', '11490000', 'assets/images/products/iphone-12.jpg', '6.1\" OLED', 'Apple A14 Bionic', '4 GB', '64 GB', '2815 mAh', 18, 0, 0, 'Mới 100%', 'Tím, Xanh Lam, Xanh Lục, Đỏ, Trắng, Đen', '2026-09-25 15:55:29', NULL, '[{\"color\":\"Tím\",\"quantity\":3},{\"color\":\"Xanh Lam\",\"quantity\":3},{\"color\":\"Xanh Lục\",\"quantity\":3},{\"color\":\"Đỏ\",\"quantity\":3},{\"color\":\"Trắng\",\"quantity\":3},{\"color\":\"Đen\",\"quantity\":3}]'),
(24, 1, 'iPhone 11 64GB', '11990000', '8490000', 'assets/images/products/iphone-11.png', '6.1\" LCD', 'Apple A13 Bionic', '4 GB', '64 GB', '3110 mAh', 40, 0, 0, 'Mới 100%', 'Đen, Trắng, Tím, Vàng, Xanh Lục, Đỏ', '2026-09-25 15:55:29', NULL, '[{\"color\":\"Đen\",\"quantity\":7},{\"color\":\"Trắng\",\"quantity\":7},{\"color\":\"Tím\",\"quantity\":7},{\"color\":\"Vàng\",\"quantity\":7},{\"color\":\"Xanh Lục\",\"quantity\":6},{\"color\":\"Đỏ\",\"quantity\":6}]'),
(25, 1, 'iPhone SE 2022', '10990000', '8990000', 'assets/images/products/iphone-se-2022.jpg', '4.7\" Retina', 'Apple A15 Bionic', '4 GB', '64 GB', '2018 mAh', 15, 0, 0, 'Mới 100%', 'Đen Midnight, Trắng Starlight, Đỏ Product Red', '2026-09-25 15:55:29', NULL, '[{\"color\":\"Đen Midnight\",\"quantity\":5},{\"color\":\"Trắng Starlight\",\"quantity\":5},{\"color\":\"Đỏ Product Red\",\"quantity\":5}]'),
(26, 2, 'Galaxy S24 Ultra 5G', '33990000', '26490000', 'assets/images/products/samsung-s24-ultra.png', '6.8\" 120Hz 2K+', 'Snapdragon 8 Gen 3 for Galaxy', '12 GB', '256 GB', '5000 mAh', 30, 1, 0, 'Mới 100%', 'Xám Titan, Đen Titan, Tím Titan, Vàng Titan', '2026-09-25 15:55:29', NULL, '[{\"color\":\"Xám Titan\",\"quantity\":8},{\"color\":\"Đen Titan\",\"quantity\":8},{\"color\":\"Tím Titan\",\"quantity\":7},{\"color\":\"Vàng Titan\",\"quantity\":7}]'),
(27, 2, 'Galaxy S24+ 5G', '26990000', '21490000', 'assets/images/products/samsung-s24-plus.jpg', '6.7\" 120Hz 2K+', 'Exynos 2400', '12 GB', '256 GB', '4900 mAh', 20, 0, 0, 'Mới 100%', 'Đen Onyx, Xám Marble, Tím Cobalt, Vàng Amber', '2026-09-25 15:55:29', NULL, '[{\"color\":\"Đen Onyx\",\"quantity\":5},{\"color\":\"Xám Marble\",\"quantity\":5},{\"color\":\"Tím Cobalt\",\"quantity\":5},{\"color\":\"Vàng Amber\",\"quantity\":5}]'),
(28, 2, 'Galaxy S24 5G', '22990000', '17490000', 'assets/images/products/samsung-s24.jpg', '6.2\" 120Hz', 'Exynos 2400', '8 GB', '256 GB', '4000 mAh', 15, 0, 0, 'Mới 100%', 'Đen Onyx, Xám Marble, Tím Cobalt, Vàng Amber', '2026-09-25 15:55:29', NULL, '[{\"color\":\"Đen Onyx\",\"quantity\":3},{\"color\":\"Xám Marble\",\"quantity\":3},{\"color\":\"Tím Cobalt\",\"quantity\":3},{\"color\":\"Vàng Amber\",\"quantity\":6}]'),
(29, 2, 'Galaxy S24 FE 5G', '16990000', '14990000', 'assets/images/products/samsung-s24-fe.jpg', '6.7\" 120Hz', 'Exynos 2400e', '8 GB', '128 GB', '4700 mAh', 18, 0, 0, 'Mới 100%', 'Xanh Topaz, Đen Graphite, Xám Opal, Xanh Jade', '2026-09-25 15:55:29', NULL, '[{\"color\":\"Xanh Topaz\",\"quantity\":4},{\"color\":\"Đen Graphite\",\"quantity\":4},{\"color\":\"Xám Opal\",\"quantity\":4},{\"color\":\"Xanh Jade\",\"quantity\":6}]'),
(30, 2, 'Galaxy Z Fold6 5G', '43990000', '39490000', 'assets/images/products/samsung-z-fold6.png', '7.6\" Gập 120Hz', 'Snapdragon 8 Gen 3 for Galaxy', '12 GB', '256 GB', '4400 mAh', 18, 1, 0, 'Mới 100%', 'Xám Metal, Hồng Rosé, Trắng Bạc', '2026-09-25 15:55:29', NULL, '[{\"color\":\"Xám Metal\",\"quantity\":6},{\"color\":\"Hồng Rosé\",\"quantity\":6},{\"color\":\"Xanh Navy\",\"quantity\":6}]'),
(31, 2, 'Galaxy Z Flip6 5G', '28990000', '24490000', 'assets/images/products/samsung-z-flip6.jpg', '6.7\" Gập 120Hz', 'Snapdragon 8 Gen 3 for Galaxy', '12 GB', '256 GB', '4000 mAh', 21, 1, 0, 'Mới 100%', 'Xám Metal, Xanh Maya, Vàng Solar, Xanh Mint', '2026-09-25 15:55:29', NULL, '[{\"color\":\"Xám Metal\",\"quantity\":4},{\"color\":\"Xanh Maya\",\"quantity\":5},{\"color\":\"Vàng Solar\",\"quantity\":5},{\"color\":\"Xanh Mint\",\"quantity\":7}]'),
(32, 2, 'Galaxy Z Fold5 5G', '34990000', '28990000', 'assets/images/products/samsung-z-fold5.jpg', '7.6\" Gập', 'Snapdragon 8 Gen 2', '12 GB', '256 GB', '4400 mAh', 10, 0, 0, 'Mới 100%', 'Xanh Icy Blue, Kem Ivory, Đen Phantom', '2026-09-25 15:55:29', NULL, '[{\"color\":\"Xanh Icy Blue\",\"quantity\":4},{\"color\":\"Kem Ivory\",\"quantity\":3},{\"color\":\"Đen Phantom\",\"quantity\":3}]'),
(33, 2, 'Galaxy S23 Ultra 5G', '26990000', '20490000', 'assets/images/products/samsung-s23-ultra.png', '6.8\" 120Hz', 'Snapdragon 8 Gen 2', '8 GB', '256 GB', '5000 mAh', 20, 0, 0, 'Mới 100%', 'Đen Phantom, Xanh Botanic, Kem Cotton, Tím Lilac', '2026-09-25 15:55:29', NULL, '[{\"color\":\"Đen Phantom\",\"quantity\":5},{\"color\":\"Xanh Botanic\",\"quantity\":5},{\"color\":\"Kem Cotton\",\"quantity\":5},{\"color\":\"Tím Lilac\",\"quantity\":5}]'),
(34, 2, 'Galaxy A55 5G', '9990000', '8690000', 'assets/images/products/samsung-a55.png', '6.6\" 120Hz', 'Exynos 1480', '8 GB', '128 GB', '5000 mAh', 40, 0, 0, 'Mới 100%', 'Xanh Iceblue, Xanh Navy, Tím Lilac', '2026-09-25 15:55:29', NULL, '[{\"color\":\"Xanh Iceblue\",\"quantity\":10},{\"color\":\"Xanh Navy\",\"quantity\":10},{\"color\":\"Vàng Lemon\",\"quantity\":10},{\"color\":\"Tím Lilac\",\"quantity\":10}]'),
(35, 2, 'Galaxy A35 5G', '7990000', '6990000', 'assets/images/products/samsung-a35.jpg', '6.6\" 120Hz', 'Exynos 1380', '8 GB', '128 GB', '5000 mAh', 30, 0, 0, 'Mới 100%', 'Xanh Iceblue, Vàng Lemon, Xanh Navy', '2026-09-25 15:55:29', NULL, '[{\"color\":\"Xanh Iceblue\",\"quantity\":7},{\"color\":\"Xanh Navy\",\"quantity\":7},{\"color\":\"Vàng Lemon\",\"quantity\":7},{\"color\":\"Tím Lilac\",\"quantity\":9}]'),
(36, 2, 'Galaxy A15 5G', '4990000', '4190000', 'assets/images/products/samsung-a15.jpg', '6.5\" 90Hz', 'Dimensity 6100+', '8 GB', '128 GB', '5000 mAh', 35, 0, 0, 'Mới 100%', 'Xanh Lạc Quan, Vàng Cá Tính, Đen Bản Lĩnh', '2026-09-25 15:55:29', NULL, '[{\"color\":\"Xanh Lạc Quan\",\"quantity\":11},{\"color\":\"Vàng Cá Tính\",\"quantity\":11},{\"color\":\"Đen Bản Lĩnh\",\"quantity\":13}]'),
(37, 2, 'Galaxy A05s', '3990000', '3390000', 'assets/images/products/samsung-a05s.jpg', '6.7\" 90Hz', 'Snapdragon 680', '4 GB', '128 GB', '5000 mAh', 40, 0, 0, 'Mới 100%', 'Đen Đá, Xanh Matcha, Bạc Xỉu', '2026-09-25 15:55:29', NULL, '[{\"color\":\"Đen Đá\",\"quantity\":13},{\"color\":\"Xanh Matcha\",\"quantity\":13},{\"color\":\"Bạc Xỉu\",\"quantity\":14}]'),
(38, 3, 'Xiaomi 14 Ultra 5G', '32990000', '28990000', 'assets/images/products/xiaomi-14-ultra.png', '6.73\" 2K+ LTPO', 'Snapdragon 8 Gen 3', '16 GB', '512 GB', '5000 mAh', 15, 1, 0, 'Mới 100%', 'Đen, Xanh Dragon Crystal', '2026-09-25 15:55:29', NULL, '[{\"color\":\"Đen\",\"quantity\":7},{\"color\":\"Trắng\",\"quantity\":8}]'),
(39, 3, 'Xiaomi 14 5G', '22990000', '18490000', 'assets/images/products/xiaomi-14.jpg', '6.36\" 1.5K', 'Snapdragon 8 Gen 3', '12 GB', '256 GB', '4610 mAh', 20, 0, 0, 'Mới 100%', 'Đen, Trắng, Xanh Ngọc Bích', '2026-09-25 15:55:29', NULL, '[{\"color\":\"Đen\",\"quantity\":6},{\"color\":\"Trắng\",\"quantity\":6},{\"color\":\"Xanh Ngọc Bích\",\"quantity\":8}]'),
(40, 3, 'Redmi Note 13 Pro+ 5G', '10990000', '9190000', 'assets/images/products/redmi-note-13-pro-plus.jpg', '6.67\" 1.5K Cong', 'Dimensity 7200 Ultra', '8 GB', '256 GB', '5000 mAh', 30, 0, 0, 'Mới 100%', 'Đen Midnight, Tím Cực Quang, Trắng Ánh Trăng', '2026-09-25 15:55:29', NULL, '[{\"color\":\"Đen Midnight\",\"quantity\":10},{\"color\":\"Tím Cực Quang\",\"quantity\":10},{\"color\":\"Trắng Ánh Trăng\",\"quantity\":10}]'),
(41, 3, 'Redmi 13', '4290000', '3790000', 'assets/images/products/redmi-13.jpg', '6.79\" 90Hz', 'Helio G91 Ultra', '6 GB', '128 GB', '5030 mAh', 45, 0, 0, 'Mới 100%', 'Đen Bóng Đêm, Xanh Đại Dương, Hồng Ngọc Trai', '2026-09-25 15:55:29', NULL, '[{\"color\":\"Đen Bóng Đêm\",\"quantity\":11},{\"color\":\"Vàng Biển Cát\",\"quantity\":11},{\"color\":\"Hồng Ngọc Trai\",\"quantity\":11},{\"color\":\"Xanh Đại Dương\",\"quantity\":12}]'),
(42, 3, 'POCO F6 Pro 5G', '14990000', '12990000', 'assets/images/products/poco-f6-pro.jpg', '6.67\" 2K 120Hz', 'Snapdragon 8 Gen 2', '12 GB', '256 GB', '5000 mAh', 15, 0, 0, 'Mới 100%', 'Đen Vũ Trụ', '2026-09-25 15:55:29', NULL, '[{\"color\":\"Đen\",\"quantity\":7},{\"color\":\"Trắng\",\"quantity\":8}]'),
(43, 3, 'POCO X6 Pro 5G', '9990000', '8490000', 'assets/images/products/poco-x6-pro.jpg', '6.67\" 1.5K', 'Dimensity 8300-Ultra', '8 GB', '256 GB', '5000 mAh', 20, 0, 0, 'Mới 100%', 'Vàng POCO, Đen, Xám', '2026-09-25 15:55:29', NULL, '[{\"color\":\"Vàng POCO\",\"quantity\":6},{\"color\":\"Đen\",\"quantity\":6},{\"color\":\"Xám\",\"quantity\":8}]'),
(44, 4, 'OPPO Find N3 5G', '44990000', '39490000', 'assets/images/products/oppo-find-n3.png', '7.82\" Gập 120Hz', 'Snapdragon 8 Gen 2', '16 GB', '512 GB', '4805 mAh', 12, 1, 0, 'Mới 100%', 'Vàng Cổ Điển, Đen Cổ Điển', '2026-09-25 15:55:29', NULL, '[{\"color\":\"Vàng Cổ Điển\",\"quantity\":6},{\"color\":\"Đen Cổ Điển\",\"quantity\":6}]'),
(45, 4, 'OPPO Find N3 Flip', '22990000', '17990000', 'assets/images/products/oppo-find-n3-flip.jpg', '6.8\" Gập', 'Dimensity 9200', '12 GB', '256 GB', '4300 mAh', 15, 0, 0, 'Mới 100%', 'Vàng Thạch Anh, Đen Kính', '2026-09-25 15:55:29', NULL, '[{\"color\":\"Vàng Thạch Anh\",\"quantity\":5},{\"color\":\"Hồng Phấn\",\"quantity\":5},{\"color\":\"Đen Kính\",\"quantity\":5}]'),
(46, 4, 'OPPO Reno12 Pro 5G', '18990000', '16490000', 'assets/images/products/oppo-reno12-pro.jpg', '6.7\" 120Hz Cong', 'Dimensity 7300-Energy', '12 GB', '512 GB', '5000 mAh', 20, 0, 0, 'Mới 100%', 'Bạc Ngân Hà, Đen Không Gian', '2026-09-25 15:55:29', NULL, '[{\"color\":\"Bạc Ngân Hà\",\"quantity\":10},{\"color\":\"Đen Không Gian\",\"quantity\":10}]'),
(47, 4, 'OPPO Reno11 F 5G', '8990000', '7690000', 'assets/images/products/oppo-reno11-f.jpg', '6.7\" 120Hz', 'Dimensity 7050', '8 GB', '256 GB', '5000 mAh', 30, 0, 0, 'Mới 100%', 'Xanh Đại Dương, Xanh Sơn Lâm', '2026-09-25 15:55:29', NULL, '[{\"color\":\"Xanh Đại Dương\",\"quantity\":10},{\"color\":\"Xanh Sơn Lâm\",\"quantity\":10},{\"color\":\"Tím Coral\",\"quantity\":10}]'),
(48, 5, 'Vivo X100 Pro 5G', '22990000', '19490000', 'assets/images/products/vivo-x100-pro.jpg', '6.78\" 1.5K LTPO', 'Dimensity 9300', '16 GB', '512 GB', '5400 mAh', 10, 1, 0, 'Mới 100%', 'Cam Hoàng Hôn, Xanh Tinh Tú, Đen', '2026-09-25 15:55:29', NULL, '[{\"color\":\"Cam Hoàng Hôn\",\"quantity\":2},{\"color\":\"Xanh Tinh Tú\",\"quantity\":2},{\"color\":\"Trắng Ánh Trăng\",\"quantity\":2},{\"color\":\"Đen\",\"quantity\":4}]'),
(49, 5, 'Vivo Y28', '5790000', '4990000', 'assets/images/products/vivo-y28.jpg', '6.68\" 90Hz', 'Helio G85', '8 GB', '128 GB', '6000 mAh', 35, 0, 0, 'Mới 100%', 'Cam Ánh Dương, Xanh Lục Bảo', '2026-09-25 15:55:29', NULL, '[{\"color\":\"Cam Ánh Dương\",\"quantity\":17},{\"color\":\"Xanh Lục Bảo\",\"quantity\":18}]'),
(50, 6, 'Google Pixel 9 Pro XL', '29990000', '26490000', 'assets/images/products/google-pixel-9.png', '6.8\" 120Hz LTPO', 'Google Tensor G4', '16 GB', '256 GB', '5060 mAh', 10, 1, 0, 'Mới 100%', 'Đen Obsidian, Trắng Sứ Porcelain, Xám Xanh Hazel, Hồng Rose Quartz', '2026-09-25 15:55:29', NULL, '[{\"color\":\"Đen Obsidian\",\"quantity\":2},{\"color\":\"Trắng Sứ Porcelain\",\"quantity\":2},{\"color\":\"Xám Xanh Hazel\",\"quantity\":2},{\"color\":\"Hồng Rose Quartz\",\"quantity\":4}]'),
(51, 7, 'ASUS ROG Phone 8 Pro', '31990000', '27490000', 'assets/images/products/asus-rog-phone-8.jpg', '6.78\" 165Hz LTPO', 'Snapdragon 8 Gen 3', '16 GB', '512 GB', '5500 mAh', 8, 1, 0, 'Mới 100%', 'Đen Phantom', '2026-09-25 15:55:29', NULL, '[{\"color\":\"Đen Phantom\",\"quantity\":8}]'),
(52, 8, 'Sony Xperia 1 VI', '34990000', '30490000', 'assets/images/products/sony-xperia-1-vi.png', '6.5\" 120Hz LTPO', 'Snapdragon 8 Gen 3', '12 GB', '256 GB', '5000 mAh', 8, 1, 0, 'Mới 100%', 'Đen, Bạc Bạch Kim, Xanh Rêu Khaki', '2026-09-25 15:55:29', NULL, '[{\"color\":\"Đen\",\"quantity\":2},{\"color\":\"Bạc Bạch Kim\",\"quantity\":2},{\"color\":\"Xanh Rêu Khaki\",\"quantity\":2},{\"color\":\"Đỏ Scarlet\",\"quantity\":2}]'),
(53, 1, '[Cũ 99%] iPhone 15 Pro Max 256GB', '28990000', '21990000', 'assets/images/products/iphone-15-promax.png', '6.7\" 120Hz', 'Apple A17 Pro', '8 GB', '256 GB', '4422 mAh', 7, 0, 1, 'Đẹp 99% - Pin 96%', 'Titan Tự Nhiên, Titan Xanh, Titan Đen', '2026-09-25 15:55:29', NULL, '[{\"color\":\"Titan Tự Nhiên\",\"quantity\":3},{\"color\":\"Titan Xanh\",\"quantity\":2},{\"color\":\"Titan Đen\",\"quantity\":2}]'),
(54, 1, '[Cũ 99%] iPhone 14 Pro Max 128GB', '24990000', '17990000', 'assets/images/products/iphone-14-promax.png', '6.7\" 120Hz', 'Apple A16 Bionic', '6 GB', '128 GB', '4323 mAh', 5, 0, 1, 'Đẹp 99% - Pin 93%', 'Tím Deep Purple, Vàng Gold, Đen Space', '2026-09-25 15:55:29', NULL, '[{\"color\":\"Tím Deep Purple\",\"quantity\":2},{\"color\":\"Vàng Gold\",\"quantity\":2},{\"color\":\"Đen Space\",\"quantity\":1}]'),
(55, 1, '[Cũ 99%] iPhone 13 128GB', '14990000', '10490000', 'assets/images/products/iphone-13.png', '6.1\" OLED', 'Apple A15 Bionic', '4 GB', '128 GB', '3240 mAh', 10, 0, 1, 'Đẹp 98% - Pin 90%', 'Trắng Ánh Sao, Đen Midnight, Hồng', '2026-09-25 15:55:29', NULL, '[{\"color\":\"Trắng Ánh Sao\",\"quantity\":5},{\"color\":\"Đen Midnight\",\"quantity\":3},{\"color\":\"Hồng\",\"quantity\":2}]'),
(56, 1, '[Cũ 99%] iPhone 11 64GB', '8990000', '5490000', 'assets/images/products/iphone-11.png', '6.1\" LCD', 'Apple A13 Bionic', '4 GB', '64 GB', '3110 mAh', 15, 0, 1, 'Đẹp 98% - Pin 89%', 'Đen, Trắng, Đỏ', '2026-09-25 15:55:29', NULL, '[{\"color\":\"Đen\",\"quantity\":5},{\"color\":\"Trắng\",\"quantity\":5},{\"color\":\"Đỏ\",\"quantity\":5}]'),
(57, 2, '[Cũ 99%] Samsung Galaxy S23 Ultra 256GB', '22990000', '15990000', 'assets/images/products/samsung-s23-ultra.png', '6.8\" 120Hz', 'Snapdragon 8 Gen 2', '8 GB', '256 GB', '5000 mAh', 6, 0, 1, 'Đẹp Like New 99%', 'Đen Phantom, Kem Cotton, Xanh Botanic', '2026-09-25 15:55:29', NULL, '[{\"color\":\"Đen Phantom\",\"quantity\":2},{\"color\":\"Kem Cotton\",\"quantity\":1},{\"color\":\"Xanh Botanic\",\"quantity\":1}]'),
(58, 2, '[Cũ 99%] Samsung Galaxy Z Fold5 5G', '32990000', '21990000', 'assets/images/products/samsung-z-fold5.jpg', '7.6\" Gập', 'Snapdragon 8 Gen 2', '12 GB', '256 GB', '4400 mAh', 2, 0, 1, 'Bản gập nguyên zin 99%', 'Xanh Icy Blue, Đen Phantom', '2026-09-25 15:55:29', NULL, '[{\"color\":\"Xanh Icy Blue\",\"quantity\":2},{\"color\":\"Đen Phantom\",\"quantity\":0}]');

-- --------------------------------------------------------
-- Cấu trúc bảng `user_addresses`
-- --------------------------------------------------------
CREATE TABLE `user_addresses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `recipient_name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` varchar(255) NOT NULL,
  `label` varchar(50) DEFAULT 'Nhà riêng',
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `user_addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dữ liệu bảng `user_addresses` (3 dòng)
INSERT INTO `user_addresses` (`id`, `user_id`, `recipient_name`, `phone`, `address`, `label`, `is_default`, `created_at`) VALUES
(1, 1, 'Võ Minh Hiếu', '0901234567', 'Quận 1, TP.HCM', 'Nhà riêng', 1, '2026-10-15 09:19:32'),
(2, 2, 'Huỳnh Bá Lực', '0909888999', 'Quận 7, TP.HCM', 'Nhà riêng', 1, '2026-10-15 09:19:32'),
(3, 3, 'Quản Trị Viên V-Phone', '18006868', 'Trụ sở V-Phone', 'Nhà riêng', 1, '2026-10-15 09:19:32');

-- --------------------------------------------------------
-- Cấu trúc bảng `user_notifications`
-- --------------------------------------------------------
CREATE TABLE `user_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `cancel_reason` varchar(255) DEFAULT NULL,
  `type` varchar(50) DEFAULT 'order_cancelled',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `phone` (`phone`),
  KEY `order_id` (`order_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dữ liệu bảng `user_notifications` (11 dòng)
INSERT INTO `user_notifications` (`id`, `user_id`, `phone`, `order_id`, `title`, `message`, `cancel_reason`, `type`, `is_read`, `created_at`) VALUES
(1, 1, '0901234567', 15, 'Đơn hàng #VP-15 đã bị hủy bởi cửa hàng', 'Đơn hàng #VP-15 (Tổng thanh toán: 34.990.000 VNĐ) đã được xử lý hủy bởi V-Phone Store.\nLý do: Sản phẩm tạm thời hết hàng trong kho. V-Phone xin lỗi quý khách vì sự bất tiện này..\nRất xin lỗi quý khách vì sự bất tiện này. Quý khách vui lòng liên hệ hotline 1800 6868 nếu cần hỗ trợ thêm.', 'Sản phẩm tạm thời hết hàng trong kho. V-Phone xin lỗi quý khách vì sự bất tiện này.', 'order_cancelled', 1, '2026-10-15 21:06:57'),
(2, 1, '0901234567', 9, 'Đơn hàng #VP-9 đã bị hủy bởi cửa hàng', 'Đơn hàng #VP-9 (Tổng thanh toán: 42.990.000 VNĐ) đã được xử lý hủy bởi V-Phone Store.\nLý do: Sản phẩm tạm thời hết hàng trong kho. V-Phone xin lỗi quý khách vì sự bất tiện này..\nRất xin lỗi quý khách vì sự bất tiện này. Quý khách vui lòng liên hệ hotline 1800 6868 nếu cần hỗ trợ thêm.', 'Sản phẩm tạm thời hết hàng trong kho. V-Phone xin lỗi quý khách vì sự bất tiện này.', 'order_cancelled', 1, '2026-10-15 21:08:49'),
(3, 1, '0901234567', 11, 'Đơn hàng #VP-11 đang được giao đến bạn!', 'Đơn hàng #VP-11 (Tổng thanh toán: 74.990.000 VNĐ) đã được đóng gói và bàn giao cho đơn vị vận chuyển. Shipper sẽ sớm liên hệ giao hàng đến bạn!', NULL, 'order_shipping', 0, '2026-10-16 08:17:35'),
(4, 1, '0901234567', 12, 'Đơn hàng #VP-12 đã giao thành công!', 'Đơn hàng #VP-12 (Tổng thanh toán: 24.490.000 VNĐ) đã được giao thành công. Cảm ơn quý khách đã tin tưởng và ủng hộ V-Phone Store!', NULL, 'order_delivered', 1, '2026-10-16 08:17:35'),
(5, 2, '0909888999', 1, 'Đơn hàng #VP-1 đã giao thành công!', 'Đơn hàng #VP-1 (Tổng thanh toán: 28.490.000 VNĐ) đã được giao thành công. Cảm ơn quý khách đã tin tưởng và ủng hộ V-Phone Store!', NULL, 'order_delivered', 1, '2026-10-16 08:22:06'),
(6, 2, '0909888999', 2, 'Đơn hàng #VP-2 đã bị hủy', 'Đơn hàng #VP-2 (Tổng thanh toán: 64.890.000 VNĐ) đã được xử lý hủy.', 'Theo yêu cầu của khách hàng hoặc hết hàng', 'order_cancelled', 1, '2026-10-16 08:22:06'),
(7, 3, '18006868', 3, 'Đơn hàng #VP-3 đã giao thành công!', 'Đơn hàng #VP-3 (Tổng thanh toán: 25.990.000 VNĐ) đã được giao thành công. Cảm ơn quý khách đã tin tưởng và ủng hộ V-Phone Store!', NULL, 'order_delivered', 1, '2026-10-16 08:22:06'),
(8, 2, '0909888999', 7, 'Đơn hàng #VP-7 đã được xác nhận!', 'Đơn hàng #VP-7 (Tổng thanh toán: 64.990.000 VNĐ) đã được tiếp nhận. Cửa hàng đang tiến hành chuẩn bị sản phẩm.', NULL, 'order_processing', 1, '2026-10-16 08:22:06'),
(9, 2, '0909888999', 8, 'Đơn hàng #VP-8 đã được xác nhận!', 'Đơn hàng #VP-8 (Tổng thanh toán: 64.990.000 VNĐ) đã được tiếp nhận. Cửa hàng đang tiến hành chuẩn bị sản phẩm.', NULL, 'order_processing', 1, '2026-10-16 08:22:06'),
(10, 2, '0909888999', 13, 'Đơn hàng #VP-13 đang được giao đến bạn!', 'Đơn hàng #VP-13 (Tổng thanh toán: 56.990.000 VNĐ) đã được đóng gói và bàn giao cho đơn vị vận chuyển. Shipper sẽ sớm liên hệ giao hàng đến bạn!', NULL, 'order_shipping', 1, '2026-10-16 08:22:06'),
(11, 2, '0909888999', 14, 'Đơn hàng #VP-14 đã được xác nhận!', 'Đơn hàng #VP-14 (Tổng thanh toán: 64.990.000 VNĐ) đã được tiếp nhận. Cửa hàng đang tiến hành chuẩn bị sản phẩm.', NULL, 'order_processing', 1, '2026-10-16 08:22:06');

-- --------------------------------------------------------
-- Cấu trúc bảng `users`
-- --------------------------------------------------------
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `google_id` varchar(100) DEFAULT NULL,
  `auth_provider` varchar(30) NOT NULL DEFAULT 'local',
  `phone` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` tinyint(4) DEFAULT 0,
  `cart_data` longtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `staff_role` varchar(50) DEFAULT NULL,
  `staff_title` varchar(100) DEFAULT NULL,
  `permissions` text DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dữ liệu bảng `users` (10 dòng)
INSERT INTO `users` (`id`, `fullname`, `email`, `google_id`, `auth_provider`, `phone`, `address`, `avatar`, `password`, `role`, `cart_data`, `created_at`, `staff_role`, `staff_title`, `permissions`, `status`) VALUES
(1, 'Võ Minh Hiếu', 'hieu@vphone.vn', NULL, 'local', '0901234567', 'Quận 1, TP.HCM', NULL, '$2y$10$33vtXT.qs4BNnJHX6h0beO7FJR2yZtucxlwkwu.KIKWx3qvzN8u1G', 1, '[]', '2026-10-08 09:30:19', 'admin', 'Quản trị viên', NULL, 1),
(2, 'Huỳnh Bá Lực', 'luc@vphone.vn', NULL, 'local', '0909888999', 'Quận 7, TP.HCM', NULL, '$2y$10$kZ76ftCCfjpFJkWv8aOyFuYo2fmvz5loyyGZyQzqRvajascv78iKu', 2, '[]', '2026-10-08 09:30:19', 'staff', 'Nhân viên bán hàng', '[\"orders\",\"warranties\",\"reviews\"]', 1),
(3, 'Quản Trị Viên V-Phone', 'admin@vphone.vn', NULL, 'local', '18006868', 'Trụ sở V-Phone', NULL, '$2y$10$kZ76ftCCfjpFJkWv8aOyFuYo2fmvz5loyyGZyQzqRvajascv78iKu', 1, '{\"25_8990000_64GBenMidnight\":{\"cart_key\":\"25_8990000_64GBenMidnight\",\"id\":25,\"name\":\"iPhone SE 2022\",\"rom\":\"64 GB\",\"color\":\"Đen Midnight\",\"price\":8990000,\"image\":\"assets\\/images\\/products\\/iphone-se-2022.jpg\",\"quantity\":1}}', '2026-10-08 09:30:19', 'super_admin', 'Quản Trị Tối Cao', NULL, 1),
(8, 'Lực Huỳnh', 'beluc5102003@gmail.com', '112858085185933992393', 'google', '0888443655', 'Chưa cập nhật', 'assets/images/users/avatar_user_8.png', NULL, 1, '{\"3_64990000_256GBTrngnhSao\":{\"cart_key\":\"3_64990000_256GBTrngnhSao\",\"id\":3,\"name\":\"iPhone Duo (Màn Gập Apple)\",\"rom\":\"256 GB\",\"color\":\"Trắng Ánh Sao\",\"price\":64990000,\"image\":\"assets\\/images\\/products\\/iphone-18-duo-trang-anh-sao.png\",\"quantity\":1}}', '2026-10-15 19:23:57', 'admin', 'Quản trị viên', NULL, 1),
(10, 'lokidptri2k3', 'ongnoitapchoi2003@gmail.com', '108680907798119891576', 'google', '', 'Chưa cập nhật', 'assets/images/users/avatar_user_10.png', NULL, 0, '[]', '2026-10-15 19:45:59', NULL, NULL, NULL, 1),
(12, 'Nguyễn Văn Bán (NV Bán Hàng)', 'nhanvien@vphone.vn', NULL, 'local', '0908889999', 'Cửa hàng V-Phone', NULL, '$2y$10$Q9ReBr6uvYmSZd9ZXBsImOMjHiGNbYtn8NV37w575UAPeWJvcZrRa', 2, '[]', '2026-10-16 08:43:18', 'sales', 'Nhân viên Bán hàng & Đơn hàng', '[\"orders\",\"vouchers\"]', 1),
(13, 'Trần Văn Kho (NV Kho)', 'kho@vphone.vn', NULL, 'local', '0907778888', 'Cửa hàng V-Phone', NULL, '$2y$10$/hwVIOgM0h6HiOgHc/X/MesEzw4fB9gaYKlOz6Og5r6JfylX/mkU.', 2, '[]', '2026-10-16 08:43:19', 'warehouse', 'Nhân viên Quản lý Kho', '[\"products\",\"inventory\"]', 1),
(14, 'Lê Kỹ Thuật (NV Bảo Hành & CSKH)', 'baohanh@vphone.vn', NULL, 'local', '0906667777', 'Cửa hàng V-Phone', NULL, '$2y$10$jzUiNXhVKIostXxvQsI6Ce2Ti2IP8kzoKiDF4UezvrVFrXEu6X9Fe', 2, NULL, '2026-10-16 08:43:19', 'support', 'Chuyên viên CSKH & Bảo hành', '[\"warranties\",\"reviews\"]', 1),
(15, 'Nghiện Nhạc', 'ongnoitapchoi2009@gmail.com', '104701851243694340335', 'google', '', 'Chưa cập nhật', 'assets/images/users/avatar_user_15.png', NULL, 0, NULL, '2026-10-17 14:24:29', NULL, NULL, NULL, 1),
(16, 'octiukiu l7', 'l7octiukiu@gmail.com', '110911761992361094636', 'google', '', 'Chưa cập nhật', 'assets/images/users/avatar_user_16.png', NULL, 0, NULL, '2026-10-20 11:17:37', NULL, NULL, NULL, 1);

-- --------------------------------------------------------
-- Cấu trúc bảng `vouchers`
-- --------------------------------------------------------
CREATE TABLE `vouchers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(40) NOT NULL,
  `discount_type` enum('percent','fixed') NOT NULL,
  `discount_value` decimal(12,0) NOT NULL,
  `minimum_order` decimal(12,0) NOT NULL DEFAULT 0,
  `max_discount` decimal(12,0) DEFAULT NULL,
  `starts_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `usage_limit` int(11) DEFAULT NULL,
  `used_count` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=427 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dữ liệu bảng `vouchers` (10 dòng)
INSERT INTO `vouchers` (`id`, `code`, `discount_type`, `discount_value`, `minimum_order`, `max_discount`, `starts_at`, `expires_at`, `usage_limit`, `used_count`, `is_active`, `created_at`) VALUES
(1, 'VPHONE5', 'percent', '5', '500000', '250000', NULL, NULL, NULL, 0, 1, '2026-10-08 09:30:19'),
(2, 'VPHONE10', 'percent', '10', '1000000', '500000', NULL, NULL, NULL, 0, 1, '2026-10-08 09:30:19'),
(3, 'VPHONE15', 'percent', '15', '5000000', '1000000', NULL, NULL, NULL, 0, 1, '2026-10-08 09:30:19'),
(4, 'FLASH20', 'percent', '20', '10000000', '2000000', NULL, NULL, NULL, 0, 1, '2026-10-08 09:30:19'),
(5, 'WELCOME50', 'fixed', '50000', '500000', NULL, NULL, NULL, NULL, 0, 1, '2026-10-08 09:30:19'),
(6, 'GIAM100K', 'fixed', '100000', '1000000', NULL, NULL, NULL, NULL, 1, 1, '2026-10-08 09:30:19'),
(7, 'GIAM200K', 'fixed', '200000', '2000000', NULL, NULL, NULL, NULL, 0, 1, '2026-10-08 09:30:19'),
(8, 'GIAM500K', 'fixed', '500000', '5000000', NULL, NULL, NULL, NULL, 0, 1, '2026-10-08 09:30:19'),
(361, 'VPHONE500', 'fixed', '500000', '15000000', NULL, NULL, NULL, NULL, 0, 1, '2026-10-19 11:18:38'),
(362, 'FLAGSHIP2026', 'fixed', '1000000', '20000000', NULL, NULL, NULL, NULL, 0, 1, '2026-10-19 11:18:38');

-- --------------------------------------------------------
-- Cấu trúc bảng `warranty_claims`
-- --------------------------------------------------------
CREATE TABLE `warranty_claims` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `order_id` int(11) NOT NULL,
  `detail_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `product_name` varchar(255) NOT NULL,
  `imei` varchar(50) NOT NULL,
  `customer_name` varchar(150) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `issue_type` varchar(100) NOT NULL,
  `issue_description` text DEFAULT NULL,
  `claim_method` varchar(50) DEFAULT 'store',
  `status` varchar(50) DEFAULT 'Chờ tiếp nhận',
  `admin_note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_imei` (`imei`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dữ liệu bảng `warranty_claims` (1 dòng)
INSERT INTO `warranty_claims` (`id`, `user_id`, `order_id`, `detail_id`, `product_id`, `product_name`, `imei`, `customer_name`, `customer_phone`, `issue_type`, `issue_description`, `claim_method`, `status`, `admin_note`, `created_at`, `updated_at`) VALUES
(1, 2, 1, 1, 11, 'iPhone 17 Pro (128 GB - Màu: Titan Tự Nhiên)', '353273692033888', 'Huỳnh Bá Lực', '0909888999', 'Lỗi màn hình / cảm ứng / sọc màn', 'Màn hình xuất hiện sọc xanh nhấp nháy góc dưới bên phải', 'Mang trực tiếp tới cửa hàng', 'Đã sửa chữa', 'Xác nhận lỗi phần cứng NSX. Đã đổi ngay máy mới 100% nguyên seal.', '2026-10-15 10:22:38', '2026-10-15 10:54:36');

SET FOREIGN_KEY_CHECKS = 1;
