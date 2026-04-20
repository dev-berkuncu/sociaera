<?php
/**
 * Sociaera — Header Partial
 * Tüm sayfalarda include edilir. $pageTitle değişkeni dışarıdan gelir.
 */
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo escape($pageDescription ?? 'Sociaera — Sosyal Keşif & Check-in Platformu'); ?>">
    <title><?php echo View::title($pageTitle ?? ''); ?></title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/img/favicon.png">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Sociaera Theme -->
    <link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>?v=<?php echo time(); ?>">

    <!-- CSRF Meta -->
    <?php echo csrfMeta(); ?>
    <meta name="base-url" content="<?php echo BASE_URL; ?>">
    <script>window.BASE_URL = '<?php echo BASE_URL; ?>';</script>
</head>
<body>
