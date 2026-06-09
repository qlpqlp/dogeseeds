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
$validToken = false;

if ($token !== '') {
    $user = Database::fetch(
        'SELECT id FROM users WHERE password_reset_token = ? AND password_reset_expires > NOW()',
        [$token]
    );
    $validToken = (bool) $user;
}

$siteName = getSetting('site_name', 'DogeSeeds.org') ?? 'DogeSeeds.org';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(I18n::getLang()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($t('password_reset_title')) ?> — <?= htmlspecialchars($siteName) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="verify-page">
<div class="verify-card">
    <img src="assets/img/DogeSeeds_logo.png" alt="<?= htmlspecialchars($siteName) ?>" class="verify-logo">
    <?php if ($validToken): ?>
    <span class="material-icons verify-icon success">lock_reset</span>
    <h1><?= htmlspecialchars($t('password_reset_title')) ?></h1>
    <p><?= htmlspecialchars($t('password_reset_desc')) ?></p>
    <form id="resetForm" class="reset-form">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
        <div class="auth-field">
            <label for="resetPassword"><?= htmlspecialchars($t('password')) ?></label>
            <div class="auth-input-wrap">
                <span class="material-icons auth-input-icon">lock</span>
                <input type="password" name="password" id="resetPassword" minlength="8" required autocomplete="new-password" placeholder="<?= htmlspecialchars($t('password')) ?>">
            </div>
        </div>
        <div class="auth-field">
            <label for="resetPasswordConfirm"><?= htmlspecialchars($t('password_confirm')) ?></label>
            <div class="auth-input-wrap">
                <span class="material-icons auth-input-icon">lock</span>
                <input type="password" name="password_confirm" id="resetPasswordConfirm" minlength="8" required autocomplete="new-password" placeholder="<?= htmlspecialchars($t('password_confirm')) ?>">
            </div>
        </div>
        <button type="submit" class="btn btn-block auth-submit">
            <span class="material-icons">check</span>
            <span><?= htmlspecialchars($t('password_reset_submit')) ?></span>
        </button>
    </form>
    <p id="resetMessage" class="reset-message hidden"></p>
    <?php else: ?>
    <span class="material-icons verify-icon error">error_outline</span>
    <h1><?= htmlspecialchars($t('password_reset_invalid')) ?></h1>
    <p><?= htmlspecialchars($t('password_reset_invalid_desc')) ?></p>
    <?php endif; ?>
    <a href="./" class="btn btn-outline"><?= htmlspecialchars($t('email_verify_back')) ?></a>
</div>
<?php if ($validToken): ?>
<script>
(function () {
    const form = document.getElementById('resetForm');
    const msg = document.getElementById('resetMessage');
    const mismatch = <?= json_encode($t('password_mismatch')) ?>;
    const success = <?= json_encode($t('password_reset_success')) ?>;
    const apiBase = 'api/';

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData(form);
        const password = fd.get('password');
        const confirm = fd.get('password_confirm');
        if (password !== confirm) {
            msg.textContent = mismatch;
            msg.className = 'reset-message error';
            return;
        }
        try {
            const res = await fetch(apiBase + 'auth/reset', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ token: fd.get('token'), password }),
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.error || 'Error');
            form.classList.add('hidden');
            msg.textContent = success;
            msg.className = 'reset-message success';
        } catch (err) {
            msg.textContent = err.message;
            msg.className = 'reset-message error';
        }
    });
})();
</script>
<?php endif; ?>
</body>
</html>
