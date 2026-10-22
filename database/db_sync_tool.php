<?php
/**
 * V-Phone Store - Database Sync & Backup Engine
 * Hỗ trợ xuất và nạp dữ liệu tự động giữa các máy (Mac <-> Windows máy trường)
 */

class DatabaseSyncTool {
    /** @var PDO */
    protected $pdo;

    /** @var string */
    protected string $dumpFile = '';

    public function __construct(?PDO $pdo = null) {
        if ($pdo) {
            $this->pdo = $pdo;
        } else {
            global $pdo;
            require_once __DIR__ . '/../config/database.php';
            if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
                $this->pdo = $GLOBALS['pdo'];
            } elseif ($pdo instanceof PDO) {
                $this->pdo = $pdo;
            } else {
                $host = 'localhost';
                $dbname = 'db_phone_store';
                $username = 'root';
                $password = '';
                $this->pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
                $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            }
        }
        $this->dumpFile = __DIR__ . '/db_phone_store.sql';
    }

    /**
     * Xuất toàn bộ cơ sở dữ liệu hiện tại ra file SQL
     */
    public function export(): array {
        try {
            $tables = $this->pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            if (empty($tables)) {
                return ['success' => false, 'message' => 'Không tìm thấy bảng nào trong CSDL!'];
            }

            $now = time();
            $rawDate = date('Y-m-d H:i:s', $now);
            $formattedDate = date('H:i:s - d/m/Y', $now);

            $sql = "-- =========================================================================\n";
            $sql .= "-- CƠ SỞ DỮ LIỆU ĐỒ ÁN V-PHONE STORE (ĐÃ SAO LƯU TỰ ĐỘNG)\n";
            $sql .= "-- Thời gian sao lưu: " . $rawDate . " (" . $formattedDate . ")\n";
            $sql .= "-- =========================================================================\n\n";
            $sql .= "CREATE DATABASE IF NOT EXISTS `db_phone_store` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n";
            $sql .= "USE `db_phone_store`;\n\n";
            $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n";
            $sql .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
            $sql .= "SET time_zone = \"+07:00\";\n\n";

            $totalRows = 0;
            $tableStats = [];

            // 1. DROP TABLES
            foreach ($tables as $t) {
                $sql .= "DROP TABLE IF EXISTS `{$t}`;\n";
            }
            $sql .= "\n";

            // 2. CREATE TABLE & INSERT DATA
            foreach ($tables as $table) {
                // Lấy câu lệnh tạo bảng
                $createStmt = $this->pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_ASSOC);
                $createSql = $createStmt['Create Table'] ?? '';
                $sql .= "-- --------------------------------------------------------\n";
                $sql .= "-- Cấu trúc bảng `{$table}`\n";
                $sql .= "-- --------------------------------------------------------\n";
                $sql .= $createSql . ";\n\n";

                // Lấy dữ liệu
                $rows = $this->pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
                $rowCount = count($rows);
                $totalRows += $rowCount;
                $tableStats[$table] = $rowCount;

                if ($rowCount > 0) {
                    $sql .= "-- Dữ liệu bảng `{$table}` ({$rowCount} dòng)\n";
                    
                    // Insert theo batch để tối ưu tốc độ
                    $batchSize = 100;
                    $chunks = array_chunk($rows, $batchSize);

                    foreach ($chunks as $chunk) {
                        $colNames = array_keys($chunk[0]);
                        $escapedCols = array_map(function($c) { return "`$c`"; }, $colNames);
                        $sql .= "INSERT INTO `{$table}` (" . implode(', ', $escapedCols) . ") VALUES\n";

                        $valLines = [];
                        foreach ($chunk as $row) {
                            $escapedVals = [];
                            foreach ($row as $val) {
                                if ($val === null) {
                                    $escapedVals[] = 'NULL';
                                } elseif (is_numeric($val) && !is_string($val)) {
                                    $escapedVals[] = $val;
                                } else {
                                    $escapedVals[] = $this->pdo->quote($val);
                                }
                            }
                            $valLines[] = "(" . implode(', ', $escapedVals) . ")";
                        }
                        $sql .= implode(",\n", $valLines) . ";\n";
                    }
                    $sql .= "\n";
                }
            }

            $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";

            if (file_exists($this->dumpFile)) {
                @chmod($this->dumpFile, 0777);
            }
            if (is_dir(__DIR__)) {
                @chmod(__DIR__, 0777);
            }

            $written = @file_put_contents($this->dumpFile, $sql);
            if ($written === false) {
                // Thử xóa file cũ rồi ghi lại
                @unlink($this->dumpFile);
                $written = @file_put_contents($this->dumpFile, $sql);
            }

            if ($written === false) {
                return [
                    'success' => false, 
                    'message' => 'Không thể ghi vào file: ' . $this->dumpFile . '. Vui lòng cấp quyền ghi bằng lệnh: chmod -R 777 database/'
                ];
            }

            @chmod($this->dumpFile, 0777);

            return [
                'success' => true,
                'message' => 'Đã xuất Database thành công!',
                'file' => $this->dumpFile,
                'size_bytes' => $written,
                'size_formatted' => round($written / 1024, 2) . ' KB',
                'table_count' => count($tables),
                'total_rows' => $totalRows,
                'stats' => $tableStats,
                'timestamp' => date('d/m/Y H:i:s')
            ];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Lỗi khi xuất CSDL: ' . $e->getMessage()];
        }
    }

    /**
     * Nhập file SQL từ thư mục git vào cơ sở dữ liệu hiện tại
     */
    public function import(): array {
        try {
            if (!file_exists($this->dumpFile)) {
                return ['success' => false, 'message' => 'Không tìm thấy file: ' . $this->dumpFile];
            }

            $sql = file_get_contents($this->dumpFile);
            if (empty($sql)) {
                return ['success' => false, 'message' => 'File sao lưu SQL bị rỗng!'];
            }

            // Đọc thời gian xuất từ header file SQL trước khi nạp
            $exportedAt = $this->getExportTimeFromSqlFile() ?? 'Không xác định';

            // Thực thi SQL nhiều truy vấn
            $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, 1);
            $this->pdo->exec($sql);

            // Kiểm tra lại số lượng bảng sau khi import
            $tables = $this->pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            $orderCount = (int)$this->pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
            $prodCount = (int)$this->pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
            $userCount = (int)$this->pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

            return [
                'success' => true,
                'message' => 'Đã nạp toàn bộ dữ liệu từ Git vào MySQL thành công!',
                'exported_at' => $exportedAt,
                'table_count' => count($tables),
                'orders_count' => $orderCount,
                'products_count' => $prodCount,
                'users_count' => $userCount,
                'timestamp' => date('d/m/Y H:i:s')
            ];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Lỗi khi nạp CSDL: ' . $e->getMessage()];
        }
    }

    /**
     * Đọc thời gian sao lưu chính xác từ header file SQL
     */
    public function getExportTimeFromSqlFile(): ?string {
        if (!file_exists($this->dumpFile)) return null;
        $handle = @fopen($this->dumpFile, 'r');
        if (!$handle) return null;
        $timeStr = null;
        for ($i = 0; $i < 15; $i++) {
            $line = fgets($handle);
            if ($line === false) break;
            if (preg_match('/Thời gian sao lưu:\s*([0-9]{4}-[0-9]{2}-[0-9]{2}\s+[0-9]{2}:[0-9]{2}:[0-9]{2})/u', $line, $matches)) {
                $rawTime = trim($matches[1]);
                $dt = DateTime::createFromFormat('Y-m-d H:i:s', $rawTime);
                $timeStr = $dt ? $dt->format('H:i:s - d/m/Y') : $rawTime;
                break;
            }
        }
        fclose($handle);
        return $timeStr;
    }

    /**
     * Lấy thông tin trạng thái file backup hiện tại
     */
    public function getBackupInfo(): array {
        if (!file_exists($this->dumpFile)) {
            return [
                'exists' => false,
                'file' => $this->dumpFile,
                'message' => 'Chưa có file sao lưu nào trong thư mục database/'
            ];
        }

        $size = filesize($this->dumpFile);
        $mtime = filemtime($this->dumpFile);
        $exportedAt = $this->getExportTimeFromSqlFile();

        return [
            'exists' => true,
            'file' => $this->dumpFile,
            'size_bytes' => $size,
            'size_formatted' => round($size / 1024, 2) . ' KB',
            'exported_at' => $exportedAt ? $exportedAt : date('H:i:s - d/m/Y', $mtime),
            'updated_at' => date('d/m/Y H:i:s', $mtime),
            'timestamp' => $mtime
        ];
    }
}

