# Tiến độ V-Phone

- Chuẩn hóa ứng dụng theo MVC; gom giỏ hàng về một controller và view.
- Nâng cấp admin: sửa sản phẩm, tồn kho, đơn hàng, khách hàng, thương hiệu, voucher và tin tức.
- Thêm voucher checkout; lưu bài viết vào database và hỗ trợ bản nháp/xuất bản.
- Khôi phục 58 sản phẩm từ dump dự phòng; sửa mapping hãng và đổi tên thành “iPhone Duo”. File SQL hiện cũng seed lại catalog.
- Tối ưu một số truy vấn admin và tra cứu email.

**Lưu ý:** `database/db_phone_store.sql` có lệnh xóa rồi tạo lại bảng. Sao lưu database trước khi import lại.
