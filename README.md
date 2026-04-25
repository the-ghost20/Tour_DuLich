# Tour_DuLich — Website đặt tour du lịch

Ứng dụng web **PHP + MySQL** (PDO): khách xem tour, đặt chỗ, thanh toán / theo dõi đơn, đánh giá, blog; **quản trị viên** và **nhân viên** quản lý tour, đơn, khách, mã giảm giá, blog và báo cáo. Giao diện HTML/CSS/JS trong `assets/`, logic chính xử lý phía server bằng PHP, **không bắt buộc framework**.

---

## Yêu cầu hệ thống

| Thành phần | Ghi chú |
|------------|---------|
| **PHP** | 8.0 trở lên (khuyến nghị 8.2+): `password_hash` / `password_verify`, PDO MySQL, `strict_types` trong nhiều file |
| **MySQL** | 8.x hoặc **MariaDB** 10.5+ |
| **Web server** | Apache (XAMPP / MAMP / WAMP) hoặc nginx + PHP-FPM |
| **Trình duyệt** | Hiện đại (ES6+ cho một số đoạn JS) |

---

## Tính năng theo vai trò

### Khách (`frontend/`)

- **Trang chủ, giới thiệu, bảng giá, FAQ, điều khoản, chính sách, hướng dẫn, liên hệ**
- **Danh sách tour** (`tours.php`): lọc theo điểm đến, giá, thời lượng, loại hình, tìm kiếm
- **Chi tiết tour** (`tour_detail.php`): ảnh/gallery, lịch trình (itinerary), lịch khởi hành, đặt chỗ
- **Đặt tour** (`booking.php`), **báo giá / tóm tắt** (`booking_quote.php`): chọn ngày, áp mã coupon, phụ thu ngày lễ (nếu cấu hình)
- **Thanh toán chuyển khoản** (`payment.php`, cấu hình `includes/payment_bank.php`): xem thông tin ngân hàng, xác nhận đã chuyển (cập nhật trạng thái đơn tùy luồng)
- **Đơn của tôi** (`my_bookings.php`), **yêu thích** (`wishlist.php`)
- **Đánh giá tour** (form trên `tour_detail.php`, lưu `tour_reviews`)
- **Blog** (`blog.php`, `blog_detail.php`): tìm kiếm/lọc danh mục client-side, phản hồi blog
- **Hồ sơ & đổi mật khẩu** (`profile.php`)

### Xác thực (`auth/`)

- Đăng ký, đăng nhập (phân luồng theo role: admin / staff / khách)
- Đăng xuất, quên mật khẩu, đặt lại mật khẩu

### Quản trị (`admin/`)

- Dashboard, thống kê / báo cáo doanh thu, xuất dữ liệu
- **Tour**: CRUD, ảnh, lịch trình JSON, gallery
- **Danh mục tour**, **đơn đặt**, **yêu cầu hủy**, **đánh giá**
- **Người dùng**, **nhân viên**, **mã giảm giá (coupon)**
- **Blog**: danh sách, thêm/sửa bài
- **Cài đặt** hệ thống (tùy module)

### Nhân viên (`staff/`)

- Dashboard, xử lý **đơn đặt** (danh sách, chi tiết, cập nhật trạng thái)
- **Blog** (thêm/sửa theo quyền dự án)
- **Tour**: cập nhật số chỗ (slots) và các thao tác được phân quyền
- **Liên hệ** / phản hồi (nếu bật trong routing)

---

## Công nghệ

- **Backend:** PHP, PDO, session
- **Cơ sở dữ liệu:** MySQL/MariaDB (`utf8mb4`)
- **Frontend:** HTML5, CSS (`assets/css/style.css`, `admin.css`, `staff.css`), JavaScript (`assets/js/main.js`)
- **Icon:** Font Awesome (CDN trên một số trang)

---

## Cấu trúc thư mục

**Bản đầy đủ (file nào giữ, SQL nào khi nào):** mục **« Cấu trúc project Tour_DuLich »** ở **cuối README**.

Tóm tắt:

```text
Tour_DuLich/
├── README.md
├── config/database.php         # → includes/db.php
├── includes/                   # DB, config, migration, layout, helper (booking_slots, payment_bank, …)
├── assets/                     # css / js (main.js) / images
├── frontend/                   # Trang khách
├── auth/                       # Đăng nhập, đăng ký, demo_account_setup
├── admin/                      # Quản trị
├── staff/                      # Nhân viên
├── uploads/                    # Upload ảnh (runtime)
└── database/                   # tour_management.sql, sample_data.sql, migrations/, dev-scripts/
```

