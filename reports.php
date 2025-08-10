<?php
require __DIR__.'/config.php';
requireAdmin();
$defaultMonth = date('Y-m');
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>Aylık Raporlar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .card{box-shadow:0 2px 10px rgba(0,0,0,.05)}
        .stat .label{color:#6b7280;font-size:.9rem}
        .stat .value{font-size:1.4rem;font-weight:700}
        .grid-2{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
        @media (max-width: 992px){.grid-2{grid-template-columns:1fr}}
    </style>
</head>
<body class="bg-light">
<?php include __DIR__.'/header.php'; ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-end mb-3">
        <div>
            <h4>Aylık Raporlar (Grafikli)</h4>
            <div class="text-muted">Seçilen aya göre genel değerlendirme.</div>
        </div>
        <form id="monthForm" class="row g-2">
            <div class="col-auto">
                <label class="form-label">Ay</label>
                <input type="month" class="form-control" name="month" id="monthInput" value="<?=$defaultMonth?>">
            </div>
            <div class="col-auto">
                <label class="form-label d-block">&nbsp;</label>
                <button class="btn btn-primary">Raporu Getir</button>
            </div>
        </form>
    </div>

    <!-- Özet kartlar -->
    <div class="row g-3 mb-3">
        <div class="col-md-3"><div class="card p-3 stat">
                <div class="label">Aylık Drop Geliri</div>
                <div id="stRevenue" class="value">–</div>
            </div></div>
        <div class="col-md-3"><div class="card p-3 stat">
                <div class="label">Aylık Katılım</div>
                <div id="stAttendance" class="value">–</div>
            </div></div>
        <div class="col-md-3"><div class="card p-3 stat">
                <div class="label">Kasa (Ay / Toplam)</div>
                <div><span id="stBankMonth" class="value">–</span> <span class="text-muted">/</span> <span id="stBankTotal" class="value">–</span></div>
            </div></div>
        <div class="col-md-3"><div class="card p-3 stat">
                <div class="label">Gelen / Ödenen / Kalan</div>
                <div class="value"><span id="stIncoming">–</span> <span class="text-muted">/</span> <span id="stPaid">–</span> <span class="text-muted">/</span> <span id="stRemain">–</span></div>
            </div></div>
    </div>

    <!-- Top üye + Drop özet -->
    <div class="row g-3 mb-3">
        <div class="col-md-6"><div class="card p-3">
                <div class="d-flex justify-content-between">
                    <h6>En Çok Katılım Sağlayan Üye</h6>
                    <span class="text-muted" id="topMemberCount">–</span>
                </div>
                <div class="display-6" id="topMemberName">–</div>
                <div class="text-muted">Ay boyunca katılım sayısı.</div>
            </div></div>
        <div class="col-md-6"><div class="card p-3">
                <h6>Drop Durumu (Ay)</h6>
                <canvas id="chDropStatus"></canvas>
            </div></div>
    </div>

    <div class="grid-2 mb-3">
        <div class="card p-3">
            <h6>Günlük Drop Geliri</h6>
            <canvas id="chDailyRevenue"></canvas>
        </div>
        <div class="card p-3">
            <h6>Günlük Katılım</h6>
            <canvas id="chDailyAttendance"></canvas>
        </div>
    </div>

    <div class="grid-2">
        <div class="card p-3">
            <h6>Üye İş Sınıfı Dağılımı</h6>
            <canvas id="chJobs"></canvas>
            <div class="text-muted mt-1" style="font-size:.9rem">Sadece aktif üyeler dikkate alınır.</div>
        </div>
        <div class="card p-3">
            <h6>Gelen Pay vs Ödenen</h6>
            <canvas id="chPay"></canvas>
        </div>
    </div>
</div>

<script>
    let chDropStatus, chDailyRevenue, chDailyAttendance, chJobs, chPay;

    function nf(n){ return new Intl.NumberFormat('tr-TR').format(n||0); }

    async function loadReport(month){
        const r = await fetch(`/api/reports_monthly.php?month=${encodeURIComponent(month)}`);
        const j = await r.json();
        if(!j.ok) { alert('Rapor alınamadı'); return; }

        const t = j.totals, s = j.series;

        // Stat kartlar
        document.getElementById('stRevenue').textContent    = nf(t.monthly_revenue);
        document.getElementById('stAttendance').textContent = nf(t.monthly_attendance);
        document.getElementById('stBankMonth').textContent  = nf(t.bank_month);
        document.getElementById('stBankTotal').textContent  = nf(t.bank_total);
        document.getElementById('stIncoming').textContent   = nf(t.incoming_payouts);
        document.getElementById('stPaid').textContent       = nf(t.paid_sum);
        document.getElementById('stRemain').textContent     = nf(t.remaining_sum);

        // Top member
        document.getElementById('topMemberName').textContent  = t.top_member?.username || '—';
        document.getElementById('topMemberCount').textContent = t.top_member?.c ? `${t.top_member.c} katılım` : '—';

        // Drop status chart
        const dsData = {
            labels:['Satıldı','Bekliyor'],
            datasets:[{ data:[t.drop_sold, t.drop_wait] }]
        };
        if(chDropStatus) chDropStatus.destroy();
        chDropStatus = new Chart(document.getElementById('chDropStatus'), { type:'doughnut', data: dsData, options:{responsive:true} });

        // Daily revenue
        const d1 = s.daily_revenue || [];
        if(chDailyRevenue) chDailyRevenue.destroy();
        chDailyRevenue = new Chart(document.getElementById('chDailyRevenue'), {
            type:'bar',
            data:{ labels:d1.map(x=>x.date), datasets:[{ label:'Gelir', data:d1.map(x=>+x.total||0) }] },
            options:{ responsive:true, plugins:{legend:{display:false}} }
        });

        // Daily attendance
        const d2 = s.daily_attendance || [];
        if(chDailyAttendance) chDailyAttendance.destroy();
        chDailyAttendance = new Chart(document.getElementById('chDailyAttendance'), {
            type:'line',
            data:{ labels:d2.map(x=>x.date), datasets:[{ label:'Katılım', data:d2.map(x=>+x.cnt||0), tension:.3 }] },
            options:{ responsive:true, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true, ticks:{precision:0}}} }
        });

        // Jobs
        const jb = t.jobs || {};
        if(chJobs) chJobs.destroy();
        chJobs = new Chart(document.getElementById('chJobs'), {
            type:'doughnut',
            data:{ labels:Object.keys(jb), datasets:[{ data:Object.values(jb) }] },
            options:{ responsive:true }
        });

        // Payouts vs payments
        if(chPay) chPay.destroy();
        chPay = new Chart(document.getElementById('chPay'), {
            type:'bar',
            data:{
                labels:['Bu Ay'],
                datasets:[
                    { label:'Gelen Pay', data:[t.incoming_payouts] },
                    { label:'Ödenen',    data:[t.paid_sum] },
                    { label:'Kalan',     data:[t.remaining_sum] }
                ]
            },
            options:{ responsive:true, scales:{y:{beginAtZero:true}}, plugins:{legend:{position:'bottom'}} }
        });
    }

    document.getElementById('monthForm').addEventListener('submit', (e)=>{
        e.preventDefault();
        const m = document.getElementById('monthInput').value || '<?= $defaultMonth ?>';
        loadReport(m);
    });

    // ilk yükleme
    loadReport(document.getElementById('monthInput').value || '<?= $defaultMonth ?>');
</script>
</body>
</html>
