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

$returnUrl = (string) ($_POST['return_url'] ?? 'index.php');

if (!is_logged_in()) {
    redirect($returnUrl . (str_contains($returnUrl, '?') ? '&' : '?') . 'error=login-required');
}

$csrfToken = (string) ($_POST['csrf_token'] ?? '');
if (!verify_csrf_token($csrfToken)) {
    redirect($returnUrl . (str_contains($returnUrl, '?') ? '&' : '?') . 'error=csrf');
}

$authorId = filter_input(INPUT_POST, 'author_id', FILTER_VALIDATE_INT);
$followerId = (int) $_SESSION['user_id'];

if (!$authorId || $authorId === $followerId) {
    redirect($returnUrl);
}

// Check author exists
$stmtCheck = db()->prepare('SELECT id FROM users WHERE id = ?');
$stmtCheck->execute([$authorId]);
if (!$stmtCheck->fetch()) {
    redirect($returnUrl);
}

// Check follow state
$stmtFollow = db()->prepare('SELECT id FROM follows WHERE follower_id = ? AND author_id = ?');
$stmtFollow->execute([$followerId, $authorId]);
$existing = $stmtFollow->fetch();

if ($existing) {
    $stmtDel = db()->prepare('DELETE FROM follows WHERE follower_id = ? AND author_id = ?');
    $stmtDel->execute([$followerId, $authorId]);
    $msg = 'unfollowed';
} else {
    $stmtIns = db()->prepare('INSERT INTO follows (follower_id, author_id) VALUES (?, ?)');
    $stmtIns->execute([$followerId, $authorId]);
    $msg = 'followed';

    // Trigger notification
    $notif = db()->prepare("INSERT INTO notifications (user_id, actor_id, type, target_id, content) VALUES (?, ?, 'follow', NULL, 'đã bắt đầu theo dõi gian bếp của bạn.')");
    $notif->execute([$authorId, $followerId]);
}

redirect($returnUrl . (str_contains($returnUrl, '?') ? '&' : '?') . 'follow=' . $msg);