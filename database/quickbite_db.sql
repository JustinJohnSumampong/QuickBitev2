CREATE DATABASE IF NOT EXISTS quickbite_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE quickbite_db;

DROP TABLE IF EXISTS order_items,orders,delivery_addresses,foods,categories,users;

CREATE TABLE users(
 id INT AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(120) NOT NULL,
 email VARCHAR(190) NOT NULL UNIQUE,
 phone VARCHAR(40),
 password VARCHAR(255) NOT NULL,
 role ENUM('admin','customer','rider') NOT NULL DEFAULT 'customer',
 status TINYINT(1) NOT NULL DEFAULT 1,
 remember_token VARCHAR(64) NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_remember_token (remember_token)
);

CREATE TABLE categories(
 id INT AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(100) NOT NULL,
 status TINYINT(1) DEFAULT 1,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE foods(
 id INT AUTO_INCREMENT PRIMARY KEY,
 category_id INT NOT NULL,
 name VARCHAR(150) NOT NULL,
 description TEXT,
 price DECIMAL(10,2) NOT NULL,
 prep_minutes INT DEFAULT 15,
 image VARCHAR(255) NULL,
 status TINYINT(1) DEFAULT 1,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(category_id) REFERENCES categories(id)
);

CREATE TABLE delivery_addresses(
 id INT AUTO_INCREMENT PRIMARY KEY,
 user_id INT NOT NULL,
 recipient_name VARCHAR(120) NOT NULL,
 phone VARCHAR(40) NOT NULL,
 address TEXT NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(user_id) REFERENCES users(id)
);

CREATE TABLE orders(
 id INT AUTO_INCREMENT PRIMARY KEY,
 order_number VARCHAR(50) NOT NULL UNIQUE,
 user_id INT NOT NULL,
 subtotal DECIMAL(10,2) NOT NULL,
 delivery_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
 discount DECIMAL(10,2) NOT NULL DEFAULT 0,
 total_amount DECIMAL(10,2) NOT NULL,
 payment_method VARCHAR(50) NOT NULL,
 payment_status VARCHAR(50) NOT NULL DEFAULT 'Pending',
 order_status VARCHAR(50) NOT NULL DEFAULT 'Pending',
 delivery_address_id INT NOT NULL,
 rider_id INT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY(user_id) REFERENCES users(id),
 FOREIGN KEY(delivery_address_id) REFERENCES delivery_addresses(id),
 FOREIGN KEY(rider_id) REFERENCES users(id)
);

CREATE TABLE order_items(
 id INT AUTO_INCREMENT PRIMARY KEY,
 order_id INT NOT NULL,
 food_id INT NOT NULL,
 food_name VARCHAR(150) NOT NULL,
 price DECIMAL(10,2) NOT NULL,
 quantity INT NOT NULL,
 subtotal DECIMAL(10,2) NOT NULL,
 FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE,
 FOREIGN KEY(food_id) REFERENCES foods(id)
);

INSERT INTO users(name,email,phone,password,role,status) VALUES
('QuickBite Admin','admin@quickbite.com','09170000000','$2y$10$9qSNiAMADKy0VjDUxumaTOKPwbwyNlrHdZU/dwL9MJMhl18MxxpIa','admin',1);
-- Default admin password: Admin@123

INSERT INTO categories(name) VALUES
('Burgers'),('Pizza'),('Chicken'),('Pasta'),('Rice Meals'),('Snacks'),('Desserts'),('Beverages'),('Combo Meals');

INSERT INTO foods(category_id,name,description,price,prep_minutes,status) VALUES
(1,'Classic Burger','Juicy beef patty, lettuce, tomato and QuickBite sauce.',129,15,1),
(1,'Cheese Burger','Classic beef burger with melted cheese.',149,15,1),
(1,'Chicken Burger','Crispy chicken fillet with fresh vegetables.',139,15,1),
(2,'Pepperoni Pizza','Classic pepperoni pizza with mozzarella cheese.',399,25,1),
(2,'Hawaiian Pizza','Ham, pineapple and mozzarella.',399,25,1),
(3,'Crispy Chicken Meal','Crispy chicken with rice and gravy.',159,20,1),
(3,'Chicken Wings','Six pieces of crispy flavored chicken wings.',189,20,1),
(4,'Spaghetti','Sweet Filipino-style spaghetti with cheese.',129,15,1),
(4,'Carbonara','Creamy pasta with bacon and parmesan.',149,15,1),
(5,'Beef Tapa Meal','Tender beef tapa served with garlic rice and egg.',179,20,1),
(6,'French Fries','Crispy golden fries.',79,10,1),
(6,'Cheese Fries','Fries topped with creamy cheese sauce.',99,10,1),
(7,'Chocolate Cake','Rich chocolate cake slice.',99,10,1),
(8,'Iced Tea','Refreshing house iced tea.',59,5,1),
(8,'Milkshake','Creamy vanilla milkshake.',99,10,1);
