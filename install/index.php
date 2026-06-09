<?php

declare(strict_types=1);

session_start();

define('ROOT_PATH', dirname(__DIR__));
$configFile = ROOT_PATH . '/config/config.php';
$lockFile = ROOT_PATH . '/config/.installed';

if (file_exists($lockFile) && file_exists($configFile)) {
    header('Location: ../');
    exit;
}

$step = (int) ($_GET['step'] ?? 1);
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'requirements') {
        header('Location: ?step=2');
        exit;
    }

    if ($action === 'database') {
        $dbHost = trim($_POST['db_host'] ?? 'localhost');
        $dbName = trim($_POST['db_name'] ?? '');
        $dbUser = trim($_POST['db_user'] ?? '');
        $dbPass = $_POST['db_password'] ?? '';

        if (!$dbName || !$dbUser) {
            $error = 'Database name and user are required.';
            $step = 2;
        } else {
            try {
                $dsn = "mysql:host={$dbHost};charset=utf8mb4";
                $pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $pdo->exec("USE `{$dbName}`");

                $schema = file_get_contents(ROOT_PATH . '/database/schema.sql');
                $pdo->exec($schema);
                $pdo->exec("UPDATE `settings` SET `value` = 'en' WHERE `key` = 'default_language'");

                $_SESSION['install_db'] = compact('dbHost', 'dbName', 'dbUser', 'dbPass');
                header('Location: ?step=3');
                exit;
            } catch (PDOException $e) {
                $error = 'Database connection failed: ' . $e->getMessage();
                $step = 2;
            }
        }
    }

    if ($action === 'site') {
        $db = $_SESSION['install_db'] ?? null;
        if (!$db) {
            header('Location: ?step=2');
            exit;
        }

        $siteUrl = rtrim(trim($_POST['site_url'] ?? ''), '/');
        $adminEmail = trim($_POST['admin_email'] ?? '');
        $adminPassword = $_POST['admin_password'] ?? '';
        $adminName = trim($_POST['admin_name'] ?? 'Admin');
        $dogeWallet = trim($_POST['doge_wallet'] ?? '');
        $allowedLangs = array_keys(require ROOT_PATH . '/includes/languages.php');
        $defaultLang = $_POST['default_language'] ?? 'en';
        if (!in_array($defaultLang, $allowedLangs, true)) {
            $defaultLang = 'en';
        }

        if (!$siteUrl || !$adminEmail || strlen($adminPassword) < 8) {
            $error = 'Site URL, admin email, and password (8+ chars) are required.';
            $step = 3;
        } else {
            $secretKey = bin2hex(random_bytes(32));

            $config = "<?php\nreturn " . var_export([
                'db' => [
                    'host'     => $db['dbHost'],
                    'name'     => $db['dbName'],
                    'user'     => $db['dbUser'],
                    'password' => $db['dbPass'],
                    'charset'  => 'utf8mb4',
                ],
                'app' => [
                    'name'       => 'DogeSeeds.org',
                    'url'        => $siteUrl,
                    'debug'      => false,
                    'timezone'   => 'UTC',
                    'secret_key' => $secretKey,
                ],
                'session' => [
                    'lifetime' => 7200,
                    'name'     => 'dogeseeds_session',
                ],
            ], true) . ";\n";

            if (!is_dir(ROOT_PATH . '/config')) {
                mkdir(ROOT_PATH . '/config', 0755, true);
            }
            file_put_contents($configFile, $config);

            require_once ROOT_PATH . '/includes/Database.php';
            Database::init([
                'host' => $db['dbHost'],
                'name' => $db['dbName'],
                'user' => $db['dbUser'],
                'password' => $db['dbPass'],
                'charset' => 'utf8mb4',
            ]);

            Database::insert('users', [
                'email'         => $adminEmail,
                'password_hash' => password_hash($adminPassword, PASSWORD_DEFAULT),
                'name'          => $adminName,
                'role'          => 'admin',
                'verified'      => 1,
            ]);

            Database::query("UPDATE settings SET `value` = ? WHERE `key` = 'site_installed'", ['1']);
            Database::query("UPDATE settings SET `value` = ? WHERE `key` = 'doge_wallet'", [$dogeWallet]);
            Database::query("UPDATE settings SET `value` = 'en' WHERE `key` = 'default_language'");

            file_put_contents($lockFile, date('c'));
            unset($_SESSION['install_db']);
            header('Location: ?step=4');
            exit;
        }
    }
}

$requirements = [
    'PHP 8.0+' => version_compare(PHP_VERSION, '8.0.0', '>='),
    'PDO MySQL' => extension_loaded('pdo_mysql'),
    'JSON' => extension_loaded('json'),
    'config/ writable' => is_writable(ROOT_PATH . '/config') || @mkdir(ROOT_PATH . '/config', 0755, true),
];
$allMet = !in_array(false, $requirements, true);

