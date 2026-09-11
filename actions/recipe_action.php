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
$action = (string) ($_POST['action'] ?? 'create');

if (!verify_csrf_token($csrfToken)) {
    $redirectUrl = match ($action) {
        'edit' => 'views/edit-recipe.php?id=' . ((int) ($_POST['recipe_id'] ?? 0)) . '&error=csrf',
        'delete_own' => 'views/my-recipes.php?error=csrf',
        default => 'views/create-recipe.php?error=csrf',
    };
    redirect($redirectUrl);
}

// 1. DELETE OWN RECIPE
if ($action === 'delete_own') {
    $recipeId = filter_input(INPUT_POST, 'recipe_id', FILTER_VALIDATE_INT);
    if ($recipeId) {
        $stmtFind = db()->prepare('SELECT * FROM recipes WHERE id = ?');
        $stmtFind->execute([$recipeId]);
        $recipe = $stmtFind->fetch();

        if ($recipe && ((int) $recipe['author_id'] === (int) $_SESSION['user_id'] || is_admin())) {
            if (!empty($recipe['image_url'])) {
                $filePath = __DIR__ . '/../' . $recipe['image_url'];
                if (is_file($filePath)) {
                    @unlink($filePath);
                }
            }
            $stmtDel = db()->prepare('DELETE FROM recipes WHERE id = ?');
            $stmtDel->execute([$recipeId]);
        }
    }
    redirect('views/my-recipes.php?success=deleted');
}

