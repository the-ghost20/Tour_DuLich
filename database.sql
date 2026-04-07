-- ==========================================================
-- CSDL Quan Ly Tour Du Lich (MySQL 8+)
-- Gom 2 nhom quyen: admin, user
-- ==========================================================

SET NAMES utf8mb4;
SET time_zone = '+07:00';

DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS tours;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tours (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tour_name VARCHAR(180) NOT NULL,
    description TEXT NOT NULL,
    destination VARCHAR(120) NOT NULL,
    duration VARCHAR(30) NOT NULL COMMENT 'Vi du: 3N2D, 4N3D',
    price DECIMAL(15,2) NOT NULL,
    image_url VARCHAR(255) NULL,
    available_slots INT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('ẩn', 'hiện') NOT NULL DEFAULT 'hiện' COMMENT 'ẩn = ẩn tour, hiện = hiển thị',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_tours_price_non_negative CHECK (price >= 0),
    CONSTRAINT chk_tours_slots_non_negative CHECK (available_slots >= 0)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE bookings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    tour_id BIGINT UNSIGNED NOT NULL,
    adults INT UNSIGNED NOT NULL DEFAULT 1,
    children INT UNSIGNED NOT NULL DEFAULT 0,
    total_amount DECIMAL(15,2) NOT NULL,
    status ENUM('chờ duyệt', 'đã xác nhận', 'đã hủy') NOT NULL DEFAULT 'chờ duyệt',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_bookings_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_bookings_tour
        FOREIGN KEY (tour_id) REFERENCES tours(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT chk_bookings_adults_min CHECK (adults >= 1),
    CONSTRAINT chk_bookings_children_non_negative CHECK (children >= 0),
    CONSTRAINT chk_bookings_total_non_negative CHECK (total_amount >= 0)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_bookings_user_id ON bookings(user_id);
CREATE INDEX idx_bookings_tour_id ON bookings(tour_id);
CREATE INDEX idx_tours_destination ON tours(destination);
CREATE INDEX idx_tours_status ON tours(status);

-- ==========================================================
-- Du lieu mau (mock data) tieng Viet
-- ==========================================================

INSERT INTO users (full_name, email, password, phone, role) VALUES
('Nguyen Van Admin', 'admin@dulichviet.vn', '$2y$10$QW5QYQ7z6Kjv2c0u0E5MeOHfM4rN0q6k8dr8n5m9QzA4A0QfC2wQe', '0909000001', 'admin'),
('Tran Thi Mai', 'mai.tran@gmail.com', '$2y$10$wqfFBM6Xv1Q3P4C4G6Jm0.vv5eIuQx2zLw4UQ3R4N5Q3qz7xGvYxS', '0911222333', 'user'),
('Le Hoang Nam', 'nam.le@gmail.com', '$2y$10$S9zXr3pL7rK8mWcL8qB9x.h3Vj1nA2dD5kJ1fY1fK5n9u2b8pC0gO', '0988777666', 'user'),
('Pham Thu Ha', 'ha.pham@gmail.com', '$2y$10$eR8qRjP5kN6wK4uH1oJk8.N7nS4mQ0vQ9xZ7xD3L0pR2bM8wK6tY2', '0933555777', 'user');

INSERT INTO tours (tour_name, description, destination, duration, price, image_url, available_slots, status) VALUES
('Đà Nẵng - Hội An - Bà Nà', 'Khám phá thành phố biển Đà Nẵng, phố cổ Hội An và vui chơi tại Bà Nà Hills.', 'Đà Nẵng', '3N2Đ', 3590000.00, 'images/tours/danang-hoian-bana.jpg', 25, 'hiện'),
('Hà Nội - Hạ Long', 'Tham quan thủ đô Hà Nội và du thuyền ngắm vịnh Hạ Long kỳ quan.', 'Quảng Ninh', '3N2Đ', 4290000.00, 'images/tours/hanoi-halong.jpg', 18, 'hiện'),
('TP.HCM - Phú Quốc', 'Nghỉ dưỡng biển đảo Phú Quốc, tham quan cáp treo Hòn Thơm và chợ đêm.', 'Kiên Giang', '4N3Đ', 5990000.00, 'images/tours/phuquoc.jpg', 12, 'hiện'),
('Đà Lạt Mộng Mơ', 'Trải nghiệm thành phố ngàn hoa với nhiều điểm check-in nổi tiếng.', 'Lâm Đồng', '3N2Đ', 2890000.00, 'images/tours/dalat.jpg', 0, 'ẩn');

INSERT INTO bookings (user_id, tour_id, adults, children, total_amount, status) VALUES
(2, 1, 2, 1, 8970000.00, 'chờ duyệt'),
(3, 2, 2, 0, 8580000.00, 'đã xác nhận'),
(4, 3, 1, 1, 8990000.00, 'chờ duyệt'),
(2, 4, 2, 0, 5780000.00, 'đã hủy');
