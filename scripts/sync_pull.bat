@echo off
chcp 65001 >nul
title V-Phone Store - Dong bo code va Database tu GitHub

REM Chuyen ve thu muc goc cua du an
cd /d "%~dp0\.."

echo ========================================================
echo  [1/2] Dang keo code moi nhat tu GitHub ve may truong...
echo ========================================================
git pull

if %ERRORLEVEL% NEQ 0 (
    echo.
    echo [LOI] Khong the keo code tu GitHub. Vui long kiem tra mang!
    pause
    exit /b %ERRORLEVEL%
)

echo.
echo ========================================================
echo  [2/2] Dang nap toan bo Database (Don hang, iPhone 18,...) vao MySQL...
echo ========================================================

set PHP_CMD=php

if exist "C:\xampp\php\php.exe" (
    set PHP_CMD="C:\xampp\php\php.exe"
) else if exist "D:\xampp\php\php.exe" (
    set PHP_CMD="D:\xampp\php\php.exe"
) else if exist "E:\xampp\php\php.exe" (
    set PHP_CMD="E:\xampp\php\php.exe"
)

%PHP_CMD% database\db_sync_tool.php import

if %ERRORLEVEL% NEQ 0 (
    echo.
    echo [LOI] Khong the nap Database. Hay dam bao da Start Apache va MySQL trong XAMPP!
    echo Ban cung co the mo trinh duyet vao: http://localhost/phone-store/admin/db_sync.php va bam nut "Nap Du Lieu".
    pause
    exit /b %ERRORLEVEL%
)

echo.
echo ========================================================
echo  [THANH CONG 100%]
echo  Tat ca don hang, san pham va du lieu tu may Mac da duoc
echo  dong bo hoan toan vao MySQL tren may truong!
echo ========================================================
echo.
pause

