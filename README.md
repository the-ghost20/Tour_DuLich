# Tour_DuLich — Website đặt tour du lịch

Ứng dụng web PHP + MySQL: khách xem tour, đặt tour, đánh giá; quản trị viên / nhân viên xử lý đơn và nội dung. Giao diện tĩnh kết hợp PHP server-side, không bắt buộc framework.

## Công nghệ

- **PHP** (phiên bản có hỗ trợ `password_hash`, PDO MySQL)
- **MySQL** 8.x hoặc MariaDB 10.5+
- **HTML / CSS / JavaScript** (tài nguyên trong `assets/`)
- **Apache** (khuyến nghị XAMPP, WAMP, hoặc Laravel Valet / built-in PHP server khi cấu hình đúng document root)

## Cấu trúc thư mục (tóm tắt)

```text
Tour_DuLich/
├── README.md
├── config/
│   └── database.php          # Tương thích: require includes/db.php
├── includes/                 # Cấu hình DB, PDO, hàm URL, header/footer, layout admin & staff
├── assets/
│   ├── css/                  # style.css, admin.css, staff.css
│   ├── js/                   # main.js, admin.js, filter.js
│   └── images/
├── frontend/                 # Trang khách: trang chủ, tour, đặt tour, blog, hồ sơ, …
├── auth/                     # Đăng nhập, đăng ký, đăng xuất, quên mật khẩu, …
├── admin/                    # Quản trị: dashboard, tours, bookings, users, …
├── staff/                    # Khu vực nhân viên (dashboard, đơn, blog, …)
├── uploads/                  # File upload (tours, blog, avatars)
└── database/
    ├── tour_management.sql   # Tạo database + toàn bộ bảng
    └── sample_data.sql       # Dữ liệu mẫu (tài khoản, tour, đặt tour, …)
```

## Cài đặt cơ sở dữ liệu

1. Bật MySQL (ví dụ trong XAMPP).
2. Tạo schema và bảng:

   ```bash
   mysql -u root -p < database/tour_management.sql
   ```

3. (Khuyến nghị) Nạp dữ liệu mẫu:

   ```bash
   mysql -u root -p tour_dulich < database/sample_data.sql
   ```

   Hoặc dùng phpMyAdmin: import lần lượt hai file trên.

4. Cấu hình kết nối trong `includes/config.php` (host, user, password, port, tên DB `tour_dulich`).

File `config/database.php` chỉ `require` lại `includes/db.php` để giữ một nơi cấu hình.

### Tài khoản mẫu (sau khi chạy `sample_data.sql`)

| Vai trò | Email | Mật khẩu |
|--------|--------|----------|
| Admin | `admin@dulichviet.test` | `password` |
| Staff | `staff@dulichviet.test` | `password` |
| Khách | `user1@dulichviet.test` … `user4@dulichviet.test` | `password` |

Nếu đăng nhập lỗi, tạo hash mới bằng PHP và cập nhật cột `password` trong bảng `users`:

```bash
php -r "echo password_hash('password', PASSWORD_DEFAULT), PHP_EOL;"
```

## Chạy dự án (XAMPP)

1. Copy toàn bộ project vào `htdocs`, ví dụ:

   `C:\xampp\htdocs\Tour_DuLich`  
   hoặc macOS: `/Applications/XAMPP/xamppfiles/htdocs/Tour_DuLich`

2. Bật **Apache** và **MySQL**.

3. Truy cập (điều chỉnh theo đường dẫn thực tế):

   - Trang chủ: `http://localhost/Tour_DuLich/frontend/index.php`
   - Đăng nhập: `http://localhost/Tour_DuLich/auth/login.php`
   - Admin: `http://localhost/Tour_DuLich/admin/index.php`
   - Staff: `http://localhost/Tour_DuLich/staff/index.php`

Nếu bạn cấu hình **virtual host** trỏ document root vào thư mục `Tour_DuLich`, URL sẽ ngắn hơn (ví dụ `/frontend/index.php`).

## Chạy với MAMP (macOS)

1. Đặt project trong **`/Applications/MAMP/htdocs/Tour_DuLich`** (hoặc symlink từ thư mục làm việc của bạn vào đây).
2. Mở **MAMP** → bật **Start** (Apache + MySQL). Port mặc định thường là **Web 8888**, **MySQL 8889** (kiểm tra *Preferences → Ports*).
3. Trong **`includes/config.php`** dùng đúng port MySQL và mật khẩu (mặc định MAMP hay là user `root` / password `root`, port `8889`). Repository đã có comment hướng dẫn chỉnh cho XAMPP.
4. Import CSDL (terminal, đường dẫn `mysql` tùy phiên bản MAMP):

   ```bash
   /Applications/MAMP/Library/bin/mysql80/bin/mysql -u root -proot -h 127.0.0.1 -P 8889 < database/tour_management.sql
   /Applications/MAMP/Library/bin/mysql80/bin/mysql -u root -proot -h 127.0.0.1 -P 8889 tour_dulich < database/sample_data.sql
   ```

5. Truy cập:

   - Trang chủ: `http://localhost:8888/Tour_DuLich/frontend/index.php`
   - Đăng nhập: `http://localhost:8888/Tour_DuLich/auth/login.php`

**Lưu ý:** Nếu bạn mở project từ Desktop trong Cursor nhưng Apache trỏ vào `htdocs`, hai bản có thể **lệch file** — sau khi sửa code nên đồng bộ (copy/rsync/symlink) hoặc chỉ làm việc trên một bản duy nhất.

## Chức năng chính (mapping code)

- **Khách (`frontend/`)**: xem tour, chi tiết, đặt tour (`booking.php` — JSON), lịch sử đặt, đánh giá tour, blog & phản hồi.
- **Xác thực (`auth/`)**: đăng ký, đăng nhập (phân luồng admin / staff / user), đăng xuất, quên mật khẩu.
- **Admin (`admin/`)**: dashboard thống kê, quản lý tour, đơn, người dùng, nhiều module stub sẵn cấu trúc thư mục.
- **Staff (`staff/`)**: dashboard và module xử lý (stub theo cấu trúc dự án).

## Ghi chú

- Đặt tour và phần lớn luồng dùng **PHP + MySQL**, không phụ thuộc Node.js.
- Trong `assets/js/main.js` có thể còn hằng số `API_BASE_URL` (ví dụ `localhost:4000`); các luồng hiện tại ưu tiên gọi endpoint PHP (`booking.php`, …). Nếu bạn thêm backend Node riêng, cần chỉnh URL cho khớp.
- Khi deploy production: đổi mật khẩu database, tắt hiển thị lỗi PHP, bật HTTPS, và rà soát quyền thư mục `uploads/`.

---

© Dự án Tour_DuLich — tài liệu cập nhật theo cấu trúc mã nguồn hiện tại.
