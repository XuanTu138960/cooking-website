-- =======================================================
-- COOKIO DATABASE SCHEMA & SEED DATA (FULL DUMP)
-- Generated for GitHub: https://github.com/XuanTu138960/cooking-website
-- =======================================================

CREATE DATABASE IF NOT EXISTS cookio_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cookio_db;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS cookbook_recipes;
DROP TABLE IF EXISTS cookbooks;
DROP TABLE IF EXISTS recipe_likes;
DROP TABLE IF EXISTS follows;
DROP TABLE IF EXISTS comments;
DROP TABLE IF EXISTS saved_recipes;
DROP TABLE IF EXISTS recipes;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- 1. USERS TABLE
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NULL,
    bio VARCHAR(500) NULL,
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. RECIPES TABLE
CREATE TABLE recipes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT NULL,
    category VARCHAR(50) NOT NULL DEFAULT 'Món chính',
    cooking_time VARCHAR(50) NOT NULL DEFAULT '30 phút',
    servings VARCHAR(50) NOT NULL DEFAULT '2 - 4 người',
    ingredients TEXT NOT NULL,
    instructions TEXT NOT NULL,
    tips TEXT NULL,
    calories INT UNSIGNED NULL DEFAULT NULL,
    protein INT UNSIGNED NULL DEFAULT NULL,
    carbs INT UNSIGNED NULL DEFAULT NULL,
    fat INT UNSIGNED NULL DEFAULT NULL,
    dietary_tags VARCHAR(255) NULL DEFAULT NULL,
    image_url VARCHAR(255) NULL,
    author_id INT UNSIGNED NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    views_count INT UNSIGNED NOT NULL DEFAULT 0,
    likes_count INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_recipes_author FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_recipes_status (status),
    INDEX idx_recipes_author (author_id),
    INDEX idx_recipes_category (category),
    INDEX idx_recipes_views (views_count),
    INDEX idx_recipes_likes (likes_count)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. SAVED RECIPES TABLE
CREATE TABLE saved_recipes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    recipe_id INT UNSIGNED NOT NULL,
    note TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_recipe (user_id, recipe_id),
    CONSTRAINT fk_saved_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_saved_recipe FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. COMMENTS & REPLIES TABLE
CREATE TABLE comments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    recipe_id INT UNSIGNED NOT NULL,
    parent_id INT UNSIGNED NULL DEFAULT NULL,
    user_id INT UNSIGNED NOT NULL,
    content TEXT NOT NULL,
    rating TINYINT UNSIGNED NOT NULL DEFAULT 5,
    image_url VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_comments_recipe FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE,
    CONSTRAINT fk_comments_parent FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE CASCADE,
    CONSTRAINT fk_comments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_comments_recipe (recipe_id),
    INDEX idx_comments_parent (parent_id),
    INDEX idx_comments_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. FOLLOWS TABLE
