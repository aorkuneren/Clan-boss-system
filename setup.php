<?php
require __DIR__.'/config.php';

try{
  $pdo->exec("
  CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    job ENUM('Rogue','Priest','Mage','Warrior') NOT NULL DEFAULT 'Warrior',
    role ENUM('Admin','Paydaş','Üye') NOT NULL DEFAULT 'Üye',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

  $pdo->exec("
  CREATE TABLE IF NOT EXISTS events(
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_date DATE NOT NULL UNIQUE,
    status ENUM('Kesildi','Kesilmedi') NOT NULL DEFAULT 'Kesilmedi',
    note VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

  $pdo->exec("
  CREATE TABLE IF NOT EXISTS event_participants(
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    user_id INT NOT NULL,
    UNIQUE KEY uk_event_user(event_id,user_id),
    CONSTRAINT fk_ep_event FOREIGN KEY(event_id) REFERENCES events(id) ON DELETE CASCADE,
    CONSTRAINT fk_ep_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$pdo->exec("
CREATE TABLE IF NOT EXISTS payouts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  drop_id INT NOT NULL,
  user_id INT NOT NULL,
  amount BIGINT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_payout_drop FOREIGN KEY (drop_id) REFERENCES drops(id) ON DELETE CASCADE,
  CONSTRAINT fk_payout_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");


  $pdo->exec("
  CREATE TABLE IF NOT EXISTS drops(
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    item_name VARCHAR(120) NOT NULL,
    status ENUM('Satıldı','Bekliyor') NOT NULL DEFAULT 'Bekliyor',
    price BIGINT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_drop_event FOREIGN KEY(event_id) REFERENCES events(id) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("
  CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount BIGINT NOT NULL,           -- parseCoins ile gelen net rakam
    note VARCHAR(255) DEFAULT NULL,
    created_by INT DEFAULT NULL,      -- ödemeyi yapan admin (opsiyonel)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pay_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_pay_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

  $q=$pdo->prepare("SELECT COUNT(*) FROM users WHERE username='admin'"); $q->execute();
  if((int)$q->fetchColumn()===0){
    $pass=password_hash('admin123', PASSWORD_DEFAULT);
    $pdo->prepare("INSERT INTO users(username,password,job,role) VALUES('admin',?,?, 'Admin')")->execute([$pass,'Warrior']);
  }

  echo '✅ Kurulum tamam. Admin: admin / admin123';
}catch(Throwable $e){
  http_response_code(500); echo 'Hata: '.$e->getMessage();
}
