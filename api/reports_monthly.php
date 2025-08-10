<?php
require __DIR__ . '/../config.php';
requireAdmin();

$month = $_GET['month'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $month)) $month = date('Y-m');

// Aylık toplam drop geliri (Satıldı)
$st = $pdo->prepare("
  SELECT COALESCE(SUM(d.price),0) AS total
  FROM drops d
  JOIN events e ON e.id = d.event_id
  WHERE d.status='Satıldı' AND DATE_FORMAT(e.event_date,'%Y-%m')=?
");
$st->execute([$month]);
$monthly_revenue = (int)$st->fetchColumn();

// Aylık katılım (toplam ve günlük seri)
$st = $pdo->prepare("
  SELECT e.event_date AS date, COUNT(ep.user_id) AS cnt
  FROM events e
  LEFT JOIN event_participants ep ON ep.event_id = e.id
  JOIN users u ON u.id=ep.user_id AND u.is_active=1
  WHERE DATE_FORMAT(e.event_date,'%Y-%m')=?
  GROUP BY e.event_date
  ORDER BY e.event_date
");
$st->execute([$month]);
$daily_attendance = $st->fetchAll(PDO::FETCH_ASSOC);
$monthly_attendance_sum = array_sum(array_map(fn($r)=> (int)$r['cnt'], $daily_attendance));

// En çok katılım sağlayan üye (ay içinde)
$st = $pdo->prepare("
  SELECT u.username, COUNT(*) AS c
  FROM event_participants ep
  JOIN events e ON e.id = ep.event_id
  JOIN users u  ON u.id = ep.user_id
  WHERE DATE_FORMAT(e.event_date,'%Y-%m')=?
  GROUP BY u.id
  ORDER BY c DESC, u.username ASC
  LIMIT 1
");
$st->execute([$month]);
$top_member = $st->fetch(PDO::FETCH_ASSOC) ?: ['username'=>null,'c'=>0];

// Drop durumları (ay içinde)
$st = $pdo->prepare("
  SELECT
    SUM(1) AS total,
    SUM(CASE WHEN d.status='Satıldı' THEN 1 ELSE 0 END) AS sold
  FROM drops d
  JOIN events e ON e.id=d.event_id
  WHERE DATE_FORMAT(e.event_date,'%Y-%m')=?
");
$st->execute([$month]);
$dr = $st->fetch(PDO::FETCH_ASSOC);
$drop_total = (int)($dr['total'] ?? 0);
$drop_sold  = (int)($dr['sold'] ?? 0);
$drop_wait  = max(0, $drop_total - $drop_sold);

// Günlük gelir serisi (Satıldı)
$st = $pdo->prepare("
  SELECT e.event_date AS date, COALESCE(SUM(d.price),0) AS total
  FROM events e
  LEFT JOIN drops d ON d.event_id=e.id AND d.status='Satıldı'
  WHERE DATE_FORMAT(e.event_date,'%Y-%m')=?
  GROUP BY e.event_date
  ORDER BY e.event_date
");
$st->execute([$month]);
$daily_revenue = $st->fetchAll(PDO::FETCH_ASSOC);

// Kasa (vergi) — ay içi ve toplam
$st = $pdo->prepare("
  SELECT COALESCE(SUM(p.tax_amount),0) AS month_tax
  FROM payouts p
  JOIN drops d ON d.id=p.drop_id
  JOIN events e ON e.id=d.event_id
  WHERE DATE_FORMAT(e.event_date,'%Y-%m')=?
");
$st->execute([$month]);
$bank_month = (int)$st->fetchColumn();

$bank_total = (int)$pdo->query("
  SELECT COALESCE(SUM(tax_amount),0)
  FROM payouts
")->fetchColumn();

// Üye payları (net) ve ödemeler: gelen/ödenen/kalan (ay içinde)
$st = $pdo->prepare("
  SELECT COALESCE(SUM(p.amount),0)
  FROM payouts p
  JOIN drops d ON d.id=p.drop_id
  JOIN events e ON e.id=d.event_id
  WHERE DATE_FORMAT(e.event_date,'%Y-%m')=?
");
$st->execute([$month]);
$incoming_payouts = (int)$st->fetchColumn();

$st = $pdo->prepare("
  SELECT COALESCE(SUM(amount),0)
  FROM payments
  WHERE DATE_FORMAT(created_at,'%Y-%m')=?
");
$st->execute([$month]);
$paid_sum = (int)$st->fetchColumn();

$remaining_sum = max($incoming_payouts - $paid_sum, 0);

// Aktif üyelerin job dağılımı
$jobs = ['Rogue'=>0,'Priest'=>0,'Mage'=>0,'Warrior'=>0];
foreach ($pdo->query("SELECT job, COUNT(*) AS c FROM users WHERE is_active=1 GROUP BY job") as $r) {
    $jobs[$r['job']] = (int)$r['c'];
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'ok'=>true,
    'month'=>$month,
    'totals'=>[
        'monthly_revenue'=>$monthly_revenue,
        'monthly_attendance'=>$monthly_attendance_sum,
        'top_member'=>$top_member,
        'drop_total'=>$drop_total,
        'drop_sold'=>$drop_sold,
        'drop_wait'=>$drop_wait,
        'bank_month'=>$bank_month,
        'bank_total'=>$bank_total,
        'incoming_payouts'=>$incoming_payouts,
        'paid_sum'=>$paid_sum,
        'remaining_sum'=>$remaining_sum,
        'jobs'=>$jobs
    ],
    'series'=>[
        'daily_revenue'=>$daily_revenue,
        'daily_attendance'=>$daily_attendance
    ]
], JSON_UNESCAPED_UNICODE);
