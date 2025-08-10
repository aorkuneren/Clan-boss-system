<?php
require __DIR__.'/../config.php'; requireAdmin();

$tax_active = isset($_POST['tax_active']) ? (int)$_POST['tax_active'] : 0;
$tax_percent = isset($_POST['tax_percent']) ? (float)$_POST['tax_percent'] : 0.0;
$tax_start_date = $_POST['tax_start_date'] ?? null;

if ($tax_start_date && !preg_match('/^\d{4}-\d{2}-\d{2}$/',$tax_start_date)) $tax_start_date = null;
if ($tax_percent < 0) $tax_percent = 0; if ($tax_percent > 100) $tax_percent = 100;

$st = $pdo->prepare("UPDATE settings SET tax_active=?, tax_percent=?, tax_start_date=? WHERE id=1");
$st->execute([$tax_active, $tax_percent, $tax_start_date]);

header('Location: /settings.php?ok=1');
