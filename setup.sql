CREATE DATABASE IF NOT EXISTS jalaram_rd CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE jalaram_rd;

CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS customers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_code VARCHAR(30) UNIQUE,
  full_name VARCHAR(120) NOT NULL,
  mobile VARCHAR(20) NOT NULL,
  address TEXT,
  monthly_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  start_date DATE NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS rd_payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NOT NULL,
  payment_month DATE NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  payment_date DATE NOT NULL,
  receipt_no VARCHAR(40) UNIQUE,
  note VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_customer_month (customer_id, payment_month),
  CONSTRAINT fk_rd_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);