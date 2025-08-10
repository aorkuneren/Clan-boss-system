<?php
require __DIR__.'/../config.php'; requireLogin();
header('Content-Type: application/json; charset=utf-8');
$start=$_GET['start']??null; $end=$_GET['end']??null;
$sql='SELECT id,event_date,status,note FROM events'; $params=[];
if($start&&$end){ $sql.=' WHERE event_date BETWEEN ? AND ?'; $params=[$start,$end]; }
$sql.=' ORDER BY event_date ASC'; $st=$pdo->prepare($sql); $st->execute($params);
$rows=$st->fetchAll(); $out=[];
foreach($rows as $r){ $color=($r['status']==='Kesildi')?'#22c55e':'#ef4444';
  $out[]=['id'=>(int)$r['id'],'title'=>$r['status'].($r['note']?' • '.$r['note']:''),'start'=>$r['event_date'],'allDay'=>true,'backgroundColor'=>$color,'textColor'=>'#111827'];
}
echo json_encode($out, JSON_UNESCAPED_UNICODE);