### Các bảng chính (schema gốc)

`categories`, `users`, `tours`, `bookings`, `tour_reviews`, `blog_feedback`, `coupons`, `blog_posts` — xem định nghĩa đầy đủ trong `database/tour_management.sql`.

Khi ứng dụng chạy, `includes/db.php` có thể **tự thêm cột** (itinerary, gallery, blog meta, cột booking coupon/departure, `paid_at` cho đơn đã thanh toán, …) nếu database cũ chưa import hết file trong `database/migrations/`. Cơ chế này idempotent (an toàn chạy lại).

---

## Danh sách file mã nguồn (in / đính kèm Word, báo cáo)

Dùng danh sách dưới đây khi cần **trích đoạn code** hoặc **mục lục phụ lục** trong tài liệu Word. Nhóm theo vai trò trong hệ thống:

| Nhóm | Đường dẫn (file chính) |
|------|-------------------------|
| **Cấu hình & lõi** | `includes/config.php`, `includes/db.php`, `includes/functions.php`, `includes/session.php`, `config/database.php`, `includes/schema_migrations.php` |
| **Đặt chỗ & giá** | `includes/booking_slots.php`, `includes/booking_pricing.php`, `includes/booking_holidays.php`, `includes/booking_modal.php`, `frontend/booking.php`, `frontend/booking_quote.php` |
| **Thanh toán** | `includes/payment_bank.php`, `frontend/payment.php` |
| **Tour & nội dung** | `includes/tour_itinerary.php`, `includes/tour_itinerary_defaults.php`, `includes/tour_content_helpers.php`, `frontend/tours.php`, `frontend/tour_detail.php`, `frontend/index.php` |
| **Khách (còn lại)** | `frontend/my_bookings.php`, `frontend/wishlist.php`, `frontend/blog.php`, `frontend/blog_detail.php`, `frontend/profile.php`, các trang tĩnh `about.php`, `faq.php`, `pricing.php`, `guide.php`, `terms.php`, `privacy.php` |
| **Auth** | `auth/login.php`, `auth/register.php`, `auth/logout.php`, `auth/forgot_password.php`, `auth/demo_account_setup.php` |
| **Admin** | `admin/index.php`, `admin/tours/*.php`, `admin/bookings/*.php`, `admin/cancel_requests/*.php`, `admin/users/*.php`, `admin/staff/*.php`, `admin/coupons/*.php`, `admin/categories/*.php`, `admin/blog/*.php`, `admin/reviews/list.php`, `admin/reports/*.php`, `admin/settings/index.php` |
| **Staff** | `staff/index.php`, `staff/bookings/*.php`, `staff/tours/update_slots.php`, `staff/blog/*.php`, `staff/reviews/list.php`, `staff/contact/list.php`, `staff/profile.php` |
| **Giao diện chung** | `includes/header.php`, `includes/footer.php`, `includes/admin_header.php`, `includes/admin_footer.php`, `includes/staff_header.php`, `includes/staff_footer.php`, `assets/css/style.css`, `assets/css/admin.css`, `assets/css/staff.css`, `assets/js/main.js` |
| **Blog / email** | `includes/blog_helpers.php`, `includes/blog_listing_fragment.php`, `includes/blog_articles.php`, `includes/mailer.php` |
| **CSDL** | `database/tour_management.sql`, `database/sample_data.sql`, `database/migrations/*.sql`, `database/dev-scripts/*` |

**Gợi ý:** với báo cáo ngắn, ưu tiên in/ghi chú: `config.php` + `db.php`, một luồng `booking.php` → `booking_quote.php` → `payment.php`, và `tour_management.sql` (sơ đồ bảng).

---

## Cài đặt cơ sở dữ liệu

1. Bật MySQL.
2. Import schema (tạo database và bảng):

   ```bash
   mysql -u root -p < database/tour_management.sql
   ```

3. **Khuyến nghị:** nạp dữ liệu mẫu (xóa/insert theo thứ tự FK trong file):

   ```bash
   mysql -u root -p tour_dulich < database/sample_data.sql
   ```

