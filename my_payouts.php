<?php
require __DIR__ . '/config.php';
requireLogin();
$uid = currentUserId();
if (!$uid) { header('Location: /login.php'); exit; }

$month = $_GET['month'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/',$month)) $month = date('Y-m');

// Toplamlar (ay)
$st = $pdo->prepare("
  SELECT COALESCE(SUM(p.amount),0) AS total
  FROM payouts p
  JOIN drops d ON d.id=p.drop_id
  JOIN events e ON e.id=d.event_id
  WHERE p.user_id=? AND DATE_FORMAT(e.event_date,'%Y-%m')=?
");
$st->execute([$uid,$month]);
$total = (int)$st->fetchColumn();

$sp = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM payments WHERE user_id=? AND DATE_FORMAT(created_at,'%Y-%m')=?");
$sp->execute([$uid,$month]);
$paid = (int)$sp->fetchColumn();
$remain = max($total-$paid,0);

// Detay satırlar (drop bazlı)
$sd = $pdo->prepare("
  SELECT e.event_date, d.item_name, d.status, COALESCE(d.price,0) AS price, p.amount
  FROM payouts p
  JOIN drops d ON d.id=p.drop_id
  JOIN events e ON e.id=d.event_id
  WHERE p.user_id=? AND DATE_FORMAT(e.event_date,'%Y-%m')=?
  ORDER BY e.event_date DESC, d.id DESC
");
$sd->execute([$uid,$month]);
$rows = $sd->fetchAll();

// Ödeme geçmişi
$sh = $pdo->prepare("SELECT amount, note, created_at FROM payments WHERE user_id=? AND DATE_FORMAT(created_at,'%Y-%m')=? ORDER BY id DESC");
$sh->execute([$uid,$month]);
$hist = $sh->fetchAll();

// Kullanıcı bilgisi
$u = $pdo->prepare("SELECT username, role FROM users WHERE id=?");
$u->execute([$uid]); $user = $u->fetch();
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>Paylarım</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include __DIR__.'/header.php'; ?>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-end mb-3">
        <div>
            <h4><?=htmlspecialchars($user['username'])?> – Paylarım</h4>
            <div class="text-muted"><?=htmlspecialchars($user['role'])?></div>
        </div>
        <form method="get" class="row g-2">
            <div class="col-auto">
                <label class="form-label">Ay</label>
                <input type="month" class="form-control" name="month" value="<?=htmlspecialchars($month)?>">
            </div>
            <div class="col-auto">
                <label class="form-label d-block">&nbsp;</label>
                <button class="btn btn-primary">Göster</button>
            </div>
        </form>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4"><div class="card p-3"><div class="text-muted">Toplam</div><div class="fs-4 fw-bold"><?=formatCoins($total)?></div></div></div>
        <div class="col-md-4"><div class="card p-3"><div class="text-muted">Ödenen</div><div class="fs-4 fw-bold text-success"><?=formatCoins($paid)?></div></div></div>
        <div class="col-md-4"><div class="card p-3"><div class="text-muted">Kalan</div><div class="fs-4 fw-bold text-danger"><?=formatCoins($remain)?></div></div></div>
    </div>

    <div class="card mb-3">
        <div class="card-header">Drop Bazlı Detay</div>
        <div class="card-body table-responsive">
            <table class="table table-striped align-middle">
                <thead><tr><th>Tarih</th><th>Drop</th><th>Durum</th><th>Satış</th><th>Kişi Payı (Net)</th></tr></thead>
                <tbody>
                <?php foreach($rows as $r): ?>
                    <tr>
                        <td><?=htmlspecialchars($r['event_date'])?></td>
                        <td><?=htmlspecialchars($r['item_name'])?></td>
                        <td><?=$r['status']?></td>
                        <td><?=$r['status']==='Satıldı'?formatCoins((int)$r['price']):'—'?></td>
                        <td><?=formatCoins((int)$r['amount'])?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if(empty($rows)): ?><tr><td colspan="5" class="text-muted">Kayıt bulunamadı.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Ödeme Geçmişim</div>
        <div class="card-body table-responsive">
            <table class="table table-striped align-middle">
                <thead><tr><th>Tarih</th><th>Tutar</th><th>Not</th></tr></thead>
                <tbody>
                <?php foreach($hist as $h): ?>
                    <tr>
                        <td><?=htmlspecialchars($h['created_at'])?></td>
                        <td><?=formatCoins((int)$h['amount'])?></td>
                        <td><?=htmlspecialchars($h['note'] ?? '')?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if(empty($hist)): ?><tr><td colspan="3" class="text-muted">Ödeme kaydı yok.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
