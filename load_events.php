<?php
require __DIR__.'/config.php'; requireLogin();
header('Content-Type: application/json; charset=utf-8');
$st=$pdo->query('SELECT id,event_date,status,note FROM events ORDER BY event_date ASC');
$out=[]; while($r=$st->fetch()){
  $color=($r['status']==='Kesildi')?'#22c55e':'#ef4444';
  $out[]=['id'=>(int)$r['id'],'start'=>$r['event_date'],'title'=>$r['status'].($r['note']?' • '.$r['note']:''),'allDay'=>true,'backgroundColor'=>$color,'textColor'=>'#111827'];
}
echo json_encode($out, JSON_UNESCAPED_UNICODE);
