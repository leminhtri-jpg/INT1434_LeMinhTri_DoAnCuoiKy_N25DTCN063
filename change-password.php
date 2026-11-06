<?php
require __DIR__.'/includes/init.php';require_login();
$errors=[];
if(is_post()){
  verify_csrf();
  $current=(string)($_POST['current_password']??'');
  $new=(string)($_POST['new_password']??'');
  $confirm=(string)($_POST['confirm_password']??'');
  $stmt=db()->prepare('SELECT password_hash FROM users WHERE id=?');
  $stmt->execute([(int)current_user()['id']]);
  $hash=$stmt->fetchColumn();
  if(!$hash || !password_verify($current,$hash)) $errors[]='Mật khẩu hiện tại không chính xác.';
  if(strlen($new)<8 || strlen($new)>72) $errors[]='Mật khẩu mới phải từ 8 đến 72 ký tự.';
  if($new!==$confirm) $errors[]='Hai mật khẩu mới không trùng khớp.';
  if($current===$new) $errors[]='Mật khẩu mới phải khác mật khẩu cũ.';
  if(!$errors){
    $stmt=db()->prepare('UPDATE users SET password_hash=? WHERE id=?');
    $stmt->execute([password_hash($new,PASSWORD_DEFAULT),(int)current_user()['id']]);
    session_regenerate_id(true);
    set_flash('success','Đổi mật khẩu thành công.');
    redirect('profile.php');
  }
}
$pageTitle='Đổi mật khẩu';$activePage='profile.php';require __DIR__.'/includes/header.php';
?>
<section class="form-page"><h2>Đổi mật khẩu</h2>
<?php foreach($errors as $error): ?><p class="alert alert-error" role="alert"><?= e($error) ?></p><?php endforeach; ?>
<form method="post" class="form-card" action="<?= e(url('change-password.php')) ?>">
<?= csrf_field() ?>
<label for="current_password">Mật khẩu hiện tại</label><input id="current_password" name="current_password" type="password" required autocomplete="current-password">
<label for="new_password">Mật khẩu mới</label><input id="new_password" name="new_password" type="password" required minlength="8" maxlength="72" autocomplete="new-password">
<label for="confirm_password">Nhập lại mật khẩu mới</label><input id="confirm_password" name="confirm_password" type="password" required minlength="8" maxlength="72" autocomplete="new-password">
<button type="submit">Đổi mật khẩu</button></form></section>
<?php require __DIR__.'/includes/footer.php'; ?>
