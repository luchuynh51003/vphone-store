<?php
class ProductModel {
    private PDO $pdo;
    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function ensureStorageOptionsColumn(): void {
        if (!empty($_SESSION['col_storage_options_verified'])) {
            return;
        }

        try {
            $column = $this->pdo->query("SHOW COLUMNS FROM products LIKE 'storage_options'")->fetch();
            if (!$column) {
                $this->pdo->exec('ALTER TABLE products ADD COLUMN storage_options LONGTEXT NULL');
            }
            $_SESSION['col_storage_options_verified'] = true;
        } catch (\Throwable $e) {}
    }

    public function ensureColorQuantitiesColumn(): void {
        if (!empty($_SESSION['col_color_quantities_verified'])) {
            return;
        }

        try {
            $column = $this->pdo->query("SHOW COLUMNS FROM products LIKE 'color_quantities'")->fetch();
            if (!$column) {
                $this->pdo->exec('ALTER TABLE products ADD COLUMN color_quantities LONGTEXT NULL');
            }
            $_SESSION['col_color_quantities_verified'] = true;
        } catch (\Throwable $e) {}
    }

    public function getAll(int $brandId = 0, string $keyword = '', array $filters = []): array {
        $this->ensureStorageOptionsColumn();
        $this->ensureColorQuantitiesColumn();
        $sql = "SELECT p.*, b.name as brand_name,
                (CASE WHEN p.sale_price > 0 AND p.sale_price < p.price THEN p.sale_price ELSE p.price END) as effective_price
                FROM products p 
                LEFT JOIN brands b ON p.brand_id = b.id 
                WHERE 1=1";
        $params = [];
        if ($brandId > 0) { $sql .= " AND p.brand_id = :bid"; $params[':bid'] = $brandId; }
        if (!empty($keyword)) {
            $kw = mb_strtolower(trim($keyword), 'UTF-8');
            $searchTerms = [$kw];
            if ($kw === 'ss' || $kw === 'sam') {
                $searchTerms[] = 'samsung';
            } elseif ($kw === 'ip') {
                $searchTerms[] = 'iphone';
            } elseif (preg_match('/^ip\s*(.*)$/i', $kw, $m)) {
                $searchTerms[] = 'iphone ' . trim($m[1]);
            } elseif (preg_match('/^ss\s*(.*)$/i', $kw, $m)) {
                $searchTerms[] = 'samsung ' . trim($m[1]);
            }

            $orParts = [];
            foreach ($searchTerms as $idx => $term) {
                $orParts[] = "(p.name LIKE :kw{$idx} OR b.name LIKE :kwb{$idx})";
                $params[":kw{$idx}"] = '%' . $term . '%';
                $params[":kwb{$idx}"] = '%' . $term . '%';
            }
            $sql .= " AND (" . implode(" OR ", $orParts) . ")";
        }

        if (!empty($filters['filter_type'])) {
            if ($filters['filter_type'] === 'flagship') {
                $sql .= " AND p.is_featured = 1";
            } elseif ($filters['filter_type'] === 'used') {
                $sql .= " AND p.is_used = 1";
            }
        }

        if (!empty($filters['price_range'])) {
            switch ($filters['price_range']) {
                case 'under-5m':
                    $sql .= " AND (CASE WHEN p.sale_price > 0 AND p.sale_price < p.price THEN p.sale_price ELSE p.price END) < 5000000";
                    break;
                case '5m-10m':
                    $sql .= " AND (CASE WHEN p.sale_price > 0 AND p.sale_price < p.price THEN p.sale_price ELSE p.price END) BETWEEN 5000000 AND 10000000";
                    break;
                case '10m-20m':
                    $sql .= " AND (CASE WHEN p.sale_price > 0 AND p.sale_price < p.price THEN p.sale_price ELSE p.price END) BETWEEN 10000000 AND 20000000";
                    break;
                case '20m-30m':
                    $sql .= " AND (CASE WHEN p.sale_price > 0 AND p.sale_price < p.price THEN p.sale_price ELSE p.price END) BETWEEN 20000000 AND 30000000";
                    break;
                case 'above-30m':
                    $sql .= " AND (CASE WHEN p.sale_price > 0 AND p.sale_price < p.price THEN p.sale_price ELSE p.price END) > 30000000";
                    break;
            }
        }

        if (!empty($filters['min_price']) && is_numeric($filters['min_price'])) {
            $sql .= " AND (CASE WHEN p.sale_price > 0 AND p.sale_price < p.price THEN p.sale_price ELSE p.price END) >= :min_price";
            $params[':min_price'] = (float)$filters['min_price'];
        }
        if (!empty($filters['max_price']) && is_numeric($filters['max_price'])) {
            $sql .= " AND (CASE WHEN p.sale_price > 0 AND p.sale_price < p.price THEN p.sale_price ELSE p.price END) <= :max_price";
            $params[':max_price'] = (float)$filters['max_price'];
        }

        if (!empty($filters['ram']) && $filters['ram'] !== 'all') {
            $ramNum = (int)filter_var($filters['ram'], FILTER_SANITIZE_NUMBER_INT);
            if ($ramNum > 0) {
                $sql .= " AND (p.ram LIKE :ram_filter OR p.ram = :ram_exact)";
                $params[':ram_filter'] = $ramNum . ' %';
                $params[':ram_exact'] = $ramNum . ' GB';
            }
        }

        if (!empty($filters['rom']) && $filters['rom'] !== 'all') {
            $romNum = (int)filter_var($filters['rom'], FILTER_SANITIZE_NUMBER_INT);
            if ($romNum >= 1000 || stripos((string)$filters['rom'], 'tb') !== false) {
                $sql .= " AND (p.rom LIKE '%1 TB%' OR p.rom LIKE '%1TB%' OR p.name LIKE '%1TB%' OR p.storage_options LIKE '%1 TB%')";
            } elseif ($romNum > 0) {
                $sql .= " AND (p.rom LIKE :rom_filter OR p.name LIKE :rom_filter_name OR p.storage_options LIKE :rom_filter_opt)";
                $params[':rom_filter'] = '%' . $romNum . ' GB%';
                $params[':rom_filter_name'] = '%' . $romNum . 'GB%';
                $params[':rom_filter_opt'] = '%' . $romNum . ' GB%';
            }
        }

        $sort = $filters['sort'] ?? 'default';
        switch ($sort) {
            case 'price-asc':
                $sql .= " ORDER BY effective_price ASC, p.id ASC";
                break;
            case 'price-desc':
                $sql .= " ORDER BY effective_price DESC, p.id ASC";
                break;
            case 'name-asc':
                $sql .= " ORDER BY p.name ASC";
                break;
            case 'newest':
                $sql .= " ORDER BY p.id DESC";
                break;
            case 'default':
            default:
                $sql .= " ORDER BY p.is_featured DESC, p.id ASC";
                break;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getUsed(int $brandId = 0, string $keyword = '', array $filters = []): array {
        $filters['filter_type'] = 'used';
        return $this->getAll($brandId, $keyword, $filters);
    }

    public function getById(int $id): array|false {
        $this->ensureStorageOptionsColumn();
        $this->ensureColorQuantitiesColumn();
        $stmt = $this->pdo->prepare("SELECT p.*, b.name as brand_name FROM products p LEFT JOIN brands b ON p.brand_id = b.id WHERE p.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getRelated(int $brandId, int $currentId): array {
        $stmt = $this->pdo->prepare("SELECT * FROM products WHERE brand_id = ? AND id != ? LIMIT 4");
        $stmt->execute([$brandId, $currentId]);
        return $stmt->fetchAll();
    }

    public function deductStock(int $id, int $qty, string $color = ''): bool {
        $stmt = $this->pdo->prepare("SELECT quantity, color_quantities FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $prod = $stmt->fetch();
        if (!$prod) return false;

        $newQty = max(0, (int)$prod['quantity'] - $qty);
        $colorQuantities = json_decode($prod['color_quantities'] ?? '', true);
        if (is_array($colorQuantities) && !empty($color)) {
            foreach ($colorQuantities as &$cq) {
                if (isset($cq['color']) && mb_strtolower(trim($cq['color'])) === mb_strtolower(trim($color))) {
                    $cq['quantity'] = max(0, (int)$cq['quantity'] - $qty);
                    break;
                }
            }
            $updatedJson = json_encode($colorQuantities, JSON_UNESCAPED_UNICODE);
            $up = $this->pdo->prepare("UPDATE products SET quantity = ?, color_quantities = ? WHERE id = ?");
            return $up->execute([$newQty, $updatedJson, $id]);
        }

        $up = $this->pdo->prepare("UPDATE products SET quantity = ? WHERE id = ?");
        return $up->execute([$newQty, $id]);
    }

    public function restoreStock(int $id, int $qty, string $color = ''): bool {
        $stmt = $this->pdo->prepare("SELECT quantity, color_quantities FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $prod = $stmt->fetch();
        if (!$prod) return false;

        $newQty = (int)$prod['quantity'] + $qty;
        $colorQuantities = json_decode($prod['color_quantities'] ?? '', true);
        if (is_array($colorQuantities) && !empty($color)) {
            foreach ($colorQuantities as &$cq) {
                if (isset($cq['color']) && mb_strtolower(trim($cq['color'])) === mb_strtolower(trim($color))) {
                    $cq['quantity'] = (int)$cq['quantity'] + $qty;
                    break;
                }
            }
            $updatedJson = json_encode($colorQuantities, JSON_UNESCAPED_UNICODE);
            $up = $this->pdo->prepare("UPDATE products SET quantity = ?, color_quantities = ? WHERE id = ?");
            return $up->execute([$newQty, $updatedJson, $id]);
        }

        $up = $this->pdo->prepare("UPDATE products SET quantity = ? WHERE id = ?");
        return $up->execute([$newQty, $id]);
    }
}

