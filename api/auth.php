<?php

declare(strict_types=1);

$action = explode('/', $_GET['route'] ?? '')[1] ?? '';

match ($action) {
    'login' => handleLogin($body),
    'register' => handleRegister($body),
    'logout' => handleLogout(),
    'me' => handleMe(),
    'forgot' => handleForgot($body),
    'reset' => handleReset($body),
    default => jsonResponse(['error' => 'Not found'], 404),
};

function handleLogin(array $body): void
{
    $email = trim($body['email'] ?? '');
    $password = $body['password'] ?? '';

    if (!$email || !$password) {
        jsonResponse(['error' => 'Email and password required'], 400);
    }

    $user = Auth::login($email, $password);
    if (!$user) {
        jsonResponse(['error' => 'Invalid credentials'], 401);
    }

    unset($user['password_hash']);
    jsonResponse(['user' => $user]);
}

function handleRegister(array $body): void
{
    $email = trim($body['email'] ?? '');
    $password = $body['password'] ?? '';
    $name = trim($body['name'] ?? '');
    $role = $body['role'] ?? 'user';

    if (!$email || !$password || !$name) {
        jsonResponse(['error' => 'All fields required'], 400);
    }

    if (strlen($password) < 8) {
        jsonResponse(['error' => 'Password must be at least 8 characters'], 400);
    }

    $confirm = $body['password_confirm'] ?? '';
    if ($confirm !== '' && $password !== $confirm) {
        jsonResponse(['error' => 'Passwords do not match'], 400);
    }

    $user = Auth::register($email, $password, $name, $role);
    $_SESSION['user_id'] = $user['id'];
    unset($user['password_hash'], $user['verification_token']);
    jsonResponse(['user' => $user, 'email_sent' => Mailer::isEnabled()], 201);
}

function handleLogout(): void
{
    Auth::logout();
    jsonResponse(['message' => 'Logged out']);
}

function handleMe(): void
{
    $user = Auth::requireAuth();
    unset($user['password_hash']);
    jsonResponse(['user' => $user]);
}

function handleForgot(array $body): void
{
    $email = trim($body['email'] ?? '');
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['error' => 'Valid email required'], 400);
    }

    Auth::requestPasswordReset($email);
    jsonResponse(['message' => 'If that email is registered, a reset link was sent']);
}

function handleReset(array $body): void
{
    $token = trim($body['token'] ?? '');
    $password = $body['password'] ?? '';

    if (!$token || !$password) {
        jsonResponse(['error' => 'Token and password required'], 400);
    }

    if (strlen($password) < 8) {
        jsonResponse(['error' => 'Password must be at least 8 characters'], 400);
    }

    if (!Auth::resetPassword($token, $password)) {
        jsonResponse(['error' => 'Invalid or expired reset link'], 400);
    }

    jsonResponse(['message' => 'Password updated']);
}
