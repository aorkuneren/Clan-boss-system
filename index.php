<?php
require __DIR__ . '/config.php';
requireLogin();
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>Clan Boss Takvim</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
    <link href="css/style.css?v3" rel="stylesheet">
    <style>
        .choices__inner {
            min-height: 38px;
            padding: 4px 8px;
        }

        .choices__list--multiple .choices__item {
            font-size: 12px;
        }
    </style>

</head>
<body>
<?php include __DIR__ . '/header.php'; ?>
<div class="container py-4">
    <div id="calendar"></div>
</div>
<div class="modal fade" id="eventModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Gün Detayı</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="modalContent">Yükleniyor...</div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/locales-all.global.min.js"></script>
<script src="/js/main.js?v3"></script>
<script>window.App = {
        isAdmin: <?= json_encode(($_SESSION['role'] ?? '') === 'Admin') ?>,
        username: <?= json_encode($_SESSION['username'] ?? '') ?>
    };</script>
</body>
</html>
