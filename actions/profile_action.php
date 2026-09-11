<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !is_logged_in()) {
    redirect('index.php?error=login-required');
}

$csrfToken = (string) ($_POST['csrf_token'] ?? '');
if (!verify_csrf_token($csrfToken)) {
    redirect('views/profile.php?error=csrf');
}

$action = (string) ($_POST['action'] ?? 'update_info');
$userId = (int) $_SESSION['user_id'];

if ($action === 'update_info') {
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $bio = trim((string) ($_POST['bio'] ?? ''));
    $stmt = db()->prepare('UPDATE users SET phone = ?, bio = ? WHERE id = ?');
    $stmt->execute([$phone ?: null, $bio ?: null, $userId]);
    $_SESSION['phone'] = $phone;
    redirect('views/profile.php?success=info-updated');
}

if ($action === 'change_password') {
    $currentPassword = (string) ($_POST['current_password'] ?? '');
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if ($newPassword === '' || $newPassword !== $confirmPassword) {
        redirect('views/profile.php?error=password-mismatch');
    }

    if (strlen($newPassword) < 3) {
        redirect('views/profile.php?error=password-short');
    }

    $stmtUser = db()->prepare('SELECT password FROM users WHERE id = ?');
    $stmtUser->execute([$userId]);
    $user = $stmtUser->fetch();

    if (!$user || !password_verify($currentPassword, (string) $user['password'])) {
        redirect('views/profile.php?error=current-password-wrong');
    }

    $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmtUpdate = db()->prepare('UPDATE users SET password = ? WHERE id = ?');
    $stmtUpdate->execute([$hashed, $userId]);

    redirect('views/profile.php?success=password-updated');
}

redirect('views/profile.php');
