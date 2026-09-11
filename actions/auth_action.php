<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

$csrfToken = (string) ($_POST['csrf_token'] ?? '');
if (!verify_csrf_token($csrfToken)) {
    redirect('index.php?error=csrf');
}

$action = (string) ($_POST['action'] ?? 'login');
$username = trim((string) ($_POST['username'] ?? ''));
$password = (string) ($_POST['password'] ?? '');

if ($action === 'register') {
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if ($username === '' || strlen($password) < 1 || $password !== $confirmPassword) {
        redirect('index.php?error=register');
    }

    $stmtCheck = db()->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
    $stmtCheck->execute([$username]);
    if ($stmtCheck->fetch()) {
        redirect('index.php?error=user-exists');
    }

    $statement = db()->prepare('INSERT INTO users (username, password, phone, role) VALUES (?, ?, ?, "user")');
    $statement->execute([$username, password_hash($password, PASSWORD_DEFAULT), $phone ?: null]);

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) db()->lastInsertId();
    $_SESSION['username'] = $username;
    $_SESSION['role'] = 'user';
    $_SESSION['phone'] = $phone;

    redirect('index.php?success=registered');
}

$statement = db()->prepare('SELECT id, username, password, role FROM users WHERE username = ? LIMIT 1');
$statement->execute([$username]);
$user = $statement->fetch();

$passwordMatches = $user && password_verify($password, (string) $user['password']);

if (!$passwordMatches) {
    redirect('index.php?error=login');
}

session_regenerate_id(true);
$_SESSION['user_id'] = (int) $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['role'] = $user['role'];

redirect($user['role'] === 'admin' ? 'admin/index.php' : 'index.php?success=login');
