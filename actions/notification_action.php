<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}

$userId = (int) $_SESSION['user_id'];
$action = (string) ($_GET['action'] ?? $_POST['action'] ?? 'list');
$filterType = trim((string) ($_GET['type'] ?? $_POST['type'] ?? 'all'));

if ($action === 'list') {
    $whereClause = "WHERE n.user_id = ?";
    $params = [$userId];

    if ($filterType === 'like') {
        $whereClause .= " AND n.type = 'like'";
    } elseif ($filterType === 'comment') {
        $whereClause .= " AND (n.type = 'comment' OR n.type = 'cooksnap')";
    } elseif ($filterType === 'follow') {
        $whereClause .= " AND n.type = 'follow'";
    }

    $stmt = db()->prepare("
        SELECT n.*, u.username AS actor_name
        FROM notifications n
        JOIN users u ON u.id = n.actor_id
        $whereClause
        ORDER BY n.created_at DESC
        LIMIT 30
    ");
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $unreadCount = (int) db()->query("SELECT COUNT(*) FROM notifications WHERE user_id = $userId AND is_read = 0")->fetchColumn();

    $items = [];
    $now = time();

    foreach ($rows as $row) {
        $type = $row['type'];
        $icon = match ($type) {
            'like' => '❤️',
            'comment' => '💬',
            'cooksnap' => '📸',
            'follow' => '👥',
            default => '🔔'
        };

        $targetUrl = '#';
        if ($row['target_id']) {
            $targetUrl = BASE_URL . '/views/recipe-detail.php?id=' . $row['target_id'] . ($type === 'comment' || $type === 'cooksnap' ? '#comments' : '');
        } elseif ($type === 'follow') {
            $targetUrl = BASE_URL . '/views/author.php?id=' . $row['actor_id'];
        }

        // Relative time calculation
        $createdTime = strtotime((string)$row['created_at']);
        $diff = $now - $createdTime;
        if ($diff < 60) {
            $timeAgo = 'Vừa xong';
        } elseif ($diff < 3600) {
            $timeAgo = floor($diff / 60) . ' phút trước';
        } elseif ($diff < 86400) {
            $timeAgo = floor($diff / 3600) . ' giờ trước';
        } elseif ($diff < 172800) {
            $timeAgo = 'Hôm qua, ' . date('H:i', $createdTime);
        } else {
            $timeAgo = date('d/m/Y H:i', $createdTime);
        }

        $items[] = [
            'id' => (int) $row['id'],
            'actor_name' => (string) $row['actor_name'],
            'icon' => $icon,
            'type' => $type,
            'content' => (string) $row['content'],
            'is_read' => (int) $row['is_read'] === 1,
            'target_url' => $targetUrl,
            'created_at' => $timeAgo
        ];
    }

    echo json_encode([
        'success' => true,
        'unread_count' => $unreadCount,
        'notifications' => $items
    ]);
    exit;
}

if ($action === 'mark_read') {
    $notifId = filter_input(INPUT_POST, 'notif_id', FILTER_VALIDATE_INT);
    if ($notifId) {
        $up = db()->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $up->execute([$notifId, $userId]);
    }
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'mark_all_read') {
    $up = db()->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $up->execute([$userId]);
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);