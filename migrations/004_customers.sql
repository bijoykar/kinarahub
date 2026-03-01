-- customers table
CREATE TABLE IF NOT EXISTS customers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  store_id INT NOT NULL,
  name VARCHAR(255) NOT NULL,
  mobile VARCHAR(20) NULL,
  email VARCHAR(255) NULL,
  is_default TINYINT(1) DEFAULT 0,
  outstanding_balance DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_store_id (store_id),
  INDEX idx_store_mobile (store_id, mobile)
);

-- customer_credits table
CREATE TABLE IF NOT EXISTS customer_credits (
  id INT AUTO_INCREMENT PRIMARY KEY,
  store_id INT NOT NULL,
  customer_id INT NOT NULL,
  sale_id INT NULL,
  amount_due DECIMAL(10,2) NOT NULL,
  amount_paid DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  balance DECIMAL(10,2) NOT NULL,
  due_date DATE NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_store_id (store_id),
  INDEX idx_customer_id (customer_id)
);

-- credit_payments table
CREATE TABLE IF NOT EXISTS credit_payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  store_id INT NOT NULL,
  customer_id INT NOT NULL,
  credit_id INT NULL,
  amount_paid DECIMAL(10,2) NOT NULL,
  payment_method ENUM('cash','upi','card') NOT NULL,
  payment_date DATE NOT NULL,
  notes TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_store_id (store_id),
  INDEX idx_customer_id (customer_id)
);
