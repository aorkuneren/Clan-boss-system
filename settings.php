<?php
require __DIR__.'/config.php'; requireAdmin();

// mevcut ayarları çek
$st = $pdo->query("SELECT tax_active, tax_percent, tax_start_date FROM settings WHERE id=1");
$cfg = $st->fetch() ?: ['tax_active'=>0,'tax_percent'=>0,'tax_start_date'=>null];
$ok = $_GET['ok'] ?? '';

?>
<!doctype html><html lang="tr"><head>
    <meta charset="utf-8"><title>Ayarlar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head><body class="bg-light">
<?php include __DIR__.'/header.php'; ?>
<div class="container py-4">
    <h4>Vergi Ayarları</h4>
    <?php if($ok==='1'): ?><div class="alert alert-success">Ayarlar kaydedildi.</div><?php endif; ?>

    <div class="card"><div class="card-body">
            <form method="post" action="/api/settings_save.php" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Vergi Aktif mi?</label>
                    <select name="tax_active" class="form-select">
                        <option value="1" <?=((int)$cfg['tax_active']===1?'selected':'')?>>Evet</option>
                        <option value="0" <?=((int)$cfg['tax_active']===0?'selected':'')?>>Hayır</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Vergi Oranı (%)</label>
                    <input type="number" step="0.01" min="0" max="100" name="tax_percent" class="form-control"
                           value="<?=htmlspecialchars($cfg['tax_percent'])?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Başlangıç Tarihi</label>
                    <input type="date" name="tax_start_date" class="form-control"
                           value="<?=htmlspecialchars($cfg['tax_start_date'] ?? '')?>">
                    <div class="form-text">Bu tarihten sonraki paylara uygulanır.</div>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-primary w-100">Kaydet</button>
                </div>
            </form>
        </div></div>
</div>
</body></html>
