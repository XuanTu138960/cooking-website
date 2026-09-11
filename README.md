#  Cookio - Mạng Xã Hội Ẩm Thực & Nền Tảng Chia Sẻ Công Thức Bếp Gia Đình

> Nền tảng ẩm thực gia đình hiện đại, ấm cúng và thông minh theo chuẩn **Cookpad**, **Tasty** và **Yummly**, được xây dựng bằng **PHP thuần**, **MySQL**, **JavaScript (ES6+)** và **CSS hiện đại**.

---

##  Tính Năng Nổi Bật

### 1. Giao Diện Bếp Nhà & Banner Tự Trượt Hiện Đại (Hero Carousel)
- **3 Banner tự động trượt mượt mà**: Tự động chuyển đổi sau 5 giây, dừng khi rê chuột, có nút điều hướng mũi tên và chấm tròn phân trang.
  -  *Bếp Nhà Sum Vầy*: Tìm kiếm thông minh, thẻ tag thịnh hành & gợi ý ngẫu nhiên *" Hôm nay ăn gì?"*.
  -  *Tủ Lạnh Thông Minh 2.0*: Chọn nguyên liệu có sẵn trong tủ, Cookio đề xuất công thức chuẩn xác chống lãng phí.
  -  *Sổ Tay & Thực Đơn 7 Ngày*: Khám phá menu cả tuần cân bằng dinh dưỡng, gom nguyên liệu đi chợ trong 1 cú nhấp.
- **Bộ ảnh HD vector riêng biệt cho từng món ăn**: Toàn bộ 16+ công thức đều sở hữu ảnh minh họa sắc nét chuẩn retina, màu sắc tươi ngon, bắt mắt.
- **Chế độ Bếp Ban Đêm (High-Contrast Dark Mode)**: Tối ưu độ tương phản cao, thẻ than chì sâu viền sắc nét, chữ sáng rõ ràng, không mỏi mắt khi nấu nướng trong điều kiện ánh sáng yếu.

### 2. Trải Nghiệm Tương Tác Cộng Đồng Sôi Động
- **Trung tâm Thông báo Thời gian thực (Phân tách riêng biệt theo từng tài khoản)**:
  - Thông báo đa dạng: Thả tim món ăn (`like`), bình luận/Cooksnap (`comment`, `cooksnap`), trả lời hỏi đáp Q&A, người theo dõi mới (`follow`).
  - Phân loại tab thông báo tiện lợi: *Tất cả*, * Tim*, * Bình luận*, * Theo dõi*.
  - Đảm bảo tài khoản thành viên chỉ xem thông báo gửi riêng cho mình, tách biệt hoàn toàn với quản trị viên.
- **Chuỗi Thảo luận & Hỏi Đáp Tác Giả Đa Tầng (Threaded Author Q&A)**:
  - Phản hồi lùi vào bên phải kèm huy hiệu danh dự ** Tác giả / Đầu bếp**.
  - Khung trả lời nhanh mở linh hoạt dưới từng nhận xét.
- **Ảnh thực hành Cooksnap**: Tải ảnh chụp đĩa ăn thực tế đã nấu để chia sẻ cùng tác giả và cộng đồng.

### 3. Sổ Tay Ẩm Thực & Bếp Yêu Thích Nâng Cấp
- **Bộ Sưu Tập Thực Đơn Tuyển Chọn (Curated Cookbooks)**:
  - *Thực đơn cơm gia đình 7 ngày sum vầy*.
  - *Thực đơn Eat Clean & Giảm cân thanh lọc cơ thể*.
  - *Món ngon cuối tuần & Tụ tập bạn bè*.
  - *Bữa sáng cấp tốc tràn đầy năng lượng dưới 15 phút*.
  - Bảng tổng kết thực đơn: Tổng số món, thời gian nấu ước tính cả menu, tổng calo.
