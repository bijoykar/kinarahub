-- categories table
CREATE TABLE IF NOT EXISTS categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  store_id INT NOT NULL,
  name VARCHAR(100) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_store_id (store_id),
  UNIQUE KEY uq_store_category (store_id, name)
);

-- units_of_measure table (platform-wide)
CREATE TABLE IF NOT EXISTS units_of_measure (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE,
  abbreviation VARCHAR(20) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- products table
CREATE TABLE IF NOT EXISTS products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  store_id INT NOT NULL,
  sku VARCHAR(100) NOT NULL,
  name VARCHAR(255) NOT NULL,
  category_id INT NULL,
  uom_id INT NULL,
  selling_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  cost_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  stock_quantity DECIMAL(10,3) NOT NULL DEFAULT 0.000,
  reorder_point DECIMAL(10,3) NOT NULL DEFAULT 0.000,
  status ENUM('active','inactive') DEFAULT 'active',
  version INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_store_sku (store_id, sku),
  INDEX idx_store_id (store_id),
  INDEX idx_store_category (store_id, category_id),
  INDEX idx_store_status (store_id, status)
);

-- product_variants table
CREATE TABLE IF NOT EXISTS product_variants (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  store_id INT NOT NULL,
  variant_name VARCHAR(255) NOT NULL,
  sku VARCHAR(100) NOT NULL,
  selling_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  cost_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  stock_quantity DECIMAL(10,3) NOT NULL DEFAULT 0.000,
  reorder_point DECIMAL(10,3) NOT NULL DEFAULT 0.000,
  version INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_product_id (product_id),
  INDEX idx_store_id (store_id),
  UNIQUE KEY uq_store_variant_sku (store_id, sku),
  CONSTRAINT fk_variant_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);
