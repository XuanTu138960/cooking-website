<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';

/**
 * COOKIO AUTONOMOUS DAILY COMMUNITY BOT
 * Tự động tạo tài khoản, đăng bài mới, tương tác bình luận, thả tim và theo dõi mỗi 1 ngày (24 giờ).
 */
function runDailyCommunityBot(bool $force = false): array
{
    $logFile = __DIR__ . '/bot_status.json';
    $now = time();

    if (!$force && file_exists($logFile)) {
        $status = json_decode((string) file_get_contents($logFile), true);
        $lastRun = (int) ($status['last_run'] ?? 0);
        // Nếu chưa đủ 24 giờ (86400 giây) thì bỏ qua
        if (($now - $lastRun) < 86400) {
            return [
                'executed' => false,
                'message'  => 'Chưa đủ 24h kể từ lần chạy trước (lần chạy gần nhất: ' . date('Y-m-d H:i:s', $lastRun) . ')',
                'last_run' => $lastRun
            ];
        }
    }

    $results = [
        'executed'          => true,
        'accounts_created'  => 0,
        'recipes_published' => 0,
        'likes_given'       => 0,
        'comments_posted'   => 0,
        'follows_made'      => 0,
        'timestamp'         => date('Y-m-d H:i:s')
    ];

    try {
        $db = db();

        // =================================================================
        // BƯỚC 1: TẠO THÊM CÁC TÀI KHOẢN CỘNG ĐỒNG NẾU CHƯA CÓ
        // =================================================================
        $communityUsers = [
            [
                'username' => 'chef_duy',
                'password' => '1',
                'role'     => 'author',
                'phone'    => '0912345678',
                'bio'      => 'Bếp trưởng món Á với 12 năm kinh nghiệm, yêu hương vị truyền thống.'
            ],
            [
                'username' => 'bep_co_hoa',
                'password' => '1',
                'role'     => 'author',
                'phone'    => '0934567890',
                'bio'      => 'Mẹ nội trợ 25 năm kinh nghiệm nấu mâm cơm gia đình ấm áp chuẩn vị Bắc.'
            ],
            [
                'username' => 'ha_foodie',
                'password' => '1',
                'role'     => 'author',
                'phone'    => '0945678901',
                'bio'      => 'Khám phá ẩm thực 3 miền, thích làm các món dân dã và đặc sản độc đáo.'
            ],
            [
                'username' => 'huong_kitchen',
                'password' => '1',
                'role'     => 'author',
                'phone'    => '0956789012',
                'bio'      => 'Yêu thích làm bánh ngọt, tráng miệng và đồ uống mát lành cho gia đình.'
            ],
            [
                'username' => 'nam_phuot_cook',
                'password' => '1',
                'role'     => 'author',
                'phone'    => '0967890123',
                'bio'      => 'Chuyên các món nướng ngoài trời, lẩu và món nhậu thơm lừng cùng bạn bè.'
            ],
            [
                'username' => 'quynh_healthy',
                'password' => '1',
                'role'     => 'author',
                'phone'    => '0978901234',
                'bio'      => 'Thực đơn Eat Clean, giảm mỡ tăng cơ, ăn ngon sống khỏe mỗi ngày.'
            ],
            [
                'username' => 'thao_sweet',
                'password' => '1',
                'role'     => 'author',
                'phone'    => '0989012345',
                'bio'      => 'Đam mê bánh mì hoa cúc, bánh quy bơ và đồ ăn vặt lành tính cho trẻ nhỏ.'
            ],
            [
                'username' => 'tuan_mon_viet',
                'password' => '1',
                'role'     => 'author',
                'phone'    => '0990123456',
                'bio'      => 'Gìn giữ hồn cốt cơm mẹ nấu qua từng niêu cá kho và bát canh cua đồng quê.'
            ],
        ];

        $accountIds = [];
        $checkUserStmt = $db->prepare('SELECT id FROM users WHERE username = ?');
        $insertUserStmt = $db->prepare('INSERT INTO users (username, password_hash, role, phone, bio, created_at) VALUES (?, ?, ?, ?, ?, ?)');

        foreach ($communityUsers as $u) {
            $checkUserStmt->execute([$u['username']]);
            $existing = $checkUserStmt->fetch();
            if ($existing) {
                $accountIds[$u['username']] = (int) $existing['id'];
            } else {
                $hash = password_hash($u['password'], PASSWORD_DEFAULT);
                $insertUserStmt->execute([
                    $u['username'],
                    $hash,
                    $u['role'],
                    $u['phone'],
                    $u['bio'],
                    date('Y-m-d H:i:s', strtotime('-' . rand(20, 60) . ' days'))
                ]);
                $accountIds[$u['username']] = (int) $db->lastInsertId();
                $results['accounts_created']++;
            }
        }

        // =================================================================
        // BƯỚC 2: TỰ ĐỘNG ĐĂNG BÀI MÓN ĂN MỚI
        // =================================================================
        $newRecipesPool = [
            [
                'author'       => 'bep_co_hoa',
                'title'        => 'Thịt heo rim mắm tỏi ớt đưa cơm ngày mưa',
                'description'  => 'Món thịt heo thái mỏng rim vàng ruộm, quyện sốt nước mắm chua cay mặn ngọt cực kỳ đậm đà.',
                'ingredients'  => "500g Thịt ba chỉ hoặc nạc vai\n3 củ Tỏi băm nhuyễn\n2 quả Ớt tươi băm\n3 thìa Nước mắm ngon\n2 thìa Đường vàng\n1 thìa Hạt nêm, tiêu xay\nHành hoa thái nhỏ",
                'instructions' => "Bước 1: Thịt thái con chì vừa ăn, chần qua nước sôi rồi để ráo.\nBước 2: Cho thịt vào chảo đảo xém cạnh cho ra bớt mỡ.\nBước 3: Phi thơm tỏi ớt, cho hỗn hợp nước mắm đường vào đun sôi sền sệt.\nBước 4: Trút thịt vào đảo đều lửa nhỏ cho ngấm gia vị đến khi keo lại, rắc hành hoa rồi tắt bếp.",
                'category'     => 'Món kho',
                'cooking_time' => '25 phút',
                'servings'     => 4,
                'difficulty'   => 'Dễ',
                'calories'     => 380,
                'image_url'    => 'assets/images/real_dishes/tom-rim-ba-chi.jpg',
            ],
            [
                'author'       => 'chef_duy',
                'title'        => 'Bò lúc lắc sốt tiêu đen khoai tây chiên',
                'description'  => 'Thịt bò thăn mềm mọng nước xào lửa lớn cùng ớt chuông, hành tây và sốt tiêu đen thơm nức mũi.',
                'ingredients'  => "400g Thịt thăn bò mềm\n1 củ Hành tây\n1 quả Ớt chuông xanh, đỏ\n2 thìa Tiêu đen đập dập\n2 thìa Dầu hào, xì dầu\nBơ lạt, tỏi băm",
                'instructions' => "Bước 1: Thịt bò thái quân cờ vuông 2cm, ướp dầu hào, tiêu, tỏi băm 15 phút.\nBước 2: Hành tây và ớt chuông cắt miếng vuông vừa ăn.\nBước 3: Đun chảo thật nóng, cho bơ tỏi vào phi thơm rồi trút thịt bò vào lắc đều tay lửa lớn trong 2 phút.\nBước 4: Cho ớt chuông hành tây vào đảo nhanh, tắt bếp khi rau củ còn giòn ngọt.",
                'category'     => 'Món xào',
                'cooking_time' => '20 phút',
                'servings'     => 3,
                'difficulty'   => 'Trung bình',
                'calories'     => 420,
                'image_url'    => 'assets/images/real_dishes/bo-ne-thien-ly.jpg',
            ],
            [
                'author'       => 'quynh_healthy',
                'title'        => 'Salad tôm bơ chanh dây thanh mát giảm cân',
                'description'  => 'Món salad giàu đạm và chất béo tốt, kết hợp tôm hấp ngọt thịt và quả bơ bùi ngậy sốt chanh leo.',
                'ingredients'  => "300g Tôm sú tươi bóc vỏ\n1 quả Bơ sáp chín tới\nXà lách Romaine, cà chua bi\n2 quả Chanh dây vắt lấy cốt\n1 thìa Mật ong nguyên chất\n1 thìa Dầu ô liu extra virgin",
                'instructions' => "Bước 1: Tôm hấp chín tới với vài lát gừng, ngâm đá cho giòn.\nBước 2: Xà lách rửa sạch ngâm nước muối loãng, bơ thái hạt lựu, cà chua bi bổ đôi.\nBước 3: Pha sốt chanh leo cùng mật ong, dầu ô liu và chút muối tiêu.\nBước 4: Cho tất cả nguyên liệu vào bát lớn, rưới sốt và trộn nhẹ tay thưởng thức ngay.",
                'category'     => 'Món xào',
                'cooking_time' => '15 phút',
                'servings'     => 2,
                'difficulty'   => 'Dễ',
                'calories'     => 260,
                'image_url'    => 'assets/images/real_dishes/salad-uc-ga.jpg',
            ],
            [
                'author'       => 'tuan_mon_viet',
                'title'        => 'Canh sườn hầm rau củ ngọt thanh bổ dưỡng',
                'description'  => 'Nước canh trong veo ngọt lịm từ sườn non ninh kỹ cùng bắp ngọt, cà rốt và nấm hương tươi.',
                'ingredients'  => "500g Sườn non tươi\n1 bắp Ngô ngọt cắt khúc\n1 củ Cà rốt tỉa hoa\n1 củ Khoai tây\n10 tai Nấm hương\nHành ngò, gia vị vừa đủ",
                'instructions' => "Bước 1: Sườn chặt khúc chần nước sôi rồi rửa sạch bọt bẩn.\nBước 2: Cho sườn vào nồi hầm với 1.5 lít nước trong 30 phút lửa vừa.\nBước 3: Cho ngô ngọt, cà rốt, nấm hương vào ninh tiếp 15 phút.\nBước 4: Nêm nếm gia vị vừa miệng, tắt bếp rắc hành ngò thơm lừng.",
                'category'     => 'Món canh',
                'cooking_time' => '45 phút',
                'servings'     => 4,
                'difficulty'   => 'Dễ',
                'calories'     => 320,
                'image_url'    => 'assets/images/real_dishes/canh-bo-rau-xanh.jpg',
            ]
        ];

        $checkRecipeStmt = $db->prepare('SELECT id FROM recipes WHERE title = ?');
        $insertRecipeStmt = $db->prepare("
            INSERT INTO recipes (author_id, title, description, ingredients, instructions, category, cooking_time, servings, difficulty, calories, image_url, status, views_count, likes_count, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'approved', ?, ?, ?)
        ");

        foreach ($newRecipesPool as $recipe) {
            $checkRecipeStmt->execute([$recipe['title']]);
            if (!$checkRecipeStmt->fetch()) {
                $authorId = $accountIds[$recipe['author']] ?? 1;
                $views = rand(150, 850);
                $likes = rand(15, 65);
                $insertRecipeStmt->execute([
                    $authorId,
                    $recipe['title'],
                    $recipe['description'],
                    $recipe['ingredients'],
                    $recipe['instructions'],
                    $recipe['category'],
                    $recipe['cooking_time'],
                    $recipe['servings'],
                    $recipe['difficulty'],
                    $recipe['calories'],
                    $recipe['image_url'],
                    $views,
                    $likes,
                    date('Y-m-d H:i:s')
                ]);
                $results['recipes_published']++;
            }
        }

        // =================================================================
        // BƯỚC 3: TƯƠNG TÁC THẢ TIM MÓN ĂN (RECIPE LIKES)
        // =================================================================
        $allRecipes = $db->query('SELECT id, author_id, title FROM recipes WHERE status = "approved" ORDER BY id DESC LIMIT 20')->fetchAll();
        $checkLikeStmt = $db->prepare('SELECT id FROM recipe_likes WHERE recipe_id = ? AND user_id = ?');
        $insLikeStmt = $db->prepare('INSERT INTO recipe_likes (recipe_id, user_id, created_at) VALUES (?, ?, ?)');
        $insSavedStmt = $db->prepare('INSERT IGNORE INTO saved_recipes (user_id, recipe_id, created_at) VALUES (?, ?, ?)');
        $updateLikesCountStmt = $db->prepare('UPDATE recipes SET likes_count = (SELECT COUNT(*) FROM recipe_likes WHERE recipe_id = ?) WHERE id = ?');

        foreach ($allRecipes as $r) {
            // Mỗi món có xác suất được các tài khoản bot thả tim
            foreach ($accountIds as $botName => $botId) {
                if ($botId === (int) $r['author_id']) continue;
                if (rand(1, 100) <= 40) { // 40% cơ hội like
                    $checkLikeStmt->execute([(int) $r['id'], $botId]);
                    if (!$checkLikeStmt->fetch()) {
                        $nowStr = date('Y-m-d H:i:s');
                        $insLikeStmt->execute([(int) $r['id'], $botId, $nowStr]);
                        $insSavedStmt->execute([$botId, (int) $r['id'], $nowStr]);
                        $updateLikesCountStmt->execute([(int) $r['id'], (int) $r['id']]);
                        $results['likes_given']++;
                    }
                }
            }
        }

        // =================================================================
        // BƯỚC 4: TƯƠNG TÁC BÌNH LUẬN & ĐÁNH GIÁ (COMMENTS)
        // =================================================================
        $commentComments = [
            'Công thức ướp rất vừa miệng, mình làm cho cả nhà ăn ai cũng tấm tắc khen ngợi!',
            'Mẹo nhỏ của tác giả rất hữu ích, món ăn giòn thơm chuẩn vị luôn ạ!',
            'Nhìn hấp dẫn quá, mình vừa lưu lại để cuối tuần này vào bếp nấu cho gia đình.',
            'Mình đã nấu thử và thành công ngay lần đầu tiên, cảm ơn bạn đã chia sẻ công thức tận tâm! ❤️',
            'Món này ăn kèm cơm nóng vào thời tiết se lạnh này thì chuẩn bài luôn, cho 5 sao!',
            'Nước sốt đậm đà, màu sắc bắt mắt quá tác giả ơi, hướng dẫn rất chi tiết dễ hiểu.',
            'Hôm qua vừa làm theo tỷ lệ gia vị của bạn, bạn bè đến chơi ai cũng khen ngon.',
            'Một món ăn vừa ngon vừa đủ chất dinh dưỡng cho cả gia đình, rất đáng thử!'
        ];

        $checkCommentStmt = $db->prepare('SELECT id FROM comments WHERE recipe_id = ? AND user_id = ?');
        $insCommentStmt = $db->prepare('INSERT INTO comments (recipe_id, user_id, content, rating, created_at) VALUES (?, ?, ?, ?, ?)');

        foreach ($allRecipes as $r) {
            foreach ($accountIds as $botName => $botId) {
                if ($botId === (int) $r['author_id']) continue;
                if (rand(1, 100) <= 25) { // 25% cơ hội bình luận
                    $checkCommentStmt->execute([(int) $r['id'], $botId]);
                    if (!$checkCommentStmt->fetch()) {
                        $commentText = $commentComments[array_rand($commentComments)];
                        $rating = rand(4, 5);
                        $insCommentStmt->execute([
                            (int) $r['id'],
                            $botId,
                            $commentText,
                            $rating,
                            date('Y-m-d H:i:s', strtotime('-' . rand(1, 48) . ' hours'))
                        ]);
                        $results['comments_posted']++;
                    }
                }
            }
        }

        // =================================================================
        // BƯỚC 5: TƯƠNG TÁC THEO DÕI NHAU (FOLLOWS)
        // =================================================================
        $checkFollowStmt = $db->prepare('SELECT id FROM follows WHERE follower_id = ? AND author_id = ?');
        $insFollowStmt = $db->prepare('INSERT INTO follows (follower_id, author_id, created_at) VALUES (?, ?, ?)');

        $botIdList = array_values($accountIds);
        foreach ($botIdList as $followerId) {
            foreach ($botIdList as $authorId) {
                if ($followerId === $authorId) continue;
                if (rand(1, 100) <= 50) {
                    $checkFollowStmt->execute([$followerId, $authorId]);
                    if (!$checkFollowStmt->fetch()) {
                        $insFollowStmt->execute([$followerId, $authorId, date('Y-m-d H:i:s')]);
                        $results['follows_made']++;
                    }
                }
            }
        }

        // Cập nhật trạng thái lần chạy thành công
        file_put_contents($logFile, json_encode([
            'last_run' => $now,
            'summary'  => $results
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    } catch (Throwable $e) {
        $results['error'] = $e->getMessage();
    }

    return $results;
}

// Nếu chạy trực tiếp từ dòng lệnh CLI
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    $res = runDailyCommunityBot(true);
    echo "=== COOKIO DAILY COMMUNITY BOT RESULT ===" . PHP_EOL;
    echo json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
