<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

if (!isInstalled()) {
    header('Location: ../install/');
    exit;
}

$user = Auth::user();
if (!$user || $user['role'] !== 'admin') {
    http_response_code(403);
    echo 'Admin access required. <a href="../">Back to site</a>';
    exit;
}

I18n::init($_GET['lang'] ?? null);
$t = fn(string $key) => I18n::t($key);
$siteName = getSetting('site_name', 'DogeSeeds.org') ?? 'DogeSeeds.org';
$languages = I18n::getLanguageMeta();
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(I18n::getLang()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($t('admin_title')) ?> | <?= htmlspecialchars($siteName) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="admin-page">
<div class="admin-wrap">
    <header class="admin-header">
        <a href="../" class="admin-back"><span class="material-icons">arrow_back</span><?= htmlspecialchars($t('admin_back')) ?></a>
        <h1><span class="material-icons">settings</span><?= htmlspecialchars($t('admin_title')) ?></h1>
        <p><?= htmlspecialchars($t('admin_subtitle')) ?></p>
    </header>

    <form id="adminForm" class="admin-form">
        <section class="admin-card">
            <h2><span class="material-icons">public</span><?= htmlspecialchars($t('admin_general_title')) ?></h2>
            <p class="hint"><?= htmlspecialchars($t('admin_general_hint')) ?></p>

            <label><?= htmlspecialchars($t('admin_site_name')) ?></label>
            <input type="text" name="site_name" id="siteName" required>

            <label><?= htmlspecialchars($t('admin_site_url')) ?></label>
            <input type="url" name="site_url" id="siteUrl" placeholder="https://yourdomain.com">
            <p class="hint"><?= htmlspecialchars($t('admin_site_url_hint')) ?></p>

            <label><?= htmlspecialchars($t('admin_default_language')) ?></label>
            <select name="default_language" id="defaultLanguage">
                <?php foreach ($languages as $code => $meta): ?>
                <option value="<?= htmlspecialchars($code) ?>"><?= htmlspecialchars($meta['label']) ?></option>
                <?php endforeach; ?>
            </select>
            <p class="hint"><?= htmlspecialchars($t('admin_default_language_hint')) ?></p>
        </section>

        <section class="admin-card">
            <h2><span class="material-icons">map</span><?= htmlspecialchars($t('admin_map_title')) ?></h2>
            <p class="hint"><?= htmlspecialchars($t('admin_map_hint')) ?></p>

            <div class="form-row">
                <div>
                    <label><?= htmlspecialchars($t('admin_map_lat')) ?></label>
                    <input type="number" name="map_default_lat" id="mapLat" step="any" min="-90" max="90">
                </div>
                <div>
                    <label><?= htmlspecialchars($t('admin_map_lng')) ?></label>
                    <input type="number" name="map_default_lng" id="mapLng" step="any" min="-180" max="180">
                </div>
            </div>

            <label><?= htmlspecialchars($t('admin_map_zoom')) ?></label>
            <input type="number" name="map_default_zoom" id="mapZoom" min="1" max="18" step="1">
        </section>

        <section class="admin-card">
            <h2><span class="material-icons">currency_bitcoin</span><?= htmlspecialchars($t('admin_donate_title')) ?></h2>
            <p class="hint"><?= htmlspecialchars($t('admin_donate_hint')) ?></p>

            <label><?= htmlspecialchars($t('donate_wallet')) ?></label>
            <input type="text" name="doge_wallet" id="dogeWallet" placeholder="D...">

            <label><?= htmlspecialchars($t('admin_donate_note')) ?></label>
            <textarea name="doge_transparency_note" id="dogeNote" rows="3"></textarea>
        </section>

        <section class="admin-card">
            <h2><span class="material-icons">mail</span><?= htmlspecialchars($t('admin_smtp_title')) ?></h2>
            <p class="hint"><?= htmlspecialchars($t('admin_smtp_hint')) ?></p>

            <label class="switch-row switch-row-block">
                <span class="switch-row-label">
                    <span class="material-icons">power_settings_new</span>
                    <span><?= htmlspecialchars($t('admin_smtp_enable')) ?></span>
                </span>
                <span class="switch">
                    <input type="checkbox" name="smtp_enabled" id="smtpEnabled" value="1">
                    <span class="switch-slider"></span>
                </span>
            </label>

            <div class="form-row">
                <div>
                    <label><?= htmlspecialchars($t('admin_smtp_host')) ?></label>
                    <input type="text" name="smtp_host" id="smtpHost" placeholder="smtp.example.com">
                </div>
                <div>
                    <label><?= htmlspecialchars($t('admin_smtp_port')) ?></label>
                    <input type="number" name="smtp_port" id="smtpPort" value="587" min="1" max="65535">
                </div>
            </div>

            <label><?= htmlspecialchars($t('admin_smtp_encryption')) ?></label>
            <select name="smtp_encryption" id="smtpEncryption">
                <option value="tls">TLS</option>
                <option value="ssl">SSL</option>
                <option value="none"><?= htmlspecialchars($t('admin_smtp_none')) ?></option>
            </select>

            <div class="form-row">
                <div>
                    <label><?= htmlspecialchars($t('admin_smtp_username')) ?></label>
                    <input type="text" name="smtp_username" id="smtpUsername" autocomplete="username">
                </div>
                <div>
                    <label><?= htmlspecialchars($t('admin_smtp_password')) ?></label>
                    <input type="password" name="smtp_password" id="smtpPassword" autocomplete="new-password" placeholder="<?= htmlspecialchars($t('admin_smtp_password_placeholder')) ?>">
                </div>
            </div>

            <div class="form-row">
                <div>
                    <label><?= htmlspecialchars($t('admin_smtp_from_email')) ?></label>
                    <input type="email" name="smtp_from_email" id="smtpFromEmail">
                </div>
                <div>
                    <label><?= htmlspecialchars($t('admin_smtp_from_name')) ?></label>
                    <input type="text" name="smtp_from_name" id="smtpFromName">
                </div>
            </div>

            <div class="admin-actions admin-actions-inline">
                <button type="button" class="btn btn-outline" id="btnTestEmail">
                    <span class="material-icons">send</span><?= htmlspecialchars($t('admin_test_email')) ?>
                </button>
                <p class="hint"><?= htmlspecialchars($t('admin_test_email_hint')) ?></p>
            </div>
        </section>

        <div class="admin-save-bar">
            <button type="submit" class="btn btn-block">
                <span class="material-icons">save</span><?= htmlspecialchars($t('admin_save_all')) ?>
            </button>
            <p class="admin-status" id="adminStatus" hidden></p>
        </div>
    </form>
</div>

<script>
window.DogeSeedsAdmin = {
    apiBase: '../api/',
    strings: <?= json_encode([
        'saved' => $t('admin_saved'),
        'test_sent' => $t('admin_test_sent'),
        'error' => $t('admin_error'),
    ], JSON_UNESCAPED_UNICODE) ?>,
};
</script>
<script src="../assets/js/admin.js"></script>
</body>
</html>
