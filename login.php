<?php
require __DIR__.'/includes/init.php';
if(is_logged_in()) redirect('index.php');
$error='';$email='';
if(is_post()){
  verify_csrf();
  $email=trim((string)($_POST['email']??''));
  $password=(string)($_POST['password']??'');
  if(!filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($email)>150){
    $error='Email hoặc mật khẩu không đúng.';
  } else {
    $wait=login_block_seconds($email);
    if($wait>0){
      $error='Bạn đã nhập sai 5 lần. Vui lòng thử lại sau '.(int)ceil($wait/60).' phút.';
    } else {
      // Nếu thời gian khóa cũ đã hết thì cho phép một chu kỳ thử mới.
      db()->prepare('DELETE FROM login_attempts WHERE email_key=? AND locked_until IS NOT NULL AND locked_until <= NOW()')
          ->execute([login_attempt_key($email)]);
      $stmt=db()->prepare('SELECT id,fullname,password_hash,role FROM users WHERE email=? LIMIT 1');
      $stmt->execute([$email]);$user=$stmt->fetch();
      if($user && password_verify($password,$user['password_hash'])){
        clear_login_failures($email);
        session_regenerate_id(true);
        $_SESSION['user']=['id'=>(int)$user['id'],'fullname'=>$user['fullname'],'role'=>$user['role']];
        set_flash('success','Xin chào '.$user['fullname'].'!');
        $destination=(string)($_SESSION['login_redirect']??'index.php');
        unset($_SESSION['login_redirect']);
        $pathname=(string)parse_url($destination,PHP_URL_PATH);
        if(!in_array($pathname,['checkout.php','orders.php','order-detail.php','profile.php','change-password.php'],true)) $destination='index.php';
        redirect($destination);
      }
      register_login_failure($email);
      $error=login_block_seconds($email)>0
        ? 'Bạn đã nhập sai 5 lần. Form đăng nhập bị khóa 5 phút.'
        : 'Email hoặc mật khẩu không đúng.';
    }
  }
}
$lockSeconds = $email !== '' ? login_block_seconds($email) : 0;
$pageTitle='Đăng nhập';$activePage='login.php';require __DIR__.'/includes/header.php';
?>
<section class="form-page"><h2>Đăng nhập</h2>
<form method="post" class="form-card" action="<?= e(url('login.php')) ?>">
<?= csrf_field() ?>
<?php if($error): ?><p class="alert alert-error" role="alert"><?= e($error) ?></p><?php endif; ?>
<?php if($lockSeconds>0): ?><p class="notice">Tài khoản này đang bị khóa đăng nhập tạm thời. Vui lòng thử lại sau <?= (int)ceil($lockSeconds/60) ?> phút.</p><?php endif; ?>
<label for="email">Email</label><input id="email" name="email" type="email" value="<?= e($email) ?>" autocomplete="email" required>
<label for="password">Mật khẩu</label><input id="password" name="password" type="password" autocomplete="current-password" <?= $lockSeconds>0 ? 'disabled' : '' ?> required>
<button type="submit" <?= $lockSeconds>0 ? 'disabled' : '' ?>>Đăng nhập</button>
<p>Chưa có tài khoản? <a href="<?= e(url('register.php')) ?>">Đăng ký ngay</a>.</p>
</form></section>
<?php require __DIR__.'/includes/footer.php'; ?>
