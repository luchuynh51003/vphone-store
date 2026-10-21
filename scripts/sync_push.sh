#!/bin/bash
# =========================================================================
# V-PHONE STORE - SCRIPT DONG BO VA DAY CODE + DATABASE LEN GITHUB
# =========================================================================

# Chuyen ve thu muc goc cua du an
ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT_DIR" || exit 1

chmod -R 777 database/ 2>/dev/null

echo "========================================================"
echo "[1/3] Dang xuat toan bo Database ra file SQL..."
echo "========================================================"

PHP_BIN="php"
if [ -f "/Applications/XAMPP/xamppfiles/bin/php" ]; then
    PHP_BIN="/Applications/XAMPP/xamppfiles/bin/php"
fi

$PHP_BIN database/db_sync_tool.php export

if [ $? -ne 0 ]; then
    echo "[LOI] Xuat Database that bai! Dung tien trinh."
    exit 1
fi

echo ""
echo "========================================================"
echo "[2/3] Dang commit code va database vao Git..."
echo "========================================================"

git add .
COMMIT_MSG="Dong bo code va CSDL [$(date '+%d/%m/%Y %H:%M:%S')]"
git commit -m "$COMMIT_MSG"

echo ""
echo "========================================================"
echo "[3/3] Dang day len GitHub (git push)..."
echo "========================================================"

git push

echo ""
echo "========================================================"
echo "[THANH CONG] Hoan tat dong bo toan bo du lieu len GitHub!"
echo "Sang may truong, chay: ./scripts/sync_pull.sh (hoac click dup scripts/sync_pull.bat tren Windows)"
echo "hoac vao admin/db_sync.php tren trinh duyet."
echo "========================================================"

