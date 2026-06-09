<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (!isInstalled()) {
    header('Location: install/');
    exit;
}

I18n::init($_GET['lang'] ?? null);
$t = fn(string $key) => I18n::t($key);

$token = trim($_GET['token'] ?? '');
$success = false;

if ($token !== '') {
    $user = Database::fetch('SELECT id FROM users WHERE verification_token = ?', [$token]);
    if ($user) {
        Database::query(
            'UPDATE users SET verified = 1, verification_token = NULL WHERE id = ?',
            [$user['id']]
        );
        $success = true;
    }
}

$siteName = getSetting('site_name', 'DogeSeeds.org') ?? 'DogeSeeds.org';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(I18n::getLang()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($siteName) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="verify-page">
<div class="verify-card">
    <img src="assets/img/DogeSeeds_logo.png" alt="<?= htmlspecialchars($siteName) ?>" class="verify-logo">
    <?php if ($success): ?>
    <span class="material-icons verify-icon success">check_circle</span>
    <h1><?= htmlspecialchars($t('email_verify_success')) ?></h1>
    <p><?= htmlspecialchars($t('email_verify_success_desc')) ?></p>
    <?php else: ?>
    <span class="material-icons verify-icon error">error_outline</span>
    <h1><?= htmlspecialchars($t('email_verify_failed')) ?></h1>
    <p><?= htmlspecialchars($t('email_verify_failed_desc')) ?></p>
    <?php endif; ?>
    <a href="./" class="btn"><?= htmlspecialchars($t('email_verify_back')) ?></a>
</div>
</body>
</html>
