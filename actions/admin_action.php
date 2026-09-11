<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!is_admin() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

$csrfToken = (string) ($_POST['csrf_token'] ?? '');
if (!verify_csrf_token($csrfToken)) {
    redirect('admin/index.php?error=csrf');
}

$action = (string) ($_POST['action'] ?? 'update_status');
$returnUrl = (string) ($_POST['return_url'] ?? 'admin/index.php');

if ($action === 'delete_recipe') {
    $recipeId = filter_input(INPUT_POST, 'recipe_id', FILTER_VALIDATE_INT);
    if ($recipeId) {
        $stmtFind = db()->prepare('SELECT image_url FROM recipes WHERE id = ?');
        $stmtFind->execute([$recipeId]);
        $row = $stmtFind->fetch();
        if ($row && !empty($row['image_url'])) {
            $filePath = __DIR__ . '/../' . $row['image_url'];
            if (is_file($filePath)) {
                @unlink($filePath);
            }
        }
        $stmtDelete = db()->prepare('DELETE FROM recipes WHERE id = ?');
        $stmtDelete->execute([$recipeId]);
    }
    redirect($returnUrl . '?success=deleted');
}

if ($action === 'delete_user') {
    $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    if ($userId && $userId !== (int) ($_SESSION['user_id'] ?? 0)) {
        $stmtDelete = db()->prepare('DELETE FROM users WHERE id = ?');
        $stmtDelete->execute([$userId]);
    }
    redirect('admin/manage-users.php?success=user-deleted');
}

if ($action === 'toggle_role') {
    $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    $newRole = (string) ($_POST['new_role'] ?? 'user');
    if ($userId && $userId !== (int) ($_SESSION['user_id'] ?? 0) && in_array($newRole, ['user', 'admin'], true)) {
        $stmtUpdate = db()->prepare('UPDATE users SET role = ? WHERE id = ?');
        $stmtUpdate->execute([$newRole, $userId]);
    }
    redirect('admin/manage-users.php?success=role-updated');
}

$recipeId = filter_input(INPUT_POST, 'recipe_id', FILTER_VALIDATE_INT);
$status = (string) ($_POST['status'] ?? '');
if (!$recipeId || !in_array($status, ['approved', 'rejected'], true)) {
    redirect($returnUrl . '?error=invalid-action');
}

$statement = db()->prepare('UPDATE recipes SET status = ? WHERE id = ?');
$statement->execute([$status, $recipeId]);

redirect($returnUrl . '?success=updated');
