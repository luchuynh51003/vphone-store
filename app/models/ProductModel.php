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

    public function getAll(int $brandId = 0, string $keyword = ''): array {
        $this->ensureStorageOptionsColumn();
        $this->ensureColorQuantitiesColumn();
        $sql = "SELECT p.*, b.name as brand_name FROM products p LEFT JOIN brands b ON p.brand_id = b.id WHERE 1=1";
        $params = [];
        if ($brandId > 0) { $sql .= " AND p.brand_id = :bid"; $params[':bid'] = $brandId; }
        if (!empty($keyword)) { $sql .= " AND p.name LIKE :kw"; $params[':kw'] = '%' . $keyword . '%'; }
        $sql .= " ORDER BY p.is_featured DESC, p.id ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getUsed(int $brandId = 0, string $keyword = ''): array {
        $this->ensureStorageOptionsColumn();
        $this->ensureColorQuantitiesColumn();
        $sql = "SELECT p.*, b.name as brand_name FROM products p LEFT JOIN brands b ON p.brand_id = b.id WHERE p.is_used = 1";
        $params = [];
        if ($brandId > 0) { $sql .= " AND p.brand_id = :bid"; $params[':bid'] = $brandId; }
        if (!empty($keyword)) { $sql .= " AND p.name LIKE :kw"; $params[':kw'] = '%' . $keyword . '%'; }
        $sql .= " ORDER BY p.id DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
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

