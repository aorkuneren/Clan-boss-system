<?php
require __DIR__ . '/../config.php';
requireAdmin();

$user_id = (int)($_POST['user_id'] ?? 0);
$amount_raw = (string)($_POST['amount'] ?? '');
$note   = trim($_POST['note'] ?? '');
$start  = $_POST['start'] ?? null; // isteğe bağlı; geri dönüşte tabloyu tazelemek için
$end    = $_POST['end'] ?? null;

if ($user_id <= 0) {
    echo json_encode(['ok'=>false,'msg'=>'Geçersiz kullanıcı']); exit;
}

$amount = parseCoins($amount_raw); // sadece rakam, noktalı yazımı destekli
if ($amount <= 0) {
    echo json_encode(['ok'=>false,'msg'=>'Geçersiz tutar']); exit;
}

$created_by = $_SESSION['user_id'] ?? null;

$st = $pdo->prepare("INSERT INTO payments (user_id, amount, note, created_by) VALUES (?,?,?,?)");
$st->execute([$user_id, $amount, $note ?: null, $created_by]);

echo json_encode(['ok'=>true, 'user_id'=>$user_id]);