CREATE TABLE follows (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    follower_id INT UNSIGNED NOT NULL,
    author_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_follow (follower_id, author_id),
    CONSTRAINT fk_follows_follower FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_follows_author FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_follows_author (author_id),
    INDEX idx_follows_follower (follower_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. RECIPE LIKES TABLE
CREATE TABLE recipe_likes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    recipe_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_recipe_user_like (recipe_id, user_id),
    CONSTRAINT fk_likes_recipe FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE,
    CONSTRAINT fk_likes_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_recipe_likes_recipe (recipe_id),
    INDEX idx_recipe_likes_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. COOKBOOKS TABLE
CREATE TABLE cookbooks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NULL,
    cover_image VARCHAR(255) NULL,
    is_public TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_cookbooks_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_cookbooks_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. COOKBOOK RECIPES TABLE
CREATE TABLE cookbook_recipes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cookbook_id INT UNSIGNED NOT NULL,
    recipe_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_cookbook_recipe (cookbook_id, recipe_id),
    CONSTRAINT fk_cr_cookbook FOREIGN KEY (cookbook_id) REFERENCES cookbooks(id) ON DELETE CASCADE,
    CONSTRAINT fk_cr_recipe FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE,
    INDEX idx_cr_cookbook (cookbook_id),
    INDEX idx_cr_recipe (recipe_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. NOTIFICATIONS TABLE
CREATE TABLE notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    actor_id INT UNSIGNED NOT NULL,
    type ENUM('like', 'comment', 'cooksnap', 'follow') NOT NULL,
    target_id INT UNSIGNED NULL,
    content VARCHAR(255) NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_notif_actor FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_notif_user (user_id),
    INDEX idx_notif_actor (actor_id),
    INDEX idx_notif_read (is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SEED DATA: `users`
INSERT INTO `users` (`id`, `username`, `password`, `phone`, `bio`, `role`, `created_at`, `updated_at`) VALUES ('1', 'admin1111', '$2y$10$AmxvQ1h.0xCy6oir4ymTeepHjZXoQQ7UOnMfZIvUNqnek7YqPrpY6', NULL, 'Đam mê nấu các món ngon truyền thống Việt Nam và sáng tạo ẩm thực gia đình ấm cúng.', 'admin', '2026-09-09 18:06:10', '2026-09-10 00:15:46');
INSERT INTO `users` (`id`, `username`, `password`, `phone`, `bio`, `role`, `created_at`, `updated_at`) VALUES ('2', 'chef_lan', '$2y$10$EkTk5/uBhryBEWJxBgHkGuSw9akhB0FgPSFpZaU2p/Q9UOalj.x2S', '0987112233', NULL, 'user', '2026-09-09 18:06:10', '2026-09-09 21:46:16');
INSERT INTO `users` (`id`, `username`, `password`, `phone`, `bio`, `role`, `created_at`, `updated_at`) VALUES ('4', 'user1111', '$2y$10$zwW.k0yYRD81ufsIfSRKMeM0Ha55XZpUl.zCYzmJEjtvsQEuUCmDq', '012345678', NULL, 'user', '2026-09-10 00:06:55', '2026-09-10 00:06:55');
INSERT INTO `users` (`id`, `username`, `password`, `phone`, `bio`, `role`, `created_at`, `updated_at`) VALUES ('5', 'me_bong', '$2y$10$4ad6aTsu9mqYSG85dBxRquWqBoDVXZXkrCaiJbOZRN0agY0A9wzGa', NULL, 'Yêu thích những bữa cơm gia đình giản dị, đượm vị quê hương Bắc Bộ.', 'user', '2026-09-11 13:46:57', '2026-09-11 13:46:57');
INSERT INTO `users` (`id`, `username`, `password`, `phone`, `bio`, `role`, `created_at`, `updated_at`) VALUES ('6', 'bep_hoa', '$2y$10$4ad6aTsu9mqYSG85dBxRquWqBoDVXZXkrCaiJbOZRN0agY0A9wzGa', NULL, 'Nấu ăn bằng cả trái tim, đam mê các món kho, món canh thanh mát chuẩn vị mẹ nấu.', 'user', '2026-09-11 13:46:57', '2026-09-11 13:46:57');
INSERT INTO `users` (`id`, `username`, `password`, `phone`, `bio`, `role`, `created_at`, `updated_at`) VALUES ('7', 'chu_nam_cook', '$2y$10$4ad6aTsu9mqYSG85dBxRquWqBoDVXZXkrCaiJbOZRN0agY0A9wzGa', NULL, 'Chuyên các món kho quẹt, cá linh bông điên điển, canh chua đậm chất sông nước Nam Bộ.', 'user', '2026-09-11 13:46:57', '2026-09-11 13:46:57');
INSERT INTO `users` (`id`, `username`, `password`, `phone`, `bio`, `role`, `created_at`, `updated_at`) VALUES ('8', 'an_ngon_moingay', '$2y$10$4ad6aTsu9mqYSG85dBxRquWqBoDVXZXkrCaiJbOZRN0agY0A9wzGa', NULL, 'Chia sẻ công thức nấu nhanh dưới 20 phút cho người bận rộn và dân văn phòng.', 'user', '2026-09-11 13:46:57', '2026-09-11 13:46:57');
INSERT INTO `users` (`id`, `username`, `password`, `phone`, `bio`, `role`, `created_at`, `updated_at`) VALUES ('9', 'lan_anh_kitchen', '$2y$10$4ad6aTsu9mqYSG85dBxRquWqBoDVXZXkrCaiJbOZRN0agY0A9wzGa', NULL, 'Theo đuổi lối sống lành mạnh, tính toán dinh dưỡng, ít dầu mỡ mà vẫn ngon miệng.', 'user', '2026-09-11 13:46:57', '2026-09-11 13:46:57');

-- SEED DATA: `recipes`
INSERT INTO `recipes` (`id`, `title`, `description`, `category`, `cooking_time`, `servings`, `ingredients`, `instructions`, `tips`, `calories`, `protein`, `carbs`, `fat`, `dietary_tags`, `image_url`, `author_id`, `status`, `views_count`, `likes_count`, `created_at`, `updated_at`) VALUES ('1', 'Bò xào hành tây cần tây', 'Món xào thơm lừng, thịt bò mềm ngọt kết hợp cùng hành tây giòn ngọt.', 'Món xào', '20 phút', '3 - 4 người', 'Thịt bò\nHành tây\nTỏi\nDầu ăn\nTiêu', '1. Ướp thịt bò với tỏi băm, tiêu, hạt nêm trong 15 phút.\n2. Phi thơm tỏi với dầu ăn trên lửa lớn.\n3. Cho thịt bò vào xào nhanh chín tới rồi trút ra đĩa.\n4. Xào hành tây vừa chín, trút thịt bò vào đảo đều rồi tắt bếp.', 'Nên chọn thịt bò phi-lê mềm, thái ngang thớ mỏng. Khi xào bật lửa lớn đảo nhanh tay trong 2-3 phút thịt sẽ mềm ngọt không bị dai.', '340', '32', '8', '18', 'Giàu Protein, Ít Calo, Nhanh < 30p, Đậm Đà', 'assets/images/recipes/bo-xao-hanh-tay.svg', '2', 'approved', '154', '48', '2026-09-09 18:06:10', '2026-09-11 14:31:25');
INSERT INTO `recipes` (`id`, `title`, `description`, `category`, `cooking_time`, `servings`, `ingredients`, `instructions`, `tips`, `calories`, `protein`, `carbs`, `fat`, `dietary_tags`, `image_url`, `author_id`, `status`, `views_count`, `likes_count`, `created_at`, `updated_at`) VALUES ('2', 'Trứng chiên cà chua hành hoa', 'Món ăn gia đình nhanh gọn, vị chua ngọt thanh nhẹ.', 'Món chiên', '15 phút', '2 người', 'Trứng\nCà chua\nHành tây\nTỏi\nRau xanh', '1. Đập trứng vào bát, nêm một chút nước mắm và hạt nêm rồi đánh tan.\n2. Phi thơm tỏi, xào cà chua cho mềm nát tạo sốt.\n3. Đổ trứng vào chảo, đảo nhẹ để trứng quyện đều cùng cà chua.\n4. Rắc hành hoa và tiêu lên trên rồi thưởng thức nóng.', 'Cho một thìa cà phê nước lọc hoặc chút mayonnaise vào đánh cùng trứng để khi chiên trứng bông xốp và mềm thơm hơn.', '450', '28', '56', '12', 'Giàu Protein, Truyền Thống, Nóng Hổi', 'assets/images/recipes/trung-chien-ca-chua.svg', '2', 'approved', '98', '2', '2026-09-09 18:06:10', '2026-09-11 14:31:25');
INSERT INTO `recipes` (`id`, `title`, `description`, `category`, `cooking_time`, `servings`, `ingredients`, `instructions`, `tips`, `calories`, `protein`, `carbs`, `fat`, `dietary_tags`, `image_url`, `author_id`, `status`, `views_count`, `likes_count`, `created_at`, `updated_at`) VALUES ('3', 'Thịt gà hấp lá chanh', 'Gà giữ trọn độ ngọt tự nhiên, thơm thoang thoảng mùi lá chanh tươi.', 'Món hấp', '35 phút', '4 người', 'Thịt gà\nTỏi\nMuối\nTiêu', '1. Gà làm sạch, xát muối và tiêu đều khắp mình gà.\n2. Xếp lá chanh xuống đáy xửng hấp và phủ một lớp lên trên gà.\n3. Hấp gà trong khoảng 25-30 phút cho đến khi chín tới.\n4. Để nguội bớt, chặt miếng vừa ăn và chấm muối tiêu chanh.', 'Ướp gà với chút gừng đập dập và rượu trắng trước khi hấp giúp thịt gà thơm nức, da giòn sần sật vàng óng.', '270', '22', '14', '11', 'Cay Nồng, Ấm Bụng, Dưới 30p', 'assets/images/recipes/ga-hap-la-chanh.svg', '2', 'approved', '215', '32', '2026-09-09 18:06:10', '2026-09-11 14:31:25');
INSERT INTO `recipes` (`id`, `title`, `description`, `category`, `cooking_time`, `servings`, `ingredients`, `instructions`, `tips`, `calories`, `protein`, `carbs`, `fat`, `dietary_tags`, `image_url`, `author_id`, `status`, `views_count`, `likes_count`, `created_at`, `updated_at`) VALUES ('4', 'Canh thịt bò nấu rau xanh thanh mát', 'Canh giải nhiệt bổ dưỡng cho bữa cơm trưa gia đình.', 'Món canh', '25 phút', '3 - 4 người', 'Thịt bò\nRau xanh\nTỏi\nGia vị', '1. Thịt bò băm hoặc thái mỏng, ướp sơ gia vị.\n2. Rau xanh rửa sạch, cắt khúc vừa ăn.\n3. Phi tỏi băm, xào sơ thịt bò rồi cho nước sôi vào nấu.\n4. Thả rau xanh vào đun sôi bùng, nêm nếm lại rồi tắt bếp.', 'Cà chua nên xào chín nhừ để tạo màu đỏ tự nhiên đẹp mắt cho nước dùng canh, nêm thêm chút rau ngổ và ngò gai khi tắt bếp.', '290', '18', '28', '9', 'Eat Clean, Bổ Dưỡng, Dễ Nấu, Cho Bé', 'assets/images/recipes/canh-bo-rau-xanh.svg', '2', 'approved', '67', '61', '2026-09-09 18:06:10', '2026-09-11 14:31:25');
INSERT INTO `recipes` (`id`, `title`, `description`, `category`, `cooking_time`, `servings`, `ingredients`, `instructions`, `tips`, `calories`, `protein`, `carbs`, `fat`, `dietary_tags`, `image_url`, `author_id`, `status`, `views_count`, `likes_count`, `created_at`, `updated_at`) VALUES ('7', 'Phở bò Hà Nội gia truyền', 'Món phở bò mang đậm hương vị phố cổ Hà Nội. Nước dùng ninh từ xương bò ngọt thanh tự nhiên, thoang thoảng mùi hoa hồi, thảo quả, quế và gừng nướng thơm nức mũi.', 'Món canh', '45 phút', '4 - 5 người', '500g Bánh phở tươi\n400g Bắp bò hoặc nạm bò tươi\n1kg Xương ống bò (ninh lấy nước dùng)\n1 củ Gừng tươi, 3 củ Hành khô nướng thơm\n2 hoa Hồi, 1 thanh Quế, 1 quả Thảo quả\nHành hoa, rau mùi, húng láng, chanh ớt\nGia vị: Mắm cốt ngon, hạt nêm, đường phèn', '1. Xương bò rửa sạch, luộc sơ nước đầu rồi rửa lại nước lạnh cho thật sạch để nước dùng trong.\n2. Cho xương vào nồi ninh với 3 lít nước, thả gừng nướng, hành khô nướng và gói thảo mộc hồi quế đã rang thơm vào.\n3. Ninh nhỏ lửa 1.5 - 2 tiếng, hớt bọt thường xuyên, nêm nước mắm ngon và chút đường phèn cho ngọt thanh.\n4. Thịt bắp bò thái thật mỏng ngang thớ. Chần bánh phở qua nước sôi xếp vào tô.\n5. Xếp thịt bò, hành hoa, mùi tàu lên trên rồi chan nước dùng thật sôi. Ăn kèm quẩy nóng và giấm tỏi ớt.', 'Bí quyết nước dùng trong: không đậy vung khi sôi lớn và luôn hớt sạch bọt. Hành khô và gừng phải nướng xém vỏ cạo sạch mới dậy mùi thơm đặc trưng.', '460', '32', '58', '12', 'Truyền Thống, Giàu Protein, Món Tủ Gia Đình, Ấm Bụng', 'assets/images/recipes/pho-bo.svg', '6', 'approved', '3420', '285', '2026-09-11 13:46:57', '2026-09-11 14:31:25');
INSERT INTO `recipes` (`id`, `title`, `description`, `category`, `cooking_time`, `servings`, `ingredients`, `instructions`, `tips`, `calories`, `protein`, `carbs`, `fat`, `dietary_tags`, `image_url`, `author_id`, `status`, `views_count`, `likes_count`, `created_at`, `updated_at`) VALUES ('8', 'Sườn xào chua ngọt chuẩn vị cơm mẹ nấu', 'Món sườn xào chua ngọt óng ả màu cánh gián, thịt sườn mềm róc xương, sốt chua thanh cay ngọt quyện đều từng miếng khiến cả nhà ăn hết bay nồi cơm.', 'Món xào', '25 phút', '3 - 4 người', '600g Sườn non heo (chọn dẻ sườn nhỏ, nạc mềm)\n2 thìa Nước mắm cốt truyền thống\n2 thìa Giấm gạo hoặc nước cốt chanh\n2 thìa Đường vàng\n1 thìa Tương ớt Chin-su\n3 tép Tỏi băm, 2 củ Hành tím băm\nHành lá cắt khúc, tiêu xay, ớt tươi', '1. Sườn non chặt khúc vừa ăn, chần qua nước sôi có chút muối và gừng đập dập rồi rửa sạch để ráo.\n2. Ướp sườn với 1 thìa hạt nêm, chút tiêu trong 15 phút. Rán sơ cho vàng đều 2 mặt.\n3. Pha nước sốt thần thánh: 2 thìa mắm, 2 thìa đường, 2 thìa giấm, 1 thìa tương ớt, 4 thìa nước lọc khuấy tan.\n4. Phi thơm hành tỏi băm, trút sườn vào rồi đổ bát nước sốt vào đảo đều trên lửa vừa.\n5. Đun liu riu cho đến khi nước sốt sệt lại, áo đều một lớp màu hổ phách óng ánh quanh miếng sườn thì rắc hành lá và tắt bếp.', 'Pha tỷ lệ nước sốt 2 mắm : 2 đường : 2 chua : 1 cay là công thức bất bại. Dùng đường vàng hoặc chút dầu màu điều giúp màu sắc bắt mắt tự nhiên không cần phẩm màu.', '520', '28', '22', '36', 'Đậm Đà, Bắt Cơm, Món Tủ Gia Đình, Dễ Nấu', 'assets/images/recipes/suon-xao-chua-ngot.svg', '5', 'approved', '2890', '240', '2026-09-11 13:46:57', '2026-09-11 14:31:25');
INSERT INTO `recipes` (`id`, `title`, `description`, `category`, `cooking_time`, `servings`, `ingredients`, `instructions`, `tips`, `calories`, `protein`, `carbs`, `fat`, `dietary_tags`, `image_url`, `author_id`, `status`, `views_count`, `likes_count`, `created_at`, `updated_at`) VALUES ('9', 'Thịt kho tàu nước dừa trứng cút', 'Món thịt kho tàu truyền thống mềm tan trong miệng, mỡ trong veo, nạc ngọt đậm đà hòa quyện cùng trứng cút thấm vị và nước dừa xiêm ngọt lịm.', 'Món kho', '40 phút', '4 - 5 người', '600g Thịt ba chỉ hoặc thịt mông heo\n15 - 20 quả Trứng cút luộc bóc vỏ\n1 quả Dừa xiêm lấy nước tươi (khoảng 350ml)\n3 củ Hành tím, 4 tép Tỏi đập dập\nGia vị: Nước mắm nhĩ, đường thắng nước màu, tiêu, ớt hiểm', '1. Thịt heo rửa sạch với nước muối, thái miếng vuông con cờ khoảng 3 - 4cm.\n2. Ướp thịt với tỏi ớt băm, 3 thìa nước mắm ngon, 1 thìa đường, tiêu và nước hàng trong 30 phút.\n3. Cho thịt lên bếp xào săn để miếng mỡ trong lại, sau đó đổ toàn bộ nước dừa xiêm ngập mặt thịt.\n4. Đun sôi rồi hạ lửa nhỏ liu riu, hớt bọt. Khi thịt chín mềm thì thả trứng cút vào kho cùng.\n5. Kho thêm 15 - 20 phút cho nước cạn bớt, sánh sệt và có màu cánh gián tuyệt đẹp là hoàn thành.', 'Không nên đậy nắp vung kín khi kho bằng nước dừa để nước kho được trong và mỡ heo có độ trong veo bóng bẩy.', '580', '30', '18', '44', 'Truyền Thống, Đậm Đà, Bắt Cơm, Món Tết', 'assets/images/recipes/thit-kho-tau.svg', '7', 'approved', '3120', '265', '2026-09-11 13:46:57', '2026-09-11 14:31:25');
INSERT INTO `recipes` (`id`, `title`, `description`, `category`, `cooking_time`, `servings`, `ingredients`, `instructions`, `tips`, `calories`, `protein`, `carbs`, `fat`, `dietary_tags`, `image_url`, `author_id`, `status`, `views_count`, `likes_count`, `created_at`, `updated_at`) VALUES ('10', 'Canh chua cá lóc rau nhút bông điên điển', 'Tô canh chua miền Tây đúng điệu giải nhiệt ngày oi bức. Thịt cá lóc đồng ngọt thơm, vị chua thanh từ me chín, giòn sần sật của dọc mùng, giá đỗ và mùi thơm ngát của ngò om ngò gai.', 'Món canh', '20 phút', '4 người', '1 con Cá lóc đồng (khoảng 600g) cắt khúc\n2 quả Cà chua, 1/4 quả Dứa chín\n1 nắm Rau nhút, 100g Giá đỗ, 2 cây Dọc mùng (bạc hà)\n1 vắt Me chín chua\nRau nêm: Ngò om, ngò gai, tỏi phi vàng, ớt lát\nGia vị: Nước mắm ngon, muối, đường cát', '1. Cá lóc làm sạch nhớt bằng muối và chanh, rửa sạch cắt khúc.\n2. Me dầm với nước sôi lấy nước cốt chua.\n3. Đun sôi nồi nước (khoảng 1.2 lít), cho nước cốt me và dứa vào nấu sôi, nêm mắm đường hơi đậm một chút.\n4. Thả từng khúc cá vào nấu chín tới, vớt bọt liên tục để nước canh trong.\n5. Cho cà chua, dọc mùng, rau nhút và giá đỗ vào đun sôi bùng lên rồi tắt bếp ngay.\n6. Múc ra tô, rắc ngò om, ngò gai thái nhỏ và một thìa tỏi phi thơm lừng lên trên.', 'Phải cho cá vào khi nước thật sôi và không đảo mạnh tay để thịt cá không bị tanh và nát. Tỏi phi vàng rắc lên trên là linh hồn của món canh chua miền Tây.', '280', '34', '24', '6', 'Thanh Mát, Ít Calo, Giàu Protein, Giải Nhiệt', 'assets/images/recipes/canh-chua-ca-loc.svg', '7', 'approved', '2150', '195', '2026-09-11 13:46:57', '2026-09-11 14:31:25');
INSERT INTO `recipes` (`id`, `title`, `description`, `category`, `cooking_time`, `servings`, `ingredients`, `instructions`, `tips`, `calories`, `protein`, `carbs`, `fat`, `dietary_tags`, `image_url`, `author_id`, `status`, `views_count`, `likes_count`, `created_at`, `updated_at`) VALUES ('11', 'Salad ức gà sốt mè rang Eat Clean', 'Món ăn chân ái cho người giảm cân, Eat Clean hoặc tập gym. Ức gà áp chảo mềm mọng nước không hề bị khô xác, kết hợp rau xà lách thủy canh tươi giòn và sốt mè rang bùi béo.', 'Món chay', '12 phút', '2 người', '250g Ức gà tươi phi lê\n1 cây Xà lách Romaine hoặc xà lách mỡ\n1 củ Dưa leo baby, 8 quả Cà chua bi\n1/2 bắp Ngô ngọt luộc tách hạt\n1 quả Trứng gà luộc lòng đào\nSốt mè rang Kewpie (hoặc tự làm từ mè trắng rang, sữa chua Hy Lạp, dầu oliu)\nGia vị ướp gà: Muối hồng, tiêu đen, tỏi ớt bột Paprika', '1. Ức gà khía nhẹ mặt, ướp với chút muối hồng, tiêu xay và bột tỏi trong 10 phút.\n2. Làm nóng chảo chống dính với 1 thìa cà phê dầu oliu, áp chảo ức gà mỗi mặt 4 - 5 phút ở lửa vừa cho chín vàng thơm.\n3. Để ức gà nghỉ 3 phút rồi thái lát dày khoảng 1cm (thịt sẽ giữ trọn nước ngọt mềm).\n4. Rau xà lách rửa sạch vẩy ráo nước cắt khúc vừa ăn, dưa leo thái lát, cà chua bi bổ đôi.\n5. Xếp rau củ ra đĩa sâu lòng, xếp ức gà và trứng lòng đào lên trên, rưới sốt mè rang thơm phức và thưởng thức.', 'Sau khi áp chảo, nhất định phải để thịt nghỉ 3 phút trước khi thái. Nếu thái ngay khi vừa nhấc khỏi chảo, nước ngọt trong thịt sẽ chảy hết ra ngoài khiến ức gà bị khô xác.', '295', '38', '16', '9', 'Eat Clean, Giàu Protein, Ít Calo, Nhanh < 15p, Low-Carb', 'assets/images/recipes/salad-uc-ga.svg', '9', 'approved', '2780', '230', '2026-09-11 13:46:57', '2026-09-11 14:31:25');
INSERT INTO `recipes` (`id`, `title`, `description`, `category`, `cooking_time`, `servings`, `ingredients`, `instructions`, `tips`, `calories`, `protein`, `carbs`, `fat`, `dietary_tags`, `image_url`, `author_id`, `status`, `views_count`, `likes_count`, `created_at`, `updated_at`) VALUES ('12', 'Bánh mì chảo xíu mại trứng ốp la', 'Bữa sáng cấp tốc, giàu năng lượng cho ngày mới tràn đầy sức sống. Viên xíu mại thịt heo mềm thơm đượm sốt cà chua sền sệt, trứng ốp lòng đào béo ngậy chấm cùng bánh mì nóng giòn rụm.', 'Món chiên', '15 phút', '2 người', '2 ổ Bánh mì giòn\n2 quả Trứng gà tươi\n150g Thịt heo băm nhỏ viên xíu mại\n1 cây Xúc xích heo hoặc phô mai con bò cười\n2 quả Cà chua băm nhuyễn tạo sốt\nBơ Tường An, pate gan, dưa leo, hành ngò, tiêu xay', '1. Thịt băm trộn hành tây thái hạt lựu, nêm chút hạt nêm, tiêu rồi viên tròn đem hấp sơ 5 phút.\n2. Làm sốt cà chua: Phi tỏi băm thơm, cho cà chua băm vào xào mềm nhuyễn, nêm xíu dầu hào và đường cho sốt sánh đỏ.\n3. Dùng chảo gang hoặc chảo nhỏ, đun chảy 1 thìa bơ, đập 2 quả trứng gà vào làm trứng ốp la lòng đào.\n4. Thả xúc xích khía nhẹ, xíu mại, 1 thìa pate gan vào chảo, rưới sốt cà chua nóng hổi lên.\n5. Rắc tiêu xay, ớt tỉa hoa và vài nhánh ngò rí. Dọn chảo lên bàn cùng bánh mì nóng giòn chấm ăn liền.', 'Ăn ngay khi chảo còn xèo xèo nóng hổi là ngon nhất. Dùng bánh mì vừa nướng lại trong nồi chiên không dầu 3 phút để bánh siêu giòn.', '510', '24', '45', '26', 'Nhanh < 15p, Bữa Sáng, Béo Ngậy, Giàu Năng Lượng', 'assets/images/recipes/banh-mi-chao.svg', '8', 'approved', '1980', '175', '2026-09-11 13:46:57', '2026-09-11 14:31:25');
INSERT INTO `recipes` (`id`, `title`, `description`, `category`, `cooking_time`, `servings`, `ingredients`, `instructions`, `tips`, `calories`, `protein`, `carbs`, `fat`, `dietary_tags`, `image_url`, `author_id`, `status`, `views_count`, `likes_count`, `created_at`, `updated_at`) VALUES ('13', 'Cá bống kho tiêu gừng nồi đất', 'Hương vị đồng quê gợi nhớ bữa cơm chiều ngày mưa của mẹ. Từng con cá bống săn chắc, ngấm vị cay ấm nồng nàn của hạt tiêu sọ và gừng tươi thái sợi, ăn cùng cơm trắng nóng hổi thì không gì sánh bằng.', 'Món kho', '30 phút', '3 - 4 người', '400g Cá bống tươi sống\n1 nhánh Gừng tươi thái sợi mỏng\n2 thìa Hạt tiêu sọ đập dập\n3 củ Hành tím, 2 tép Tỏi\n3 thìa Nước mắm truyền thống thơm ngon\n1 thìa Nước màu dừa, 1 thìa Mỡ heo (hoặc dầu ăn)\n2 quả Ớt hiểm đỏ, hành lá', '1. Cá bống làm sạch vảy, xát muối cho hết nhớt rồi rửa sạch để thật ráo nước.\n2. Ướp cá với gừng thái sợi, tiêu đập dập, nước mắm, đường, nước màu dừa trong nồi đất khoảng 20 phút.\n3. Đặt nồi đất lên bếp đun lửa lớn cho cá sôi bùng lên và thịt cá bắt đầu săn chắc lại.\n4. Hạ lửa nhỏ nhất, đậy hé vung kho liu riu cho cá thấm đều gia vị và ngả màu nâu cánh gián óng ả.\n5. Khi nước kho sánh cạn lại, rưới 1 thìa mỡ heo lên mặt cá, rắc thêm thật nhiều tiêu xay và ớt hiểm rồi tắt bếp.', 'Kho bằng nồi đất và dùng mỡ heo thay dầu ăn sẽ làm cá bống thơm ngậy đặc biệt, thịt cá săn chắc không bị vỡ nát.', '320', '32', '12', '16', 'Truyền Thống, Cay Nồng, Ấm Bụng, Bắt Cơm', 'assets/images/recipes/ca-bong-kho-tieu.svg', '6', 'approved', '1650', '150', '2026-09-11 13:46:57', '2026-09-11 14:31:25');
INSERT INTO `recipes` (`id`, `title`, `description`, `category`, `cooking_time`, `servings`, `ingredients`, `instructions`, `tips`, `calories`, `protein`, `carbs`, `fat`, `dietary_tags`, `image_url`, `author_id`, `status`, `views_count`, `likes_count`, `created_at`, `updated_at`) VALUES ('14', 'Nộm hoa chuối tai heo giòn sần sật', 'Món nộm dân dã chống ngấy tuyệt vời trong mâm cơm hoặc bữa tiệc gia đình. Hoa chuối trắng nõn giòn sần sật quyện tai heo luộc giòn, vị chua cay mặn ngọt hài hòa rắc thêm đậu phộng rang thơm bùi.', 'Món xào', '20 phút', '4 người', '1 cái Tai heo làm sạch\n1 cái Bắp hoa chuối tây bào mỏng\n1 củ Cà rốt bào sợi\n100g Đậu phộng (lạc) rang giã dập\nRau thơm: Kinh giới, rau húng bạc hà, mùi tàu\nNước mắm pha nộm: 3 thìa mắm, 2 thìa chanh, 2 thìa đường, tỏi ớt băm nhuyễn', '1. Hoa chuối bào mỏng ngâm ngay vào chậu nước có pha nước cốt chanh và chút muối để hoa chuối không bị thâm đen, sau đó vớt ra vẩy ráo.\n2. Tai heo luộc chín cùng chút giấm và gừng, vớt ra ngâm ngay vào tô nước đá lạnh 10 phút để tai heo giòn trắng rồi thái lát mỏng dài.\n3. Pha nước trộn nộm: Khuấy tan nước cốt chanh, đường, nước mắm ngon, tỏi ớt băm nhuyễn sao cho có vị chua ngọt đậm đà.\n4. Cho hoa chuối, cà rốt bào, tai heo vào thau lớn, rưới 2/3 lượng nước sốt vào bóp nhẹ tay cho ngấm gia vị trong 5 phút.\n5. Chắt bớt nước tiết ra, cho rau thơm thái nhỏ và phần nước sốt còn lại vào trộn đều.\n6. Bày ra đĩa, rắc đậu phộng rang vàng giã dập lên trên cùng.', 'Ngâm tai heo vừa luộc vào nước đá lạnh là bí quyết giúp tai heo giữ trọn độ giòn sần sật, không bị nhớt hay mềm nhũn.', '260', '22', '18', '11', 'Thanh Mát, Khai Vị, Giòn Rụm, Dễ Nấu', 'assets/images/recipes/nom-hoa-chuoi.svg', '5', 'approved', '2240', '180', '2026-09-11 13:46:57', '2026-09-11 14:31:25');
INSERT INTO `recipes` (`id`, `title`, `description`, `category`, `cooking_time`, `servings`, `ingredients`, `instructions`, `tips`, `calories`, `protein`, `carbs`, `fat`, `dietary_tags`, `image_url`, `author_id`, `status`, `views_count`, `likes_count`, `created_at`, `updated_at`) VALUES ('15', 'Bún chả Hà Nội nướng than hoa', 'Món ăn làm say lòng bất kỳ ai từng ghé thăm Hà Nội. Chả băm và chả miếng nướng xém than hoa thơm nức mũi, thả vào bát nước mắm chua ngọt ấm nóng ăn cùng bún tươi và rau sống thanh mát.', 'Món chính', '35 phút', '4 người', '500g Thịt ba chỉ thái lát mỏng (làm chả miếng)\n400g Thịt nạc vai băm nhỏ (làm chả viên)\n1kg Bún tươi sợi nhỏ\n1 củ Su hào hoặc đu đủ xanh, 1 củ Cà rốt làm dưa góp\nRau sống: Xà lách, kinh giới, tía tô, húng láng\nGia vị ướp thịt: Nước hàng, hành khô băm, nước mắm ngon, dầu hào, tiêu, đường\nNước mắm chấm: 1 mắm : 1 đường : 1 giấm : 5 nước ấm, tỏi ớt băm', '1. Ướp chả miếng và chả viên riêng trong 30 phút với hành tỏi băm, nước mắm, nước hàng, dầu hào và chút tiêu.\n2. Viên chả băm thành từng viên tròn dẹt vừa ăn. Kẹp chả vào vỉ nướng trên than hoa đỏ rực (hoặc nướng nồi chiên không dầu 180°C trong 15 phút) cho đến khi vàng xém thơm lừng.\n3. Su hào cà rốt thái mỏng bóp muối, rửa sạch rồi ngâm chua ngọt làm dưa góp giòn sần sật.\n4. Pha nước mắm chấm chua ngọt đun ấm nhẹ, thả dưa góp vào cùng vài lát ớt tươi.\n5. Xếp bún tươi, rau sống ra mẹt, múc chả nóng vào tô nước chấm và thưởng thức.', 'Để chả viên mềm mọng nước không bị khô, nên chọn thịt nạc vai có chút mỡ giắt và trộn thêm 1 thìa dầu ăn vào thịt băm trước khi viên.', '540', '32', '65', '18', 'Đặc Sản Hà Nội, Bắt Vị, Món Tủ Gia Đình, Truyền Thống', 'assets/images/recipes/bun-cha-ha-noi.svg', '5', 'approved', '3890', '310', '2026-09-11 14:31:25', '2026-09-11 14:31:25');
INSERT INTO `recipes` (`id`, `title`, `description`, `category`, `cooking_time`, `servings`, `ingredients`, `instructions`, `tips`, `calories`, `protein`, `carbs`, `fat`, `dietary_tags`, `image_url`, `author_id`, `status`, `views_count`, `likes_count`, `created_at`, `updated_at`) VALUES ('16', 'Bánh xèo miền Tây vàng giòn tôm thịt', 'Bánh xèo miền Tây chuẩn vị vỏ mỏng vàng ươm giòn rụm, thơm béo nước cốt dừa và thoang thoảng mùi bột nghệ. Nhân ngập tràn tôm sông tươi ngọt, thịt ba chỉ xào và giá đỗ cuốn bánh tráng rau rừng chấm mắm chua ngọt cay xè.', 'Món chiên', '30 phút', '4 - 5 người', '400g Bột bánh xèo Hương Xưa pha sẵn (hoặc bột gạo)\n1 lon Nước cốt dừa (khoảng 200ml)\n1 thìa Bột nghệ tạo màu vàng ươm\n300g Tôm sông tươi rửa sạch cắt râu\n300g Thịt ba rọi thái lát mỏng\n200g Giá đỗ, 1 củ Hành tây thái mỏng, hành lá cắt nhỏ\nRau ăn kèm: Cải bẹ xanh, xà lách, rau diếp cá, đọt cóc, lá xoài non\nNước mắm tỏi ớt chua ngọt cà rốt bào sợi', '1. Khuấy tan bột bánh xèo với nước cốt dừa, 600ml nước lọc, bột nghệ, hành lá cắt nhỏ và chút bia (bí quyết giúp vỏ giòn lâu).\n2. Xào sơ tôm thịt trên chảo với chút hạt nêm và tiêu cho vừa chín tới.\n3. Dùng chảo chống dính sâu lòng, đun nóng thật nóng với 1 thìa dầu ăn. Múc 1 muôi bột tráng thật mỏng đều khắp lòng chảo (nghe tiếng xèo thật to).\n4. Xếp tôm, thịt, hành tây và giá đỗ lên một nửa mặt bánh. Đậy vung 2 phút ở lửa vừa cho bánh chín giòn.\n5. Mở vung, đun thêm 1 phút cho viền bánh róc và giòn tan, gập đôi bánh lại trút ra đĩa thưởng thức nóng.', 'Thêm 1 chén bia nhỏ vào thau bột pha là bí quyết gia truyền giúp vỏ bánh xèo giòn rụm từ lúc mới đổ cho đến khi ăn xong mà không bị ỉu.', '480', '26', '54', '20', 'Đặc Sản Nam Bộ, Giòn Rụm, Món Ăn Chơi, Cuối Tuần', 'assets/images/recipes/banh-xeo-mien-tay.svg', '7', 'approved', '2950', '260', '2026-09-11 14:31:25', '2026-09-11 14:31:25');
INSERT INTO `recipes` (`id`, `title`, `description`, `category`, `cooking_time`, `servings`, `ingredients`, `instructions`, `tips`, `calories`, `protein`, `carbs`, `fat`, `dietary_tags`, `image_url`, `author_id`, `status`, `views_count`, `likes_count`, `created_at`, `updated_at`) VALUES ('17', 'Nem rán truyền thống giòn rụm', 'Món nem rán (chả giò) không thể thiếu trong mâm cỗ Tết và bữa cơm sum họp gia đình Việt. Vỏ bánh đa nem giòn tan rụm rụm, nhân thịt mộc nhĩ nấm hương miến dong ngọt bùi đượm vị.', 'Món chiên', '30 phút', '4 - 6 người', '400g Thịt nạc vai xay nhuyễn\n1 tập Bánh đa nem loại ngon hoặc vỏ ram Hà Tĩnh\n50g Miến dong ngâm mềm cắt khúc ngắn\n3 tai Mộc nhĩ, 5 tai Nấm hương ngâm nở băm nhỏ\n1 củ Cà rốt, 1/2 củ Su hào bào sợi vắt bớt nước\n2 quả Trứng gà tươi\nHành hoa, rau mùi ta, tiêu bắc xay, hạt nêm, nước mắm', '1. Trộn đều thịt xay, miến, mộc nhĩ, nấm hương, cà rốt, hành mùi với 2 lòng đỏ trứng và gia vị. Không trộn quá nhiều trứng để nhân không bị ướt làm rách vỏ.\n2. Trải vỏ bánh đa nem ra đĩa, múc 1 thìa nhân vào giữa rồi cuộn chặt vừa tay.\n3. Đun sôi dầu ăn ngập nửa chiếc nem trên lửa vừa. Thả từng chiếc nem vào rán ngập dầu.\n4. Rán 2 lần lửa: Lần 1 rán chín tới vớt ra để ráo dầu; Lần 2 khi chuẩn bị ăn bật lửa lớn rán nhanh cho vỏ nem vàng giòn rụm.\n5. Cắt đôi bày ra đĩa ăn kèm bún tươi, rau sống và nước chấm dưa góp chua ngọt.', 'Rán 2 lần lửa và vắt kiệt nước các loại củ (cà rốt, su hào) là bí kíp giúp vỏ nem giòn rụm đến 4 - 5 tiếng đồng hồ không lo bị ỉu mềm.', '420', '24', '38', '22', 'Truyền Thống, Món Cỗ Ngày Lễ, Giòn Tan, Sum Họp', 'assets/images/recipes/nem-ran-truyen-thong.svg', '6', 'approved', '3340', '295', '2026-09-11 14:31:25', '2026-09-11 14:31:25');
INSERT INTO `recipes` (`id`, `title`, `description`, `category`, `cooking_time`, `servings`, `ingredients`, `instructions`, `tips`, `calories`, `protein`, `carbs`, `fat`, `dietary_tags`, `image_url`, `author_id`, `status`, `views_count`, `likes_count`, `created_at`, `updated_at`) VALUES ('18', 'Mực xào sa tế cay nồng giòn ngọt', 'Món xào nóng hổi cay nồng cực bén cơm và phù hợp cho những bữa tiệc lai rai cuối tuần. Mực tươi xào lửa lớn giữ trọn độ giòn sần sật ngọt lịm, quyện sốt sa tế thơm lừng tỏi ớt và rau củ tươi.', 'Món xào', '15 phút', '3 - 4 người', '500g Mực ống tươi sống làm sạch khía vảy rồng\n2 thìa Sa tế tôm cay thơm\n1 quả Ớt chuông đỏ, 1 quả Ớt chuông xanh thái miếng\n1 củ Hành tây thái múi cau, 2 nhánh Cần tây cắt khúc\n3 tép Tỏi băm, 1 củ Gừng tươi thái chỉ\nGia vị: Dầu hào Maggi, nước mắm, tiêu sọ, hạt nêm', '1. Mực làm sạch khía vảy rồng, chần nhanh qua nước sôi có gừng đập dập 30 giây rồi vớt ra ngâm nước đá cho mực giòn trắng.\n2. Phi thơm tỏi băm với dầu ăn trên lửa lớn, cho 2 thìa sa tế tôm vào đảo đều dậy mùi thơm đỏ óng.\n3. Trút mực vào xào thật nhanh tay ở lửa lớn trong 1 - 2 phút rồi trút ra đĩa riêng.\n4. Cho ớt chuông, hành tây, cần tây vào xào vừa chín tới, nêm dầu hào và nước mắm vừa ăn.\n5. Trút mực trở lại đảo đều 30 giây cho ngấm sốt rồi rắc tiêu sọ tắt bếp ngay.', 'Mực chỉ xào trên lửa thật lớn và không xào quá lâu (dưới 3 phút) để mực giữ được độ giòn ngọt mọng nước mà không bị teo dai và ra nước.', '310', '36', '14', '12', 'Nhanh < 15p, Cay Nồng, Giàu Protein, Món Nhậu', 'assets/images/recipes/muc-xao-sa-te.svg', '8', 'approved', '2450', '215', '2026-09-11 14:31:25', '2026-09-11 14:31:25');

-- SEED DATA: `saved_recipes`
INSERT INTO `saved_recipes` (`id`, `user_id`, `recipe_id`, `note`, `created_at`) VALUES ('3', '1', '2', NULL, '2026-09-09 22:08:44');
INSERT INTO `saved_recipes` (`id`, `user_id`, `recipe_id`, `note`, `created_at`) VALUES ('6', '4', '1', NULL, '2026-09-11 13:37:53');
INSERT INTO `saved_recipes` (`id`, `user_id`, `recipe_id`, `note`, `created_at`) VALUES ('7', '2', '1', 'Lần sau ướp thêm xíu tiêu gừng, trẻ con nhà mình rất thích ăn với cơm nóng.', '2026-09-11 13:46:57');
INSERT INTO `saved_recipes` (`id`, `user_id`, `recipe_id`, `note`, `created_at`) VALUES ('8', '2', '2', 'Công thức này làm bữa sáng siêu nhanh, rán trứng lửa vừa để không bị khô.', '2026-09-11 13:46:57');
INSERT INTO `saved_recipes` (`id`, `user_id`, `recipe_id`, `note`, `created_at`) VALUES ('9', '1', '1', 'Món tủ để đãi bạn bè cuối tuần, mua thịt thăn bò mềm ngon nhất.', '2026-09-11 13:46:57');
INSERT INTO `saved_recipes` (`id`, `user_id`, `recipe_id`, `note`, `created_at`) VALUES ('10', '1', '3', 'Gà hấp lá chanh giữ được vị ngọt nguyên bản, chấm muối ớt đỏ tuyệt vời.', '2026-09-11 13:46:57');

-- SEED DATA: `comments`
INSERT INTO `comments` (`id`, `recipe_id`, `parent_id`, `user_id`, `content`, `rating`, `image_url`, `created_at`) VALUES ('1', '1', NULL, '1', 'Món này làm rất nhanh mà thịt bò mềm ngọt tuyệt vời!', '5', NULL, '2026-09-09 18:11:04');
INSERT INTO `comments` (`id`, `recipe_id`, `parent_id`, `user_id`, `content`, `rating`, `image_url`, `created_at`) VALUES ('2', '2', NULL, '2', 'Công thức rất chuẩn, gia đình mình ai cũng khen ngon.', '5', NULL, '2026-09-09 18:11:04');
INSERT INTO `comments` (`id`, `recipe_id`, `parent_id`, `user_id`, `content`, `rating`, `image_url`, `created_at`) VALUES ('4', '1', NULL, '1', 'Món bò xào này siêu ngon, mình đã làm thử và cả nhà đều khen!', '5', 'uploads/recipes/bo_xao.jpg', '2026-09-10 00:23:13');
INSERT INTO `comments` (`id`, `recipe_id`, `parent_id`, `user_id`, `content`, `rating`, `image_url`, `created_at`) VALUES ('9', '7', NULL, '5', 'Nước dùng ninh xương bò trong veo và thơm ngát mùi hồi quế đúng điệu phố cổ luôn cô Hoa ơi! Cả nhà cháu ăn ai cũng tấm tắc khen ngon hơn cả đi ăn tiệm ngoài phố.', '5', NULL, '2026-09-11 13:46:57');
INSERT INTO `comments` (`id`, `recipe_id`, `parent_id`, `user_id`, `content`, `rating`, `image_url`, `created_at`) VALUES ('10', '7', '9', '6', 'Cảm ơn Mẹ Bống nhiều nhé! Nhớ hớt bọt thật kỹ lúc nước bắt đầu sôi liu riu là nước dùng sẽ giữ được độ trong vắt như gương luôn nha con.', '5', NULL, '2026-09-11 13:46:57');
INSERT INTO `comments` (`id`, `recipe_id`, `parent_id`, `user_id`, `content`, `rating`, `image_url`, `created_at`) VALUES ('11', '7', NULL, '7', 'Công thức nấu nước dùng chuẩn chỉ lắm. Mình cho thêm 1 thìa nước mắm ngon lúc chuẩn bị múc ra bát, mùi thơm bốc lên nức nở khắp cả gian bếp.', '5', NULL, '2026-09-11 13:46:57');
INSERT INTO `comments` (`id`, `recipe_id`, `parent_id`, `user_id`, `content`, `rating`, `image_url`, `created_at`) VALUES ('12', '7', '11', '6', 'Dạ đúng rồi chú Năm ạ, nước mắm cho vào sau cùng vừa giữ được hương vị tinh túy mà không bị chua nước phở ạ!', '5', NULL, '2026-09-11 13:46:57');
INSERT INTO `comments` (`id`, `recipe_id`, `parent_id`, `user_id`, `content`, `rating`, `image_url`, `created_at`) VALUES ('13', '8', NULL, '9', 'Tỷ lệ nước sốt 2 mắm : 2 đường : 2 chua : 1 cay đúng là chuẩn chỉnh! Sườn mềm róc xương, sốt óng ả bám dính lấy từng miếng thịt. Hôm nay nhà mình cạn sạch cả nồi cơm điện.', '5', NULL, '2026-09-11 13:46:57');
INSERT INTO `comments` (`id`, `recipe_id`, `parent_id`, `user_id`, `content`, `rating`, `image_url`, `created_at`) VALUES ('14', '8', '13', '5', 'Hihi cảm ơn Lan Anh nhé! Bữa nào thử thêm một xíu dứa băm nhuyễn xào cùng sườn nữa xem, sốt sẽ thơm nức nở hơn nữa đấy ❤️', '5', NULL, '2026-09-11 13:46:57');
INSERT INTO `comments` (`id`, `recipe_id`, `parent_id`, `user_id`, `content`, `rating`, `image_url`, `created_at`) VALUES ('15', '8', NULL, '8', 'Món này làm nhanh mà ngon mê ly! Mình rán sơ sườn trước nên miếng sườn xém cạnh ăn giòn thơm bên ngoài mà bên trong vẫn mọng nước ngọt lịm.', '5', NULL, '2026-09-11 13:46:57');
INSERT INTO `comments` (`id`, `recipe_id`, `parent_id`, `user_id`, `content`, `rating`, `image_url`, `created_at`) VALUES ('16', '9', NULL, '6', 'Kho bằng nước dừa xiêm đúng là chân ái chú Năm ơi. Miếng mỡ trong veo béo ngậy mà không hề ngấy, cắn miếng thịt cảm giác như tan chảy trên đầu lưỡi vậy.', '5', NULL, '2026-09-11 13:46:57');
INSERT INTO `comments` (`id`, `recipe_id`, `parent_id`, `user_id`, `content`, `rating`, `image_url`, `created_at`) VALUES ('17', '9', '16', '7', 'Cảm ơn cô Hoa ghé thăm gian bếp! Nước dừa xiêm ngọt thanh tự nhiên nên chú không cần nêm nhiều đường, ăn vừa ngon vừa tốt cho sức khỏe.', '5', NULL, '2026-09-11 13:46:57');
INSERT INTO `comments` (`id`, `recipe_id`, `parent_id`, `user_id`, `content`, `rating`, `image_url`, `created_at`) VALUES ('18', '10', NULL, '2', 'Hôm nay nắng nóng 38 độ nấu ngay tô canh chua này của chú Năm giải nhiệt kịp thời luôn. Cá lóc ngọt thịt, vị chua thanh dịu của me chín hòa quyện ngò om thơm lừng.', '5', NULL, '2026-09-11 13:46:57');
INSERT INTO `comments` (`id`, `recipe_id`, `parent_id`, `user_id`, `content`, `rating`, `image_url`, `created_at`) VALUES ('19', '10', '18', '7', 'Cảm ơn đầu bếp Lan nhé! Canh chua cá lóc ăn kèm đĩa cá kho tộ nồi đất nữa là thành bộ đôi huyền thoại của miền Tây sông nước đó con.', '5', NULL, '2026-09-11 13:46:57');
INSERT INTO `comments` (`id`, `recipe_id`, `parent_id`, `user_id`, `content`, `rating`, `image_url`, `created_at`) VALUES ('20', '11', NULL, '5', 'Trước giờ mình ngại ăn ức gà vì sợ khô bã, nhưng học mẹo để thịt nghỉ 3 phút của bạn Lan Anh xong thì thịt mọng nước và mềm ngọt bất ngờ luôn!', '5', NULL, '2026-09-11 13:46:57');
INSERT INTO `comments` (`id`, `recipe_id`, `parent_id`, `user_id`, `content`, `rating`, `image_url`, `created_at`) VALUES ('21', '11', '20', '9', 'Tuyệt vời quá chị Bống ơi! Mẹo này áp dụng cho bít tết hay bất kỳ loại thịt áp chảo nào cũng giữ được độ ngọt mọng nước 100% đó ạ!', '5', NULL, '2026-09-11 13:46:57');
INSERT INTO `comments` (`id`, `recipe_id`, `parent_id`, `user_id`, `content`, `rating`, `image_url`, `created_at`) VALUES ('22', '12', NULL, '9', 'Sốt cà chua sền sệt chấm bánh mì giòn tan ngon đỉnh chóp! Sáng cuối tuần làm một chảo này đãi cả nhà ai cũng mê tít thò lò.', '5', NULL, '2026-09-11 13:46:57');
INSERT INTO `comments` (`id`, `recipe_id`, `parent_id`, `user_id`, `content`, `rating`, `image_url`, `created_at`) VALUES ('23', '12', '22', '8', 'Cảm ơn bạn nhé! Thêm chút tương ớt cay cay và lòng đỏ trứng còn sánh dẻo chấm vào là chuẩn không cần chỉnh luôn.', '5', NULL, '2026-09-11 13:46:57');
INSERT INTO `comments` (`id`, `recipe_id`, `parent_id`, `user_id`, `content`, `rating`, `image_url`, `created_at`) VALUES ('24', '13', NULL, '5', 'Nhìn con cá bống kho săn đanh lại, màu cánh gián đẹp mắt là thấy ấm lòng rồi. Mùa đông hay ngày mưa se lạnh ăn món này đưa cơm dã man!', '5', NULL, '2026-09-11 13:46:57');
INSERT INTO `comments` (`id`, `recipe_id`, `parent_id`, `user_id`, `content`, `rating`, `image_url`, `created_at`) VALUES ('25', '13', '24', '6', 'Chuẩn bài chị Bống ạ! Cá bống phải kho thật kỹ lửa nhỏ trên nồi đất thì xương cũng mềm rục mà thịt vẫn săn chắc thơm ngon.', '5', NULL, '2026-09-11 13:46:57');
INSERT INTO `comments` (`id`, `recipe_id`, `parent_id`, `user_id`, `content`, `rating`, `image_url`, `created_at`) VALUES ('26', '2', NULL, '4', 'tuyệtttt', '5', NULL, '2026-09-11 14:06:28');
INSERT INTO `comments` (`id`, `recipe_id`, `parent_id`, `user_id`, `content`, `rating`, `image_url`, `created_at`) VALUES ('27', '15', NULL, '2', 'Công thức làm chuẩn chỉ và chi tiết lắm tác giả ơi! Chiều nay mình trổ tài cho cả nhà ai cũng khen nức nở.', '5', NULL, '2026-09-11 14:31:25');
INSERT INTO `comments` (`id`, `recipe_id`, `parent_id`, `user_id`, `content`, `rating`, `image_url`, `created_at`) VALUES ('28', '15', '27', '5', 'Cảm ơn bạn nhiều nha! Chúc bạn và gia đình luôn có những bữa cơm ngon miệng cùng Cookio ❤️', '5', NULL, '2026-09-11 14:31:25');
INSERT INTO `comments` (`id`, `recipe_id`, `parent_id`, `user_id`, `content`, `rating`, `image_url`, `created_at`) VALUES ('29', '16', NULL, '2', 'Công thức làm chuẩn chỉ và chi tiết lắm tác giả ơi! Chiều nay mình trổ tài cho cả nhà ai cũng khen nức nở.', '5', NULL, '2026-09-11 14:31:25');
INSERT INTO `comments` (`id`, `recipe_id`, `parent_id`, `user_id`, `content`, `rating`, `image_url`, `created_at`) VALUES ('30', '16', '29', '7', 'Cảm ơn bạn nhiều nha! Chúc bạn và gia đình luôn có những bữa cơm ngon miệng cùng Cookio ❤️', '5', NULL, '2026-09-11 14:31:25');
INSERT INTO `comments` (`id`, `recipe_id`, `parent_id`, `user_id`, `content`, `rating`, `image_url`, `created_at`) VALUES ('31', '17', NULL, '2', 'Công thức làm chuẩn chỉ và chi tiết lắm tác giả ơi! Chiều nay mình trổ tài cho cả nhà ai cũng khen nức nở.', '5', NULL, '2026-09-11 14:31:25');
INSERT INTO `comments` (`id`, `recipe_id`, `parent_id`, `user_id`, `content`, `rating`, `image_url`, `created_at`) VALUES ('32', '17', '31', '6', 'Cảm ơn bạn nhiều nha! Chúc bạn và gia đình luôn có những bữa cơm ngon miệng cùng Cookio ❤️', '5', NULL, '2026-09-11 14:31:25');
INSERT INTO `comments` (`id`, `recipe_id`, `parent_id`, `user_id`, `content`, `rating`, `image_url`, `created_at`) VALUES ('33', '18', NULL, '2', 'Công thức làm chuẩn chỉ và chi tiết lắm tác giả ơi! Chiều nay mình trổ tài cho cả nhà ai cũng khen nức nở.', '5', NULL, '2026-09-11 14:31:25');
INSERT INTO `comments` (`id`, `recipe_id`, `parent_id`, `user_id`, `content`, `rating`, `image_url`, `created_at`) VALUES ('34', '18', '33', '8', 'Cảm ơn bạn nhiều nha! Chúc bạn và gia đình luôn có những bữa cơm ngon miệng cùng Cookio ❤️', '5', NULL, '2026-09-11 14:31:25');

-- SEED DATA: `follows`
INSERT INTO `follows` (`id`, `follower_id`, `author_id`, `created_at`) VALUES ('1', '1', '2', '2026-09-10 00:32:53');
INSERT INTO `follows` (`id`, `follower_id`, `author_id`, `created_at`) VALUES ('3', '4', '2', '2026-09-11 14:07:20');

-- SEED DATA: `recipe_likes`
INSERT INTO `recipe_likes` (`id`, `recipe_id`, `user_id`, `created_at`) VALUES ('1', '1', '2', '2026-09-10 00:50:58');
INSERT INTO `recipe_likes` (`id`, `recipe_id`, `user_id`, `created_at`) VALUES ('2', '2', '2', '2026-09-10 00:50:58');
INSERT INTO `recipe_likes` (`id`, `recipe_id`, `user_id`, `created_at`) VALUES ('4', '2', '4', '2026-09-11 14:08:04');

-- SEED DATA: `cookbooks`
INSERT INTO `cookbooks` (`id`, `user_id`, `title`, `description`, `cover_image`, `is_public`, `created_at`) VALUES ('1', '2', 'Thực Đơn Tăng Cơ Giảm Mỡ (High-Protein)', 'Bộ sưu tập các món ăn giàu đạm, ít tinh bột xấu, hoàn hảo cho người tập luyện và ăn kiêng.', 'sample_beef_celery.jpg', '1', '2026-09-10 00:50:58');
INSERT INTO `cookbooks` (`id`, `user_id`, `title`, `description`, `cover_image`, `is_public`, `created_at`) VALUES ('2', '2', 'Món Canh & Súp Ấm Bụng Mùa Mưa', 'Những bát canh nóng hổi, thanh ngọt dễ nấu cho bữa cơm sum vầy bên gia đình.', 'sample_pumpkin_soup.jpg', '1', '2026-09-10 00:50:58');
INSERT INTO `cookbooks` (`id`, `user_id`, `title`, `description`, `cover_image`, `is_public`, `created_at`) VALUES ('3', '5', 'Thực đơn cơm gia đình 7 ngày sum vầy', 'Tuyển tập những món ăn ngon, dễ làm, cân bằng dinh dưỡng giúp bạn không còn phải đau đầu nghĩ \"Hôm nay ăn gì?\" cho cả nhà.', NULL, '1', '2026-09-11 13:46:57');
INSERT INTO `cookbooks` (`id`, `user_id`, `title`, `description`, `cover_image`, `is_public`, `created_at`) VALUES ('4', '9', 'Thực đơn Eat Clean & Giảm cân thanh lọc', 'Bộ thực đơn healthy ít dầu mỡ, giàu đạm sạch và chất xơ, hỗ trợ giữ dáng thon gọn mà cơ thể luôn tràn đầy năng lượng.', NULL, '1', '2026-09-11 13:46:57');
INSERT INTO `cookbooks` (`id`, `user_id`, `title`, `description`, `cover_image`, `is_public`, `created_at`) VALUES ('5', '7', 'Món ngon cuối tuần & Tụ tập bạn bè', 'Những món ăn đậm đà, hấp dẫn rất thích hợp để trổ tài chiêu đãi người thân, bạn bè trong những buổi gặp gỡ rộn rã tiếng cười.', NULL, '1', '2026-09-11 13:46:57');
INSERT INTO `cookbooks` (`id`, `user_id`, `title`, `description`, `cover_image`, `is_public`, `created_at`) VALUES ('6', '8', 'Bữa sáng cấp tốc tràn đầy năng lượng', 'Các món ăn sáng siêu tốc chỉ từ 10 - 15 phút nhưng thơm ngon, nóng hổi và cung cấp đủ dinh dưỡng cho ngày mới năng động.', NULL, '1', '2026-09-11 13:46:57');

-- SEED DATA: `cookbook_recipes`
INSERT INTO `cookbook_recipes` (`id`, `cookbook_id`, `recipe_id`, `created_at`) VALUES ('1', '1', '1', '2026-09-10 00:50:58');
INSERT INTO `cookbook_recipes` (`id`, `cookbook_id`, `recipe_id`, `created_at`) VALUES ('2', '1', '2', '2026-09-10 00:50:58');
INSERT INTO `cookbook_recipes` (`id`, `cookbook_id`, `recipe_id`, `created_at`) VALUES ('3', '2', '3', '2026-09-10 00:50:58');
INSERT INTO `cookbook_recipes` (`id`, `cookbook_id`, `recipe_id`, `created_at`) VALUES ('4', '2', '4', '2026-09-10 00:50:58');
INSERT INTO `cookbook_recipes` (`id`, `cookbook_id`, `recipe_id`, `created_at`) VALUES ('5', '3', '8', '2026-09-11 13:46:57');
INSERT INTO `cookbook_recipes` (`id`, `cookbook_id`, `recipe_id`, `created_at`) VALUES ('6', '3', '4', '2026-09-11 13:46:57');
INSERT INTO `cookbook_recipes` (`id`, `cookbook_id`, `recipe_id`, `created_at`) VALUES ('7', '3', '9', '2026-09-11 13:46:57');
INSERT INTO `cookbook_recipes` (`id`, `cookbook_id`, `recipe_id`, `created_at`) VALUES ('8', '3', '2', '2026-09-11 13:46:57');
INSERT INTO `cookbook_recipes` (`id`, `cookbook_id`, `recipe_id`, `created_at`) VALUES ('9', '4', '11', '2026-09-11 13:46:57');
INSERT INTO `cookbook_recipes` (`id`, `cookbook_id`, `recipe_id`, `created_at`) VALUES ('10', '4', '3', '2026-09-11 13:46:57');
INSERT INTO `cookbook_recipes` (`id`, `cookbook_id`, `recipe_id`, `created_at`) VALUES ('11', '4', '14', '2026-09-11 13:46:57');
INSERT INTO `cookbook_recipes` (`id`, `cookbook_id`, `recipe_id`, `created_at`) VALUES ('12', '5', '7', '2026-09-11 13:46:57');
INSERT INTO `cookbook_recipes` (`id`, `cookbook_id`, `recipe_id`, `created_at`) VALUES ('13', '5', '13', '2026-09-11 13:46:57');
INSERT INTO `cookbook_recipes` (`id`, `cookbook_id`, `recipe_id`, `created_at`) VALUES ('14', '5', '1', '2026-09-11 13:46:57');
INSERT INTO `cookbook_recipes` (`id`, `cookbook_id`, `recipe_id`, `created_at`) VALUES ('15', '5', '10', '2026-09-11 13:46:57');
INSERT INTO `cookbook_recipes` (`id`, `cookbook_id`, `recipe_id`, `created_at`) VALUES ('16', '6', '12', '2026-09-11 13:46:57');
INSERT INTO `cookbook_recipes` (`id`, `cookbook_id`, `recipe_id`, `created_at`) VALUES ('17', '6', '2', '2026-09-11 13:46:57');

-- SEED DATA: `notifications`
INSERT INTO `notifications` (`id`, `user_id`, `actor_id`, `type`, `target_id`, `content`, `is_read`, `created_at`) VALUES ('18', '2', '5', 'like', '1', 'đã thả tim món \"Bò xào hành tây cần tây\" của bạn.', '0', '2026-09-11 14:31:25');
INSERT INTO `notifications` (`id`, `user_id`, `actor_id`, `type`, `target_id`, `content`, `is_read`, `created_at`) VALUES ('19', '2', '9', 'comment', '2', 'đã để lại một nhận xét 5 sao cho món \"Trứng chiên cà chua\".', '0', '2026-09-11 14:31:25');
INSERT INTO `notifications` (`id`, `user_id`, `actor_id`, `type`, `target_id`, `content`, `is_read`, `created_at`) VALUES ('20', '2', '7', 'follow', NULL, 'đã bắt đầu theo dõi gian bếp của bạn.', '0', '2026-09-11 14:31:25');
INSERT INTO `notifications` (`id`, `user_id`, `actor_id`, `type`, `target_id`, `content`, `is_read`, `created_at`) VALUES ('21', '2', '6', 'cooksnap', '3', 'đã nấu thử thành công và chia sẻ ảnh Cooksnap món \"Thịt gà hấp lá chanh\"!', '0', '2026-09-11 14:31:25');
INSERT INTO `notifications` (`id`, `user_id`, `actor_id`, `type`, `target_id`, `content`, `is_read`, `created_at`) VALUES ('22', '2', '8', 'like', '4', 'đã lưu món \"Canh thịt bò rau xanh\" của bạn vào sổ tay yêu thích.', '0', '2026-09-11 14:31:25');
INSERT INTO `notifications` (`id`, `user_id`, `actor_id`, `type`, `target_id`, `content`, `is_read`, `created_at`) VALUES ('23', '5', '2', 'like', '8', 'đã thả tim món \"Sườn xào chua ngọt chuẩn vị cơm mẹ nấu\" của bạn.', '0', '2026-09-11 14:31:25');
INSERT INTO `notifications` (`id`, `user_id`, `actor_id`, `type`, `target_id`, `content`, `is_read`, `created_at`) VALUES ('24', '5', '9', 'comment', '8', 'vừa hỏi bạn một câu về tỷ lệ nước sốt sườn chua ngọt.', '0', '2026-09-11 14:31:25');
INSERT INTO `notifications` (`id`, `user_id`, `actor_id`, `type`, `target_id`, `content`, `is_read`, `created_at`) VALUES ('25', '5', '6', 'follow', NULL, 'đã ghé thăm và theo dõi gian bếp của bạn.', '0', '2026-09-11 14:31:25');
INSERT INTO `notifications` (`id`, `user_id`, `actor_id`, `type`, `target_id`, `content`, `is_read`, `created_at`) VALUES ('26', '1', '2', 'comment', '1', '[Hệ thống] Thành viên @chef_lan vừa đăng một nhận xét mới cần duyệt.', '0', '2026-09-11 14:31:25');
INSERT INTO `notifications` (`id`, `user_id`, `actor_id`, `type`, `target_id`, `content`, `is_read`, `created_at`) VALUES ('27', '1', '9', 'follow', NULL, '[Hệ thống] Thành viên mới @lan_anh_kitchen vừa tham gia cộng đồng Cookio.', '0', '2026-09-11 14:31:25');
INSERT INTO `notifications` (`id`, `user_id`, `actor_id`, `type`, `target_id`, `content`, `is_read`, `created_at`) VALUES ('28', '1', '7', 'like', '1', '[Hệ thống] Công thức \"Bò xào hành tây\" vừa cán mốc 100 lượt thả tim.', '0', '2026-09-11 14:31:25');
