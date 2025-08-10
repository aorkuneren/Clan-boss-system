<?php
require __DIR__ . '/../config.php';
requireLogin();

$date = $_GET['date'] ?? null;
if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/',$date)) {
    echo json_encode(['ok'=>false,'msg'=>'Geçersiz tarih']); exit;
}

// Etkinliği çek / yoksa boş bir şablon hazırla
$st = $pdo->prepare("SELECT id, event_date, status, note FROM events WHERE event_date=?");
$st->execute([$date]);
$event = $st->fetch();
if (!$event) {
    $event = ['id'=>null,'event_date'=>$date,'status'=>'Kesilmedi','note'=>null];
}

// Katılımcılar (sadece aktif)
$p = $pdo->prepare("
  SELECT ep.user_id, u.username, u.job, u.role
  FROM event_participants ep
  JOIN users u ON u.id=ep.user_id
  WHERE ep.event_id=? AND u.is_active=1
  ORDER BY u.username
");
$p->execute([$event['id'] ?? 0]);
$participants = $p->fetchAll();

// Tüm aktif kullanıcılar (admin formu için)
$users = $pdo->query("SELECT id, username, job, role, is_active FROM users WHERE is_active=1 ORDER BY username")->fetchAll();

// Droplar
$d = $pdo->prepare("SELECT id, item_name, status, price FROM drops WHERE event_id=? ORDER BY id ASC");
$d->execute([$event['id'] ?? 0]);
$drops = [];
$total_sales = 0;
while($r = $d->fetch()){
    $r['price_fmt'] = $r['price']!==null ? formatCoins((int)$r['price']) : '';
    if ($r['status']==='Satıldı' && $r['price']) $total_sales += (int)$r['price'];
    $drops[] = $r;
}

// Kişi başı pay (aktif katılımcılara göre)
$per_share = 0;
if (count($participants)>0) { $per_share = intdiv($total_sales, count($participants)); }

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'ok'=>true,
    'event'=>$event,
    'users'=>$users,
    'participants'=>$participants,
    'drops'=>$drops,
    'total_sales'=>$total_sales,
    'total_sales_fmt'=>formatCoins($total_sales),
    'per_share'=>$per_share,
    'per_share_fmt'=>formatCoins($per_share),
], JSON_UNESCAPED_UNICODE);
