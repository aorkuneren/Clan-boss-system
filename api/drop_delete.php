<?php
require __DIR__ . '/../config.php';
requireAdmin();

$drop_id = isset($_POST['drop_id']) ? (int)$_POST['drop_id'] : 0;

if ($drop_id <= 0) {
    echo json_encode(['ok'=>false, 'msg'=>'Geçersiz drop ID']);
    exit;
}

// Drop'u sil
$stmt = $pdo->prepare("DELETE FROM drops WHERE id=?");
$ok = $stmt->execute([$drop_id]);

// Payouts tablosundan da sil
if ($ok) {
    $pdo->prepare("DELETE FROM payouts WHERE drop_id=?")->execute([$drop_id]);
    echo json_encode(['ok'=>true]);
} else {
    echo json_encode(['ok'=>false, 'msg'=>'Silme başarısız']);
}
