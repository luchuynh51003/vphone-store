<?php
class ReviewModel {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->ensureTableAndSeeds();
    }

    public function ensureTableAndSeeds(): void {
        if (!empty($_SESSION['reviews_table_verified'])) {
            return;
        }

        $sql = "
        CREATE TABLE IF NOT EXISTS product_reviews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            user_id INT NULL,
            fullname VARCHAR(100) NOT NULL,
            email VARCHAR(100) NULL,
            phone VARCHAR(20) NULL,
            rating TINYINT NOT NULL DEFAULT 5,
            comment TEXT NOT NULL,
            is_buyer TINYINT(1) DEFAULT 0,
            status TINYINT(1) DEFAULT 1,
            admin_reply TEXT NULL,
            admin_reply_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_product_id (product_id),
            INDEX idx_user_id (user_id),
            INDEX idx_rating (rating),
            INDEX idx_status (status),
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";

        try {
            $this->pdo->exec($sql);

            $count = (int)$this->pdo->query("SELECT COUNT(*) FROM product_reviews")->fetchColumn();
            if ($count === 0) {
                $this->seedInitialReviews();
            }

            $_SESSION['reviews_table_verified'] = true;
        } catch (PDOException $e) {
            // Ignore if already created
        }
    }

    private function seedInitialReviews(): void {
        $seeds = [
            // iPhone 18 Pro Max (ID 1)
            [
                'product_id' => 1,
                'user_id' => 1,
                'fullname' => 'Võ Minh Hiếu',
                'email' => 'hieu@vphone.vn',
                'phone' => '0912345678',
                'rating' => 5,
                'comment' => 'Máy quá đỉnh! Khung titan nhôm cầm đầm tay, vi xử lý A20 Pro chạy cực mát. Màn hình Promotion siêu mượt, nhận hàng sau 1h tại TP.HCM. Rất hài lòng với V-Phone!',
                'is_buyer' => 1,
                'admin_reply' => 'Dạ V-Phone cảm ơn anh Hiếu rất nhiều đã luôn tin tưởng và ủng hộ siêu phẩm tại V-Phone. Chúc anh có những trải nghiệm tuyệt vời cùng máy ạ!',
                'admin_reply_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 days'))
            ],
            [
                'product_id' => 1,
                'user_id' => null,
                'fullname' => 'Trần Hải Đăng',
                'email' => 'dang.tran@gmail.com',
                'phone' => '0933123456',
                'rating' => 5,
                'comment' => 'Camera chụp đêm sắc nét không bị bệt màu, zoom quang học 10x chi tiết rõ mồn một. Shop tư vấn nhiệt tình, đóng gói cẩn thận 2 lớp chống sốc.',
                'is_buyer' => 1,
                'admin_reply' => 'V-Phone cảm ơn anh Đăng đã đánh giá 5 sao. Bộ quà tặng ốp lưng và dán cường lực VIP đã kích hoạt bảo hành điện tử chính hãng 12 tháng rồi anh nhé!',
                'admin_reply_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
                'created_at' => date('Y-m-d H:i:s', strtotime('-3 days'))
            ],
            [
                'product_id' => 1,
                'user_id' => null,
                'fullname' => 'Nguyễn Bích Phương',
                'email' => 'phuong.bich@gmail.com',
                'phone' => '0988776655',
                'rating' => 5,
                'comment' => 'Màu sắc bên ngoài đẹp hơn trong hình nhiều, máy nguyên seal nguyên kiện tem mác đầy đủ. Đổi từ 13 Pro Max lên thấy pin trâu gấp rưỡi.',
                'is_buyer' => 1,
                'admin_reply' => null,
                'admin_reply_at' => null,
                'created_at' => date('Y-m-d H:i:s', strtotime('-4 days'))
            ],

            // iPhone Duo (ID 3)
            [
                'product_id' => 3,
                'user_id' => 2,
                'fullname' => 'Huỳnh Bá Lực',
                'email' => 'luc@vphone.vn',
                'phone' => '0909888999',
                'rating' => 5,
                'comment' => 'Bản gập đầu tiên của Apple thực sự là bước nhảy vọt, bản lề titan gập rất chắc chắn và gần như không thấy nếp nhăn. Cảm giác cầm sang trọng bậc nhất!',
                'is_buyer' => 1,
                'admin_reply' => 'Cảm ơn anh Lực đã là một trong những khách hàng đầu tiên sở hữu iPhone Duo tại Việt Nam! V-Phone có kỹ thuật viên hỗ trợ tận nơi 24/7 ạ.',
                'admin_reply_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 days'))
            ],
            [
                'product_id' => 3,
                'user_id' => null,
                'fullname' => 'Lê Quốc Hưng',
                'email' => 'hung.le@fpt.vn',
                'phone' => '0977223344',
                'rating' => 5,
                'comment' => 'Màn hình OLED mở rộng đa nhiệm cực tốt, đọc tài liệu và xem biểu đồ kinh doanh mượt mà. Đáng đồng tiền bát gạo.',
                'is_buyer' => 0,
                'admin_reply' => null,
                'admin_reply_at' => null,
                'created_at' => date('Y-m-d H:i:s', strtotime('-5 days'))
            ],

            // Galaxy Z Tri-Fold (ID 5)
            [
                'product_id' => 5,
                'user_id' => null,
                'fullname' => 'Hoàng Minh Tuấn',
                'email' => 'tuan.hm@gmail.com',
                'phone' => '0918112233',
                'rating' => 5,
                'comment' => 'Gập 3 màn hình công nghệ tương lai! Khi mở full ra như một chiếc tablet 10.2 inch, làm việc và vẽ đồ họa cùng S-Pen rất tiện lợi. Ship giao hỏa tốc 2 tiếng đúng cam kết.',
                'is_buyer' => 1,
                'admin_reply' => 'Cảm ơn anh Tuấn! Dòng máy Tri-Fold được tặng kèm gói bảo hiểm rơi vỡ toàn diện 1 năm độc quyền từ V-Phone anh nhé!',
                'admin_reply_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
                'created_at' => date('Y-m-d H:i:s', strtotime('-3 days'))
            ],

            // Galaxy S26 Ultra (ID 6)
            [
                'product_id' => 6,
                'user_id' => null,
                'fullname' => 'Phạm Gia Bảo',
                'email' => 'giabao.pham@gmail.com',
                'phone' => '0903445566',
                'rating' => 5,
                'comment' => 'Chip Snapdragon 8 Gen 5 chiến game mượt khỏi bàn, AI dịch thuật trực tiếp trong cuộc gọi siêu tiện. Màn hình phẳng viền siêu mỏng, bút S-Pen viết rất êm tay.',
                'is_buyer' => 1,
                'admin_reply' => 'Dạ V-Phone cảm ơn anh Bảo đã tin tưởng lựa chọn Galaxy S26 Ultra chính hãng tại V-Phone. Chúc anh có những trải nghiệm công nghệ đỉnh cao!',
                'admin_reply_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 days'))
            ],
            [
                'product_id' => 6,
                'user_id' => null,
                'fullname' => 'Ngô Thùy Trang',
                'email' => 'thuytrang.ngo@yahoo.com',
                'phone' => '0938667788',
                'rating' => 4,
                'comment' => 'Máy chụp chân dung rất đẹp, pin trâu dùng cả ngày xem phim lướt web còn hơn 40%. Trừ 1 sao vì hộp không kèm củ sạc nhưng được shop tặng kèm sạc nhanh 45W bù lại.',
                'is_buyer' => 1,
                'admin_reply' => 'Dạ cảm ơn chị Trang nhiều ạ. Do chính sách chung của hãng nên hộp máy không kèm sạc, V-Phone đã tặng ngay củ sạc 45W chính hãng để chị an tâm sử dụng tốt nhất ạ!',
                'admin_reply_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
                'created_at' => date('Y-m-d H:i:s', strtotime('-4 days'))
            ],

            // Xiaomi 16 Ultra (ID 8)
            [
                'product_id' => 8,
                'user_id' => null,
                'fullname' => 'Đặng Anh Khoa',
                'email' => 'khoa.dang@gmail.com',
                'phone' => '0944556677',
                'rating' => 5,
                'comment' => 'Ống kính Leica chụp ảnh màu sắc cổ điển cực kỳ có hồn, cảm biến 1 inch thu sáng ban đêm xuất sắc. Giao diện mượt mà, sạc 120W chỉ 18 phút là đầy pin!',
                'is_buyer' => 1,
                'admin_reply' => 'V-Phone cảm ơn anh Khoa! Đội ngũ Leica đã tối ưu thuật toán xử lý ảnh thế hệ mới nhất cho dòng 16 Ultra, chúc anh có thật nhiều bức ảnh đẹp!',
                'admin_reply_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
                'created_at' => date('Y-m-d H:i:s', strtotime('-5 days'))
            ],

            // iPhone 17 Pro Max (ID 10)
            [
                'product_id' => 10,
                'user_id' => null,
                'fullname' => 'Nguyễn Minh Khang',
                'email' => 'khang.minh@outlook.com',
                'phone' => '0908771122',
                'rating' => 5,
                'comment' => 'Màu sắc Titan sa mạc quá sang, máy chạy mát lạnh dù quay video 4K liên tục. Nhân viên giao hàng kiểm tra kỹ lưỡng kích hoạt bảo hành tại chỗ.',
                'is_buyer' => 1,
                'admin_reply' => 'V-Phone xin chân thành cảm ơn anh Khang đã ủng hộ! Nếu cần hỗ trợ chuyển đổi dữ liệu hay phụ kiện anh liên hệ hotline 1800 6868 bất cứ lúc nào nhé.',
                'admin_reply_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'created_at' => date('Y-m-d H:i:s', strtotime('-3 days'))
            ],

            // iPhone 17 Pro (ID 11)
            [
                'product_id' => 11,
                'user_id' => 2,
                'fullname' => 'Huỳnh Bá Lực',
                'email' => 'luc@vphone.vn',
                'phone' => '0909888999',
                'rating' => 5,
                'comment' => 'Kích thước 6.3 inch cầm vừa tay, Dynamic Island tiện lợi. Đơn hàng trước mình đặt giao rất chuẩn chỉ, máy nguyên seal chưa active.',
                'is_buyer' => 1,
                'admin_reply' => 'Dạ cảm ơn anh Lực rất nhiều đã quay lại ủng hộ đơn hàng tiếp theo tại hệ thống V-Phone Store!',
                'admin_reply_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
                'created_at' => date('Y-m-d H:i:s', strtotime('-4 days'))
            ],

            // iPhone 16 Pro Max (ID 14)
            [
                'product_id' => 14,
                'user_id' => null,
                'fullname' => 'Lý Gia Hân',
                'email' => 'giahan.ly@gmail.com',
                'phone' => '0932889900',
                'rating' => 5,
                'comment' => 'Giá hiện tại quá hời cho một chiếc Flagship toàn diện. Nút Camera Control chụp nhanh rất nhạy, màn hình to 6.9 inch xem phim đã mắt.',
                'is_buyer' => 1,
                'admin_reply' => 'Cảm ơn chị Hân đã dành thời gian gửi lời đánh giá 5 sao cho V-Phone! Chúc chị có những phút giây trải nghiệm thật vui cùng máy.',
                'admin_reply_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
                'created_at' => date('Y-m-d H:i:s', strtotime('-6 days'))
            ],

            // Galaxy S24 Ultra (ID 26)
            [
                'product_id' => 26,
                'user_id' => null,
                'fullname' => 'Bùi Tiến Dũng',
                'email' => 'tiendung.bui@gmail.com',
                'phone' => '0917228899',
                'rating' => 5,
                'comment' => 'Máy dùng rất mượt, viền titan nhám chống bám vân tay tốt. Tính năng khoanh tròn để tìm kiếm bằng AI cực tiện dụng khi lướt web.',
                'is_buyer' => 1,
                'admin_reply' => null,
                'admin_reply_at' => null,
                'created_at' => date('Y-m-d H:i:s', strtotime('-5 days'))
            ]
        ];

        $stmt = $this->pdo->prepare("
            INSERT INTO product_reviews 
            (product_id, user_id, fullname, email, phone, rating, comment, is_buyer, status, admin_reply, admin_reply_at, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?)
        ");

        foreach ($seeds as $s) {
            $stmt->execute([
                $s['product_id'],
                $s['user_id'],
                $s['fullname'],
                $s['email'],
                $s['phone'],
                $s['rating'],
                $s['comment'],
                $s['is_buyer'],
                $s['admin_reply'],
                $s['admin_reply_at'],
                $s['created_at']
            ]);
        }
    }

    public function getRatingSummary(int $productId): array {
        $stmt = $this->pdo->prepare("
            SELECT rating, COUNT(*) as cnt 
            FROM product_reviews 
            WHERE product_id = ? AND status = 1 
            GROUP BY rating
        ");
        $stmt->execute([$productId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalReviews = 0;
        $totalPoints = 0;
        $starsCount = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];

        foreach ($rows as $r) {
            $star = (int)$r['rating'];
            $cnt = (int)$r['cnt'];
            if (isset($starsCount[$star])) {
                $starsCount[$star] = $cnt;
            }
            $totalReviews += $cnt;
            $totalPoints += ($star * $cnt);
        }

        $avgRating = $totalReviews > 0 ? round($totalPoints / $totalReviews, 1) : 5.0;

        $starsPercent = [];
        foreach ($starsCount as $st => $c) {
            $starsPercent[$st] = $totalReviews > 0 ? round(($c / $totalReviews) * 100) : 0;
        }

        return [
            'avg_rating' => $avgRating,
            'total_reviews' => $totalReviews,
            'stars_count' => $starsCount,
            'stars_percent' => $starsPercent,
            'has_reviews' => ($totalReviews > 0)
        ];
    }

    public function getSummaryMapForAllProducts(): array {
        $stmt = $this->pdo->query("
            SELECT product_id, ROUND(AVG(rating), 1) as avg_rating, COUNT(*) as review_count
            FROM product_reviews
            WHERE status = 1
            GROUP BY product_id
        ");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $map = [];
        foreach ($rows as $r) {
            $map[(int)$r['product_id']] = [
                'avg_rating' => (float)$r['avg_rating'],
                'review_count' => (int)$r['review_count']
            ];
        }
        return $map;
    }

    public function getReviewsByProduct(int $productId, ?int $filterStar = null, bool $onlyWithReply = false, int $limit = 50): array {
        $sql = "SELECT * FROM product_reviews WHERE product_id = ? AND status = 1";
        $params = [$productId];

        if ($filterStar !== null && $filterStar >= 1 && $filterStar <= 5) {
            $sql .= " AND rating = ?";
            $params[] = $filterStar;
        }

        if ($onlyWithReply) {
            $sql .= " AND admin_reply IS NOT NULL AND admin_reply != ''";
        }

        $sql .= " ORDER BY created_at DESC LIMIT " . (int)$limit;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function hasUserPurchased(int $productId, ?int $userId = null, ?string $phone = null): bool {
        if (empty($userId) && empty($phone)) {
            return false;
        }

        $sql = "
            SELECT COUNT(*) 
            FROM orders o
            JOIN order_details od ON o.id = od.order_id
            WHERE od.product_id = :pid AND o.status NOT IN ('Đã hủy')
        ";
        $params = [':pid' => $productId];

        if ($userId && $phone) {
            $sql .= " AND (o.user_id = :uid OR o.phone = :phone)";
            $params[':uid'] = $userId;
            $params[':phone'] = $phone;
        } elseif ($userId) {
            $sql .= " AND o.user_id = :uid";
            $params[':uid'] = $userId;
        } else {
            $sql .= " AND o.phone = :phone";
            $params[':phone'] = $phone;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return ((int)$stmt->fetchColumn()) > 0;
    }

    public function addReview(array $data): int {
        $productId = (int)$data['product_id'];
        $userId = !empty($data['user_id']) ? (int)$data['user_id'] : null;
        $fullname = trim($data['fullname'] ?? '');
        $email = trim($data['email'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $rating = max(1, min(5, (int)($data['rating'] ?? 5)));
        $comment = trim($data['comment'] ?? '');

        // Kiểm tra xem khách hàng đã mua sản phẩm này chưa
        $isBuyer = !empty($data['is_buyer']) ? 1 : 0;
        if (!$isBuyer) {
            $isBuyer = $this->hasUserPurchased($productId, $userId, $phone) ? 1 : 0;
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO product_reviews 
            (product_id, user_id, fullname, email, phone, rating, comment, is_buyer, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
        ");
        $stmt->execute([$productId, $userId, $fullname, $email, $phone, $rating, $comment, $isBuyer]);
        return (int)$this->pdo->lastInsertId();
    }

    public function getAllReviews(string $keyword = '', ?int $rating = null, $status = null, ?int $productId = null, ?bool $isBuyer = null): array {
        $sql = "
            SELECT r.*, p.name as product_name, p.image as product_image, p.price as product_price
            FROM product_reviews r
            JOIN products p ON r.product_id = p.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($keyword)) {
            $sql .= " AND (r.fullname LIKE ? OR r.phone LIKE ? OR r.email LIKE ? OR r.comment LIKE ? OR p.name LIKE ?)";
            $kw = "%{$keyword}%";
            $params[] = $kw;
            $params[] = $kw;
            $params[] = $kw;
            $params[] = $kw;
            $params[] = $kw;
        }

        if ($rating !== null && $rating >= 1 && $rating <= 5) {
            $sql .= " AND r.rating = ?";
            $params[] = $rating;
        }

        if ($status !== null && $status !== '' && $status !== 'all') {
            if ($status === 'unreplied' || $status === 'pending') {
                $sql .= " AND (r.admin_reply IS NULL OR r.admin_reply = '') AND r.status = 1";
            } elseif ($status === 'replied') {
                $sql .= " AND (r.admin_reply IS NOT NULL AND r.admin_reply != '')";
            } elseif (is_numeric($status)) {
                $sql .= " AND r.status = ?";
                $params[] = (int)$status;
            }
        }

        if ($productId !== null && $productId > 0) {
            $sql .= " AND r.product_id = ?";
            $params[] = $productId;
        }

        if ($isBuyer !== null) {
            $sql .= " AND r.is_buyer = ?";
            $params[] = $isBuyer ? 1 : 0;
        }

        $sql .= " ORDER BY r.id DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getReviewById(int $id): ?array {
        $stmt = $this->pdo->prepare("
            SELECT r.*, p.name as product_name, p.image as product_image
            FROM product_reviews r
            JOIN products p ON r.product_id = p.id
            WHERE r.id = ?
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function updateStatus(int $id, int $status): bool {
        $stmt = $this->pdo->prepare("UPDATE product_reviews SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    public function replyReview(int $id, string $adminReply): bool {
        $stmt = $this->pdo->prepare("
            UPDATE product_reviews 
            SET admin_reply = ?, admin_reply_at = NOW() 
            WHERE id = ?
        ");
        return $stmt->execute([trim($adminReply), $id]);
    }

    public function deleteReview(int $id): bool {
        $stmt = $this->pdo->prepare("DELETE FROM product_reviews WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getAdminStats(): array {
        $totalReviews = (int)$this->pdo->query("SELECT COUNT(*) FROM product_reviews")->fetchColumn();
        $avgScore = (float)$this->pdo->query("SELECT ROUND(AVG(rating), 1) FROM product_reviews WHERE status = 1")->fetchColumn() ?: 5.0;
        $fiveStarCount = (int)$this->pdo->query("SELECT COUNT(*) FROM product_reviews WHERE rating = 5")->fetchColumn();
        $hiddenCount = (int)$this->pdo->query("SELECT COUNT(*) FROM product_reviews WHERE status = 0")->fetchColumn();
        $repliedCount = (int)$this->pdo->query("SELECT COUNT(*) FROM product_reviews WHERE admin_reply IS NOT NULL AND admin_reply != ''")->fetchColumn();
        $buyerReviewsCount = (int)$this->pdo->query("SELECT COUNT(*) FROM product_reviews WHERE is_buyer = 1")->fetchColumn();
        $unrepliedCount = (int)$this->pdo->query("SELECT COUNT(*) FROM product_reviews WHERE (admin_reply IS NULL OR admin_reply = '') AND status = 1")->fetchColumn();

        return [
            'total_reviews' => $totalReviews,
            'avg_score' => $avgScore,
            'five_star_count' => $fiveStarCount,
            'hidden_count' => $hiddenCount,
            'replied_count' => $repliedCount,
            'buyer_reviews_count' => $buyerReviewsCount,
            'unreplied_count' => $unrepliedCount
        ];
    }
}

