<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="no-referrer">
    <title><?= isset($pageTitle) ? $pageTitle : "V-Phone - Siêu Thị Flagship 2026" ?></title>
    <link rel="icon" type="image/png" href="assets/images/favicon.png?v=2026">
    <link rel="shortcut icon" href="assets/images/favicon.png?v=2026">
    <link rel="apple-touch-icon" href="assets/images/vphone-icon.png?v=2026">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/vendor/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= file_exists(__DIR__ . '/../../../assets/css/style.css') ? filemtime(__DIR__ . '/../../../assets/css/style.css') : '20261016_v12' ?>">
    <script src="https://accounts.google.com/gsi/client" async defer></script>

    <style>
        html { scrollbar-gutter: stable; }
        body { padding-top: 84px !important; }
        body.modal-open,
        body.offcanvas-open,
        body[data-bs-overflow="hidden"] {
            padding-right: 0 !important;
        }
        .navbar-vphone {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            width: 100% !important;
            z-index: 1030 !important;
            margin: 0 !important;
            padding-right: 0 !important;
        }
    </style>
</head>
<body>
