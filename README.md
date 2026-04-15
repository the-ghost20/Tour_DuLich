# Tour_DuLich - Website Đặt Tour

Dự án website đặt tour du lịch với mô hình kết hợp:
- **Frontend + giao diện quản trị:** PHP (chạy trên XAMPP)
- **Database:** MySQL
- **Backend API (cho một số chức năng frontend):** Node.js/Express (nếu sử dụng)

## Công nghệ sử dụng

- PHP, HTML, CSS, JavaScript
- MySQL (import từ `database.sql`)
- XAMPP (Apache + MySQL)
- Node.js (cho API tại `http://localhost:4000/api` nếu bạn bật backend)

## Cấu trúc dự án 

```text
Tour_DuLich/
├── README.md
├── database.sql
├── config/
│   └── database.php
├── admin/
│   └── tours.php
└── frontend/
    ├── index.php
    ├── tours.php
    ├── pricing.php
    ├── about.php
    ├── blog.php
    ├── faq.php
    ├── guide.php
    ├── privacy.php
    ├── terms.php
    ├── login.php
    ├── register.php
    ├── logout.php
    ├── css/
    │   └── styles.css
    ├── js/
    │   └── script.js
    ├── includes/
    │   ├── header.php
    │   └── footer.php
    ├── data/
    └── assets/
        └── img/
```

## Hướng dẫn cài đặt và chạy dự án

### 1) Chạy phần PHP + MySQL bằng XAMPP

1. Đặt source vào thư mục:
   - `/Applications/XAMPP/xamppfiles/htdocs/Tour_DuLich`
2. Mở XAMPP và bật:
   - Apache
   - MySQL
3. Import CSDL:
   - Vào phpMyAdmin (`http://localhost/phpmyadmin`)
   - Tạo database mới (ví dụ: `tour_dulich`)
   - Import file `database.sql`
4. Kiểm tra file kết nối DB:
   - `config/database.php`
   - Cập nhật host, user, password, tên database cho đúng máy của bạn
5. Truy cập dự án:
   - Trang người dùng: `http://localhost/Tour_DuLich/frontend/`
   - Trang quản trị: `http://localhost/Tour_DuLich/admin/tours.php`

### 2) Chạy backend Node.js API (nếu sử dụng)

Trong `frontend/js/script.js`, một số chức năng đang gọi API:
- `GET /api/tours`
- `GET /api/bookings`
- `POST /api/bookings`

Mặc định API base URL:
- `http://localhost:4000/api`

Nếu bạn có thư mục backend riêng, có thể chạy như sau:

```bash
cd backend
npm install
npm run dev
```

## Lưu ý

- Nếu không bật backend Node.js, các chức năng dùng API (lấy tour, đặt tour đồng bộ qua API) có thể không hoạt động đầy đủ.
- Nên đồng bộ cấu hình URL API trong `frontend/js/script.js` với môi trường bạn đang chạy.
- Khi deploy thật, cần đổi thông tin database và URL API theo server sản xuất.
