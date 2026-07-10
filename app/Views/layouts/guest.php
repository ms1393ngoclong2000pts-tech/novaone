<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Novaone</title>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= e(asset_url('public/assets/novaone-logo.png')) ?>?v=<?= filemtime(BASE_PATH . '/public/assets/novaone-logo.png') ?>">
    <link rel="shortcut icon" type="image/png" href="<?= e(asset_url('public/assets/novaone-logo.png')) ?>?v=<?= filemtime(BASE_PATH . '/public/assets/novaone-logo.png') ?>">
    <link rel="apple-touch-icon" href="<?= e(asset_url('public/assets/novaone-logo.png')) ?>?v=<?= filemtime(BASE_PATH . '/public/assets/novaone-logo.png') ?>">
    <link rel="stylesheet" href="<?= e(asset_url('public/assets/app.css')) ?>?v=<?= filemtime(BASE_PATH . '/public/assets/app.css') ?>">
</head>
<body>
    <?= $content ?>
</body>
</html>