4. **Cấu hình kết nối** trong `includes/config.php`:

   - `$dbHost`, `$dbName` (`tour_dulich`), `$dbUser`, `$dbPass`, **`$dbPort`**
   - **MAMP (macOS)** thường dùng MySQL port **8889**, user `root` / pass `root` — file mặc định trong repo thường trùng cấu hình này.
   - **XAMPP** thường port **3306**, mật khẩu `root` để trống — **cần sửa** `config.php` cho khớp.

`config/database.php` chỉ `require` lại `includes/db.php` để tập trung cấu hình.

### Tài khoản mẫu (`sample_data.sql`)

Đăng nhập bằng **email** (cột `users.email` là duy nhất). Mật khẩu mẫu: **`password`** (8 ký tự).

| Vai trò | Email | Mật khẩu |
|--------|--------|----------|
| Admin | `admin.dulichviet@gmail.com` | `password` |
| Staff | `staff.dulichviet@gmail.com` | `password` |
| Khách | `user1.dulichviet@gmail.com` … `user4.dulichviet@gmail.com` | `password` |

**Không đăng nhập được với email Gmail mới?** File trong repo chỉ là mã nguồn — MySQL trên máy bạn **không tự đổi** cho đến khi bạn import lại `sample_data.sql` hoặc chạy SQL cập nhật.

- **Cách dễ nhất (trình duyệt):** tạo file rỗng `database/.enable_demo_setup`, rồi mở **`auth/demo_account_setup.php`** (ví dụ `http://localhost:8888/Tour_DuLich/auth/demo_account_setup.php`). Trang sẽ hiện email đang lưu trong DB và có nút **Đặt lại tài khoản demo** (ghi email Gmail + mật khẩu `password` bằng `password_hash` của PHP). Sau khi xong, file `.enable_demo_setup` tự bị xóa.
- **Hoặc dùng MySQL:** import **`database/dev-scripts/fix_demo_accounts.sql`** (cùng hash bcrypt có sẵn trong repo).

Đăng nhập cần **đủ hai ô**: email `admin.dulichviet@gmail.com` và mật khẩu chữ thường **`password`**. Kiểm tra `includes/config.php` trùng **host/port/tên DB** với nơi bạn đã import dữ liệu (MAMP thường port **8889**).

### Quy tắc nhập liệu người dùng (ứng dụng)

- **Mật khẩu:** độ dài tối thiểu **8** ký tự, tối đa **128** ký tự; không để trống (khuyến nghị 8–12 ký tự cho dễ nhớ). Áp dụng tại đăng ký, đổi mật khẩu, tạo/sửa nhân viên (admin).
- **Trường bắt buộc:** họ tên, email, số điện thoại (và mật khẩu khi tạo tài khoản) không được để trống ở các form tương ứng.
- **Không trùng lặp:** **email** đảm bảo duy nhất toàn hệ thống (ràng buộc DB + kiểm tra khi đăng ký/cập nhật). **Số điện thoại** kiểm tra trùng trên mã nguồn (so khớp linh hoạt `0xxxx` / `+84`).

Tạo hash mới nếu cần cập nhật tay cột `users.password`:

```bash
php -r "echo password_hash('password', PASSWORD_DEFAULT), PHP_EOL;"
```

---

## Chạy dự án

### Apache (XAMPP — Windows / macOS / Linux)

1. Copy project vào `htdocs`, ví dụ:
   - Windows: `C:\xampp\htdocs\Tour_DuLich`
   - macOS: `/Applications/XAMPP/xamppfiles/htdocs/Tour_DuLich`
2. Bật **Apache** và **MySQL**.
3. Sửa `includes/config.php` cho đúng port MySQL (thường **3306**) và mật khẩu.
4. URL ví dụ:

   - Trang chủ: `http://localhost/Tour_DuLich/frontend/index.php`
   - Đăng nhập: `http://localhost/Tour_DuLich/auth/login.php`
   - Admin: `http://localhost/Tour_DuLich/admin/index.php`
   - Staff: `http://localhost/Tour_DuLich/staff/index.php`

Nếu cấu hình **virtual host** trỏ document root vào thư mục `Tour_DuLich`, đường dẫn sẽ ngắn hơn (ví dụ `/frontend/index.php`).

### MAMP (macOS)

