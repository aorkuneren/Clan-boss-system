<?php
require __DIR__ . '/../config.php';
requireLogin();

$start = $_GET['start'] ?? null;
$end   = $_GET['end'] ?? null;

if (!$start || !$end || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $start) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) {
  http_response_code(422);
  echo json_encode(['ok'=>false,'msg'=>'Geçersiz tarih aralığı']); exit;
}

// Toplam satış (Satıldı drop fiyatları)
$st = $pdo->prepare("
  SELECT COALESCE(SUM(d.price),0) AS total
  FROM drops d
  JOIN events e ON e.id = d.event_id
  WHERE e.event_date BETWEEN ? AND ? AND d.status = 'Satıldı'
");
$st->execute([$start,$end]);
$total = (int)$st->fetchColumn();

// Toplam drop & Satılan drop
$st = $pdo->prepare("
  SELECT
    SUM(1) AS total_drops,
    SUM(CASE WHEN d.status='Satıldı' THEN 1 ELSE 0 END) AS sold_drops
  FROM drops d
  JOIN events e ON e.id = d.event_id
  WHERE e.event_date BETWEEN ? AND ?
");
$st->execute([$start,$end]);
$row = $st->fetch();
$total_drops = (int)($row['total_drops'] ?? 0);
$sold_drops  = (int)($row['sold_drops'] ?? 0);

// Katılımcı sayısı (tüm günlerdeki toplam kayıt)
$st = $pdo->prepare("
  SELECT COUNT(*) FROM event_participants ep
  JOIN events e ON e.id = ep.event_id
  WHERE e.event_date BETWEEN ? AND ?
");
$st->execute([$start,$end]);
$participants = (int)$st->fetchColumn();

// Günlük satış (date, total)
$st = $pdo->prepare("
  SELECT e.event_date AS date, COALESCE(SUM(CASE WHEN d.status='Satıldı' THEN d.price ELSE 0 END),0) AS total
  FROM events e
  LEFT JOIN drops d ON d.event_id = e.id
  WHERE e.event_date BETWEEN ? AND ?
  GROUP BY e.event_date
  ORDER BY e.event_date ASC
");
$st->execute([$start,$end]);
$daily = [];
while($r = $st->fetch()){
  $daily[] = ['date'=>$r['date'], 'total'=>(int)$r['total']];
}

// Drop durumları özet
$drop_status = [
  'sold'    => $sold_drops,
  'waiting' => max(0, $total_drops - $sold_drops)
];

// Katılımda ilk 10 üye
$st = $pdo->prepare("
  SELECT u.username, COUNT(*) AS cnt
  FROM event_participants ep
  JOIN events e ON e.id = ep.event_id
  JOIN users u  ON u.id = ep.user_id
  WHERE e.event_date BETWEEN ? AND ?
  GROUP BY u.id
  ORDER BY cnt DESC, u.username ASC
  LIMIT 10
");
$st->execute([$start,$end]);
$top_members = [];
while ($r = $st->fetch()){
  $top_members[] = ['username'=>$r['username'], 'count'=>(int)$r['cnt']];
}

// formatCoins() kullanarak okunabilir toplam
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
  'ok'             => true,
  'total_sales'    => $total,
  'total_sales_fmt'=> formatCoins($total),
  'total_drops'    => $total_drops,
  'sold_drops'     => $sold_drops,
  'participants'   => $participants,
  'daily_sales'    => $daily,
  'drop_status'    => $drop_status,
  'top_members'    => $top_members
], JSON_UNESCAPED_UNICODE);
