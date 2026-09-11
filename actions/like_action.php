<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' || isset($_POST['ajax']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }
    redirect('index.php');
}

$returnUrl = (string) ($_POST['return_url'] ?? 'index.php');

if (!is_logged_in()) {
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để thả tim món ăn!']);
        exit;
    }
    redirect($returnUrl . (str_contains($returnUrl, '?') ? '&' : '?') . 'error=login-required');
}

$csrfToken = (string) ($_POST['csrf_token'] ?? '');
if (!verify_csrf_token($csrfToken)) {
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Mã bảo mật (CSRF) không hợp lệ']);
        exit;
    }
    redirect($returnUrl . (str_contains($returnUrl, '?') ? '&' : '?') . 'error=csrf');
}

$recipeId = filter_input(INPUT_POST, 'recipe_id', FILTER_VALIDATE_INT);
$userId = (int) $_SESSION['user_id'];

if (!$recipeId) {
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Thiếu ID món ăn']);
        exit;
    }
    redirect($returnUrl);
}

// Check recipe exists
$stmtCheck = db()->prepare('SELECT id, title, author_id, likes_count FROM recipes WHERE id = ?');
$stmtCheck->execute([$recipeId]);
$recipe = $stmtCheck->fetch();
if (!$recipe) {
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Món ăn không tồn tại']);
        exit;
    }
    redirect($returnUrl);
}

// Toggle like
$stmtLike = db()->prepare('SELECT id FROM recipe_likes WHERE recipe_id = ? AND user_id = ?');
$stmtLike->execute([$recipeId, $userId]);
$likedRow = $stmtLike->fetch();

$liked = false;
if ($likedRow) {
    $del = db()->prepare('DELETE FROM recipe_likes WHERE recipe_id = ? AND user_id = ?');
    $del->execute([$recipeId, $userId]);
    $liked = false;
} else {
    $ins = db()->prepare('INSERT INTO recipe_likes (recipe_id, user_id) VALUES (?, ?)');
    $ins->execute([$recipeId, $userId]);
    $liked = true;

    // Trigger notification to author
    $authorId = (int) $recipe['author_id'];
    if ($authorId !== $userId) {
        $notifStmt = db()->prepare("INSERT INTO notifications (user_id, actor_id, type, target_id, content) VALUES (?, ?, 'like', ?, ?)");
        $notifStmt->execute([$authorId, $userId, $recipeId, 'đã thả tim món ăn "' . $recipe['title'] . '" của bạn.']);
    }
}

// Recalculate likes_count
$countStmt = db()->prepare('SELECT COUNT(*) FROM recipe_likes WHERE recipe_id = ?');
$countStmt->execute([$recipeId]);
$newCount = (int) $countStmt->fetchColumn();

// Update recipes table
$upStmt = db()->prepare('UPDATE recipes SET likes_count = ? WHERE id = ?');
$upStmt->execute([$newCount, $recipeId]);

if ($isAjax) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => true,
        'liked' => $liked,
        'likes_count' => $newCount,
        'message' => $liked ? 'Đã thả tim món ăn!' : 'Đã bỏ thả tim'
    ]);
    exit;
}

redirect($returnUrl . (str_contains($returnUrl, '?') ? '&' : '?') . 'liked=' . ($liked ? '1' : '0'));