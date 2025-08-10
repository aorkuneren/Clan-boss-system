<?php
require __DIR__ . '/config.php';
requireAdmin();

$start = $_GET['start'] ?? '';
$end   = $_GET['end'] ?? '';

$rangeCond = '';
$params = [];
if ($start && $end && preg_match('/^\d{4}-\d{2}-\d{2}$/',$start) && preg_match('/^\d{4}-\d{2}-\d{2}$/',$end)) {
    // payouts için tarih filtresi events.event_date üzerinden
    $rangeCond = "AND e.event_date BETWEEN ? AND ?";
    $params = [$start, $end];
}

// CTE'li toplam/ödenen/kalan (yalnızca Paydaş & Admin)
$sql = "
  WITH
  payouts_sum AS (
    SELECT u.id AS user_id, COALESCE(SUM(p.amount),0) AS total_amount
    FROM users u
    LEFT JOIN payouts p ON p.user_id = u.id
    LEFT JOIN drops d   ON d.id = p.drop_id
    LEFT JOIN events e  ON e.id = d.event_id
    WHERE u.role IN ('Paydaş','Admin') " . ($rangeCond ? " {$rangeCond} " : "") . "
    GROUP BY u.id
  ),
  payments_sum AS (
    SELECT user_id, COALESCE(SUM(amount),0) AS paid_amount
    FROM payments
    WHERE user_id IN (SELECT id FROM users WHERE role IN ('Paydaş','Admin'))
    " . ($rangeCond ? " AND DATE(created_at) BETWEEN ? AND ? " : "") . "
    GROUP BY user_id
  )
  SELECT u.id, u.username, u.role,
         COALESCE(ps.total_amount,0) AS total_amount,
         COALESCE(pay.paid_amount,0) AS paid_amount,
         GREATEST(COALESCE(ps.total_amount,0) - COALESCE(pay.paid_amount,0), 0) AS remaining
  FROM users u
  LEFT JOIN payouts_sum ps ON ps.user_id = u.id
  LEFT JOIN payments_sum pay ON pay.user_id = u.id
  WHERE u.role IN ('Paydaş','Admin')
  ORDER BY remaining DESC, total_amount DESC, u.username ASC
";


$paramsMerged = $params;            // payouts_sum için
if ($rangeCond) $paramsMerged = array_merge($paramsMerged, $params); // payments_sum için

$st = $pdo->prepare($sql);
$st->execute($paramsMerged);
$rows = $st->fetchAll();
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>Paydaş & Admin Payları</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include __DIR__.'/header.php'; ?>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-end mb-3">
        <div>
            <h4>Paydaş & Admin Payları</h4>
            <small class="text-muted">Seçili aralıkta sadece Paydaş ve Admin.</small>
        </div>
        <form class="row g-2" method="get">
            <div class="col-auto">
                <label class="form-label">Başlangıç</label>
                <input type="date" class="form-control" name="start" value="<?=htmlspecialchars($start)?>">
            </div>
            <div class="col-auto">
                <label class="form-label">Bitiş</label>
                <input type="date" class="form-control" name="end" value="<?=htmlspecialchars($end)?>">
            </div>
            <div class="col-auto">
                <label class="form-label d-block">&nbsp;</label>
                <button class="btn btn-primary">Uygula</button>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                <tr>
                    <th>Kullanıcı</th>
                    <th>Rol</th>
                    <th>Toplam</th>
                    <th>Ödenen</th>
                    <th>Kalan</th>
                    <th>İşlem</th>
                    <th>Detay</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach($rows as $r): ?>
                    <tr>
                        <td><?=htmlspecialchars($r['username'])?></td>
                        <td><?=htmlspecialchars($r['role'])?></td>
                        <td><?=formatCoins((int)$r['total_amount'])?></td>
                        <td class="<?= (int)$r['paid_amount']>0 ? 'text-success':'' ?>"><?=formatCoins((int)$r['paid_amount'])?></td>
                        <td class="<?= (int)$r['remaining']>0 ? 'text-danger':'' ?>"><?=formatCoins((int)$r['remaining'])?></td>
                        <td>
                            <?php if ((int)$r['remaining']>0): ?>
                                <button class="btn btn-sm btn-primary btnPay"
                                        data-user="<?=$r['id']?>"
                                        data-remaining="<?=$r['remaining']?>">
                                    Ödeme Yap
                                </button>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a class="btn btn-sm btn-outline-primary"
                               href="/member_payouts_detail.php?user_id=<?=$r['id']?>&start=<?=urlencode($start)?>&end=<?=urlencode($end)?>">
                                İncele
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if(empty($rows)): ?>
                    <tr><td colspan="7" class="text-muted">Kayıt bulunamadı.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Ödeme Modal -->
<div class="modal fade" id="payModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="payForm">
                <div class="modal-header">
                    <h5 class="modal-title">Ödeme Yap</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="payUserId">
                    <div class="mb-2">
                        <label class="form-label">Tutar</label>
                        <input class="form-control price-input" name="amount" id="payAmount" placeholder="örn: 10.000.000">
                        <div class="form-text">“Tümünü Öde” ile kalan tutarı otomatik doldur.</div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Not (opsiyonel)</label>
                        <input class="form-control" name="note" id="payNote" placeholder="örn: Kasadan ödendi">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="btnPayAll" class="btn btn-outline-secondary">Tümünü Öde</button>
                    <button class="btn btn-primary">Ödemeyi Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Noktalı sayı maskesi
    document.addEventListener('input', function(e){
        if (e.target.classList.contains('price-input')) {
            let v = e.target.value.replace(/\D/g,'');
            e.target.value = v.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }
    });

    const payModal = new bootstrap.Modal(document.getElementById('payModal'));
    let currentRemaining = 0;

    document.querySelectorAll('.btnPay').forEach(btn=>{
        btn.addEventListener('click', ()=>{
            const uid = btn.dataset.user;
            currentRemaining = parseInt(btn.dataset.remaining,10) || 0;
            document.getElementById('payUserId').value = uid;
            document.getElementById('payAmount').value = '';
            document.getElementById('payNote').value = '';
            payModal.show();
        });
    });

    document.getElementById('btnPayAll').addEventListener('click', ()=>{
        const s = currentRemaining.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        document.getElementById('payAmount').value = s;
    });

    document.getElementById('payForm').addEventListener('submit', async (e)=>{
        e.preventDefault();
        const fd = new FormData(e.target);
        fd.append('start', '<?=htmlspecialchars($start)?>');
        fd.append('end',   '<?=htmlspecialchars($end)?>');

        const r = await fetch('/api/payment_add.php', { method:'POST', body: fd });
        const j = await r.json();
        if (j.ok) {
            payModal.hide();
            location.reload();
        } else {
            alert(j.msg || 'Hata');
        }
    });
</script>
</body>
</html>
