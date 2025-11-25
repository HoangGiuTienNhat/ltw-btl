-- 1. LÀM SẠCH DATABASE
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS product_images; -- Bỏ bảng này vì giờ gộp vào products
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

-- 2. BẢNG USERS
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address VARCHAR(255),
    role ENUM('admin', 'member') DEFAULT 'member',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. BẢNG CATEGORIES
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT
);

-- 4. BẢNG PRODUCTS (Cấu trúc mới theo yêu cầu)
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(15, 2) NOT NULL,
    old_price DECIMAL(15, 2), -- Giá cũ để làm "Giảm giá"
    amount INT DEFAULT 100, -- Số lượng kho
    
    -- Ảnh
    image VARCHAR(255), -- Ảnh chính
    image1 VARCHAR(255),
    image2 VARCHAR(255),
    image3 VARCHAR(255),
    
    -- Thông số kỹ thuật
    chip VARCHAR(100),
    ram VARCHAR(50),
    screen VARCHAR(150),
    battery VARCHAR(100),
    guarantee VARCHAR(100) DEFAULT '12 Tháng',
    
    outstanding TEXT, -- Mô tả nổi bật
    rating FLOAT DEFAULT 5,
    is_featured TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- 5. BẢNG REVIEWS (Đánh giá & Comment)
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT,
    user_name VARCHAR(100), -- Lưu tên người comment (hoặc join user_id)
    rating INT DEFAULT 5,
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- --- DATA SEEDING (Dữ liệu mẫu) ---

-- User
INSERT INTO users (full_name, email, password, role) VALUES 
('Admin', 'admin@techstore.com', '123456', 'admin'),
('Nguyễn Văn A', 'user@gmail.com', '123456', 'member');

-- Danh mục
INSERT INTO categories (id, name) VALUES 
(1, 'Laptop'), (2, 'Điện thoại'), (3, 'Tai nghe'), 
(4, 'Đồng hồ'), (5, 'Tablet'), (6, 'Camera');

-- --- SẢN PHẨM: ĐIỆN THOẠI (Cat ID: 2) - 16 Sản phẩm ---
INSERT INTO products (category_id, name, price, old_price, image, image1, image2, image3, chip, ram, screen, battery, outstanding, rating, is_featured) VALUES
(2, 'iPhone 15 Pro Max 256GB', 29990000, 34990000, 
 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/i/p/iphone-15-pro-max_3.jpg',
 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/i/p/iphone-15-pro-max_2.jpg',
 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/i/p/iphone-15-pro-max_1.jpg',
 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/i/p/iphone-15-pro-max_4.jpg',
 'Apple A17 Pro', '8GB', '6.7 inch OLED 120Hz', '4422 mAh', 'Khung viền Titan bền bỉ, nút Action Button mới, cổng Type-C tốc độ cao.', 5.0, 1),

(2, 'Samsung Galaxy S24 Ultra', 28990000, 33990000, 
 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/s/s/ss-s24-ultra-xam-222.jpg',
 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/s/a/samsung-s24-ultra-vang_1.jpg',
 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/s/a/samsung-s24-ultra-tim.jpg',
 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/s/a/samsung-s24-ultra-den.jpg',
 'Snapdragon 8 Gen 3', '12GB', '6.8 inch QHD+ 120Hz', '5000 mAh', 'Tích hợp Galaxy AI, khung Titan, camera 200MP zoom 100x.', 4.9, 1),

(2, 'Xiaomi 14 Ultra 5G', 26990000, 29990000, 
 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/x/i/xiaomi-14-ultra_1__1.jpg',
 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/x/i/xiaomi-14-ultra_2__1.jpg',
 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/x/i/xiaomi-14-ultra_3__1.jpg',
 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/x/i/xiaomi-14-ultra_4__1.jpg',
 'Snapdragon 8 Gen 3', '16GB', '6.73 inch AMOLED 120Hz', '5000 mAh', 'Camera Leica cảm biến 1 inch, sạc nhanh 90W.', 4.8, 1),

