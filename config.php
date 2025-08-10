<?php
$DB_HOST='localhost';
$DB_NAME='clanbossv3';
$DB_USER='root';
$DB_PASS='';

try{
  $pdo=new PDO("mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",$DB_USER,$DB_PASS,[
    PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC
  ]);
}catch(PDOException $e){
  http_response_code(500); die('Veritabanı bağlantı hatası: '.$e->getMessage());
}
if(session_status()!==PHP_SESSION_ACTIVE){ session_start(); }

function requireLogin(){ if(empty($_SESSION['user_id'])){ header('Location: /login.php'); exit; } }
function requireAdmin(){ requireLogin(); if(($_SESSION['role']??'')!=='Admin'){ http_response_code(403); die('Bu işlem için yetkiniz yok.'); } }
function parseCoins(string $raw): int {
  // "10.000.000" -> "10000000"
  $digits = preg_replace('/\D+/', '', $raw); // rakam dışını at (nokta, boşluk vs.)
  if ($digits === '' ) return 0;
  // BIGINT'e yazacağız; PHP int 64-bit ise sorun yok
  return (int)$digits;
}

function formatCoins(int $n): string {
  // 10000000 -> "10.000.000"
  return number_format($n, 0, ',', '.'); // TR stilinde binlik ayırıcı nokta
}


function requireAdminOrStake() {
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Admin','Paydaş'])) {
        header('HTTP/1.1 403 Forbidden'); exit('Bu sayfa için yetkiniz yok.');
    }
}
function currentUserId(): ?int {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}