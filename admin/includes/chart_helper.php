<?php
/**
 * Helper xử lý dữ liệu biểu đồ và thống kê chi tiết theo thời gian cho Admin Dashboard V-Phone
 */

if (!function_exists('getDashboardChartData')) {
    function getDashboardChartData($pdo, $params = []) {
        $filterType = $params['type'] ?? 'preset';
        $preset = $params['preset'] ?? '7days';

        $labels = [];
        $keys = [];
        $title = "7 ngày gần nhất";
        $groupBy = "DATE(o.created_at)";
        $whereClause = "";
        $whereParams = [];

        $now = new DateTime("now");

        if ($filterType === "preset") {
            if ($preset === "30days") {
                $title = "30 ngày gần nhất";
                for ($i = 29; $i >= 0; $i--) {
                    $d = (clone $now)->modify("-$i days");
                    $key = $d->format("Y-m-d");
                    $keys[] = $key;
                    $labels[] = $d->format("d/m");
                }
                $startDate = (clone $now)->modify("-29 days")->format("Y-m-d 00:00:00");
                $endDate = $now->format("Y-m-d 23:59:59");
                $whereClause = "o.created_at BETWEEN ? AND ?";
                $whereParams = [$startDate, $endDate];
            } elseif ($preset === "today") {
                $title = "Hôm nay (" . $now->format("d/m/Y") . ")";
                for ($h = 0; $h < 24; $h++) {
                    $keys[] = sprintf("%02d", $h);
                    $labels[] = sprintf("%02dh", $h);
                }
                $groupBy = "DATE_FORMAT(o.created_at, '%H')";
                $startDate = $now->format("Y-m-d 00:00:00");
                $endDate = $now->format("Y-m-d 23:59:59");
                $whereClause = "o.created_at BETWEEN ? AND ?";
                $whereParams = [$startDate, $endDate];
            } elseif ($preset === "this_month") {
                $title = "Tháng này (" . $now->format("m/Y") . ")";
                $daysInMonth = (int)$now->format("t");
                for ($d = 1; $d <= $daysInMonth; $d++) {
                    $key = $now->format("Y-m-") . sprintf("%02d", $d);
                    $keys[] = $key;
                    $labels[] = sprintf("%02d/%s", $d, $now->format("m"));
                }
                $startDate = $now->format("Y-m-01 00:00:00");
                $endDate = $now->format("Y-m-t 23:59:59");
                $whereClause = "o.created_at BETWEEN ? AND ?";
                $whereParams = [$startDate, $endDate];
            } elseif ($preset === "last_month") {
                $lastM = (clone $now)->modify("first day of last month");
                $daysInMonth = (int)$lastM->format("t");
                $title = "Tháng trước (" . $lastM->format("m/Y") . ")";
                for ($d = 1; $d <= $daysInMonth; $d++) {
                    $key = $lastM->format("Y-m-") . sprintf("%02d", $d);
                    $keys[] = $key;
                    $labels[] = sprintf("%02d/%s", $d, $lastM->format("m"));
                }
                $startDate = $lastM->format("Y-m-01 00:00:00");
                $endDate = $lastM->format("Y-m-t 23:59:59");
                $whereClause = "o.created_at BETWEEN ? AND ?";
                $whereParams = [$startDate, $endDate];
            } elseif ($preset === "this_year") {
                $title = "Năm nay (" . $now->format("Y") . ")";
                for ($m = 1; $m <= 12; $m++) {
                    $keys[] = sprintf("%02d", $m);
                    $labels[] = "Tháng " . $m;
                }
                $groupBy = "DATE_FORMAT(o.created_at, '%m')";
                $startDate = $now->format("Y-01-01 00:00:00");
                $endDate = $now->format("Y-12-31 23:59:59");
                $whereClause = "o.created_at BETWEEN ? AND ?";
                $whereParams = [$startDate, $endDate];
            } else { // 7days default
                $preset = "7days";
                $title = "7 ngày gần nhất";
                for ($i = 6; $i >= 0; $i--) {
                    $d = (clone $now)->modify("-$i days");
                    $key = $d->format("Y-m-d");
                    $keys[] = $key;
                    $labels[] = $d->format("d/m");
                }
                $startDate = (clone $now)->modify("-6 days")->format("Y-m-d 00:00:00");
                $endDate = $now->format("Y-m-d 23:59:59");
                $whereClause = "o.created_at BETWEEN ? AND ?";
                $whereParams = [$startDate, $endDate];
            }
        } elseif ($filterType === "by_month") {
            $m = (int)($params["month"] ?? $now->format("n"));
            $y = (int)($params["year"] ?? $now->format("Y"));
            if ($m < 1 || $m > 12) $m = (int)$now->format("n");
            if ($y < 2020 || $y > 2035) $y = (int)$now->format("Y");
            $targetDate = DateTime::createFromFormat("Y-n-j", "$y-$m-1");
            $daysInMonth = (int)$targetDate->format("t");
            $title = "Tháng " . sprintf("%02d/%04d", $m, $y);
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $key = sprintf("%04d-%02d-%02d", $y, $m, $d);
                $keys[] = $key;
                $labels[] = sprintf("%02d/%02d", $d, $m);
            }
            $startDate = sprintf("%04d-%02d-01 00:00:00", $y, $m);
            $endDate = sprintf("%04d-%02d-%02d 23:59:59", $y, $m, $daysInMonth);
            $whereClause = "o.created_at BETWEEN ? AND ?";
            $whereParams = [$startDate, $endDate];
        } elseif ($filterType === "by_year") {
            $y = (int)($params["year"] ?? $now->format("Y"));
            if ($y < 2020 || $y > 2035) $y = (int)$now->format("Y");
            $title = "Năm $y";
            for ($m = 1; $m <= 12; $m++) {
                $keys[] = sprintf("%02d", $m);
                $labels[] = "Tháng " . $m;
            }
            $groupBy = "DATE_FORMAT(o.created_at, '%m')";
            $startDate = sprintf("%04d-01-01 00:00:00", $y);
            $endDate = sprintf("%04d-12-31 23:59:59", $y);
            $whereClause = "o.created_at BETWEEN ? AND ?";
            $whereParams = [$startDate, $endDate];
        } elseif ($filterType === "by_day") {
            $dateStr = trim($params["date"] ?? "");
            $dObj = DateTime::createFromFormat("Y-m-d", $dateStr);
            if (!$dObj) {
                $dObj = clone $now;
            }
            $title = "Ngày " . $dObj->format("d/m/Y");
            for ($h = 0; $h < 24; $h++) {
                $keys[] = sprintf("%02d", $h);
                $labels[] = sprintf("%02dh:00", $h);
            }
            $groupBy = "DATE_FORMAT(o.created_at, '%H')";
            $startDate = $dObj->format("Y-m-d 00:00:00");
            $endDate = $dObj->format("Y-m-d 23:59:59");
            $whereClause = "o.created_at BETWEEN ? AND ?";
            $whereParams = [$startDate, $endDate];
        } elseif ($filterType === "custom") {
            $startStr = trim($params["start_date"] ?? "");
            $endStr = trim($params["end_date"] ?? "");
            $sObj = DateTime::createFromFormat("Y-m-d", $startStr);
            $eObj = DateTime::createFromFormat("Y-m-d", $endStr);
            if (!$sObj) $sObj = (clone $now)->modify("-6 days");
            if (!$eObj) $eObj = clone $now;
            if ($sObj > $eObj) {
                $tmp = $sObj; $sObj = $eObj; $eObj = $tmp;
            }
            $diffDays = $sObj->diff($eObj)->days;
            $title = "Từ " . $sObj->format("d/m/Y") . " đến " . $eObj->format("d/m/Y");
            $startDate = $sObj->format("Y-m-d 00:00:00");
            $endDate = $eObj->format("Y-m-d 23:59:59");

            if ($diffDays <= 62) {
                $cur = clone $sObj;
                while ($cur <= $eObj) {
                    $key = $cur->format("Y-m-d");
                    $keys[] = $key;
                    $labels[] = $cur->format("d/m");
                    $cur->modify("+1 day");
                }
                $groupBy = "DATE(o.created_at)";
            } else {
                $cur = (clone $sObj)->modify("first day of this month");
                $lastEnd = (clone $eObj)->modify("first day of this month");
                while ($cur <= $lastEnd) {
                    $key = $cur->format("Y-m");
                    $keys[] = $key;
                    $labels[] = "T" . $cur->format("m/Y");
                    $cur->modify("+1 month");
                }
                $groupBy = "DATE_FORMAT(o.created_at, '%Y-%m')";
            }
            $whereClause = "o.created_at BETWEEN ? AND ?";
            $whereParams = [$startDate, $endDate];
        }

        // 1. Dữ liệu phân nhóm cho biểu đồ cột/đường
        $sql = "SELECT $groupBy AS time_slot,
                       COUNT(*) AS order_count,
                       COALESCE(SUM(CASE WHEN o.status = 'Đã giao thành công' THEN o.total_money ELSE 0 END), 0) AS revenue
                FROM orders o
                WHERE $whereClause
                GROUP BY $groupBy";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($whereParams);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $slotMapRev = [];
        $slotMapOrd = [];
        foreach ($rows as $r) {
            $slotMapRev[$r["time_slot"]] = (float)$r["revenue"];
            $slotMapOrd[$r["time_slot"]] = (int)$r["order_count"];
        }

        $revenueData = [];
        $ordersData = [];
        $peakRev = 0;
        $peakLabel = "--";
        foreach ($keys as $idx => $k) {
            $rev = $slotMapRev[$k] ?? 0;
            $ord = $slotMapOrd[$k] ?? 0;
            $revenueData[] = $rev;
            $ordersData[] = $ord;
            if ($rev > $peakRev) {
                $peakRev = $rev;
                $peakLabel = $labels[$idx];
            }
        }

        // 2. Thống kê tỷ lệ trạng thái đơn hàng (Doughnut) trong kỳ
        $statusSql = "SELECT o.status, COUNT(*) as cnt FROM orders o WHERE $whereClause GROUP BY o.status";
        $statusStmt = $pdo->prepare($statusSql);
        $statusStmt->execute($whereParams);
        $statusRaw = $statusStmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $statusDist = [
            "Đã giao thành công" => (int)($statusRaw["Đã giao thành công"] ?? 0),
            "Chờ xử lý" => (int)(($statusRaw["Chờ xử lý"] ?? 0) + ($statusRaw["Đang xử lý"] ?? 0) + ($statusRaw["Chờ thanh toán"] ?? 0)),
            "Đang giao hàng" => (int)($statusRaw["Đang giao hàng"] ?? 0),
            "Đã hủy" => (int)($statusRaw["Đã hủy"] ?? 0),
        ];

        // 3. Danh sách chi tiết các đơn hàng trong kỳ
        $ordersSql = "SELECT o.id, o.fullname, o.phone, o.total_money, o.status, o.payment_method, o.created_at,
                             COALESCE(
                                (SELECT GROUP_CONCAT(CONCAT(od.product_name, ' (x', od.quantity, ')') SEPARATOR ', ')
                                 FROM order_details od WHERE od.order_id = o.id),
                                '(Chưa có thông tin sản phẩm)'
                             ) as products_summary
                      FROM orders o
                      WHERE $whereClause
                      ORDER BY o.created_at DESC, o.id DESC";
        $ordersStmt = $pdo->prepare($ordersSql);
        $ordersStmt->execute($whereParams);
        $rawOrders = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);

        $totalPeriodRev = 0;
        $totalPeriodOrders = count($rawOrders);
        $completedPeriodOrders = 0;
        $ordersList = [];

        foreach ($rawOrders as $o) {
            $tm = (float)$o["total_money"];
            $isCompleted = ($o["status"] === "Đã giao thành công");
            if ($isCompleted) {
                $totalPeriodRev += $tm;
                $completedPeriodOrders++;
            }

            $badgeClass = "bg-secondary";
            if ($o["status"] === "Đã giao thành công") $badgeClass = "bg-success";
            elseif (in_array($o["status"], ["Chờ xử lý", "Chờ thanh toán"])) $badgeClass = "bg-warning text-dark";
            elseif ($o["status"] === "Đang xử lý") $badgeClass = "bg-info text-dark";
            elseif ($o["status"] === "Đang giao hàng") $badgeClass = "bg-primary";
            elseif ($o["status"] === "Đã hủy") $badgeClass = "bg-danger";

            $cTime = strtotime($o["created_at"]);

            // Key tương ứng để liên kết với mốc điểm trên biểu đồ khi click
            $dateKey = date('Y-m-d', $cTime);
            $hourKey = date('H', $cTime);
            $monthKey = date('m', $cTime);
            $yearMonthKey = date('Y-m', $cTime);

            $ordersList[] = [
                "id" => (int)$o["id"],
                "order_code" => "#VP-" . str_pad($o["id"], 2, "0", STR_PAD_LEFT),
                "created_at" => $o["created_at"],
                "created_at_fmt" => date("d/m/Y H:i", $cTime),
                "date_key" => $dateKey,
                "hour_key" => $hourKey,
                "month_key" => $monthKey,
                "year_month_key" => $yearMonthKey,
                "fullname" => $o["fullname"] ?: "Khách vãng lai",
                "phone" => $o["phone"] ?: "--",
                "total_money" => $tm,
                "total_money_fmt" => number_format($tm, 0, ",", ".") . " đ",
                "payment_method" => $o["payment_method"] ?: "COD",
                "status" => $o["status"],
                "badge_class" => $badgeClass,
                "products_summary" => $o["products_summary"]
            ];
        }

        $avgOrderValue = $completedPeriodOrders > 0 ? ($totalPeriodRev / $completedPeriodOrders) : ($totalPeriodOrders > 0 ? ($totalPeriodRev / $totalPeriodOrders) : 0);
        $completedRate = $totalPeriodOrders > 0 ? round(($completedPeriodOrders / $totalPeriodOrders) * 100, 1) : 0;

        return [
            "success" => true,
            "filter_type" => $filterType,
            "preset" => $preset,
            "title" => $title,
            "start_date" => $startDate,
            "end_date" => $endDate,
            "labels" => $labels,
            "raw_keys" => $keys,
            "revenue" => $revenueData,
            "orders" => $ordersData,
            "kpi" => [
                "total_revenue" => $totalPeriodRev,
                "total_revenue_fmt" => number_format($totalPeriodRev, 0, ",", ".") . " đ",
                "total_orders" => $totalPeriodOrders,
                "completed_orders" => $completedPeriodOrders,
                "completed_rate" => $completedRate,
                "avg_order_value" => $avgOrderValue,
                "avg_order_value_fmt" => number_format($avgOrderValue, 0, ",", ".") . " đ",
                "peak_label" => $peakLabel,
                "peak_revenue" => $peakRev,
                "peak_revenue_fmt" => number_format($peakRev, 0, ",", ".") . " đ"
            ],
            "status_distribution" => $statusDist,
            "orders_list" => $ordersList
        ];
    }
}