// Nếu gọi trực tiếp từ CLI: php database/db_sync_tool.php [export|import|status]
if (php_sapi_name() === 'cli' && isset($argv[0]) && realpath($argv[0]) === __FILE__) {
    $action = $argv[1] ?? 'status';
    $tool = new DatabaseSyncTool();

    if ($action === 'export') {
        echo "[DANG XU LY] Dang xuat co so du lieu...\n";
        $res = $tool->export();
        if ($res['success']) {
            echo "[THANH CONG] Xuat thanh cong! File: {$res['file']} ({$res['size_formatted']})\n";
            echo "[THONG KE] Tong bang: {$res['table_count']}, Tong dong: {$res['total_rows']}\n";
        } else {
            echo "[LOI] That bai: {$res['message']}\n";
        }
    } elseif ($action === 'import') {
        echo "[DANG XU LY] Dang nap co so du lieu tu file Git vao MySQL...\n";
        $res = $tool->import();
        if ($res['success']) {
            echo "[THANH CONG] Nap thanh cong!\n";
            echo "[THOI DIEM XUAT] Ban sao luu tao luc: {$res['exported_at']}\n";
            echo "[THONG KE] So bang: {$res['table_count']}, Don hang: {$res['orders_count']}, San pham: {$res['products_count']}, Tai khoan: {$res['users_count']}\n";
        } else {
            echo "[LOI] That bai: {$res['message']}\n";
        }
    } else {
        $info = $tool->getBackupInfo();
        echo "=== THONG TIN BACKUP DATABASE ===\n";
        if ($info['exists']) {
            echo "File: {$info['file']}\n";
            echo "Kich thuoc: {$info['size_formatted']}\n";
            echo "Thoi diem xuat: {$info['exported_at']}\n";
            echo "Lan cap nhat cuoi: {$info['updated_at']}\n";
        } else {
            echo "Chua co file sao luu.\n";
        }
        echo "Cach dung: php database/db_sync_tool.php [export|import]\n";
    }
}
