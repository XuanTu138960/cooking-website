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
$returnUrl = (string) ($_POST['return_url'] ?? 'index.php');

if (!verify_csrf_token($csrfToken)) {
    redirect($returnUrl . (str_contains($returnUrl, '?') ? '&' : '?') . 'error=csrf#comments');
}

$action = (string) ($_POST['action'] ?? 'add');

if ($action === 'add') {
    $recipeId = filter_input(INPUT_POST, 'recipe_id', FILTER_VALIDATE_INT);
    $content = trim((string) ($_POST['content'] ?? ''));

    if (!$recipeId || $content === '') {
        redirect($returnUrl . (str_contains($returnUrl, '?') ? '&' : '?') . 'error=empty-comment#comments');
    }

    $stmtCheck = db()->prepare('SELECT id, title, author_id FROM recipes WHERE id = ?');
    $stmtCheck->execute([$recipeId]);
    $recipe = $stmtCheck->fetch();
    if (!$recipe) {
        redirect('index.php?error=recipe-not-found');
    }

    $parentId = filter_input(INPUT_POST, 'parent_id', FILTER_VALIDATE_INT) ?: null;

    $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
    if (!$rating || $rating < 1 || $rating > 5) {
        $rating = 5;
    }

    $imageUrl = null;
    if (isset($_FILES['cooksnap_image']) && $_FILES['cooksnap_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['cooksnap_image'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);

        if (in_array($mime, $allowedTypes, true) && $file['size'] <= $maxSize) {
            $ext = match ($mime) {
                'image/png' => 'png',
                'image/webp' => 'webp',
                default => 'jpg',
            };
            $fileName = 'cooksnap_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
            $uploadDir = __DIR__ . '/../uploads/cooksnaps';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $targetPath = $uploadDir . '/' . $fileName;

            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $imageUrl = 'uploads/cooksnaps/' . $fileName;
            }
        }
    }

    $currentUserId = (int) $_SESSION['user_id'];
    $stmt = db()->prepare('INSERT INTO comments (recipe_id, parent_id, user_id, content, rating, image_url) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$recipeId, $parentId, $currentUserId, $content, $rating, $imageUrl]);

    // Send notification
    if ($parentId) {
        // Find parent comment author
        $parentStmt = db()->prepare('SELECT user_id FROM comments WHERE id = ?');
        $parentStmt->execute([$parentId]);
        $parentUserId = (int) $parentStmt->fetchColumn();
        if ($parentUserId && $parentUserId !== $currentUserId) {
            $notif = db()->prepare("INSERT INTO notifications (user_id, actor_id, type, target_id, content) VALUES (?, ?, 'comment', ?, ?)");
            $notif->execute([$parentUserId, $currentUserId, $recipeId, 'đã trả lời bình luận của bạn trong món "' . $recipe['title'] . '".']);
        }
    } else {
        $authorId = (int) $recipe['author_id'];
        if ($authorId !== $currentUserId) {
            $type = $imageUrl ? 'cooksnap' : 'comment';
            $msg = $imageUrl 
                ? 'đã đăng 1 ảnh Cooksnap thành phẩm cho món "' . $recipe['title'] . '" của bạn.'
                : 'đã để lại nhận xét và đánh giá cho món "' . $recipe['title'] . '" của bạn.';
            $notif = db()->prepare("INSERT INTO notifications (user_id, actor_id, type, target_id, content) VALUES (?, ?, ?, ?, ?)");
            $notif->execute([$authorId, $currentUserId, $type, $recipeId, $msg]);
        }
    }

    redirect($returnUrl . (str_contains($returnUrl, '?') ? '&' : '?') . 'comment=success#comments');
}

if ($action === 'delete') {
    $commentId = filter_input(INPUT_POST, 'comment_id', FILTER_VALIDATE_INT);
    if ($commentId) {
        $stmtFind = db()->prepare('SELECT user_id, image_url FROM comments WHERE id = ?');
        $stmtFind->execute([$commentId]);
        $comment = $stmtFind->fetch();

        if ($comment && ($comment['user_id'] == $_SESSION['user_id'] || is_admin())) {
            // Delete image file if exists
            if (!empty($comment['image_url'])) {
                $filePath = __DIR__ . '/../' . $comment['image_url'];
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }
            $stmtDel = db()->prepare('DELETE FROM comments WHERE id = ?');
            $stmtDel->execute([$commentId]);
        }
    }

    redirect($returnUrl . (str_contains($returnUrl, '?') ? '&' : '?') . 'comment=deleted#comments');
}

redirect($returnUrl);
