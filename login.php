<?php
require __DIR__.'/config.php';
if(!empty($_SESSION['user_id'])){ header('Location: /index.php'); exit; }
$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $u=trim($_POST['username']??''); $p=$_POST['password']??'';
  $st=$pdo->prepare('SELECT * FROM users WHERE username=? LIMIT 1'); $st->execute([$u]); $user=$st->fetch();
  if($user && password_verify($p,$user['password'])){
    $_SESSION['user_id']=(int)$user['id']; $_SESSION['username']=$user['username']; $_SESSION['role']=$user['role'];
    header('Location: /index.php'); exit;
  } else { $err='Kullanıcı adı veya şifre hatalı.'; }
}
?>
<!doctype html><html lang="tr"><head>
<meta charset="utf-8"><title>Giriş | ClanBoss</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="/css/style.css" rel="stylesheet">
</head><body class="bg-light d-flex align-items-center" style="min-height:100vh;">
<div class="container"><div class="row justify-content-center"><div class="col-md-4">
<div class="card shadow-sm"><div class="card-header text-center"><strong>Giriş Yap</strong></div>
<div class="card-body">
<?php if($err): ?><div class="alert alert-danger"><?= $err ?></div><?php endif; ?>
<form method="post">
<div class="mb-3"><label class="form-label">Kullanıcı Adı</label><input name="username" class="form-control" required></div>
<div class="mb-3"><label class="form-label">Şifre</label><input type="password" name="password" class="form-control" required></div>
<button class="btn btn-primary w-100">Giriş Yap</button>
</form>
</div></div></div></div></div></body></html>
