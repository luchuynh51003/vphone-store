<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? $pageTitle : "V-Phone - Siêu Thị Flagship 2026" ?></title>
    
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI2NCIgaGVpZ2h0PSI2NCIgdmlld0JveD0iMCAwIDY0IDY0Ij48cmVjdCB4PSIyIiB5PSIyIiB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHJ4PSIxNiIgZmlsbD0iIzAwNTZiMyIgc3Ryb2tlPSIjMzhkZng4IiBzdHJva2Utd2lkdGg9IjMiLz48cGF0aCBkPSJNMTQgMTYgTDI4IDQ4IEwzNiA0OCBMNTAgMTYgTDQxIDE2IEwzMiAzOSBMMjMgMTYgWiIgZmlsbD0iI2ZmZmZmZiIvPjxwb2x5Z29uIHBvaW50cz0iMzQsMTAgMjYsMjcgMzIsMjcgMjgsNDIgNDIsMjMgMzUsMjMiIGZpbGw9IiNmYWNjMTUiLz48L3N2Zz4=">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        html, body { margin: 0 !important; padding: 0 !important; }
        body { padding-top: 58px !important; }
        .navbar-vphone { position: fixed !important; top: 0 !important; left: 0 !important; right: 0 !important; width: 100% !important; z-index: 1030 !important; margin: 0 !important; }
        .search-wrapper { position: relative !important; }
        .search-dropdown-menu {
            position: absolute !important;
            top: calc(100% + 8px) !important;
            left: 0 !important;
            width: 100% !important;
            background: #ffffff !important;
            border-radius: 16px !important;
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.2) !important;
            border: 1px solid #cce3ff !important;
            z-index: 999999 !important;
            max-height: 420px !important;
            overflow-y: auto !important;
            padding: 4px 0 !important;
        }
        .search-item {
            display: flex !important;
            align-items: center !important;
            padding: 10px 16px !important;
            text-decoration: none !important;
            color: #1e293b !important;
            border-bottom: 1px solid #f1f5f9 !important;
            background: #ffffff !important;
            transition: all 0.15s ease !important;
        }
        .search-item:hover { background: #f0f7ff !important; }
        .search-item:last-child { border-bottom: none !important; }
        .search-thumb { width: 44px !important; height: 44px !important; object-fit: contain !important; border-radius: 8px !important; margin-right: 12px !important; }
        .search-name { font-size: 13.5px !important; font-weight: 600 !important; color: #0f172a !important; line-height: 1.3 !important; }
        .search-price { font-size: 13px !important; font-weight: 700 !important; color: #e74c3c !important; margin-top: 2px !important; }
    </style>
</head>
<body>
