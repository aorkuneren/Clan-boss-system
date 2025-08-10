<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config.php';

$userRole = $_SESSION['role'] ?? '';
?>
<nav class="navbar navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="/index.php">Clan Boss</a>
    <div class="d-flex gap-2">
      <a href="/index.php" class="btn btn-outline-light btn-sm">Anasayfa</a>
        <?php if(isset($_SESSION['user_id'])): ?>
            <a href="/my_payouts.php" class="btn btn-outline-light btn-sm">Paylarım</a>
        <?php endif; ?>
      <a href="/members.php" class="btn btn-outline-light btn-sm">Üyeler</a>
      <a href="/payouts.php" class="btn btn-outline-light btn-sm">Günlük Paylar</a>
      <a href="/member_payouts.php" class="btn btn-outline-light btn-sm">Üye Payları</a>
      <a href="/stake_payouts.php" class="btn btn-outline-light btn-sm">Paydaş Payları</a>
      <a href="/reports.php" class="btn btn-outline-light btn-sm">Raporlar</a>
      <a href="/clan_bank.php" class="btn btn-outline-light btn-sm">Kasa</a>
      <?php if($userRole === 'Admin'): ?>
        <a href="/settings.php" class="btn btn-warning btn-sm">Ayarlar</a>
      <?php endif; ?>
      <a href="/logout.php" class="btn btn-danger btn-sm">Çıkış</a>
    </div>
  </div>
</nav>
