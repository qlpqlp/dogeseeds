<?php

declare(strict_types=1);

class Auth
{
    private static array $config = [];
    private static ?array $user = null;

    public static function init(array $config): void
    {
        self::$config = $config;
        if (session_status() === PHP_SESSION_NONE) {
            session_name($config['session']['name'] ?? 'dogeseeds_session');
            session_start();
        }
        if (!empty($_SESSION['user_id'])) {
            self::$user = Database::fetch('SELECT * FROM users WHERE id = ?', [$_SESSION['user_id']]);
        }
    }

    public static function user(): ?array
    {
        return self::$user;
    }

    public static function check(): bool
    {
        return self::$user !== null;
    }

    public static function requireAuth(): array
    {
        if (!self::check()) {
            jsonResponse(['error' => 'Unauthorized'], 401);
        }
        return self::$user;
    }

    public static function requireRole(string ...$roles): array
    {
        $user = self::requireAuth();
        if (!in_array($user['role'], $roles, true)) {
            jsonResponse(['error' => 'Forbidden'], 403);
        }
        return $user;
    }

    public static function login(string $email, string $password): ?array
    {
        $user = Database::fetch('SELECT * FROM users WHERE email = ?', [$email]);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return null;
        }
        $_SESSION['user_id'] = $user['id'];
        self::$user = $user;
        logActivity((int) $user['id'], 'login');
        return $user;
    }

    public static function register(string $email, string $password, string $name, string $role = 'user'): array
    {
        $existing = Database::fetch('SELECT id FROM users WHERE email = ?', [$email]);
        if ($existing) {
            jsonResponse(['error' => 'Email already registered'], 409);
        }

        $allowedRoles = ['user', 'business', 'volunteer', 'ngo'];
        if (!in_array($role, $allowedRoles, true)) {
            $role = 'user';
        }

        $token = bin2hex(random_bytes(32));

        $id = Database::insert('users', [
            'email'               => $email,
            'password_hash'       => password_hash($password, PASSWORD_DEFAULT),
            'name'                => $name,
            'role'                => $role,
            'language'            => I18n::getSiteDefaultLanguage(),
            'verification_token'  => $token,
        ]);

        logActivity($id, 'register', 'user', $id);
        $user = Database::fetch('SELECT * FROM users WHERE id = ?', [$id]);

        if (Mailer::isEnabled()) {
            $verifyUrl = siteUrl() . '/verify.php?token=' . urlencode($token);
            Mailer::send(
                $email,
                'Welcome to DogeSeeds - confirm your email',
                EmailTemplates::welcome($name, $verifyUrl)
            );
        }

        return $user;
    }

    public static function logout(): void
    {
        if (self::check()) {
            logActivity((int) self::$user['id'], 'logout');
        }
        $_SESSION = [];
        session_destroy();
        self::$user = null;
    }

    public static function requestPasswordReset(string $email): void
    {
        $user = Database::fetch('SELECT id, name, email FROM users WHERE email = ?', [$email]);
        if (!$user) {
            return;
        }

        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 3600);

        Database::query(
            'UPDATE users SET password_reset_token = ?, password_reset_expires = ? WHERE id = ?',
            [$token, $expires, $user['id']]
        );

        if (Mailer::isEnabled()) {
            $resetUrl = siteUrl() . '/reset.php?token=' . urlencode($token);
            Mailer::send(
                $user['email'],
                'Reset your DogeSeeds password',
                EmailTemplates::passwordReset($user['name'], $resetUrl)
            );
        }
    }

    public static function resetPassword(string $token, string $password): bool
    {
        $user = Database::fetch(
            'SELECT id FROM users WHERE password_reset_token = ? AND password_reset_expires > NOW()',
            [$token]
        );
        if (!$user) {
            return false;
        }

        Database::query(
            'UPDATE users SET password_hash = ?, password_reset_token = NULL, password_reset_expires = NULL WHERE id = ?',
            [password_hash($password, PASSWORD_DEFAULT), $user['id']]
        );

        logActivity((int) $user['id'], 'password_reset');
        return true;
    }
}