- **Món Đã Lưu & Ghi Chú Bếp Cá Nhân (Personal Chef Notes)**:
  - Viết lưu ý riêng cho từng món đã lưu (*"Lần sau bớt đường, cho thêm gừng, chồng rất thích"*).
  - Lọc món đã lưu theo từng danh mục món xào, món canh, món kho...
  - Nút **" Gom nguyên liệu đi chợ"**: Tự động tổng hợp danh sách mua sắm dưới dạng bảng kiểm (checklist) có thể tích chọn và sao chép 1-Click.

### 4. Tiện Ích Thông Minh Khác
- **Bộ lọc đa tiêu chí (Smart Filter Bar)**: Lọc đồng thời theo Thời gian nấu (< 15p, 15 - 30p, > 30p), Năng lượng (< 300 kcal, 300 - 500 kcal, > 500 kcal) và Chế độ ăn (Eat Clean, Giàu Protein, Low-Carb, Món chay).
- **Chế độ nấu rảnh tay (Cooking Mode)**: Phóng to toàn màn hình, nút lớn dễ bấm khi tay dính dầu mỡ.
- **Đồng hồ bấm giờ nhà bếp (Kitchen Timer)**: Kèm âm thanh chuông báo Web Audio API.
- **Bộ điều chỉnh khẩu phần động (Servings Scaler)**: Tự động tính toán lại định lượng nguyên liệu theo số người ăn.
- **Bảng phân tích hiệu quả gian bếp (Chef Studio Analytics)**: Thống kê lượt xem, lượt tim, người theo dõi của tác giả.

---

##  Cài Đặt & Khởi Chạy

### Yêu Cầu
- PHP 8.0 trở lên.
- MySQL 5.7+ hoặc MariaDB 10.4+ (XAMPP).

### Các Bước Cài Đặt
1. **Khởi động MySQL** trong XAMPP Control Panel.
2. **Import cơ sở dữ liệu**:
   - Sử dụng phpMyAdmin hoặc MySQL CLI import tệp: `sql/cookio_db.sql`.
3. **Khởi động máy chủ phát triển**:
   ```bash
   php -S localhost:8000
   ```
4. **Truy cập hệ thống**: Mở trình duyệt vào **[http://localhost:8000](http://localhost:8000)**.

---

##  Tài Khoản Trải Nghiệm Mẫu

| Tài khoản | Vai trò | Mật khẩu | Điểm nổi bật |
| :--- | :--- | :---: | :--- |
| **`admin1111`** | Quản trị viên (Admin) | `1` | Toàn quyền kiểm duyệt công thức, quản lý người dùng, xem thống kê hệ thống |
| **`chef_lan`** | Đầu bếp tiêu biểu | `1` | Gian bếp có 4 món ăn, nhiều huy hiệu đầu bếp, 5 thông báo tương tác cộng đồng |
| **`me_bong`** | Bếp cơm gia đình | `1` | Tác giả món Phở bò Hà Nội, Sườn xào chua ngọt, Nộm hoa chuối |
| **`chu_nam_cook`** | Món ngon miền Tây | `1` | Tác giả Thịt kho tàu nước dừa, Canh chua cá lóc, Bánh xèo |
| **`lan_anh_kitchen`**| Healthy & Eat Clean | `1` | Tác giả Salad ức gà sốt mè rang, Thực đơn Eat Clean giảm cân |

---

##  Tiêu Chuẩn Bảo Mật
- **Bảo vệ CSRF**: 100% các request thay đổi trạng thái (POST) đều được bảo vệ bởi CSRF Token ngẫu nhiên và an toàn theo session.
- **Upload An Toàn**: Kiểm tra MIME thực tế bằng `finfo`, tạo tên tệp ngẫu nhiên chống tấn công path traversal, tệp `uploads/.htaccess` cấm triệt để việc thực thi mã PHP.
- **Phân Quyền Chặt Chẽ**: Hệ thống middleware kiểm soát quyền truy cập trang Admin và thao tác chỉnh sửa bài viết của tác giả.

---

© 2026 Cookio Team. Dự án mã nguồn mở phát triển cho cộng đồng yêu bếp Việt.
