<?php
require __DIR__.'/config.php'; requireAdmin();

// Toplam kasa
$total = $pdo->query("SELECT SUM(tax_amount) FROM clan_bank")->fetchColumn() ?? 0;

// Liste
$stmt = $pdo->query("
    SELECT cb.*, u.username, e.event_date, d.item_name
    FROM clan_bank cb
    JOIN users u ON u.id = cb.user_id
    JOIN events e ON e.id = cb.event_id
    JOIN drops d ON d.id = cb.drop_id
    ORDER BY cb.created_at DESC
");
$rows = $stmt->fetchAll();
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>Clan Kasası</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include __DIR__.'/header.php'; ?>
<div class="container py-4">
    <h4>Clan Kasası</h4>
    <div class="alert alert-info">Toplam Kasa: <strong><?= formatCoins((int)$total) ?></strong></div>

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                <tr>
                    <th>Tarih</th>
                    <th>Etkinlik</th>
                    <th>Üye</th>
                    <th>Drop</th>
                    <th>Kesilen Vergi</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach($rows as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['created_at']) ?></td>
                        <td><?= htmlspecialchars($r['event_date']) ?></td>
                        <td><?= htmlspecialchars($r['username']) ?></td>
                        <td><?= htmlspecialchars($r['item_name']) ?></td>
                        <td><?= formatCoins((int)$r['tax_amount']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if(empty($rows)): ?>
                    <tr><td colspan="5" class="text-muted">Kayıt bulunamadı.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
