<?php
/**
 * V-Phone Store - Membership Service
 * Quản lý hạng thành viên và đặc quyền khách hàng
 * Mốc chi tiêu tích lũy:
 * - Silver (Bạc): 100.000.000 VNĐ
 * - Gold (Vàng): 300.000.000 VNĐ
 * - Diamond (Kim Cương): 500.000.000 VNĐ trở lên
 */

class MembershipService {
    public const TIER_STANDARD = 'standard';
    public const TIER_SILVER   = 'silver';
    public const TIER_GOLD     = 'gold';
    public const TIER_DIAMOND  = 'diamond';

    public const THRESHOLD_SILVER  = 100000000; // 100 triệu
    public const THRESHOLD_GOLD    = 300000000; // 300 triệu
    public const THRESHOLD_DIAMOND = 500000000; // 500 triệu

    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Định nghĩa đầy đủ danh sách các hạng và đặc quyền chi tiết
     */
    public static function getTierDefinitions(): array {
        return [
            self::TIER_STANDARD => [
                'key' => self::TIER_STANDARD,
                'name' => 'Thành Viên Tiêu Chuẩn',
                'short_name' => 'Tiêu Chuẩn',
                'badge_label' => 'Thành Viên',
                'min_spent' => 0,
                'max_spent' => self::THRESHOLD_SILVER,
                'discount_percent' => 0.0,
                'color' => '#64748b',
                'gradient' => 'linear-gradient(135deg, #64748b 0%, #475569 100%)',
                'card_bg' => 'linear-gradient(135deg, #334155 0%, #1e293b 100%)',
                'text_color' => '#ffffff',
                'border_color' => '#cbd5e1',
                'icon' => 'fa-solid fa-shield',
                'next_tier' => self::TIER_SILVER,
                'next_tier_spent' => self::THRESHOLD_SILVER,
                'exclusive_vouchers' => [],
                'perks_summary' => 'Ưu đãi cơ bản cho khách hàng mới',
                'benefits' => [
                    [
                        'title' => 'Tích lũy chi tiêu nâng hạng',
                        'desc' => 'Mỗi đơn hàng hoàn tất đều được tích lũy tự động để nâng hạng Silver, Gold, Diamond',
                        'icon' => 'fa-solid fa-arrow-trend-up'
                    ],
                    [
                        'title' => 'Giao hàng tiêu chuẩn miễn phí',
                        'desc' => 'Miễn 100% phí vận chuyển toàn quốc cho mọi hóa đơn',
                        'icon' => 'fa-solid fa-truck'
                    ],
                    [
                        'title' => 'Bảo hành chính hãng 12 tháng',
                        'desc' => 'Hưởng đầy đủ chế độ bảo hành chính hãng từ nhà sản xuất',
                        'icon' => 'fa-solid fa-shield-check'
                    ],
                    [
                        'title' => 'Hỗ trợ kỹ thuật trực tuyến',
                        'desc' => 'Tư vấn kỹ thuật và phần mềm miễn phí suốt vòng đời sản phẩm',
                        'icon' => 'fa-solid fa-headset'
                    ]
                ]
            ],

            self::TIER_SILVER => [
                'key' => self::TIER_SILVER,
                'name' => 'Hạng Bạc (Silver VIP)',
                'short_name' => 'Silver',
                'badge_label' => 'VIP Bạc',
                'min_spent' => self::THRESHOLD_SILVER,
                'max_spent' => self::THRESHOLD_GOLD,
                'discount_percent' => 1.5,
                'color' => '#94a3b8',
                'gradient' => 'linear-gradient(135deg, #94a3b8 0%, #cbd5e1 50%, #64748b 100%)',
                'card_bg' => 'linear-gradient(135deg, #475569 0%, #94a3b8 50%, #334155 100%)',
                'text_color' => '#ffffff',
                'border_color' => '#94a3b8',
                'icon' => 'fa-solid fa-medal',
                'next_tier' => self::TIER_GOLD,
                'next_tier_spent' => self::THRESHOLD_GOLD,
                'exclusive_vouchers' => ['SILVER500K'],
                'perks_summary' => 'Chiết khấu 1.5% đơn hàng & Voucher 500.000đ',
                'benefits' => [
                    [
                        'title' => 'Giảm trực tiếp 1.5% mỗi đơn hàng',
                        'desc' => 'Tự động chiết khấu 1.5% trên tổng giá trị hóa đơn khi thanh toán mọi đơn hàng',
                        'icon' => 'fa-solid fa-percent',
                        'highlight' => true
                    ],
                    [
                        'title' => 'Voucher 500.000 VNĐ chào mừng & sinh nhật',
                        'desc' => 'Mã ưu đãi độc quyền SILVER500K áp dụng cho đơn từ 5.000.000 VNĐ',
                        'icon' => 'fa-solid fa-ticket',
                        'highlight' => true
                    ],
                    [
                        'title' => 'Giao hàng hỏa tốc 2H miễn phí',
                        'desc' => 'Ưu tiên đóng gói và vận chuyển siêu tốc 2 giờ tại nội thành Hà Nội & TP.HCM',
                        'icon' => 'fa-solid fa-bolt'
                    ],
                    [
                        'title' => 'Đổi mới 1 đổi 1 trong 30 ngày',
                        'desc' => 'Tăng gấp đôi thời gian đổi mới sản phẩm nếu có lỗi phần cứng từ nhà sản xuất',
                        'icon' => 'fa-solid fa-arrows-rotate'
                    ],
                    [
                        'title' => 'Giảm 10% khi mua phụ kiện',
                        'desc' => 'Ưu đãi giảm thêm 10% khi mua kèm sạc, ốp lưng, tai nghe và phụ kiện chính hãng',
                        'icon' => 'fa-solid fa-tags'
                    ],
                    [
                        'title' => 'Bảo dưỡng & vệ sinh máy định kỳ',
                        'desc' => 'Miễn phí vệ sinh chuyên sâu, khử khuẩn tia UV định kỳ tại chuỗi cửa hàng V-Phone',
                        'icon' => 'fa-solid fa-spray-can-sparkles'
                    ]
                ]
            ],

            self::TIER_GOLD => [
                'key' => self::TIER_GOLD,
                'name' => 'Hạng Vàng (Gold VIP)',
                'short_name' => 'Gold',
                'badge_label' => 'VIP Vàng',
                'min_spent' => self::THRESHOLD_GOLD,
                'max_spent' => self::THRESHOLD_DIAMOND,
                'discount_percent' => 3.0,
                'color' => '#f59e0b',
                'gradient' => 'linear-gradient(135deg, #f59e0b 0%, #fbbf24 50%, #d97706 100%)',
                'card_bg' => 'linear-gradient(135deg, #78350f 0%, #b45309 50%, #d97706 100%)',
                'text_color' => '#ffffff',
                'border_color' => '#f59e0b',
                'icon' => 'fa-solid fa-crown',
                'next_tier' => self::TIER_DIAMOND,
                'next_tier_spent' => self::THRESHOLD_DIAMOND,
                'exclusive_vouchers' => ['GOLD1500K', 'SILVER500K'],
                'perks_summary' => 'Chiết khấu 3% đơn hàng & Bảo hành mở rộng +6 tháng',
                'benefits' => [
                    [
                        'title' => 'Giảm trực tiếp 3.0% mỗi đơn hàng',
                        'desc' => 'Tự động chiết khấu 3.0% tổng đơn hàng không giới hạn giá trị tối đa',
                        'icon' => 'fa-solid fa-percent',
                        'highlight' => true
                    ],
                    [
                        'title' => 'Voucher 1.500.000 VNĐ chào mừng & sinh nhật',
                        'desc' => 'Mã ưu đãi độc quyền GOLD1500K cho đơn từ 10.000.000 VNĐ',
                        'icon' => 'fa-solid fa-ticket',
                        'highlight' => true
                    ],
                    [
                        'title' => 'Tặng thêm 06 tháng bảo hành chính hãng',
                        'desc' => 'Tổng thời hạn bảo hành lên đến 18 tháng, yên tâm sử dụng máy lâu dài',
                        'icon' => 'fa-solid fa-shield-halved',
                        'highlight' => true
                    ],
                    [
                        'title' => '1 đổi 1 trong 45 ngày đầu',
                        'desc' => 'Thời gian đổi mới phần cứng linh hoạt trong 45 ngày nếu máy gặp lỗi NSX',
                        'icon' => 'fa-solid fa-arrows-rotate'
                    ],
                    [
                        'title' => 'Giảm 20% phụ kiện chính hãng trọn đời',
                        'desc' => 'Ưu đãi đặc quyền 20% cho tất cả phụ kiện Apple, Samsung, Anker, Baseus',
                        'icon' => 'fa-solid fa-tags'
                    ],
                    [
                        'title' => 'Dán cường lực & bảo dưỡng miễn phí 1 năm',
                        'desc' => 'Miễn phí dán cường lực cao cấp không giới hạn số lần trong 12 tháng',
                        'icon' => 'fa-solid fa-gem'
                    ],
                    [
                        'title' => 'Đường dây nóng CSKH VIP ưu tiên',
                        'desc' => 'Được kết nối ưu tiên trực tiếp với chuyên viên tư vấn cao cấp, không phải chờ',
                        'icon' => 'fa-solid fa-phone-volume'
                    ]
                ]
            ],

            self::TIER_DIAMOND => [
                'key' => self::TIER_DIAMOND,
                'name' => 'Hạng Kim Cương (Diamond VIP)',
                'short_name' => 'Diamond',
                'badge_label' => 'VIP Kim Cương',
                'min_spent' => self::THRESHOLD_DIAMOND,
                'max_spent' => null,
                'discount_percent' => 5.0,
                'color' => '#8b5cf6',
                'gradient' => 'linear-gradient(135deg, #8b5cf6 0%, #6366f1 50%, #ec4899 100%)',
                'card_bg' => 'linear-gradient(135deg, #312e81 0%, #4c1d95 50%, #831843 100%)',
                'text_color' => '#ffffff',
                'border_color' => '#c084fc',
                'icon' => 'fa-solid fa-gem',
                'next_tier' => null,
                'next_tier_spent' => null,
                'exclusive_vouchers' => ['DIAMOND3M', 'GOLD1500K', 'SILVER500K'],
                'perks_summary' => 'Đẳng cấp thượng hạng: Chiết khấu 5%, Bảo hiểm VIP & CSKH 1-1',
                'benefits' => [
                    [
                        'title' => 'Giảm trực tiếp 5.0% toàn bộ đơn hàng',
                        'desc' => 'Đặc quyền chiết khấu cao nhất hệ thống, tiết kiệm ngay hàng chục triệu đồng',
                        'icon' => 'fa-solid fa-percent',
                        'highlight' => true
                    ],
                    [
                        'title' => 'Voucher 3.000.000 VNĐ chào mừng & sinh nhật',
                        'desc' => 'Mã ưu đãi độc quyền thượng hạng DIAMOND3M cho đơn từ 15.000.000 VNĐ',
                        'icon' => 'fa-solid fa-ticket',
                        'highlight' => true
                    ],
                    [
                        'title' => 'Gói Bảo Hiểm Rơi Vỡ & Vào Nước Toàn Diện 12 Tháng',
                        'desc' => 'Hỗ trợ 100% chi phí sửa chữa thay thế linh kiện chính hãng nếu máy bị rơi vỡ hay ngấm nước',
                        'icon' => 'fa-solid fa-shield-virus',
                        'highlight' => true
                    ],
                    [
                        'title' => 'Đổi mới 1 đổi 1 trong 90 ngày',
                        'desc' => 'Chính sách đổi mới siêu linh hoạt 90 ngày nếu phát sinh lỗi nhà sản xuất',
                        'icon' => 'fa-solid fa-arrows-rotate',
                        'highlight' => true
                    ],
                    [
                        'title' => 'Đặc quyền Pre-Order nhận máy đầu tiên tại VN',
                        'desc' => 'Ưu tiên bàn giao sớm nhất thị trường khi có siêu phẩm mới ra mắt (iPhone 18, Fold, Tri-Fold)',
                        'icon' => 'fa-solid fa-wand-magic-sparkles',
                        'highlight' => true
                    ],
                    [
                        'title' => 'Quản lý chăm sóc khách hàng 1-1 riêng biệt 24/7',
                        'desc' => 'Có chuyên viên VIP Concierge phục vụ hỗ trợ tận nơi hoặc trực tiếp bất kỳ lúc nào',
                        'icon' => 'fa-solid fa-user-tie'
                    ],
                    [
                        'title' => 'Giảm 30% phụ kiện cao cấp & Dán màn hình trọn đời',
                        'desc' => 'Dán cường lực KingKong, Nano trọn đời máy và chiết khấu 30% phụ kiện',
                        'icon' => 'fa-solid fa-infinity'
                    ],
                    [
                        'title' => 'Hộp quà VIP tri ân sinh nhật & cuối năm',
                        'desc' => 'Quà tặng cao cấp độc quyền từ V-Phone Store gửi tận tay khách hàng VIP',
                        'icon' => 'fa-solid fa-gift'
                    ]
                ]
            ]
        ];
    }

