#!/bin/bash
# =========================================================================
# V-PHONE STORE - SCRIPT KEO CODE VA DONG BO DATABASE VAO MAY (MAC/LINUX)
# =========================================================================

# Chuyen ve thu muc goc cua du an
ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT_DIR" || exit 1

echo "========================================================"
echo "[1/2] Dang keo code moi nhat tu GitHub ve..."
echo "========================================================"

git pull

if [ $? -ne 0 ]; then
    echo "[LOI] Khong the keo code tu GitHub!"
    exit 1
fi

chmod -R 777 database/ 2>/dev/null

echo ""
echo "========================================================"
echo "[2/2] Dang nap co so du lieu vao MySQL may nay..."
echo "========================================================"

PHP_BIN="php"
if [ -f "/Applications/XAMPP/xamppfiles/bin/php" ]; then
    PHP_BIN="/Applications/XAMPP/xamppfiles/bin/php"
fi

$PHP_BIN database/db_sync_tool.php import

if [ $? -ne 0 ]; then
    echo "[LOI] Khong the nap Database! Kiem tra MySQL XAMPP da khoi dong chua."
    exit 1
fi

echo ""
echo "========================================================"
echo "[THANH CONG] Da dong bo toan bo Database tu GitHub vao may nay!"
echo "========================================================"
