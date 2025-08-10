<?php
require __DIR__ . '/../config.php';
requireAdmin();

$event_id = $_POST['event_id'] ?? null;
$date     = $_POST['date'] ?? null;
$parts    = $_POST['participants'] ?? [];

if (!$event_id) {
  if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode(['ok'=>false,'msg'=>'Geçersiz tarih']); exit;
  }
  $st = $pdo->prepare("SELECT id FROM events WHERE event_date=?");
  $st->execute([$date]);
  $event_id = $st->fetchColumn();
  if (!$event_id) {
    $pdo->prepare("INSERT INTO events (event_date, status) VALUES (?, 'Kesildi')")->execute([$date]);
    $event_id = $pdo->lastInsertId();
  }
}

$pdo->prepare("DELETE FROM event_participants WHERE event_id=?")->execute([$event_id]);
$ins = $pdo->prepare("INSERT IGNORE INTO event_participants (event_id, user_id) VALUES (?, ?)");
foreach ($parts as $uid) {
  $uid = (int)$uid;
  if ($uid > 0) $ins->execute([$event_id, $uid]);
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['ok'=>true]);
