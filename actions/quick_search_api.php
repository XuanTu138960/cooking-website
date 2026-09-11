<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');

$action = (string) ($_GET['action'] ?? 'search');

if ($action === 'search') {
    $q = trim((string) ($_GET['q'] ?? ''));
    if (mb_strlen($q) < 1) {
        echo json_encode(['success' => true, 'results' => []]);
        exit;
    }

    $term = '%' . $q . '%';
    $stmt = db()->prepare("
        SELECT r.id, r.title, r.category, r.cooking_time, r.servings, r.image_url, r.calories,
               u.username AS author_name,
               (SELECT COUNT(*) FROM recipe_likes rl WHERE rl.recipe_id = r.id) AS likes_count
        FROM recipes r
        JOIN users u ON u.id = r.author_id
        WHERE r.status = 'approved'
          AND (r.title LIKE ? OR r.ingredients LIKE ? OR r.category LIKE ?)
        ORDER BY r.views_count DESC, r.created_at DESC
        LIMIT 6
    ");
    $stmt->execute([$term, $term, $term]);
    $rows = $stmt->fetchAll();

    $results = [];
    foreach ($rows as $row) {
        $results[] = [
            'id' => (int) $row['id'],
            'title' => (string) $row['title'],
            'category' => (string) $row['category'],
            'cooking_time' => (string) $row['cooking_time'],
            'servings' => (string) $row['servings'],
            'calories' => $row['calories'] ? (int) $row['calories'] : null,
            'image_url' => $row['image_url'] ? BASE_URL . '/uploads/recipes/' . $row['image_url'] : null,
            'author_name' => (string) $row['author_name'],
            'likes_count' => (int) $row['likes_count'],
            'url' => BASE_URL . '/views/recipe-detail.php?id=' . $row['id']
        ];
    }

    echo json_encode(['success' => true, 'results' => $results]);
    exit;
}

if ($action === 'random_meal') {
    $mood = (string) ($_GET['mood'] ?? 'any');
    
    $where = ["r.status = 'approved'"];
    $params = [];

    if ($mood === 'quick') {
        $where[] = "(r.cooking_time LIKE '%15%' OR r.cooking_time LIKE '%20%' OR r.cooking_time LIKE '%25%' OR r.dietary_tags LIKE '%Nhanh%')";
    } elseif ($mood === 'healthy') {
        $where[] = "(r.dietary_tags LIKE '%Eat Clean%' OR r.dietary_tags LIKE '%Protein%' OR r.dietary_tags LIKE '%Ít Calo%' OR (r.calories IS NOT NULL AND r.calories <= 350))";
    } elseif ($mood === 'comfort') {
        $where[] = "(r.category = 'Món canh' OR r.category = 'Món lẩu' OR r.dietary_tags LIKE '%Ấm Bụng%' OR r.dietary_tags LIKE '%Truyền Thống%')";
    }

    $sql = "
        SELECT r.id, r.title, r.description, r.category, r.cooking_time, r.servings, r.image_url, r.calories, r.dietary_tags,
               u.username AS author_name,
               (SELECT COUNT(*) FROM recipe_likes rl WHERE rl.recipe_id = r.id) AS likes_count
        FROM recipes r
        JOIN users u ON u.id = r.author_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY RAND()
        LIMIT 1
    ";

    $stmt = db()->query($sql);
    $meal = $stmt->fetch();

    if (!$meal) {
        // Fallback: pick any approved recipe
        $meal = db()->query("SELECT r.*, u.username AS author_name FROM recipes r JOIN users u ON u.id = r.author_id WHERE r.status = 'approved' ORDER BY RAND() LIMIT 1")->fetch();
    }

    if ($meal) {
        echo json_encode([
            'success' => true,
            'recipe' => [
                'id' => (int) $meal['id'],
                'title' => (string) $meal['title'],
                'description' => (string) ($meal['description'] ?? ''),
                'category' => (string) $meal['category'],
                'cooking_time' => (string) $meal['cooking_time'],
                'servings' => (string) $meal['servings'],
                'calories' => $meal['calories'] ? (int) $meal['calories'] : null,
                'dietary_tags' => (string) ($meal['dietary_tags'] ?? ''),
                'image_url' => $meal['image_url'] ? BASE_URL . '/uploads/recipes/' . $meal['image_url'] : null,
                'author_name' => (string) $meal['author_name'],
                'url' => BASE_URL . '/views/recipe-detail.php?id=' . $meal['id']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Chưa có món ăn nào trong hệ thống.']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);