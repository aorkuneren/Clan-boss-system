<?php
require __DIR__ . '/config.php';
requireAdmin();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: /members.php'); exit; }

$st = $pdo->prepare("SELECT username, role FROM users WHERE id=?");
$st->execute([$id]);
$u = $st->fetch();

if (!$u) { header('Location: /members.php'); exit; }

// Admin KULLANICI asla silinemez
if ($u['role'] === 'Admin') {
  // İstersen bir flash mesaj mekanizması ekleyebilirsin
  exit('Admin kullanıcı silinemez.');
}

$pdo->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
header('Location: /members.php');
