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

if (!is_logged_in()) {
    $returnUrl = (string) ($_POST['return_url'] ?? 'index.php');
    redirect($returnUrl . (str_contains($returnUrl, '?') ? '&' : '?') . 'error=login-required');
}

$csrfToken = (string) ($_POST['csrf_token'] ?? '');
$returnUrl = (string) ($_POST['return_url'] ?? 'index.php');

if (!verify_csrf_token($csrfToken)) {
    redirect($returnUrl . (str_contains($returnUrl, '?') ? '&' : '?') . 'error=csrf');
}

$recipeId = filter_input(INPUT_POST, 'recipe_id', FILTER_VALIDATE_INT);
if (!$recipeId) {
    redirect($returnUrl . (str_contains($returnUrl, '?') ? '&' : '?') . 'error=invalid-recipe');
}

$action = (string) ($_POST['action'] ?? 'toggle');
$userId = (int) $_SESSION['user_id'];
$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || isset($_POST['ajax']);

if ($action === 'save_note') {
    $note = trim((string) ($_POST['note'] ?? ''));
    // If not saved yet, save it with note
    $stmtCheck = db()->prepare('SELECT id FROM saved_recipes WHERE user_id = ? AND recipe_id = ?');
    $stmtCheck->execute([$userId, $recipeId]);
    $saved = $stmtCheck->fetch();

    if ($saved) {
        $stmtUpdate = db()->prepare('UPDATE saved_recipes SET note = ? WHERE id = ?');
        $stmtUpdate->execute([$note !== '' ? $note : null, $saved['id']]);
    } else {
        $stmtInsert = db()->prepare('INSERT INTO saved_recipes (user_id, recipe_id, note) VALUES (?, ?, ?)');
        $stmtInsert->execute([$userId, $recipeId, $note !== '' ? $note : null]);
    }

    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'note' => $note, 'message' => 'Đã lưu ghi chú bếp thành công!']);
        exit;
    }

    redirect($returnUrl . (str_contains($returnUrl, '?') ? '&' : '?') . 'bookmark=note_saved');
}

$stmtCheck = db()->prepare('SELECT id FROM saved_recipes WHERE user_id = ? AND recipe_id = ?');
$stmtCheck->execute([$userId, $recipeId]);
$saved = $stmtCheck->fetch();

if ($saved) {
    $stmtDelete = db()->prepare('DELETE FROM saved_recipes WHERE id = ?');
    $stmtDelete->execute([$saved['id']]);
    $statusMsg = 'unsaved';
} else {
    $stmtInsert = db()->prepare('INSERT INTO saved_recipes (user_id, recipe_id) VALUES (?, ?)');
    $stmtInsert->execute([$userId, $recipeId]);
    $statusMsg = 'saved';
}

if ($isAjax) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => true, 'status' => $statusMsg]);
    exit;
}

redirect($returnUrl . (str_contains($returnUrl, '?') ? '&' : '?') . 'bookmark=' . $statusMsg);

