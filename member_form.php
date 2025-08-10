<?php
require __DIR__ . '/config.php';
requireAdmin();

$id = (int)($_GET['id'] ?? 0);
$user = null;
if ($id > 0) {
  $st = $pdo->prepare('SELECT * FROM users WHERE id=?');
  $st->execute([$id]);
  $user = $st->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $username   = trim($_POST['username'] ?? '');
  $password   = $_POST['password'] ?? '';
  $job        = $_POST['job'] ?? '';
  $role       = $_POST['role'] ?? 'Üye';
  $is_active  = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

  if ($id > 0) {
    // Son admin mi?
    $wasAdmin = ($user['role'] ?? '') === 'Admin';
    $willAdmin = $role === 'Admin';
  
    if ($wasAdmin && !$willAdmin) {
      // Sistemdeki admin sayısını kontrol et
      $cnt = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='Admin'")->fetchColumn();
      if ($cnt <= 1) {
        exit('Sistemde en az bir Admin bulunmalı. Son Admin’in rolü düşürülemez.');
      }
    }
  
    if (!empty($_POST['password'])) {
      $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
      $pdo->prepare("UPDATE users SET username=?, password=?, job=?, role=?, is_active=? WHERE id=?")
          ->execute([$username, $hash, $job, $role, $is_active, $id]);
    } else {
      $pdo->prepare("UPDATE users SET username=?, job=?, role=?, is_active=? WHERE id=?")
          ->execute([$username, $job, $role, $is_active, $id]);
    }
  } else {
    // yeni kullanıcı
    $hash = password_hash($password ?: '123456', PASSWORD_DEFAULT);
    $pdo->prepare("INSERT INTO users (username, password, job, role, is_active) VALUES (?,?,?,?,?)")
        ->execute([$username, $hash, $job, $role, $is_active]);
  }

  header('Location: /members.php');
  exit;
}
?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <title>Üye Formu</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4><?= $id > 0 ? 'Üye Düzenle' : 'Yeni Üye Ekle' ?></h4>
    <a href="/members.php" class="btn btn-secondary">Geri</a>
  </div>

  <div class="card">
    <div class="card-body">
      <form method="post">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Kullanıcı Adı</label>
            <input class="form-control" name="username" value="<?= htmlspecialchars($user['username'] ?? '') ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Şifre <?= $id > 0 ? '<small>(boş bırakırsan değişmez)</small>' : '' ?></label>
            <input type="password" class="form-control" name="password" <?= $id > 0 ? '' : 'required' ?>>
          </div>

          <div class="col-md-6">
            <label class="form-label">Job</label>
            <select class="form-select" name="job">
              <?php foreach (['Rogue','Priest','Mage','Warrior'] as $j): ?>
                <option value="<?= $j ?>" <?= (($user['job'] ?? 'Warrior') === $j) ? 'selected' : '' ?>><?= $j ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label">Rol</label>
            <select class="form-select" name="role">
              <?php foreach (['Admin','Paydaş','Üye'] as $r): ?>
                <option value="<?= $r ?>" <?= (($user['role'] ?? 'Üye') === $r) ? 'selected' : '' ?>><?= $r ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label">Durum</label>
            <select class="form-select" name="is_active">
              <option value="1" <?= (isset($user['is_active']) ? (int)$user['is_active'] === 1 : true) ? 'selected' : '' ?>>Aktif</option>
              <option value="0" <?= (isset($user['is_active']) && (int)$user['is_active'] === 0) ? 'selected' : '' ?>>Pasif</option>
            </select>
          </div>
        </div>

        <div class="mt-3">
          <button class="btn btn-primary">Kaydet</button>
        </div>
      </form>
    </div>
  </div>
</div>
</body>
</html>
