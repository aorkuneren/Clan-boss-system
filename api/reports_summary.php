<?php
require __DIR__ . '/../config.php';
requireLogin();

$start = $_GET['start'] ?? date('Y-m-01');
$end   = $_GET['end'] ?? date('Y-m-t');
$params = [':start'=>$start, ':end'=>$end];

// Toplam Brüt Satış
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(d.price),0)
    FROM drops d
    JOIN events e ON e.id=d.event_id
    WHERE d.status='Satıldı' AND e.event_date BETWEEN :start AND :end
");
$stmt->execute($params);
$gross_total = $stmt->fetchColumn();

// Toplam Vergi
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(cb.amount),0)
    FROM clan_bank cb
    JOIN events e ON e.id=cb.event_id
    WHERE e.event_date BETWEEN :start AND :end
");
$stmt->execute($params);
$tax_total = $stmt->fetchColumn();

// Toplam Net Dağıtım
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(p.amount),0)
    FROM payouts p
    JOIN drops d ON d.id=p.drop_id
    JOIN events e ON e.id=d.event_id
    WHERE e.event_date BETWEEN :start AND :end
");
$stmt->execute($params);
$net_total = $stmt->fetchColumn();

// Aktif Katılımcı Sayısı
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT p.user_id)
    FROM payouts p
    JOIN drops d ON d.id=p.drop_id
    JOIN events e ON e.id=d.event_id
    JOIN users u ON u.id=p.user_id
    WHERE e.event_date BETWEEN :start AND :end
      AND u.is_active=1
");
$stmt->execute($params);
$participants = $stmt->fetchColumn();

// Ortalama
$avg_net = $participants > 0 ? $net_total / $participants : 0;

// Günlük Net Dağıtım
$stmt = $pdo->prepare("
    SELECT e.event_date AS date, COALESCE(SUM(p.amount),0) AS amount
    FROM events e
    LEFT JOIN drops d ON d.event_id=e.id
    LEFT JOIN payouts p ON p.drop_id=d.id
    WHERE e.event_date BETWEEN :start AND :end
    GROUP BY e.event_date
    ORDER BY e.event_date
");
$stmt->execute($params);
$daily = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'gross_total' => $gross_total,
    'tax_total'   => $tax_total,
    'net_total'   => $net_total,
    'participants'=> $participants,
    'avg_net'     => $avg_net,
    'daily'       => $daily
]);
