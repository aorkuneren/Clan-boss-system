<?php
require __DIR__ . '/../config.php';
requireLogin();

$start = $_GET['start'] ?? date('Y-m-01');
$end   = $_GET['end'] ?? date('Y-m-t');

$stmt = $pdo->prepare("
    SELECT u.username, COALESCE(SUM(p.amount),0) AS total_amount
    FROM users u
    JOIN payouts p ON p.user_id=u.id
    JOIN drops d ON d.id=p.drop_id
    JOIN events e ON e.id=d.event_id
    WHERE e.event_date BETWEEN :start AND :end
    GROUP BY u.id
    ORDER BY total_amount DESC
    LIMIT 10
");
$stmt->execute([':start'=>$start, ':end'=>$end]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json; charset=utf-8');
echo json_encode($data);
