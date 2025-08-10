<?php
require __DIR__.'/config.php'; requireLogin();
$rows=$pdo->query("
  SELECT e.id,e.event_date,e.status,
         COALESCE(SUM(CASE WHEN d.status='Satıldı' THEN d.price ELSE 0 END),0) AS total_sales,
         (SELECT COUNT(*) FROM event_participants ep WHERE ep.event_id=e.id) AS attendee_count
  FROM events e
  LEFT JOIN drops d ON d.event_id=e.id
  GROUP BY e.id
  ORDER BY e.event_date DESC
")->fetchAll();
?>
<!doctype html><html lang="tr"><head>
<meta charset="utf-8"><title>Paylar</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="/css/style.css" rel="stylesheet">
</head><body class="bg-light">
<?php include __DIR__ . '/header.php'; ?>

<div class="container py-4">
<div class="d-flex justify-content-between align-items-center mb-3"><h4>Paylar</h4></div>
<div class="card"><div class="card-body table-responsive">
<table class="table table-striped align-middle"><thead><tr><th>Tarih</th><th>Durum</th><th>Toplam Satış</th><th>Katılımcı</th><th>Kişi Başı Pay</th></tr></thead><tbody>
<?php foreach($rows as $r): $total=(int)$r['total_sales']; $cnt=(int)$r['attendee_count']; $each=($r['status']==='Kesildi'&&$cnt>0)? intdiv($total,$cnt):0; ?>
<tr><td><?= htmlspecialchars($r['event_date']) ?></td><td><?= $r['status'] ?></td><td><?= formatCoins($total) ?></td><td><?= $cnt ?></td><td><?= formatCoins($each) ?></td></tr>
<?php endforeach; ?>
</tbody></table>
</div></div></div>
</body></html>
