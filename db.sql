-- Database creation
CREATE DATABASE IF NOT EXISTS `distributor_db`;
USE `distributor_db`;
 
-- Table: users
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` VARCHAR(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
 
-- Table: categories
CREATE TABLE IF NOT EXISTS `categories` (
  `category_id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_name` VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
 
-- Table: products
CREATE TABLE IF NOT EXISTS `products` (
  `product_id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT NOT NULL,
  `product_name` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `stock_quantity` INT NOT NULL,
  FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
 
-- Table: stock_requests
CREATE TABLE IF NOT EXISTS `stock_requests` (
  `request_id` INT AUTO_INCREMENT PRIMARY KEY,
  `shopkeeper_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `quantity` INT NOT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'Pending',
  `request_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`shopkeeper_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
 
-- Table: notifications
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `message` TEXT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
 
-- Insert default users for testing (default password for all is: password)
-- Hash generated for 'password' using PASSWORD_DEFAULT / bcrypt
INSERT INTO `users` (`name`, `email`, `password`, `role`) VALUES
('Admin', 'admin@example.com', '$2y$10$Y5n2b.L72AHL.k.Z.yD/DeWep9eR/T85mC1b1p6U/qS7ZJ/e1t76a', 'admin'),
('Sample Distributor', 'distributor@example.com', '$2y$10$Y5n2b.L72AHL.k.Z.yD/DeWep9eR/T85mC1b1p6U/qS7ZJ/e1t76a', 'distributor'),
('Sample Shopkeeper', 'shopkeeper@example.com', '$2y$10$Y5n2b.L72AHL.k.Z.yD/DeWep9eR/T85mC1b1p6U/qS7ZJ/e1t76a', 'shopkeeper');
 
 ALTER TABLE users
ADD phone VARCHAR(20) NOT NULL AFTER email,
ADD shop_name VARCHAR(100) NOT NULL AFTER phone;

CREATE TABLE payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    shopkeeper_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    payment_status VARCHAR(20) NOT NULL DEFAULT 'Pending',
    transaction_id VARCHAR(100) DEFAULT NULL,
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY unique_request_payment (request_id),

    FOREIGN KEY (request_id)
        REFERENCES stock_requests(request_id),

    FOREIGN KEY (shopkeeper_id)
        REFERENCES users(id)
);

CREATE TABLE orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    payment_id INT NOT NULL,
    product_id INT NOT NULL,
    shopkeeper_id INT NOT NULL,
    quantity INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    order_status VARCHAR(30) NOT NULL DEFAULT 'Completed',
    completed_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY unique_request_order (request_id),
    UNIQUE KEY unique_payment_order (payment_id),

    FOREIGN KEY (request_id)
        REFERENCES stock_requests(request_id),

    FOREIGN KEY (payment_id)
        REFERENCES payments(payment_id),

    FOREIGN KEY (product_id)
        REFERENCES products(product_id),

    FOREIGN KEY (shopkeeper_id)
        REFERENCES users(id)
);

DELETE FROM users
WHERE role = 'distributor';


CREATE TABLE payment_items (
    payment_item_id INT AUTO_INCREMENT PRIMARY KEY,
    payment_id INT NOT NULL,
    request_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,

    UNIQUE KEY unique_paid_request (request_id),

    FOREIGN KEY (payment_id)
        REFERENCES payments(payment_id),

    FOREIGN KEY (request_id)
        REFERENCES stock_requests(request_id),

    FOREIGN KEY (product_id)
        REFERENCES products(product_id)
);

SHOW CREATE TABLE payments;

SHOW CREATE TABLE orders;

ALTER TABLE orders
DROP FOREIGN KEY orders_ibfk_2;

ALTER TABLE orders
DROP INDEX unique_payment_order;

ALTER TABLE orders
ADD CONSTRAINT orders_ibfk_2
FOREIGN KEY (payment_id)
REFERENCES payments(payment_id);

SHOW CREATE TABLE orders;

SELECT
    CONSTRAINT_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'distributor_db'
  AND TABLE_NAME = 'orders'
  AND COLUMN_NAME = 'payment_id'
  AND REFERENCED_TABLE_NAME IS NOT NULL;