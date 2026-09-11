<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' || isset($_POST['ajax']) || isset($_GET['ajax']);

if (!is_logged_in()) {
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập!']);
        exit;
    }
    redirect('views/saved-recipes.php?error=login-required');
}

$userId = (int) $_SESSION['user_id'];
$action = (string) ($_POST['action'] ?? $_GET['action'] ?? '');
$returnUrl = (string) ($_POST['return_url'] ?? $_GET['return_url'] ?? 'views/cookbooks.php');

// Handle GET: list user cookbooks (used by recipe-detail modal)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list_for_recipe') {
    header('Content-Type: application/json; charset=utf-8');
    $recipeId = filter_input(INPUT_GET, 'recipe_id', FILTER_VALIDATE_INT);
    
    $stmt = db()->prepare("
        SELECT c.id, c.title, c.description,
               EXISTS(SELECT 1 FROM cookbook_recipes cr WHERE cr.cookbook_id = c.id AND cr.recipe_id = ?) AS has_recipe,
               (SELECT COUNT(*) FROM cookbook_recipes cr2 WHERE cr2.cookbook_id = c.id) AS total_recipes
        FROM cookbooks c
        WHERE c.user_id = ?
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$recipeId ?: 0, $userId]);
    $cookbooks = $stmt->fetchAll();

    echo json_encode(['success' => true, 'cookbooks' => $cookbooks]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect($returnUrl);
}

// CSRF check for POST
$csrfToken = (string) ($_POST['csrf_token'] ?? '');
if (!verify_csrf_token($csrfToken)) {
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Mã bảo mật không hợp lệ (CSRF).']);
        exit;
    }
    redirect($returnUrl . '?error=csrf');
}

switch ($action) {
    case 'create':
        $title = trim((string) ($_POST['title'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $recipeId = filter_input(INPUT_POST, 'recipe_id', FILTER_VALIDATE_INT);

        if ($title === '') {
            if ($isAjax) {
                echo json_encode(['success' => false, 'message' => 'Tên sổ tay không được để trống!']);
                exit;
            }
            redirect($returnUrl . '?error=empty-title');
        }

        $stmt = db()->prepare("INSERT INTO cookbooks (user_id, title, description, is_public) VALUES (?, ?, ?, 1)");
        $stmt->execute([$userId, $title, $description !== '' ? $description : null]);
        $newCookbookId = (int) db()->lastInsertId();

        if ($recipeId) {
            $stmtAdd = db()->prepare("INSERT IGNORE INTO cookbook_recipes (cookbook_id, recipe_id) VALUES (?, ?)");
            $stmtAdd->execute([$newCookbookId, $recipeId]);
        }

        if ($isAjax) {
            echo json_encode([
                'success' => true, 
                'message' => "Đã tạo sổ tay '{$title}' thành công!",
                'cookbook_id' => $newCookbookId
            ]);
            exit;
        }
        redirect('views/cookbooks.php?msg=created');
        break;

    case 'toggle_recipe':
        $cookbookId = filter_input(INPUT_POST, 'cookbook_id', FILTER_VALIDATE_INT);
        $recipeId = filter_input(INPUT_POST, 'recipe_id', FILTER_VALIDATE_INT);

        if (!$cookbookId || !$recipeId) {
            if ($isAjax) {
                echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
                exit;
            }
            redirect($returnUrl);
        }

        // Check ownership
        $chk = db()->prepare("SELECT id, title FROM cookbooks WHERE id = ? AND user_id = ?");
        $chk->execute([$cookbookId, $userId]);
        $cb = $chk->fetch();
        if (!$cb) {
            if ($isAjax) {
                echo json_encode(['success' => false, 'message' => 'Bạn không có quyền chỉnh sửa sổ tay này!']);
                exit;
            }
            redirect($returnUrl);
        }

        // Check exists in cookbook
        $chkRel = db()->prepare("SELECT id FROM cookbook_recipes WHERE cookbook_id = ? AND recipe_id = ?");
        $chkRel->execute([$cookbookId, $recipeId]);
        $exists = $chkRel->fetch();

        if ($exists) {
            $del = db()->prepare("DELETE FROM cookbook_recipes WHERE cookbook_id = ? AND recipe_id = ?");
            $del->execute([$cookbookId, $recipeId]);
            $added = false;
            $msg = "Đã xóa khỏi sổ tay '{$cb['title']}'";
        } else {
            $ins = db()->prepare("INSERT INTO cookbook_recipes (cookbook_id, recipe_id) VALUES (?, ?)");
            $ins->execute([$cookbookId, $recipeId]);
            $added = true;
            $msg = "Đã lưu vào sổ tay '{$cb['title']}'";
        }

        if ($isAjax) {
            echo json_encode([
                'success' => true,
                'added' => $added,
                'message' => $msg
            ]);
            exit;
        }
        redirect($returnUrl . '?msg=' . ($added ? 'saved' : 'removed'));
        break;

    case 'delete':
        $cookbookId = filter_input(INPUT_POST, 'cookbook_id', FILTER_VALIDATE_INT);
        if ($cookbookId) {
            $del = db()->prepare("DELETE FROM cookbooks WHERE id = ? AND user_id = ?");
            $del->execute([$cookbookId, $userId]);
        }
        redirect('views/cookbooks.php?msg=deleted');
        break;

    default:
        redirect($returnUrl);
}