1. Đặt project trong `/Applications/MAMP/htdocs/Tour_DuLich` (hoặc symlink).
2. MAMP → **Start** (Apache + MySQL). Kiểm tra *Preferences → Ports* (web thường **8888**, MySQL **8889**).
3. Giữ hoặc chỉnh `includes/config.php` khớp port/mật khẩu MySQL.
4. Import CSDL (đường dẫn binary `mysql` tùy phiên bản MAMP), ví dụ:

   ```bash
   /Applications/MAMP/Library/bin/mysql80/bin/mysql -u root -proot -h 127.0.0.1 -P 8889 < database/tour_management.sql
   /Applications/MAMP/Library/bin/mysql80/bin/mysql -u root -proot -h 127.0.0.1 -P 8889 tour_dulich < database/sample_data.sql
   ```

5. Truy cập ví dụ: `http://localhost:8888/Tour_DuLich/frontend/index.php`

**Lưu ý:** Nếu bạn mở project trên Desktop trong IDE nhưng Apache trỏ vào `htdocs`, hai bản có thể **lệch file** — nên làm việc trên một bản hoặc đồng bộ (copy / rsync / symlink).

### PHP built-in server (chỉ để thử nhanh)

```bash
cd /đường/dẫn/Tour_DuLich
php -S localhost:8080
```

Cần cấu hình router hoặc mở trực tiếp file `.php` trong `frontend/`; một số đường dẫn tương đối tới `assets/` có thể khác Apache — **không khuyến nghị** thay Apache/MAMP cho đầy đủ tính năng.

---

## Email và tích hợp ngoài

- **`includes/mailer.php`:** hiện là **stub** (ghi `error_log`), chưa gửi SMTP thật. Khi triển khai production có thể tích hợp PHPMailer/SwiftMailer hoặc API email.
- **`assets/js/main.js`:** có `API_BASE_URL` (ví dụ `http://localhost:4000/api`) và cờ `USE_LEGACY_NODE_TOUR_API`. Luồng mặc định dùng **PHP + MySQL** render sẵn; chỉ bật API Node nếu bạn chạy thêm backend và chỉnh URL.

---

## Upload và quyền thư mục

- Thư mục **`uploads/`** dùng cho ảnh tour, blog, avatar, …
- Trên server Linux: đảm bảo user chạy PHP (vd. `www-data`) **ghi được** thư mục con cần thiết; không commit file upload nhạy cảm vào git nếu không chủ đích.

---

## Bảo mật & triển khai production (gợi ý)

- Đổi mật khẩu database và tài khoản admin mặc định.
- Tắt hiển thị lỗi chi tiết PHP (`display_errors = Off`), bật log.
- Bật **HTTPS**, cấu hình session an toàn (cookie `Secure`/`HttpOnly` tùy môi trường).
- Sao lưu định kỳ database và thư mục `uploads/`.

---

## Gỡ lỗi thường gặp

| Hiện tượng | Hướng xử lý |
|------------|-------------|
| “Không thể kết nối đến database” | Kiểm tra MySQL đã chạy, `includes/config.php` (host/port/user/pass), database `tour_dulich` đã import |
| Đăng nhập không được sau import mẫu | Chạy lại `sample_data.sql` hoặc reset hash mật khẩu (lệnh `password_hash` ở trên) |
| CSS/JS không load | Đường dẫn tương đối `../assets/` — đảm bảo mở đúng URL qua Apache, không mở file `file://` |
| Thiếu cột bảng | Mở lại trang web sau khi kết nối DB để migration runtime chạy; hoặc import thủ công file trong `database/migrations/` |

---

## Giấy phép & ghi chú

© Dự án Tour_DuLich — tài liệu phản ánh cấu trúc mã nguồn tại thời điểm cập nhật. Nếu thêm module mới, nên bổ sung mục tương ứng vào README này.

---
# Cấu trúc project Tour_DuLich — nên giữ / nên hiểu / SQL nào khi nào

Tài liệu này giúp đọc cây thư mục nhanh: **thư mục cốt lõi**, **file phụ trợ**, **file SQL** (không gộp vào một file để bạn linh hoạt import từng bước).

---

## Sơ đồ tổng quan

```text
Tour_DuLich/
├── README.md                 # Hướng dẫn cài đặt & chạy (kèm bản đồ thư mục ở cuối)
├── .gitignore                # Bỏ qua .DS_Store, cờ demo setup, …
│
├── config/
│   └── database.php          # Giữ — trỏ tới includes/db.php (một điểm cấu hình)
│
├── includes/                 # Giữ toàn bộ — lõi: DB, header/footer, helper
├── assets/                   # Giữ — css, js, images
├── frontend/                 # Giữ — trang khách
├── auth/                     # Giữ — đăng nhập, đăng ký, demo_account_setup (công cụ)
├── admin/                    # Giữ — quản trị
├── staff/                    # Giữ — nhân viên
├── uploads/                  # Giữ thư mục — ảnh upload runtime (nội dung tuỳ môi trường)
└── database/                 # schema, sample_data, migrations/, dev-scripts/ (SQL + demo_account_setup logic)
```

