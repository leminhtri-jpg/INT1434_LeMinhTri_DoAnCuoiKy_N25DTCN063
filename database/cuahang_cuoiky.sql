-- DEMO DỮ LIỆU CỤ THỂ CHO ĐỒ ÁN CUỐI KỲ
-- CẢNH BÁO: TỆP NÀY TẠO DATABASE MỚI VÀ GHI ĐÈ BẢNG KHI IMPORT LẠI.
-- Chỉ import vào cơ sở dữ liệu cuahang_cuoiky, không import vào minishop cũ.
CREATE DATABASE IF NOT EXISTS cuahang_cuoiky CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cuahang_cuoiky;
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS login_attempts;
DROP TABLE IF EXISTS contacts;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE categories (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE products (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 category_id INT UNSIGNED NOT NULL,
 name VARCHAR(200) NOT NULL,
 price INT UNSIGNED NOT NULL,
 description TEXT,
 image VARCHAR(255) NOT NULL,
 stock INT UNSIGNED NOT NULL DEFAULT 0,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_product_category FOREIGN KEY(category_id) REFERENCES categories(id),
 INDEX idx_category(category_id),INDEX idx_price(price)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE users (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 fullname VARCHAR(100) NOT NULL,
 email VARCHAR(150) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 phone VARCHAR(15),
 address VARCHAR(255),
 role ENUM('customer','admin') NOT NULL DEFAULT 'customer',
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE orders (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id INT UNSIGNED NOT NULL,
 fullname VARCHAR(100) NOT NULL,
 phone VARCHAR(15) NOT NULL,
 address VARCHAR(255) NOT NULL,
 note TEXT NULL,
 total INT UNSIGNED NOT NULL,
 status ENUM('pending','shipping','completed','cancelled') NOT NULL DEFAULT 'pending',
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_order_user FOREIGN KEY(user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE order_items (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 order_id INT UNSIGNED NOT NULL,
 product_id INT UNSIGNED NOT NULL,
 product_name VARCHAR(200) NOT NULL,
 price INT UNSIGNED NOT NULL,
 quantity INT UNSIGNED NOT NULL,
 CONSTRAINT fk_item_order FOREIGN KEY(order_id) REFERENCES orders(id),
 CONSTRAINT fk_item_product FOREIGN KEY(product_id) REFERENCES products(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE contacts (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 fullname VARCHAR(100) NOT NULL,
 email VARCHAR(150) NOT NULL,
 subject VARCHAR(200) NOT NULL,
 message TEXT NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE login_attempts (
 email_key CHAR(64) NOT NULL PRIMARY KEY,
 fail_count INT UNSIGNED NOT NULL DEFAULT 0,
 locked_until DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO categories(id,name) VALUES
(1,'Thiết bị bàn học & laptop'),
(2,'Chuột, bàn phím & bàn di'),
(3,'Âm thanh & giao tiếp');

INSERT INTO products(id,category_id,name,price,description,image,stock) VALUES
(1,1,'Đế tản nhiệt laptop RGB',185000,'Đế tản nhiệt ba quạt có dải LED, phù hợp bàn học và góc làm việc.','Sàn phầm 01.jpg',18),
(2,2,'Chuột không dây hiệu năng cao',329000,'Chuột không dây nhẹ, cầm thoải mái, phù hợp thao tác nhiều giờ.','Sàn phầm 02.jpg',22),
(3,2,'Bàn phím cơ RGB K2',579000,'Bàn phím cơ nhỏ gọn, cảm giác gõ rõ ràng, thích hợp góc học tập.','Sàn phầm 03.jpg',14),
(4,3,'Tai nghe chống ồn học trực tuyến',690000,'Tai nghe không dây chụp tai, hỗ trợ tập trung trong môi trường ồn.','Sàn phầm 04.webp',9),
(5,1,'USB lưu trữ 64GB',159000,'USB nhỏ gọn để mang tài liệu, bài tập và các tệp dự án.','Sàn phầm 05.jpg',27),
(6,3,'Webcam Full HD cho lớp trực tuyến',399000,'Webcam Full HD cắm máy tính, phù hợp học nhóm và họp trực tuyến.','Sàn phầm 06.jpg',8),
(7,1,'Giá đỡ laptop nhôm 6 nấc',245000,'Giá đỡ giúp nâng màn hình lên tầm mắt, có thể thay đổi độ cao.','san-pham-07.jpg',21),
(8,2,'Bàn di chuột cỡ lớn chống trượt',129000,'Tấm lót rộng, chống trượt, phù hợp chuột và bàn phím.','san-pham-08.jpg',35),
(9,1,'Đèn bàn LED học tập 3 chế độ',315000,'Đèn LED điều chỉnh sáng, dùng để đọc sách và làm bài tập.','san-pham-09.jpg',12),
(10,1,'Hub USB-C 6 trong 1',475000,'Bộ chuyển USB-C hỗ trợ cắm thêm nhiều thiết bị cho laptop.','san-pham-10.jpg',7),
(11,3,'Micro USB để bàn thu âm',525000,'Micro để bàn phục vụ thuyết trình, họp online và thu tiếng nói.','san-pham-11.jpg',6),
(12,1,'Giá đỡ điện thoại để bàn',99000,'Giá đỡ điện thoại gấp gọn, hỗ trợ xem tài liệu khi học.','san-pham-12.jpg',31);

INSERT INTO users(id,fullname,email,password_hash,phone,address,role) VALUES
(1,'Quản trị bản demo','admin@demo.local','$2y$12$Es6BaUbvd5/VdHGT.wckieAJyP2uSPkhPGMt04IbovrWj1lPmuQki','0900000001','Địa chỉ thử nghiệm số 1','admin'),
(2,'Khách hàng mẫu 1','khach1@demo.local','$2y$12$84yTrLiB757LGI/JlPoSo.zBtsq.nn7.G1AV3bAQojFhkNPy7VPHC','0900000002','Địa chỉ thử nghiệm số 2','customer'),
(3,'Khách hàng mẫu 2','khach2@demo.local','$2y$12$WGyk4uNek/i5LJOkRnD.tuHdhjQmvma73y22ZdDsZd0L392vlIVj6','0900000003','Địa chỉ thử nghiệm số 3','customer');

INSERT INTO orders(id,user_id,fullname,phone,address,note,total,status,created_at) VALUES
(1,2,'Khách hàng mẫu 1','0900000002','Địa chỉ thử nghiệm số 2','Giao trong giờ hành chính',647000,'completed','2026-10-10 10:30:00'),
(2,3,'Khách hàng mẫu 2','0900000003','Địa chỉ thử nghiệm số 3','Chú ý đóng gói',708000,'shipping','2026-10-16 14:00:00'),
(3,2,'Khách hàng mẫu 1','0900000002','Địa chỉ thử nghiệm số 2','',500000,'pending','2026-10-25 08:45:00'),
(4,3,'Khách hàng mẫu 2','0900000003','Địa chỉ thử nghiệm số 3','',475000,'cancelled','2026-10-28 09:15:00'),
(5,2,'Khách hàng mẫu 1','0900000002','Địa chỉ thử nghiệm số 2','',888000,'completed','2026-10-31 17:25:00');

INSERT INTO order_items(order_id,product_id,product_name,price,quantity) VALUES
(1,2,'Chuột không dây hiệu năng cao',329000,1),
(1,5,'USB lưu trữ 64GB',159000,2),
(2,3,'Bàn phím cơ RGB K2',579000,1),
(2,8,'Bàn di chuột cỡ lớn chống trượt',129000,1),
(3,1,'Đế tản nhiệt laptop RGB',185000,1),
(3,9,'Đèn bàn LED học tập 3 chế độ',315000,1),
(4,10,'Hub USB-C 6 trong 1',475000,1),
(5,4,'Tai nghe chống ồn học trực tuyến',690000,1),
(5,12,'Giá đỡ điện thoại để bàn',99000,2);