    /**
     * Lấy tổng tiền chi tiêu tích lũy hợp lệ của người dùng
     * (Các đơn hàng đã giao thành công HOẶC đã thanh toán nhưng chưa bị hủy)
     */
    public function getUserSpent(int $userId): float {
        if ($userId <= 0) return 0.0;
        $stmt = $this->pdo->prepare("
            SELECT COALESCE(SUM(total_money), 0)
            FROM orders
            WHERE user_id = ?
              AND (
                  status = 'Đã giao thành công'
                  OR (payment_status = 'Đã thanh toán' AND status != 'Đã hủy')
              )
        ");
        $stmt->execute([$userId]);
        return (float)$stmt->fetchColumn();
    }

    /**
     * Xác định hạng thành viên từ số tiền chi tiêu và giá trị cấu hình thủ công (nếu có)
     */
    public function getTierBySpent(float $spent, ?string $manualTier = null): array {
        $tiers = self::getTierDefinitions();

        // Nếu admin gán thủ công một hạng cụ thể hợp lệ
        if (!empty($manualTier) && $manualTier !== 'auto' && isset($tiers[$manualTier])) {
            $tierKey = $manualTier;
        } else {
            if ($spent >= self::THRESHOLD_DIAMOND) {
                $tierKey = self::TIER_DIAMOND;
            } elseif ($spent >= self::THRESHOLD_GOLD) {
                $tierKey = self::TIER_GOLD;
            } elseif ($spent >= self::THRESHOLD_SILVER) {
                $tierKey = self::TIER_SILVER;
            } else {
                $tierKey = self::TIER_STANDARD;
            }
        }

        $tierData = $tiers[$tierKey];
        $tierData['spent'] = $spent;

        // Tính toán tiến trình thăng hạng
        if (!empty($tierData['next_tier'])) {
            $nextThreshold = (float)$tierData['next_tier_spent'];
            $curThreshold = (float)$tierData['min_spent'];
            $range = max(1.0, $nextThreshold - $curThreshold);
            $tierProgress = max(0.0, min(100.0, (($spent - $curThreshold) / $range) * 100));
            $amountNeeded = max(0.0, $nextThreshold - $spent);

            $tierData['progress_percent'] = round($tierProgress, 1);
            $tierData['amount_needed'] = $amountNeeded;
            $tierData['next_tier_name'] = $tiers[$tierData['next_tier']]['name'];
            $tierData['next_tier_short'] = $tiers[$tierData['next_tier']]['short_name'];
        } else {
            $tierData['progress_percent'] = 100.0;
            $tierData['amount_needed'] = 0.0;
            $tierData['next_tier_name'] = '';
            $tierData['next_tier_short'] = '';
        }

        return $tierData;
    }

    /**
     * Lấy hạng thành viên đầy đủ của người dùng theo user_id
     */
    public function getUserTier(int $userId): array {
        if ($userId <= 0) {
            return $this->getTierBySpent(0);
        }

        $stmt = $this->pdo->prepare("SELECT membership_tier FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $manual = $stmt->fetchColumn() ?: null;

        $spent = $this->getUserSpent($userId);
        return $this->getTierBySpent($spent, $manual);
    }

    /**
     * Tính số tiền chiết khấu theo hạng thành viên
     */
    public function calculateTierDiscount(array $tier, float|int $subtotal): float {
        $percent = (float)($tier['discount_percent'] ?? 0);
        if ($percent <= 0 || $subtotal <= 0) return 0.0;

        $discount = round(($subtotal * $percent) / 100);
        return min((float)$subtotal, max(0.0, $discount));
    }

    /**
     * Kiểm tra xem người dùng có quyền áp dụng mã voucher độc quyền theo hạng không
     */
    public function validateTierVoucher(string $voucherCode, array $userTier): array {
        $code = strtoupper(trim($voucherCode));
        $tierKey = $userTier['key'] ?? self::TIER_STANDARD;

        $tierRank = [
            self::TIER_STANDARD => 0,
            self::TIER_SILVER   => 1,
            self::TIER_GOLD     => 2,
            self::TIER_DIAMOND  => 3,
        ];

        $voucherTiers = [
            'SILVER500K' => ['min_tier' => self::TIER_SILVER, 'name' => 'Hạng Bạc (Silver VIP)'],
            'GOLD1500K'  => ['min_tier' => self::TIER_GOLD,   'name' => 'Hạng Vàng (Gold VIP)'],
            'DIAMOND3M'  => ['min_tier' => self::TIER_DIAMOND,'name' => 'Hạng Kim Cương (Diamond VIP)']
        ];

        if (isset($voucherTiers[$code])) {
            $req = $voucherTiers[$code];
            $userRank = $tierRank[$tierKey] ?? 0;
            $requiredRank = $tierRank[$req['min_tier']] ?? 0;

            if ($userRank < $requiredRank) {
                return [
                    'allowed' => false,
                    'valid' => false,
                    'message' => "Mã $code là đặc quyền riêng dành cho thành viên đạt {$req['name']} trở lên! Hãy tích lũy thêm chi tiêu để nâng hạng và mở khóa ưu đãi này."
                ];
            }
        }

        return ['allowed' => true, 'valid' => true, 'message' => ''];
    }

    /**
     * Đảm bảo các voucher đặc quyền cho hạng thành viên có mặt trong database
     */
    public function ensureTierVouchers(): void {
        try {
            $stmt = $this->pdo->prepare("
                INSERT IGNORE INTO vouchers (code, discount_type, discount_value, minimum_order, max_discount, is_active)
                VALUES (?, ?, ?, ?, ?, 1)
            ");
            $vouchers = [
                ['SILVER500K', 'fixed', 500000, 5000000, null],
                ['GOLD1500K', 'fixed', 1500000, 10000000, null],
                ['DIAMOND3M', 'fixed', 3000000, 15000000, null]
            ];
            foreach ($vouchers as $v) {
                $stmt->execute($v);
            }
        } catch (Throwable $e) {}
    }

    /**
     * Render badge HTML gọn đẹp hiển thị hạng thành viên
     */
    public static function renderBadge(array|string $tierOrKey, string $size = 'sm'): string {
        $tiers = self::getTierDefinitions();
        $key = is_array($tierOrKey) ? ($tierOrKey['key'] ?? self::TIER_STANDARD) : $tierOrKey;
        $tier = $tiers[$key] ?? $tiers[self::TIER_STANDARD];

        $fontSize = match($size) {
            'xs' => '0.68rem',
            'lg' => '0.88rem',
            'xl' => '1rem',
            default => '0.75rem'
        };

        $padding = match($size) {
            'xs' => 'px-2 py-0.5',
            'lg' => 'px-3 py-1.5',
            'xl' => 'px-4 py-2',
            default => 'px-2.5 py-1'
        };

        $icon = $tier['icon'];
        $label = $tier['badge_label'];
        $gradient = $tier['gradient'];

        return sprintf(
            '<span class="badge rounded-pill text-white fw-bold d-inline-flex align-items-center gap-1 shadow-xs %s" style="background: %s; font-size: %s; letter-spacing: 0.3px;"><i class="%s"></i><span>%s</span></span>',
            $padding,
            $gradient,
            $fontSize,
            $icon,
            htmlspecialchars($label)
        );
    }
}
