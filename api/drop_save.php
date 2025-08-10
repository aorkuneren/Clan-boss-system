<?php
require __DIR__ . '/../config.php';
requireAdmin();

/*
  Beklenen POST:
    - event_id (opsiyonel; yoksa 'date' ile bulunur/oluşturulur)
    - date (YYYY-MM-DD)  // event_id yoksa gerekli
    - item_name[]        // paralel diziler
    - status[]           // 'Satıldı' | 'Bekliyor'
    - price[]            // '1m', '200k' vb. -> parseCoins()
*/

$event_id = $_POST['event_id'] ?? null;
$date = $_POST['date'] ?? null;

if (!$event_id) {
    if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'msg' => 'Geçersiz tarih']);
        exit;
    }
    // varsa al, yoksa oluştur
    $st = $pdo->prepare("SELECT id FROM events WHERE event_date=? LIMIT 1");
    $st->execute([$date]);
    $event_id = $st->fetchColumn();
    if (!$event_id) {
        $pdo->prepare("INSERT INTO events (event_date, status) VALUES (?, 'Kesildi')")->execute([$date]);
        $event_id = $pdo->lastInsertId();
    }
}

$names = $_POST['item_name'] ?? [];
$statuses = $_POST['status'] ?? [];
$prices = $_POST['price'] ?? [];

// 1) Bu güne ait önce tüm dropları ve ilgili payout'ları temizle
$pdo->beginTransaction();
try {
    // O güne ait drop id'lerini bul
    $ids = $pdo->prepare("SELECT id FROM drops WHERE event_id=?");
    $ids->execute([$event_id]);
    $dropIds = array_map(fn($r) => (int)$r['id'], $ids->fetchAll());

    if ($dropIds) {
        // payouts tablosunu da temizle
        $in = implode(',', array_fill(0, count($dropIds), '?'));
        $stmt = $pdo->prepare("DELETE FROM payouts WHERE drop_id IN ($in)");
        $stmt->execute($dropIds);
    }

    // dropları sil
    $pdo->prepare("DELETE FROM drops WHERE event_id=?")->execute([$event_id]);

    // 2) Gelen dropları yeniden ekle
    $insDrop = $pdo->prepare("INSERT INTO drops (event_id, item_name, status, price) VALUES (?,?,?,?)");
    $newDropIds = []; // payout hesap için toplayacağız

    for ($i = 0; $i < count($names); $i++) {
        $name = trim($names[$i] ?? '');
        if ($name === '') continue;

        $status = $statuses[$i] ?? 'Bekliyor';
        $priceStr = $prices[$i] ?? '';
        $price = null;
        if ($status === 'Satıldı') {
            $pc = parseCoins((string)$priceStr); // sadece sayı (noktalı yazım destekli)
            if ($pc <= 0) {
                $pdo->rollBack();
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'msg' => "Satılan drop için geçersiz fiyat: {$name}"]);
                exit;
            }
            $price = $pc;
        }

        $insDrop->execute([$event_id, $name, $status, $price]);
        $newId = (int)$pdo->lastInsertId();
        $newDropIds[] = [$newId, $status, (int)$price];
    }

    // 3) O günün katılımcılarını çek (sadece aktifler)
    $p = $pdo->prepare("
  SELECT ep.user_id
  FROM event_participants ep
  JOIN users u ON u.id = ep.user_id
  WHERE ep.event_id=? AND u.is_active=1
");
    $p->execute([$event_id]);
    $participants = array_map(fn($r) => (int)$r['user_id'], $p->fetchAll());

// Ayarları çek
    $cfg = $pdo->query("SELECT tax_active, tax_percent, tax_start_date FROM settings WHERE id=1")->fetch();
    $taxActive = (int)($cfg['tax_active'] ?? 0) === 1;
    $taxPct = (float)($cfg['tax_percent'] ?? 0);
    $taxStart = $cfg['tax_start_date'] ?? null;

    // 4) Her 'Satıldı' drop için eşit pay + vergi
    if (!empty($participants)) {
        $cnt = count($participants);
        $insPay = $pdo->prepare("INSERT INTO payouts (drop_id, user_id, amount, gross_amount, tax_amount) VALUES (?,?,?,?,?)");


        // Etkinliğin tarihi (vergiyi neye göre uygulayacağımız)
        $eventDate = $date; // başta bulmuştuk

        foreach ($newDropIds as [$dropId, $stt, $price]) {
            if ($stt !== 'Satıldı' || $price === null) continue;

            // Eşit bölüş
            $share = intdiv($price, $cnt);
            $rem = $price % $cnt;

            // Vergi koşulu
            $applyTax = false;
            if ($taxActive && $taxPct > 0 && $taxStart && preg_match('/^\d{4}-\d{2}-\d{2}$/', $taxStart)) {
                // sadece tax_start_date <= eventDate ise uygula
                $applyTax = ($eventDate >= $taxStart);
            }

            // Net/gross dağıt
            foreach ($participants as $index => $uid) {
                $gross = $share + ($index < $rem ? 1 : 0); // brüt kişi payı
                if ($applyTax) {
                    // vergi = floor(gross * pct/100), net = gross - vergi
                    $taxAmt = (int)floor($gross * ($taxPct / 100));
                    $net = $gross - $taxAmt;
                } else {
                    $taxAmt = 0;
                    $net = $gross;
                }
                $insPay->execute([$dropId, $uid, $net, $gross, $taxAmt]);

                if ($applyTax && $taxAmt > 0) {
                    $pdo->prepare("INSERT INTO clan_bank (event_id, drop_id, user_id, tax_amount, created_at) VALUES (?, ?, ?, ?, NOW())")->execute([$event_id, $dropId, $uid, $taxAmt]);
                }

            }
        }
    }

    $pdo->commit();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    $pdo->rollBack();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
