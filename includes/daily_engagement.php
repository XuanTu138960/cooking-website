<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/db.php';

/**
 * Daily Auto-Engagement Engine for Cookio
 * Simulates natural community activity: daily views growth, new likes, and authentic comments.
 */
function run_daily_engagement(bool $force = false): array
{
    $pdo = db();
    $today = date('Y-m-d');

    // 1. Check last execution date
    $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'last_daily_simulation'");
    $stmt->execute();
    $lastRun = $stmt->fetchColumn();

    if (!$force && $lastRun === $today) {
        return ['status' => 'skipped', 'message' => 'Tương tác hôm nay đã được cập nhật trước đó.'];
    }

    $stats = [
        'views_added' => 0,
        'likes_added' => 0,
        'comments_added' => 0,
    ];

    try {
        // 2. Increment recipe views naturally (+15 to +55 views per recipe)
        $pdo->exec("
            UPDATE recipes 
            SET views_count = views_count + FLOOR(15 + RAND() * 45) 
            WHERE status = 'approved'
        ");
        $stats['views_added'] = 1;

        // 3. Get active member accounts to simulate interaction
        $members = $pdo->query("SELECT id, username FROM users WHERE role = 'user'")->fetchAll();
        if (empty($members)) {
            $members = [['id' => 2, 'username' => 'chef_lan']];
        }

        // 4. Select 2-3 random recipes to receive new likes
        $recipesForLikes = $pdo->query("SELECT id, author_id, title FROM recipes WHERE status = 'approved' ORDER BY RAND() LIMIT 3")->fetchAll();
        foreach ($recipesForLikes as $r) {
            $randomMember = $members[array_rand($members)];
            if ((int)$randomMember['id'] === (int)$r['author_id']) {
                continue;
            }

            $stmtCheckLike = $pdo->prepare("SELECT COUNT(*) FROM recipe_likes WHERE recipe_id = ? AND user_id = ?");
            $stmtCheckLike->execute([$r['id'], $randomMember['id']]);
            if ((int)$stmtCheckLike->fetchColumn() === 0) {
                $stmtAddLike = $pdo->prepare("INSERT INTO recipe_likes (recipe_id, user_id, created_at) VALUES (?, ?, NOW())");
                $stmtAddLike->execute([$r['id'], $randomMember['id']]);

                // Update likes count on recipe
                $pdo->prepare("UPDATE recipes SET likes_count = likes_count + 1 WHERE id = ?")->execute([$r['id']]);

                // Create notification for recipe author
                $pdo->prepare("
                    INSERT INTO notifications (user_id, actor_id, type, target_id, content, is_read, created_at)
                    VALUES (?, ?, 'like', ?, ?, 0, NOW())
                ")->execute([
                    $r['author_id'],
                    $randomMember['id'],
                    $r['id'],
                    "Đầu bếp @" . $randomMember['username'] . " vừa thả tim cho món " . $r['title'] . " của bạn!"
                ]);
                $stats['likes_added']++;
            }
        }

        // 5. Select 1-2 random recipes to receive a new authentic comment
        $commentPool = [
            'Hôm nay cả nhà mình cùng nấu món này theo công thức của bạn, nêm nếm gia vị rất vừa vặn và tốn cơm lắm!',
            'Công thức chi tiết, dễ làm theo ngay từ lần đầu tiên. Chấm 5 sao cho chủ bếp nhé!',
            'Mẹo của tác giả rất hay, món ăn thơm lừng giòn ngon chuẩn vị cơm nhà ấm cúng.',
            'Đã làm thử cho các bé ăn bữa tối, cả nhà ai cũng khen nức nở và ăn hết veo!',
            'Món ăn màu sắc bóng đẹp bắt mắt, nguyên liệu dễ tìm mà thành phẩm quá đỉnh.',
            'Thêm chút tiêu xay và ớt băm nữa là tròn vị luôn, cảm ơn bạn đã chia sẻ công thức tuyệt vời này!'
        ];

        $recipesForComments = $pdo->query("SELECT id, author_id, title FROM recipes WHERE status = 'approved' ORDER BY RAND() LIMIT 2")->fetchAll();
        foreach ($recipesForComments as $r) {
            $randomMember = $members[array_rand($members)];
            if ((int)$randomMember['id'] === (int)$r['author_id']) {
                continue;
            }

            $content = $commentPool[array_rand($commentPool)];
            $stmtAddComment = $pdo->prepare("
                INSERT INTO comments (recipe_id, user_id, rating, content, created_at)
                VALUES (?, ?, 5, ?, NOW())
            ");
            $stmtAddComment->execute([$r['id'], $randomMember['id'], $content]);
            $commentId = (int)$pdo->lastInsertId();

            // Notification for recipe author
            $pdo->prepare("
                INSERT INTO notifications (user_id, actor_id, type, target_id, content, is_read, created_at)
                VALUES (?, ?, 'comment', ?, ?, 0, NOW())
            ")->execute([
                $r['author_id'],
                $randomMember['id'],
                $commentId,
                "Đầu bếp @" . $randomMember['username'] . " vừa để lại đánh giá 5 sao cho món " . $r['title'] . "!"
            ]);
            $stats['comments_added']++;
        }

        // 6. Update last simulation date
        $pdo->prepare("
            INSERT INTO system_settings (setting_key, setting_value, updated_at)
            VALUES ('last_daily_simulation', ?, NOW())
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
        ")->execute([$today]);

        return [
            'status' => 'success',
            'message' => "Đã mô phỏng tương tác ngày $today thành công!",
            'stats' => $stats
        ];
    } catch (\Throwable $e) {
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}