---

## Thư mục — vai trò

| Thư mục | Giữ? | Ghi chú ngắn |
|---------|------|----------------|
| **`includes/`** | Có | `config.php`, `db.php`, PDO, migration tự chạy, `header.php` / `footer.php`, helper tour/booking/blog. |
| **`assets/`** | Có | `css/` (style, admin, staff), `js/` (main.js), `images/`. |
| **`frontend/`** | Có | Trang công khai: index, tours, tour_detail, booking, blog, profile, … |
| **`auth/`** | Có | login, register, forgot/reset password, **`demo_account_setup.php`** (công cụ 1 lần khi cần). |
| **`admin/`** | Có | Toàn bộ module quản trị (tour, đơn, user, coupon, blog, …). |
| **`staff/`** | Có | Module nhân viên (đơn, blog, tour slots, …). |
| **`config/`** | Có | Chủ yếu `database.php` → `includes/db.php`. |
| **`database/`** | Có | SQL và migration — **không xóa**; chỉ cần biết **khi nào chạy file nào** (mục dưới). |
| **`uploads/`** | Có (thư mục) | Ảnh do admin upload; nội dung có thể không commit tùy team. |

---

## File đã gọn / đổi vai trò (để khỏi rối)

| Trước đây | Hiện tại |
|-----------|-----------|
| `assets/js/filter.js` | **Đã xóa** — file rỗng, không trang nào nhúng; lọc tour nằm trong `main.js` + `tours.php`. |
| `frontend/search.php` | **Tuỳ bản:** có thể **redirect → `tours.php`** hoặc đã xóa — không ảnh hưởng luồng chính (tìm tour trên `tours.php`). |
| `.DS_Store` | **Đã xóa** khỏi repo mẫu; thêm **`.gitignore`** để macOS không làm bẩn diff. |

---

## File SQL trong `database/`

| File | Khi nào dùng |
|------|----------------|
| **`tour_management.sql`** | Lần đầu: tạo DB + bảng. |
| **`sample_data.sql`** | Sau đó: dữ liệu mẫu (user, tour, booking, blog, lịch trình itinerary…). |
| **`migrations/*.sql`** | DB cũ thiếu cột / bảng: import từng file theo số thứ tự **hoặc** để app tự `ALTER` khi chạy (xem `includes/schema_migrations.php`). Ví dụ `005_bookings_paid_at.sql` bổ sung thời điểm thanh toán. |
| **`dev-scripts/fix_demo_accounts.sql`** | Set email Gmail + hash mật khẩu `password` cho user id 1–6 (khi đăng nhập demo lệch). |
| **`dev-scripts/demo_account_setup.php`** | Logic cho trang `auth/demo_account_setup.php` (reset demo qua trình duyệt). |

**Gợi ý gọn:** làm việc hàng ngày chỉ cần nhớ **`tour_management.sql` + `sample_data.sql`**; khi đăng nhập demo sai thì **`database/dev-scripts/fix_demo_accounts.sql`** hoặc trang **`auth/demo_account_setup.php`** (có file cờ `database/.enable_demo_setup`).

---

## File “nhỏ” nhưng **nên giữ** (không phải rác)

| File | Lý do |
|------|--------|
| `includes/blog_articles.php` | Fallback nội dung blog khi DB không có bài (`blog_detail.php`). |
| `auth/demo_account_setup.php` | Công cụ reset tài khoản demo qua trình duyệt. |

---

## JavaScript — file thực sự dùng

- **`assets/js/main.js`** — Trang khách: menu, tour filter, booking modal, wishlist, …  

Không còn `filter.js` (đã dọn).

---

## Ghi chú phát triển

- Đổi cấu trúc `admin/` / `staff/` thành namespace PSR-4: đổi lớn — ngoài phạm vi "dọn nhẹ".
---

© Cập nhật cùng repo Tour_DuLich — phần cài đặt và tài khoản mẫu nằm ở đầu file `README.md` này.
