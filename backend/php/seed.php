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
    image_path VARCHAR(512),
    price DECIMAL(10,2) DEFAULT 0.00,
    stock INT DEFAULT 0,
    age_restricted TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

  $imageColumn = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table AND COLUMN_NAME = :column');
  $imageColumn->execute([':db'=>DB_NAME,':table'=>'products',':column'=>'image_path']);
  if((int)$imageColumn->fetchColumn() === 0){
    $pdo->exec('ALTER TABLE products ADD COLUMN image_path VARCHAR(512) NULL');
  }

  $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    total DECIMAL(10,2),
    status VARCHAR(64) DEFAULT 'pending',
    shipping_address TEXT,
    discreet_shipping TINYINT(1) DEFAULT 1,
    customer_name VARCHAR(160) NOT NULL,
    customer_email VARCHAR(255) NOT NULL,
    payment_method VARCHAR(32) NOT NULL,
    payment_reference VARCHAR(255) NULL UNIQUE,
    delivery_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

  $orderColumns = [
    'customer_name' => "VARCHAR(160) NOT NULL DEFAULT ''",
    'customer_email' => "VARCHAR(255) NOT NULL DEFAULT ''",
    'payment_method' => "VARCHAR(32) NOT NULL DEFAULT 'eft'",
    'payment_reference' => 'VARCHAR(255) NULL',
    'delivery_fee' => 'DECIMAL(10,2) NOT NULL DEFAULT 0.00'
  ];
  foreach($orderColumns as $column=>$definition){
    $check = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table AND COLUMN_NAME = :column');
    $check->execute([':db'=>DB_NAME,':table'=>'orders',':column'=>$column]);
    if((int)$check->fetchColumn() === 0){
      $pdo->exec("ALTER TABLE orders ADD COLUMN `{$column}` {$definition}");
    }
  }

  $pdo->exec("CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
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
    $insert = $pdo->prepare('INSERT INTO products (sku,name,short_description,description,category,image_path,price,stock,age_restricted) VALUES (:sku,:name,:short,:desc,:cat,:image,:price,:stock,:age)');
    $insert->execute([':sku'=>'SKU-001',':name'=>'Sensual Massage Oil',':short'=>'Lightly scented massage oil for couples.',':desc'=>'A light, non-greasy massage oil with natural ingredients.',':cat'=>'Wellness',':image'=>'images/491e5580dbe04ce2e66f7f54a55563fa.jpg',':price'=>129.99,':stock'=>50,':age'=>0]);
    $insert->execute([':sku'=>'SKU-002',':name'=>'Discreet Intimacy Kit',':short'=>'Tasteful kit with instructions and accessories.',':desc'=>'Curated intimacy kit designed for discretion and enjoyment.',':cat'=>'Date Night',':image'=>'images/1348f53552fdf085e6f5a9f8c6bf1669.jpg',':price'=>499.00,':stock'=>20,':age'=>1]);
    $insert->execute([':sku'=>'SKU-003',':name'=>'Couples Game Card Pack',':short'=>'Fun prompts to connect and laugh together.',':desc'=>'A deck of conversation and activity prompts for couples.',':cat'=>'Gifts',':image'=>'images/Matching Couple Mugs.jpg',':price'=>199.50,':stock'=>100,':age'=>0]);
    echo "Inserted sample products.\n";
  }else{
    echo "Products already exist (count={$count}). Skipping inserts.\n";
  }

  // Create or rotate the administrator only from explicitly configured credentials.
  $email = getenv('ADMIN_EMAIL') ?: '';
  $password = getenv('ADMIN_PASSWORD') ?: '';
  $validAdminPassword = strlen($password) >= 12 && stripos($password, 'replace') === false;
  if($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) && $validAdminPassword){
    $findAdmin = $pdo->prepare('SELECT id,is_admin FROM users WHERE email = :email LIMIT 1');
    $findAdmin->execute([':email'=>$email]);
    $existingAdmin = $findAdmin->fetch(PDO::FETCH_ASSOC);
    $hash = password_hash($password, PASSWORD_DEFAULT);
    if($existingAdmin && (int)$existingAdmin['is_admin'] === 1){
      $updateAdmin = $pdo->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
      $updateAdmin->execute([':hash'=>$hash,':id'=>$existingAdmin['id']]);
      echo "Updated the configured admin credentials for {$email}.\n";
    }elseif(!$existingAdmin){
      $ins = $pdo->prepare('INSERT INTO users (email,password_hash,is_admin) VALUES (:email,:hash,1)');
      $ins->execute([':email'=>$email,':hash'=>$hash]);
      echo "Created the configured admin user: {$email}\n";
    }else{
      echo "Could not create an administrator because ADMIN_EMAIL belongs to a non-admin account.\n";
    }
  }else{
    echo "Skipped admin setup. Set ADMIN_EMAIL and a unique ADMIN_PASSWORD of at least 12 characters, then run seed again.\n";
  }

  $legacyAdmin = $pdo->prepare('SELECT id,password_hash FROM users WHERE email = :email AND is_admin = 1 LIMIT 1');
  $legacyAdmin->execute([':email'=>'admin@example.com']);
  $legacy = $legacyAdmin->fetch(PDO::FETCH_ASSOC);
  if($legacy && password_verify('ChangeMe123!', $legacy['password_hash'])){
    $disableLegacy = $pdo->prepare('UPDATE users SET is_admin = 0 WHERE id = :id');
    $disableLegacy->execute([':id'=>$legacy['id']]);
    echo "Disabled the project’s insecure example admin account.\n";
  }

  echo "DB seed completed successfully.\n";
}catch(Exception $e){
  echo "DB seed failed: " . $e->getMessage() . "\n";
  exit(1);
}