(2, 'iPhone 13 128GB', 13990000, 16990000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/i/p/iphone-13_2_.jpg', '', '', '', 'Apple A15 Bionic', '4GB', '6.1 inch OLED', '3240 mAh', 'Thiết kế camera chéo, hiệu năng vẫn rất mạnh mẽ trong tầm giá.', 4.7, 1),
(2, 'Samsung Galaxy A55 5G', 9990000, 11990000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/s/a/samsung-galaxy-a55-5g-xanh-01.jpg', '', '', '', 'Exynos 1480', '8GB', '6.6 inch Super AMOLED', '5000 mAh', 'Thiết kế khung kim loại, chống nước IP67.', 4.5, 1),
(2, 'OPPO Reno11 F 5G', 8490000, 8990000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/o/p/oppo-reno-11-f-tim-1.jpg', '', '', '', 'Dimensity 7050', '8GB', '6.7 inch AMOLED', '5000 mAh', 'Camera chuyên gia chân dung, sạc nhanh 67W.', 4.6, 1),
(2, 'Xiaomi Redmi Note 13 Pro', 6490000, 7290000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/x/i/xiaomi-redmi-note-13-pro-4g-vang-thumb-1-1.jpg', '', '', '', 'Helio G99 Ultra', '8GB', '6.67 inch AMOLED', '5000 mAh', 'Camera 200MP, màn hình viền siêu mỏng.', 4.4, 0),
(2, 'iPhone 11 64GB', 8990000, 11990000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/i/p/iphone-11-64gb-chinh-hang_5_.jpg', '', '', '', 'Apple A13 Bionic', '4GB', '6.1 inch IPS', '3110 mAh', 'Huyền thoại iPhone giá rẻ, pin trâu.', 4.3, 0),
(2, 'Samsung Galaxy Z Flip5', 16990000, 25990000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/s/a/samsung-galaxy-z-flip-5-256gb-thumb.jpg', '', '', '', 'Snapdragon 8 Gen 2', '8GB', '6.7 inch Foldable', '3700 mAh', 'Màn hình phụ Flex Window lớn, gập không kẽ hở.', 4.8, 1),
(2, 'ASUS ROG Phone 8', 29990000, 31990000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/a/s/asus-rog-phone-8_1.jpg', '', '', '', 'Snapdragon 8 Gen 3', '12GB', '6.78 inch 165Hz', '5500 mAh', 'Gaming Phone tối thượng, thiết kế mới thanh lịch hơn.', 5.0, 1),
(2, 'Vivo V30e 5G', 9490000, 10490000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/v/i/vivo-v30e.jpg', '', '', '', 'Snapdragon 6 Gen 1', '8GB', '6.78 inch AMOLED', '5500 mAh', 'Vòng sáng Aura, thiết kế mỏng nhẹ.', 4.5, 0),
(2, 'Realme C65', 3690000, 3990000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/r/e/realme-c65-tim-1.jpg', '', '', '', 'Helio G85', '6GB', '6.67 inch IPS', '5000 mAh', 'Sạc nhanh 45W trong phân khúc giá rẻ.', 4.2, 0),
(2, 'iPhone 15 Plus 128GB', 22990000, 25990000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/i/p/iphone-15-plus_1__1.jpg', '', '', '', 'Apple A16 Bionic', '6GB', '6.7 inch OLED', '4383 mAh', 'Dynamic Island, pin trâu nhất dòng iPhone 15.', 4.7, 1),
(2, 'Samsung Galaxy S23 FE', 11990000, 14890000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/s/a/samsung-galaxy-s23-fe-xanh_2.jpg', '', '', '', 'Exynos 2200', '8GB', '6.4 inch AMOLED', '4500 mAh', 'Phiên bản Fan Edition, hiệu năng flagship giá tốt.', 4.6, 0),
(2, 'Xiaomi 13T 5G', 10990000, 12990000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/x/i/xiaomi-13t_1_.jpg', '', '', '', 'Dimensity 8200 Ultra', '12GB', '6.67 inch 144Hz', '5000 mAh', 'Camera Leica, màn hình 144Hz siêu mượt.', 4.5, 0),
(2, 'Nubia Neo 2 5G', 4690000, 4990000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/n/u/nubia-neo-2_3_.jpg', '', '', '', 'Unisoc T820', '8GB', '6.72 inch 120Hz', '6000 mAh', 'Gaming phone giá rẻ, thiết kế hầm hố, pin khủng.', 4.3, 0);

-- --- SẢN PHẨM: TABLET (Cat ID: 5) - 16 Sản phẩm ---
INSERT INTO products (category_id, name, price, old_price, image, image1, image2, image3, chip, ram, screen, battery, outstanding, rating, is_featured) VALUES
(5, 'iPad Pro M4 11 inch', 28990000, 30990000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/i/p/ipad-pro-13-2024-wifi-den-1_1.jpg', '', '', '', 'Apple M4', '8GB', '11 inch OLED', 'N/A', 'Siêu mỏng nhẹ, màn hình OLED Tandem đỉnh cao.', 5.0, 1),
(5, 'Samsung Galaxy Tab S9 Ultra', 24990000, 32990000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/s/a/samsung-galaxy-tab-s9-ultra-wifi-xam-1.jpg', '', '', '', 'Snapdragon 8 Gen 2', '12GB', '14.6 inch AMOLED', '11200 mAh', 'Màn hình khổng lồ, chống nước IP68.', 4.9, 1),
(5, 'iPad Air 6 M2 11 inch', 16990000, 18990000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/i/p/ipad-air-6-11-inch-m2-wifi-thumb.jpg', '', '', '', 'Apple M2', '8GB', '11 inch IPS', 'N/A', 'Hiệu năng mạnh mẽ với chip M2, nhiều màu sắc.', 4.8, 1),
(5, 'Xiaomi Pad 6', 8490000, 9990000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/x/i/xiaomi-pad-6_1_.jpg', '', '', '', 'Snapdragon 870', '8GB', '11 inch 144Hz', '8840 mAh', 'Màn hình 144Hz, hiệu năng ổn định.', 4.7, 0),
(5, 'iPad Gen 10 64GB WiFi', 8990000, 10990000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/i/p/ipad-10-9-inch-2022_2.jpg', '', '', '', 'Apple A14 Bionic', '4GB', '10.9 inch IPS', 'N/A', 'Thiết kế mới viền mỏng, cổng USB-C.', 4.6, 1),
(5, 'Samsung Galaxy Tab S9 FE', 7990000, 9990000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/s/a/samsung-galaxy-tab-s9-fe-wifi-xam-1.jpg', '', '', '', 'Exynos 1380', '6GB', '10.9 inch 90Hz', '8000 mAh', 'Kèm bút S-Pen, chống nước.', 4.5, 0),
(5, 'Lenovo Tab M11', 5490000, 6490000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/l/e/lenovo-tab-m11.jpg', '', '', '', 'Helio G88', '4GB', '11 inch IPS', '7040 mAh', 'Giá rẻ, màn hình lớn cho học tập.', 4.3, 0),
(5, 'iPad Mini 6 WiFi 64GB', 11990000, 13990000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/i/p/ipad-mini-6-purple-1.jpg', '', '', '', 'Apple A15 Bionic', '4GB', '8.3 inch IPS', 'N/A', 'Nhỏ gọn, mạnh mẽ, dễ mang theo.', 4.8, 1),
(5, 'Samsung Galaxy Tab A9+', 4990000, 5990000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/s/a/samsung-galaxy-tab-a9-plus-wifi-bac-1.jpg', '', '', '', 'Snapdragon 695', '4GB', '11 inch 90Hz', '7040 mAh', 'Tablet giải trí giá bình dân tốt nhất.', 4.2, 0),
(5, 'OPPO Pad Neo', 6490000, 6990000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/o/p/oppo-pad-neo.jpg', '', '', '', 'Helio G99', '6GB', '11.4 inch 2.4K', '8000 mAh', 'Màn hình đẹp tỉ lệ 7:5 độc đáo.', 4.4, 0),
(5, 'Huawei MatePad 11.5', 6990000, 7490000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/m/a/matepad-11.5.jpg', '', '', '', 'Snapdragon 7 Gen 1', '6GB', '11.5 inch 120Hz', '7700 mAh', 'Hỗ trợ Google qua Gbox, màn hình 120Hz.', 4.5, 0),
(5, 'iPad Pro 12.9 M2', 34990000, 37990000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/i/p/ipad-pro-12-9-2022-wifi-xam-1.jpg', '', '', '', 'Apple M2', '8GB', '12.9 inch Mini-LED', 'N/A', 'Màn hình Mini-LED xuất sắc cho đồ họa.', 5.0, 1),
(5, 'Xiaomi Redmi Pad SE', 4490000, 4990000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/x/i/xiaomi-redmi-pad-se_1_.jpg', '', '', '', 'Snapdragon 680', '4GB', '11 inch 90Hz', '8000 mAh', 'Thiết kế nhôm nguyên khối, 4 loa Dolby Atmos.', 4.1, 0),
(5, 'Samsung Galaxy Tab S6 Lite 2024', 8490000, 9990000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/t/a/tab_s6_lite_2024.jpg', '', '', '', 'Exynos 1280', '4GB', '10.4 inch TFT', '7040 mAh', 'Bản nâng cấp 2024, kèm bút S-Pen.', 4.3, 0),
(5, 'Masstel Tab 10S', 2990000, 3490000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/m/a/masstel-tab-10s_1_.jpg', '', '', '', 'Unisoc T310', '3GB', '10.1 inch IPS', '6000 mAh', 'Tablet giá rẻ thương hiệu Việt.', 3.8, 0),
(5, 'Coolpad Tab Tasker 10', 1990000, 2490000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/c/o/coolpad-tasker-10-wifi.jpg', '', '', '', 'Entry Level', '2GB', '10 inch', '5000 mAh', 'Tablet cơ bản nhất.', 3.5, 0);

-- --- SẢN PHẨM: LAPTOP (Cat ID: 1) - 16 Sản phẩm ---
INSERT INTO products (category_id, name, price, old_price, image, image1, image2, image3, chip, ram, screen, battery, outstanding, rating, is_featured) VALUES
(1, 'MacBook Pro 14 M3', 49990000, 52990000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/m/a/macbook_pro_14_m3_gray.jpg', '', '', '', 'Apple M3 Pro', '18GB', '14 inch Liquid Retina XDR', '72.4 Wh', 'Mạnh mẽ, màn hình đẹp nhất thế giới laptop.', 5.0, 1),
(1, 'MacBook Air M2 13 inch', 24990000, 28990000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/m/a/macbook-air-m2-midnight.jpg', '', '', '', 'Apple M2', '8GB', '13.6 inch IPS', '52.6 Wh', 'Mỏng nhẹ, thiết kế tai thỏ thời thượng.', 4.8, 1),
(1, 'ASUS ROG Strix G16', 32990000, 35990000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/l/a/laptop-asus-rog-strix-g16-g614ju-n3135w-thumbnails.jpg', '', '', '', 'i7-13650HX', '16GB', '16 inch FHD+ 165Hz', '90 Wh', 'Cấu hình khủng, LED RGB đẹp mắt.', 4.9, 1),
(1, 'Dell XPS 13 Plus', 45990000, 51990000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/l/a/laptop-dell-xps-13-plus-9320-1.jpg', '', '', '', 'i7-1360P', '16GB', '13.4 inch OLED 3.5K', '55 Wh', 'Thiết kế tương lai, bàn phím vô cực.', 4.7, 1),
(1, 'MSI Gaming Cyborg 15', 18490000, 21990000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/l/a/laptop-msi-gaming-cyborg-15-a12ve-240vn.jpg', '', '', '', 'i7-12650H', '8GB', '15.6 inch 144Hz', '53.5 Wh', 'Thiết kế trong suốt, giá tốt.', 4.5, 0),
(1, 'Acer Nitro 5 Tiger', 19990000, 23990000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/l/a/laptop-gaming-acer-nitro-5-tiger-an515-58-52sp.jpg', '', '', '', 'i5-12500H', '8GB', '15.6 inch 144Hz', '57 Wh', 'Quốc dân gaming, tản nhiệt tốt.', 4.6, 1),
(1, 'HP Pavilion 15', 16990000, 18990000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/l/a/laptop-hp-pavilion-15-eg3093tu-8c5l4pa.jpg', '', '', '', 'i5-1335U', '16GB', '15.6 inch IPS', '41 Wh', 'Văn phòng sang trọng, vỏ kim loại.', 4.4, 0),
(1, 'Lenovo ThinkPad X1 Carbon', 42990000, 48990000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/l/e/lenovo-thinkpad-x1-carbon-gen-10-8.jpg', '', '', '', 'i7-1260P', '16GB', '14 inch WUXGA', '57 Wh', 'Siêu bền, bàn phím gõ sướng nhất.', 4.9, 1),
(1, 'Asus Zenbook 14 OLED', 24990000, 27990000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/a/s/asus-zenbook-14-oled-ux3405ma-pp151w.jpg', '', '', '', 'Ultra 7 155H', '16GB', '14 inch OLED 3K', '75 Wh', 'Chip AI mới nhất, màn hình OLED rực rỡ.', 4.7, 1),
(1, 'LG Gram 2023 14 inch', 28990000, 32990000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/l/g/lg-gram-2023-14-inch-i7.jpg', '', '', '', 'i7-1340P', '16GB', '14 inch IPS', '72 Wh', 'Siêu nhẹ chỉ 999g, pin trâu.', 4.6, 0),
(1, 'Surface Pro 9', 26990000, 29990000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/s/u/surface-pro-9-i5.jpg', '', '', '', 'i5-1235U', '8GB', '13 inch PixelSense', '47.7 Wh', 'Laptop lai máy tính bảng đẳng cấp.', 4.8, 1),
(1, 'Dell Inspiron 15 3520', 12990000, 14990000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/l/a/laptop-dell-inspiron-15-3520-i5u085w11blu-thumbnails.jpg', '', '', '', 'i5-1235U', '8GB', '15.6 inch 120Hz', '41 Wh', 'Giá tốt cho sinh viên.', 4.3, 0),
(1, 'Asus Vivobook 15', 11490000, 13490000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/a/s/asus-vivobook-15-x1504za-nj517w.jpg', '', '', '', 'i3-1215U', '8GB', '15.6 inch FHD', '42 Wh', 'Bền bỉ, bản lề mở 180 độ.', 4.4, 0),
(1, 'MacBook Air M1', 18490000, 22990000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/m/a/macbook-air-m1-2020-gray_1.jpg', '', '', '', 'Apple M1', '8GB', '13.3 inch Retina', '49.9 Wh', 'Vẫn rất ngon trong tầm giá.', 4.9, 0),
(1, 'Lenovo Legion 5', 29990000, 33990000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/l/a/laptop-lenovo-legion-5-15arh7-82re002wvn-1.jpg', '', '', '', 'Ryzen 7 6800H', '16GB', '15.6 inch 165Hz', '80 Wh', 'Thiết kế tối giản, hiệu năng cao.', 4.8, 0),
(1, 'Gigabyte G5 MF', 20990000, 24990000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/g/i/gigabyte-g5-mf-f2vn333sh.jpg', '', '', '', 'i5-12450H', '8GB', '15.6 inch 144Hz', '54 Wh', 'Card rời RTX 4050 giá rẻ.', 4.5, 0);

-- Các danh mục khác tôi để trống để tiết kiệm dung lượng, bạn có thể copy paste sửa ID và Tên tương tự.

-- --- 1. BỔ SUNG TAI NGHE (Category ID: 3) ---
INSERT INTO products (category_id, name, price, old_price, image, image1, image2, image3, chip, ram, screen, battery, outstanding, rating, is_featured) VALUES
(3, 'Sony INZONE Buds', 4990000, 5490000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/t/a/tai-nghe-sony-inzone-buds-2.jpg', '', '', '', 'Sony V1', 'N/A', 'N/A', '12 giờ', 'Tai nghe Gaming True Wireless, độ trễ cực thấp.', 4.8, 1),
(3, 'ASUS ROG Cetra True Wireless', 1990000, 2290000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/t/a/tai-nghe-khong-day-asus-rog-cetra-true-wireless-trang_3.jpg', '', '', '', 'N/A', 'N/A', 'LED RGB', '27 giờ', 'Chế độ Gaming Mode, chống ồn ANC, đèn LED RGB.', 4.7, 1),
(3, 'Marshall Motif II A.N.C', 4990000, 5990000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/t/a/tai-nghe-bluetooth-marshall-motif-ii-a-n-c_2_.jpg', '', '', '', 'N/A', 'N/A', 'N/A', '30 giờ', 'Thiết kế đậm chất Rock, chống ồn chủ động cải tiến.', 4.6, 0),
(3, 'JBL Quantum TWS', 2490000, 3990000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/t/a/tai-nghe-khong-day-jbl-quantum-tws_1.jpg', '', '', '', 'N/A', 'N/A', 'N/A', '24 giờ', 'Kết nối Dongle Type-C 2.4GHz không độ trễ.', 4.5, 0),
(3, 'Sony WH-CH720N', 2990000, 3490000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/t/a/tai-nghe-chup-tai-sony-wh-ch720n_1.jpg', '', '', '', 'V1 Processor', 'N/A', 'N/A', '35 giờ', 'Tai nghe chụp tai chống ồn nhẹ nhất của Sony.', 4.7, 0),
(3, 'Bose QuietComfort 45', 6490000, 8490000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/t/a/tai-nghe-bose-quietcomfort-45_2_.jpg', '', '', '', 'N/A', 'N/A', 'N/A', '24 giờ', 'Huyền thoại chống ồn, cảm giác đeo thoải mái.', 4.8, 1),
(3, 'Sennheiser Accentum Plus', 4990000, 5990000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/t/a/tai-nghe-chup-tai-sennheiser-accentum-plus-wireless_1_.jpg', '', '', '', 'N/A', 'N/A', 'N/A', '50 giờ', 'Pin cực khủng 50 giờ, âm thanh chi tiết.', 4.6, 0),
(3, 'SoundPEATS Air4 Pro', 1690000, 1990000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/t/a/tai-nghe-khong-day-soundpeats-air4-pro_1_.jpg', '', '', '', 'Qualcomm QCC3071', 'N/A', 'N/A', '26 giờ', 'Hỗ trợ AptX Lossless, giá rẻ cấu hình cao.', 4.5, 0),
(3, 'Huawei FreeClip', 3990000, 4990000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/h/u/huawei-freeclip_2_.jpg', '', '', '', 'N/A', 'N/A', 'N/A', '36 giờ', 'Thiết kế C-bridge độc đáo, không đau tai.', 4.4, 1),
(3, 'Shokz OpenRun Pro', 4290000, 4990000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/t/a/tai-nghe-dan-truyen-xuong-shokz-openrun-pro-s810_3.jpg', '', '', '', 'N/A', 'N/A', 'N/A', '10 giờ', 'Tai nghe dẫn truyền xương tốt nhất cho chạy bộ.', 4.9, 0),
(3, 'Jabra Elite 10', 5990000, 6990000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/t/a/tai-nghe-khong-day-jabra-elite-10.jpg', '', '', '', 'N/A', 'N/A', 'N/A', '27 giờ', 'Công nghệ Dolby Atmos Head Tracking.', 4.7, 0),
(3, 'Monster XKT10', 450000, 990000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/t/a/tai-nghe-khong-day-monster-xkt10_1_.jpg', '', '', '', 'N/A', 'N/A', 'N/A', '20 giờ', 'Tai nghe Gaming giá rẻ thiết kế Spinner.', 4.2, 0);

-- --- 2. BỔ SUNG ĐỒNG HỒ (Category ID: 4) ---
INSERT INTO products (category_id, name, price, old_price, image, image1, image2, image3, chip, ram, screen, battery, outstanding, rating, is_featured) VALUES
(4, 'Garmin Fenix 7 Pro Solar', 23990000, 25990000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/d/o/dong-ho-thong-minh-garmin-fenix-7-pro-solar_4_.jpg', '', '', '', 'N/A', 'N/A', '1.3 inch MIP', '22 ngày', 'Sạc năng lượng mặt trời, đèn pin tích hợp.', 5.0, 1),
(4, 'Samsung Galaxy Watch6', 5990000, 6990000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/s/a/samsung-galaxy-watch-6-44mm-xanh-thumb.jpg', '', '', '', 'Exynos W930', '2GB', '1.5 inch Super AMOLED', '40 giờ', 'Viền màn hình mỏng hơn, theo dõi giấc ngủ chuyên sâu.', 4.8, 1),
(4, 'Amazfit Balance', 5990000, 6590000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/d/o/dong-ho-thong-minh-amazfit-balance_3_.jpg', '', '', '', 'N/A', 'N/A', '1.5 inch AMOLED', '14 ngày', 'Cân bằng cuộc sống, đo thành phần cơ thể.', 4.7, 0),
(4, 'Xiaomi Redmi Watch 4', 2390000, 2690000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/x/i/xiaomi-redmi-watch-4_2_.jpg', '', '', '', 'N/A', 'N/A', '1.97 inch AMOLED', '20 ngày', 'Màn hình lớn nhất dòng Redmi Watch, khung nhôm.', 4.6, 1),
(4, 'Garmin Epix Gen 2', 20990000, 22490000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/g/a/garmin-epix-gen-2-titanium.jpg', '', '', '', 'N/A', '32GB', '1.3 inch AMOLED', '16 ngày', 'Màn hình AMOLED rực rỡ trên dòng thể thao cao cấp.', 4.9, 0),
(4, 'Huawei Watch GT Cyber', 4490000, 5490000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/h/u/huawei-watch-gt-cyber-den_2.jpg', '', '', '', 'N/A', 'N/A', '1.32 inch AMOLED', '7 ngày', 'Thiết kế tháo rời khung vỏ độc đáo.', 4.5, 0),
(4, 'Suunto Race', 11990000, 13990000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/d/o/dong-ho-thong-minh-suunto-race-ban-thep_1.jpg', '', '', '', 'N/A', 'N/A', '1.43 inch AMOLED', '26 ngày', 'Núm xoay Digital Crown, bản đồ offline miễn phí.', 4.7, 0),
(4, 'Garmin Lily 2', 6490000, 6990000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/d/o/dong-ho-thong-minh-garmin-lily-2-day-da_1.jpg', '', '', '', 'N/A', 'N/A', 'LCD ẩn', '5 ngày', 'Thiết kế trang sức nhỏ gọn dành cho nữ.', 4.4, 0),
(4, 'Kieslect Ks Pro', 1690000, 2190000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/k/i/kieslect-ks-pro.jpg', '', '', '', 'N/A', 'N/A', '2.01 inch AMOLED', '6 ngày', 'Màn hình siêu lớn, gọi điện Bluetooth ổn định.', 4.3, 0),
(4, 'Apple Watch Series 8 Thép', 16990000, 19990000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/a/p/apple-watch-series-8-41mm-4g-vien-thep-day-thep-bac_1_1.jpg', '', '', '', 'Apple S8', 'N/A', '1.9 inch OLED', '18 giờ', 'Vỏ thép sang trọng, mặt kính Sapphire.', 4.9, 0),
(4, 'SoundPEATS Watch 4', 890000, 1290000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/d/o/dong-ho-thong-minh-soundpeats-watch-4_2.jpg', '', '', '', 'N/A', 'N/A', '1.85 inch HD', '7 ngày', 'Giá rẻ, đầy đủ tính năng cơ bản.', 4.1, 0),
(4, 'Masstel Smart Hero 4G', 1990000, 2490000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/d/o/dong-ho-dinh-vi-tre-em-masstel-smart-hero-4g_2_.jpg', '', '', '', 'N/A', 'N/A', '1.4 inch', '2 ngày', 'Đồng hồ định vị trẻ em, hỗ trợ sim 4G.', 4.0, 0);

-- --- 3. BỔ SUNG CAMERA (Category ID: 6) ---
-- Lưu ý: ID 6 là Camera. Nếu hệ thống của bạn ID 5 là Camera thì sửa số 6 thành 5.
INSERT INTO products (category_id, name, price, old_price, image, image1, image2, image3, chip, ram, screen, battery, outstanding, rating, is_featured) VALUES
(6, 'Sony ZV-1 II', 18990000, 20990000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/m/a/may-anh-sony-zv-1-ii_3_.jpg', '', '', '', 'BIONZ X', 'N/A', '3 inch Touch', '45 phút quay', 'Ống kính góc rộng 18-50mm, chuyên Vlog.', 4.7, 1),
(6, 'Canon EOS R10 Kit 18-45mm', 23990000, 26990000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/m/a/may-anh-canon-eos-r10-kit-18-45mm_1.jpg', '', '', '', 'DIGIC X', 'N/A', '3 inch Vari-angle', 'N/A', 'Máy ảnh Mirrorless Crop mạnh mẽ, lấy nét nhanh.', 4.8, 1),
(6, 'Nikon Z30 Kit 16-50mm', 18490000, 20490000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/m/a/may-anh-nikon-z30-kit-16-50mm-vr_2_.jpg', '', '', '', 'EXPEED 6', 'N/A', '3 inch Flip', 'N/A', 'Thiết kế cho Content Creator, quay 4K không crop.', 4.6, 0),
(6, 'Fujifilm X-S20 Body', 31990000, 33990000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/m/a/may-anh-fujifilm-x-s20-body_3_.jpg', '', '', '', 'X-Processor 5', 'N/A', '3 inch Vari-angle', '750 shots', 'Pin trâu, chế độ Vlog mới, quay 6.2K.', 4.9, 1),
(6, 'DJI Osmo Pocket 3', 12990000, 14990000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/m/a/may-quay-cam-tay-dji-osmo-pocket-3_3_.jpg', '', '', '', 'N/A', 'N/A', '2 inch Rotatable', '166 phút', 'Cảm biến 1 inch, màn hình xoay dọc ngang.', 5.0, 1),
(6, 'Insta360 GO 3 64GB', 9990000, 10990000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/c/a/camera-hanh-trinh-insta360-go-3-64gb_2_.jpg', '', '', '', 'N/A', 'N/A', '2.2 inch Flip', '170 phút', 'Camera hành trình nhỏ nhất thế giới.', 4.7, 0),
(6, 'Sony A6400 Kit 16-50mm', 22990000, 24990000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/s/o/sony-alpha-6400-bac-1.jpg', '', '', '', 'BIONZ X', 'N/A', '3 inch Tilt', '360 shots', 'Lấy nét mắt thời gian thực, best seller tầm trung.', 4.8, 0),
(6, 'Panasonic Lumix GH6 Body', 41990000, 46990000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/m/a/may-anh-panasonic-lumix-dc-gh6-body-den_2_.jpg', '', '', '', 'Venus Engine', 'N/A', '3 inch Vari-angle', 'N/A', 'Quái vật quay phim chuẩn điện ảnh.', 4.7, 0),
(6, 'Canon EOS R8 Body', 36990000, 3990000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/m/a/may-anh-canon-eos-r8-body_2_.jpg', '', '', '', 'DIGIC X', 'N/A', '3 inch', 'N/A', 'Full-frame nhẹ nhất của Canon.', 4.7, 0),
(6, 'DJI Mini 4 Pro', 21490000, 23490000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/f/l/flycam-dji-mini-4-pro-rc-n2_2_.jpg', '', '', '', 'N/A', 'N/A', 'N/A', '34 phút', 'Flycam mini an toàn nhất với cảm biến đa hướng.', 4.9, 1),
(6, 'GoPro Hero 11 Black Mini', 6990000, 8490000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/g/o/gopro-hero-11-black-mini.jpg', '', '', '', 'GP2', 'N/A', 'Không màn hình', 'Enduro', 'Nhỏ gọn, gắn mũ bảo hiểm cực tiện.', 4.5, 0),
(6, 'Insta360 X3', 10490000, 11490000, 'https://cdn2.cellphones.com.vn/insecure/rs:fill:358:358/q:90/plain/https://cellphones.com.vn/media/catalog/product/c/a/camera-hanh-trinh-insta360-x3_2_.jpg', '', '', '', 'N/A', 'N/A', '2.29 inch', '81 phút', 'Quay 360 độ sáng tạo, chống rung FlowState.', 4.8, 0);

-- Thêm vài comment mẫu cho iPhone 15 Pro Max (ID 1)
INSERT INTO reviews (product_id, user_name, rating, comment) VALUES
(1, 'Nguyễn Văn A', 5, 'Máy quá đẹp, pin trâu, chụp hình nét căng!'),
(1, 'Trần Thị B', 4, 'Hơi nóng khi chơi game nặng, còn lại ok.'),
(1, 'Lê Văn C', 5, 'Giao hàng nhanh, đóng gói kỹ.');