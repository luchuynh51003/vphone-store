# ĐỒ ÁN CHUYÊN NGÀNH: HỆ THỐNG THƯƠNG MẠI ĐIỆN TỬ V-PHONE STORE
* **Sinh viên thực hiện:** Huỳnh Bá Lực (25002479) & Võ Minh Hiếu (25002470)
* **Giảng viên hướng dẫn:** Thầy Mai Chiếm Tuấn
* **Ngành:** Lập trình máy tính | **Khóa:** 2025

---

## 📌 BÁO CÁO TIẾN ĐỘ THỰC HIỆN (25/09/2026: 11h00 - 13h30)

### 1. Giao diện & Trải nghiệm người dùng:
- Hoàn thiện bộ nhận diện V-Phone Store theo tông màu chuẩn Blue - White.
- Tối ưu thanh điều hướng, xử lý triệt để khoảng trắng đầu trang trên macOS/Windows.
- Tích hợp Menu góc trái (Sidebar Drawer) phân loại theo Hãng, Kho máy cũ 99% và Mức giá.
- Đồng bộ biểu tượng Favicon và logo đồ họa V-Phone trên toàn hệ thống.

### 2. Danh mục sản phẩm & Dữ liệu thực tế:
- Duy trì dàn Flagship 2026 dẫn đầu danh mục.
- Mở rộng kho sản phẩm lên hơn 100+ mẫu điện thoại thực tế từ Apple, Samsung, Xiaomi, OPPO, Vivo, Pixel, ROG Phone, Sony.
- Tích hợp kho ảnh sản phẩm chuẩn studio nền trắng từ Thế Giới Di Động.
- Xây dựng trang Kho máy cũ 99% (used-phones.php) và Trang tin công nghệ (news.php).

### 3. Nghiệp vụ Màu Sắc & Dung Lượng đa dạng:
- Thiết lập bảng màu sắc chính hãng thực tế ngoài đời cho 100% dòng máy.
- Tích hợp cơ chế đổi màu sắc hiển thị ảnh tương ứng khi chọn màu.
- Mở rộng các mức dung lượng từ 64GB đến 2TB với cơ chế nhảy giá tiền động.
- Chuẩn hóa quy trình: Bấm Mua ngay / Thêm giỏ mở popup tùy chọn phiên bản.

### 4. Giỏ hàng & Thanh toán (Checkout):
- Tách biệt chế độ "Mua Ngay độc lập 1 máy" không làm ảnh hưởng đến các sản phẩm trong giỏ hàng.
- Đồng bộ dữ liệu giỏ hàng gắn liền theo tài khoản người dùng vào MySQL.
- Tự động trừ số lượng tồn kho khi đơn hàng được đặt thành công.

### 5. Quản trị hệ thống (Admin Dashboard):
- Bảng điều khiển theo dõi doanh thu thực tế, đơn hàng mới, kho hàng.
- Quản lý sản phẩm, duyệt trạng thái đơn hàng (hiển thị chi tiết màu và dung lượng khách mua).
- Trang quản lý tồn kho chuyên sâu (cảnh báo máy sắp hết và nhập hàng nhanh).
- Cập nhật cơ sở dữ liệu đồng bộ vào database/db_phone_store.sql.
