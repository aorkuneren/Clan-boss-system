<?php
require __DIR__ . '/config.php';
requireAdmin();

$user_id = (int)($_GET['user_id'] ?? 0);
$start   = $_GET['start'] ?? '';
$end     = $_GET['end'] ?? '';

if ($user_id <= 0) { die('Geçersiz kullanıcı'); }

$u = $pdo->prepare("SELECT username, role FROM users WHERE id=?");
$u->execute([$user_id]);
$user = $u->fetch();
if (!$user) { die('Kullanıcı bulunamadı'); }

/* -------- PAY (DROP BAZLI) DETAY SATIRLARI -------- */
$rangeCond = '';
$params = [$user_id];
if ($start && $end && preg_match('/^\d{4}-\d{2}-\d{2}$/',$start) && preg_match('/^\d{4}-\d{2}-\d{2}$/',$end)) {
    $rangeCond = "AND e.event_date BETWEEN ? AND ?";
    $params[] = $start; $params[] = $end;
}

$sql = "SELECT e.event_date, d.item_name, d.status, COALESCE(d.price,0) AS price, p.amount, p.gross_amount, p.tax_amount
  FROM payouts p
  JOIN drops d  ON d.id = p.drop_id
  JOIN events e ON e.id = d.event_id
  WHERE p.user_id = ?
  {$rangeCond}
  ORDER BY e.event_date DESC, d.id DESC
";
$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll();

/* Toplam pay (satırların kişi payı toplamı) */
$sum = 0;
foreach ($rows as $r) { $sum += (int)$r['amount']; }

/* -------- ÖDEME GEÇMİŞİ -------- */
$payParams = [$user_id];
$payRange = '';
if ($start && $end && preg_match('/^\d{4}-\d{2}-\d{2}$/',$start) && preg_match('/^\d{4}-\d{2}-\d{2}$/',$end)) {
    $payRange = "AND DATE(created_at) BETWEEN ? AND ?";
    $payParams[] = $start; $payParams[] = $end;
}
$sp = $pdo->prepare("
  SELECT amount, note, created_at
  FROM payments
  WHERE user_id=? {$payRange}
  ORDER BY id DESC
");
$sp->execute($payParams);
$pays = $sp->fetchAll();
$paidSum = 0; foreach($pays as $p){ $paidSum += (int)$p['amount']; }
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>Pay Detayı - <?= htmlspecialchars($user['username']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4><?= htmlspecialchars($user['username']) ?> – Pay Detayı</h4>
            <div class="text-muted">
                <?= htmlspecialchars($user['role']) ?> | Toplam: <strong><?= formatCoins((int)$sum) ?></strong>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="/member_payouts.php?start=<?= urlencode($start) ?>&end=<?= urlencode($end) ?>" class="btn btn-secondary">Geri</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                <tr>
                    <th>Tarih</th>
                    <th>Drop</th>
                    <th>Durum</th>
                    <th>Satış</th>
                    <th>Brüt Payı</th>
                    <th>Net Payı</th>
                    <th>Vergi Payı</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['event_date']) ?></td>
                        <td><?= htmlspecialchars($r['item_name']) ?></td>
                        <td><?= $r['status'] ?></td>
                        <td><?= $r['status'] === 'Satıldı' ? formatCoins((int)$r['price']) : '—' ?></td>
                        <td><?= formatCoins((int)$r['amount']) ?></td>
                        <td><?= formatCoins((int)$r['gross_amount']) ?></td>
                        <td><?= formatCoins((int)$r['tax_amount']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="5" class="text-muted">Kayıt bulunamadı.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-body table-responsive">
            <h6>Ödeme Geçmişi (Toplam: <?= formatCoins((int)$paidSum) ?>)</h6>
            <table class="table table-striped align-middle">
                <thead><tr><th>Tarih</th><th>Tutar</th><th>Not</th></tr></thead>
                <tbody>
                <?php foreach($pays as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['created_at']) ?></td>
                        <td><?= formatCoins((int)$p['amount']) ?></td>
                        <td><?= htmlspecialchars($p['note'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if(empty($pays)): ?>
                    <tr><td colspan="3" class="text-muted">Ödeme kaydı yok.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
