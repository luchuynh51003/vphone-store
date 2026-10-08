<?php
class ProductModel {
    private PDO $pdo;
    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function getAll(int $brandId = 0, string $keyword = ''): array {
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
        $stmt = $this->pdo->prepare("SELECT p.*, b.name as brand_name FROM products p LEFT JOIN brands b ON p.brand_id = b.id WHERE p.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getRelated(int $brandId, int $currentId): array {
        $stmt = $this->pdo->prepare("SELECT * FROM products WHERE brand_id = ? AND id != ? LIMIT 4");
        $stmt->execute([$brandId, $currentId]);
        return $stmt->fetchAll();
    }

    public function deductStock(int $id, int $qty): bool {
        $stmt = $this->pdo->prepare("UPDATE products SET quantity = GREATEST(0, quantity - ?) WHERE id = ?");
        return $stmt->execute([$qty, $id]);
    }
}
