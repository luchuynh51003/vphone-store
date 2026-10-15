<footer class="footer-vphone pt-5 pb-3 mt-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4 col-md-6">
                <h4 class="fw-bold text-white d-flex align-items-center mb-3">
                    <img src="assets/images/vphone-logo.svg" alt="V-Phone" style="width: 32px; height: 32px; margin-right: 8px;">
                    V-Phone Store
                </h4>
                <p class="small text-light opacity-75">Hệ thống phân phối điện thoại thông minh chính hãng hàng đầu. Cam kết chất lượng, bảo hành 1 đổi 1 nhanh chóng.</p>
                <div class="p-2 rounded-3 bg-white bg-opacity-10 small text-light mt-3">
                    <i class="fa-solid fa-graduation-cap me-2 text-info"></i>Đồ án: <strong>Huỳnh Bá Lực & Võ Minh Hiếu</strong>
                </div>
            </div>

            <div class="col-lg-4 col-md-6">
                <h6 class="text-uppercase fw-bold text-white mb-3 tracking-wide">Hỗ Trợ Khách Hàng</h6>
                <ul class="list-unstyled small opacity-75 d-flex flex-column gap-2">
                    <li><a href="index.php?page=orders" class="text-light text-decoration-none"><i class="fa-solid fa-angle-right me-2 text-info"></i>Tra cứu & theo dõi tiến trình đơn hàng</a></li>
                    <li><a href="index.php?page=warranty" class="text-light text-decoration-none"><i class="fa-solid fa-angle-right me-2 text-info"></i>Chính sách bảo hành toàn diện 12 tháng</a></li>
                    <li><a href="index.php?page=warranty#doi-tra" class="text-light text-decoration-none"><i class="fa-solid fa-angle-right me-2 text-info"></i>Chính sách đổi trả 1 - 1 trong 30 ngày</a></li>
                </ul>

            </div>

            <div class="col-lg-4 col-md-12">
                <h6 class="text-uppercase fw-bold text-white mb-3 tracking-wide">Tổng Đài Liên Hệ</h6>
                <div class="d-flex flex-column gap-2 small opacity-75">
                    <p class="mb-0"><i class="fa-solid fa-phone-volume me-2 text-info"></i>Hotline: <strong>1800 6868</strong> (Miễn phí 8:00 - 21:30)</p>
                    <p class="mb-0"><i class="fa-solid fa-envelope me-2 text-info"></i>Email: cskh@vphone.vn</p>
                </div>
            </div>
        </div>

        <hr class="border-light opacity-25 my-4">

        <div class="d-flex flex-wrap justify-content-between align-items-center small text-light opacity-50">
            <div>&copy; 2026 <strong>V-Phone Store</strong>. All rights reserved.</div>
            <div>Đồ án Chuyên ngành Công Nghệ Thông Tin</div>
        </div>
    </div>
</footer>

<div id="vphoneLiveToast" role="alert" aria-live="assertive" aria-atomic="true">
    <div style="display:flex; align-items:center;">
        <div class="toast-icon-wrap" style="width:42px; height:42px; border-radius:50%; background:#e8f3ff; display:flex; align-items:center; justify-content:center; margin-right:12px; flex-shrink:0;">
            <i class="fa-solid fa-check" style="color:#0066cc; font-size:17px;"></i>
        </div>
        <div style="flex-grow:1; min-width:0;">
            <div id="vphoneToastText" style="font-weight:700; font-size:13.5px; color:#1e293b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">Đã thêm vào giỏ!</div>
            <a href="index.php?page=cart" style="color:#0066cc; text-decoration:none; font-size:12.5px; font-weight:600;">Xem giỏ hàng ngay &rarr;</a>
        </div>
        <button type="button" style="background:none; border:none; color:#94a3b8; font-size:22px; line-height:1; cursor:pointer; padding: 0 4px;" onclick="document.getElementById('vphoneLiveToast').style.display='none'">&times;</button>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js?v=20261015_v3"></script>
</body>
</html>
