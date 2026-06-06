<?php
require_once __DIR__ . '/config.php';

echo "Running DB seed...\n";

try{
  $dsn = 'mysql:host='.DB_HOST.';charset=utf8mb4';
  $pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);

  // Create database if not exists
  $pdo->exec("CREATE DATABASE IF NOT EXISTS `".DB_NAME."` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
  $pdo->exec("USE `".DB_NAME."`;");

  // Create tables
  $pdo->exec("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    is_admin TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

  $pdo->exec("CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sku VARCHAR(64) UNIQUE,
    name VARCHAR(255) NOT NULL,
    short_description VARCHAR(512),
    description TEXT,
    category VARCHAR(64),
    price DECIMAL(10,2) DEFAULT 0.00,
    stock INT DEFAULT 0,
    age_restricted TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

  $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    total DECIMAL(10,2),
    status VARCHAR(64) DEFAULT 'pending',
    shipping_address TEXT,
    discreet_shipping TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

  // Carts and cart items
  $pdo->exec("CREATE TABLE IF NOT EXISTS carts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

  $pdo->exec("CREATE TABLE IF NOT EXISTS cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cart_id INT NOT NULL,
    product_id INT DEFAULT NULL,
    name VARCHAR(255),
    price DECIMAL(10,2) DEFAULT 0.00,
    qty INT DEFAULT 1,
    options TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

  // Password resets table
  $pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (token(32)),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

  // Insert sample products if none exist
  $stmt = $pdo->query('SELECT COUNT(*) FROM products');
  $count = (int)$stmt->fetchColumn();
  if($count === 0){
    $insert = $pdo->prepare('INSERT INTO products (sku,name,short_description,description,category,price,stock,age_restricted) VALUES (:sku,:name,:short,:desc,:cat,:price,:stock,:age)');
    $insert->execute([':sku'=>'SKU-001',':name'=>'Sensual Massage Oil',':short'=>'Lightly scented massage oil for couples.',':desc'=>'A light, non-greasy massage oil with natural ingredients.',':cat'=>'Category B',':price'=>129.99,':stock'=>50,':age'=>0]);
    $insert->execute([':sku'=>'SKU-002',':name'=>'Discreet Intimacy Kit',':short'=>'Tasteful kit with instructions and accessories.',':desc'=>'Curated intimacy kit designed for discretion and enjoyment.',':cat'=>'Category A',':price'=>499.00,':stock'=>20,':age'=>1]);
    $insert->execute([':sku'=>'SKU-003',':name'=>'Couples Game Card Pack',':short'=>'Fun prompts to connect and laugh together.',':desc'=>'A deck of conversation and activity prompts for couples.',':cat'=>'Category B',':price'=>199.50,':stock'=>100,':age'=>0]);
    echo "Inserted sample products.\n";
  }else{
    echo "Products already exist (count={$count}). Skipping inserts.\n";
  }

  // Create a default admin user if none exists
  $stmt = $pdo->query('SELECT COUNT(*) FROM users');
  $userCount = (int)$stmt->fetchColumn();
  if($userCount === 0){
    $email = 'admin@example.com';
    $password = 'ChangeMe123!';
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $ins = $pdo->prepare('INSERT INTO users (email,password_hash,is_admin) VALUES (:email,:hash,1)');
    $ins->execute([':email'=>$email,':hash'=>$hash]);
    echo "Created default admin user: {$email} with password: {$password}\n";
  }else{
    echo "Users already exist (count={$userCount}). Skipping admin creation.\n";
  }

  echo "DB seed completed successfully.\n";
}catch(Exception $e){
  echo "DB seed failed: " . $e->getMessage() . "\n";
  exit(1);
}