$siteUrlGuess = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
    . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
    . rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/\\');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install DogeSeeds.org</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="icon" href="../assets/img/DogeSeeds_logo.png" type="image/png">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Nunito', sans-serif; background: #FAFAFA; color: #1A2B45; min-height: 100vh; padding: 1rem; }
        .container { max-width: 560px; margin: 2rem auto; background: #fff; border-radius: 16px; padding: 2rem; box-shadow: 0 4px 24px rgba(26,43,69,.08); }
        .install-logo { display: block; max-width: 280px; height: auto; margin: 0 auto 1rem; }
        h1 { font-size: 1.5rem; margin-bottom: .5rem; color: #1A2B45; text-align: center; }
        .subtitle { color: #5a6a7a; margin-bottom: 1.5rem; text-align: center; }
        .steps { display: flex; gap: .5rem; margin-bottom: 1.5rem; justify-content: center; }
        .step-dot { width: 32px; height: 32px; border-radius: 50%; background: #e8ecef; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: .85rem; }
        .step-dot.active { background: #4CAF50; color: #fff; }
        .step-dot.done { background: #F5A623; color: #1A2B45; }
        label { display: block; margin: 1rem 0 .35rem; font-weight: 700; }
        input, select { width: 100%; padding: .75rem; border: 2px solid #e8ecef; border-radius: 8px; font-family: inherit; font-size: 1rem; }
        input:focus, select:focus { outline: none; border-color: #4CAF50; }
        .btn { display: inline-flex; align-items: center; gap: .5rem; padding: .85rem 1.5rem; background: #4CAF50; color: #fff; border: none; border-radius: 8px; font-family: inherit; font-size: 1rem; font-weight: 700; cursor: pointer; margin-top: 1.5rem; }
        .btn:hover { background: #388E3C; }
        .btn:disabled { opacity: .5; cursor: not-allowed; }
        .error { background: #ffebee; color: #c62828; padding: .75rem; border-radius: 8px; margin-bottom: 1rem; }
        .success { background: #e8f5e9; color: #2e7d32; padding: .75rem; border-radius: 8px; margin-bottom: 1rem; }
        .req-list { list-style: none; }
        .req-list li { padding: .5rem 0; display: flex; align-items: center; gap: .5rem; }
        .ok { color: #4caf50; }
        .fail { color: #f44336; }
        .hint { font-size: .85rem; color: #888; margin-top: .25rem; }
    </style>
</head>
<body>
<div class="container">
    <img src="../assets/img/DogeSeeds_logo.png" alt="DogeSeeds.org" class="install-logo">
    <h1>DogeSeeds.org Install</h1>
    <p class="subtitle">See it. Share it. Grow kindness.</p>

    <div class="steps">
        <?php for ($i = 1; $i <= 4; $i++): ?>
            <div class="step-dot <?= $i < $step ? 'done' : ($i === $step ? 'active' : '') ?>"><?= $i ?></div>
        <?php endfor; ?>
    </div>

    <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <?php if ($step === 1): ?>
        <h2>Step 1: Requirements</h2>
        <ul class="req-list">
            <?php foreach ($requirements as $label => $met): ?>
                <li>
                    <span class="material-icons <?= $met ? 'ok' : 'fail' ?>"><?= $met ? 'check_circle' : 'cancel' ?></span>
                    <?= htmlspecialchars($label) ?>
                </li>
            <?php endforeach; ?>
        </ul>
        <form method="post">
            <input type="hidden" name="action" value="requirements">
            <button type="submit" class="btn" <?= $allMet ? '' : 'disabled' ?>>
                <span class="material-icons">arrow_forward</span> Continue
            </button>
        </form>

    <?php elseif ($step === 2): ?>
        <h2>Step 2: Database</h2>
        <p class="hint">Create a MySQL database in cPanel first, then enter the credentials below.</p>
        <form method="post">
            <input type="hidden" name="action" value="database">
            <label>Database Host</label>
            <input name="db_host" value="localhost" required>
            <label>Database Name</label>
            <input name="db_name" placeholder="dogeseeds" required>
            <label>Database User</label>
            <input name="db_user" required>
            <label>Database Password</label>
            <input name="db_password" type="password">
            <button type="submit" class="btn"><span class="material-icons">storage</span> Connect & Import Schema</button>
        </form>

    <?php elseif ($step === 3): ?>
        <h2>Step 3: Site & Admin</h2>
        <form method="post">
            <input type="hidden" name="action" value="site">
            <label>Site URL</label>
            <input name="site_url" value="<?= htmlspecialchars($siteUrlGuess) ?>" required>
            <p class="hint">Full URL without trailing slash (e.g. https://dogeseeds.org)</p>
            <label>Default Language</label>
            <select name="default_language">
                <option value="en" selected>English</option>
                <option value="pt">Português</option>
                <option value="es">Español</option>
                <option value="fr">Français</option>
                <option value="de">Deutsch</option>
                <option value="zh">中文</option>
                <option value="ja">日本語</option>
            </select>
            <label>Admin Name</label>
            <input name="admin_name" value="Admin" required>
            <label>Admin Email</label>
            <input name="admin_email" type="email" required>
            <label>Admin Password</label>
            <input name="admin_password" type="password" minlength="8" required>
            <label>Dogecoin Wallet (DOGE only)</label>
            <input name="doge_wallet" placeholder="D...">
            <p class="hint">For hosting & verified distribution support only</p>
            <button type="submit" class="btn"><span class="material-icons">rocket_launch</span> Install</button>
        </form>

    <?php elseif ($step === 4): ?>
        <div class="success">
            <strong>Installation complete!</strong> DogeSeeds.org is ready.
        </div>
        <p>For security, delete or rename the <code>install/</code> folder.</p>
        <a href="../" class="btn"><span class="material-icons">public</span> Open DogeSeeds.org</a>
    <?php endif; ?>
</div>
</body>
</html>
