<?php
require __DIR__.'/config.php'; requireAdmin();
$users=$pdo->query('SELECT * FROM users ORDER BY id DESC')->fetchAll();
?>
<!doctype html><html lang="tr"><head>
<meta charset="utf-8"><title>Üye Yönetimi</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head><body class="bg-light">
<?php include __DIR__ . '/header.php'; ?>

<div class="container py-4">
<div class="d-flex justify-content-between align-items-center mb-3">
<h4>Üye Yönetimi</h4>
<div class="d-flex gap-2">
<a href="/member_form.php" class="btn btn-success">Yeni Üye</a>
</div></div>
<div class="card"><div class="card-body"><div class="table-responsive">
<table class="table table-striped align-middle">
<thead><tr><th>ID</th><th>Kullanıcı Adı</th><th>Job</th><th>Rol</th><th>Durum</th>
<th>İşlemler</th></tr></thead><tbody>
<?php foreach($users as $u): ?>
<tr>
<td><?= $u['id'] ?></td>
<td><?= htmlspecialchars($u['username']) ?></td>
<td><?= $u['job'] ?></td>
<td><?= $u['role'] ?></td>
<td><?= ((int)$u['is_active']===1 ? '🟢 Aktif' : '⚪ Pasif') ?></td>
<td>
<a class="btn btn-sm btn-primary" href="/member_form.php?id=<?= $u['id'] ?>">Düzenle</a>
<?php if($u['username']!=='admin'): ?>
<a class="btn btn-sm btn-danger" href="/member_delete.php?id=<?= $u['id'] ?>" onclick="return confirm('Silinsin mi?')">Sil</a>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</tbody></table>
</div></div></div>
</div></body></html>