// 2. EDIT RECIPE
if ($action === 'edit') {
    $recipeId = filter_input(INPUT_POST, 'recipe_id', FILTER_VALIDATE_INT);
    if (!$recipeId) {
        redirect('views/my-recipes.php?error=invalid-recipe');
    }

    $stmtFind = db()->prepare('SELECT * FROM recipes WHERE id = ?');
    $stmtFind->execute([$recipeId]);
    $recipe = $stmtFind->fetch();

    if (!$recipe || ((int) $recipe['author_id'] !== (int) $_SESSION['user_id'] && !is_admin())) {
        redirect('views/my-recipes.php?error=forbidden');
    }

    $title = trim((string) ($_POST['title'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $ingredients = trim((string) ($_POST['ingredients'] ?? ''));
    $instructions = trim((string) ($_POST['instructions'] ?? ''));
    $imageUrl = $recipe['image_url'];

    if ($title === '' || $ingredients === '' || $instructions === '') {
        redirect('views/edit-recipe.php?id=' . $recipeId . '&error=missing-fields');
    }

    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $image = $_FILES['image'];
        if ($image['error'] !== UPLOAD_ERR_OK || $image['size'] > MAX_UPLOAD_SIZE) {
            redirect('views/edit-recipe.php?id=' . $recipeId . '&error=invalid-image');
        }

        $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($image['tmp_name']);
        if (!in_array($mimeType, ALLOWED_IMAGE_TYPES, true)) {
            redirect('views/edit-recipe.php?id=' . $recipeId . '&error=invalid-image');
        }

        if (!is_dir(UPLOAD_DIR)) {
            mkdir(UPLOAD_DIR, 0755, true);
        }

        $extension = match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            default => 'webp',
        };

        $fileName = bin2hex(random_bytes(16)) . '.' . $extension;
        if (!move_uploaded_file($image['tmp_name'], UPLOAD_DIR . $fileName)) {
            redirect('views/edit-recipe.php?id=' . $recipeId . '&error=upload-failed');
        }

        // Remove old image
        if (!empty($recipe['image_url'])) {
            $oldPath = __DIR__ . '/../' . $recipe['image_url'];
            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
        }

        $imageUrl = 'uploads/recipes/' . $fileName;
    }

    $category = trim((string) ($_POST['category'] ?? 'Món chính'));
    $cookingTime = trim((string) ($_POST['cooking_time'] ?? '30 phút'));
    $servings = trim((string) ($_POST['servings'] ?? '2 - 4 người'));
    $tips = trim((string) ($_POST['tips'] ?? ''));
    $calories = filter_input(INPUT_POST, 'calories', FILTER_VALIDATE_INT) ?: null;
    $protein = filter_input(INPUT_POST, 'protein', FILTER_VALIDATE_INT) ?: null;
    $carbs = filter_input(INPUT_POST, 'carbs', FILTER_VALIDATE_INT) ?: null;
    $fat = filter_input(INPUT_POST, 'fat', FILTER_VALIDATE_INT) ?: null;
    $dietaryTags = trim((string) ($_POST['dietary_tags'] ?? ''));

    // If edited by regular user, return to 'pending' for review
    $newStatus = is_admin() ? $recipe['status'] : 'pending';

    $stmtUpdate = db()->prepare(
        'UPDATE recipes SET title = ?, description = ?, category = ?, cooking_time = ?, servings = ?, ingredients = ?, instructions = ?, tips = ?, calories = ?, protein = ?, carbs = ?, fat = ?, dietary_tags = ?, image_url = ?, status = ? WHERE id = ?'
    );
    $stmtUpdate->execute([
        $title, 
        $description ?: null, 
        $category ?: 'Món chính', 
        $cookingTime ?: '30 phút', 
        $servings ?: '2 - 4 người', 
        $ingredients, 
        $instructions, 
        $tips ?: null,
        $calories,
        $protein,
        $carbs,
        $fat,
        $dietaryTags ?: null,
        $imageUrl, 
        $newStatus, 
        $recipeId
    ]);

    redirect('views/my-recipes.php?success=edited');
}

// 3. CREATE RECIPE (DEFAULT)
$title = trim((string) ($_POST['title'] ?? ''));
$description = trim((string) ($_POST['description'] ?? ''));
$category = trim((string) ($_POST['category'] ?? 'Món chính'));
$cookingTime = trim((string) ($_POST['cooking_time'] ?? '30 phút'));
$servings = trim((string) ($_POST['servings'] ?? '2 - 4 người'));
$ingredients = trim((string) ($_POST['ingredients'] ?? ''));
$instructions = trim((string) ($_POST['instructions'] ?? ''));
$tips = trim((string) ($_POST['tips'] ?? ''));
$calories = filter_input(INPUT_POST, 'calories', FILTER_VALIDATE_INT) ?: null;
$protein = filter_input(INPUT_POST, 'protein', FILTER_VALIDATE_INT) ?: null;
$carbs = filter_input(INPUT_POST, 'carbs', FILTER_VALIDATE_INT) ?: null;
$fat = filter_input(INPUT_POST, 'fat', FILTER_VALIDATE_INT) ?: null;
$dietaryTags = trim((string) ($_POST['dietary_tags'] ?? ''));
$imageUrl = null;

if ($title === '' || $ingredients === '' || $instructions === '') {
    redirect('views/create-recipe.php?error=missing-fields');
}

if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
    $image = $_FILES['image'];
    if ($image['error'] !== UPLOAD_ERR_OK || $image['size'] > MAX_UPLOAD_SIZE) {
        redirect('views/create-recipe.php?error=invalid-image');
    }

    $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($image['tmp_name']);
    if (!in_array($mimeType, ALLOWED_IMAGE_TYPES, true)) {
        redirect('views/create-recipe.php?error=invalid-image');
    }

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    $extension = match ($mimeType) {
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        default => 'webp',
    };

    $fileName = bin2hex(random_bytes(16)) . '.' . $extension;
    if (!move_uploaded_file($image['tmp_name'], UPLOAD_DIR . $fileName)) {
        redirect('views/create-recipe.php?error=upload-failed');
    }
    $imageUrl = 'uploads/recipes/' . $fileName;
}

$status = is_admin() ? 'approved' : 'pending';

$statement = db()->prepare(
    'INSERT INTO recipes (title, description, category, cooking_time, servings, ingredients, instructions, tips, calories, protein, carbs, fat, dietary_tags, image_url, author_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
);
$statement->execute([
    $title, 
    $description ?: null, 
    $category ?: 'Món chính', 
    $cookingTime ?: '30 phút', 
    $servings ?: '2 - 4 người', 
    $ingredients, 
    $instructions, 
    $tips ?: null,
    $calories,
    $protein,
    $carbs,
    $fat,
    $dietaryTags ?: null,
    $imageUrl, 
    (int) $_SESSION['user_id'], 
    $status
]);

redirect('views/create-recipe.php?success=' . ($status === 'approved' ? 'published' : 'submitted'));
