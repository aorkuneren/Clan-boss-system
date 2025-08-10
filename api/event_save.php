<?php
require __DIR__.'/../config.php'; requireAdmin();
$date=$_POST['date']??''; $status=$_POST['status']??'Kesilmedi'; $note=trim($_POST['note']??'');
if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date)){ echo json_encode(['ok'=>false,'msg'=>'Geçersiz tarih']); exit; }
if(!in_array($status,['Kesildi','Kesilmedi'],true)){ echo json_encode(['ok'=>false,'msg'=>'Geçersiz durum']); exit; }
$st=$pdo->prepare('SELECT id FROM events WHERE event_date=?'); $st->execute([$date]); $id=$st->fetchColumn();
if($id){ $u=$pdo->prepare('UPDATE events SET status=?, note=? WHERE id=?'); $u->execute([$status, $note?:null, $id]); }
else { $i=$pdo->prepare('INSERT INTO events(event_date,status,note) VALUES(?,?,?)'); $i->execute([$date,$status,$note?:null]); }
echo json_encode(['ok'=>true]);
