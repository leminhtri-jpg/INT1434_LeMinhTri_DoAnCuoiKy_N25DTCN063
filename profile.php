<?php
require __DIR__ . '/includes/init.php';
require_login();

$userId = (int)current_user()['id'];
$stmt = db()->prepare('SELECT id, fullname, email, phone, address FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();
if (!$user) {
    unset($_SESSION['user']);
    set_flash('error', 'Tài khoản không còn tồn tại.');
    redirect('login.php');
}

$errors = [];
if (is_post()) {
    verify_csrf();
    $fullname = trim((string)($_POST['fullname'] ?? ''));
    $phone = trim((string)($_POST['phone'] ?? ''));
    $address = trim((string)($_POST['address'] ?? ''));
    if (mb_strlen($fullname) < 2 || mb_strlen($fullname) > 100) {
        $errors['fullname'] = 'Họ tên phải từ 2 đến 100 ký tự.';
    }
    if (!valid_phone($phone)) {
        $errors['phone'] = 'Số điện thoại phải có 9–11 chữ số.';
    }
    if (mb_strlen($address) > 255) {
        $errors['address'] = 'Địa chỉ không quá 255 ký tự.';
    }
    $user['fullname'] = $fullname;
    $user['phone'] = $phone;
    $user['address'] = $address;
    if (!$errors) {
        $stmt = db()->prepare('UPDATE users SET fullname = ?, phone = ?, address = ? WHERE id = ?');
        $stmt->execute([$fullname, $phone, $address, $userId]);
        $_SESSION['user']['fullname'] = $fullname;
        set_flash('success', 'Đã cập nhật thông tin tài khoản.');
        redirect('profile.php');
    }
}
$pageTitle = 'Tài khoản của tôi';
$activePage = 'profile.php';
require __DIR__ . '/includes/header.php';
?>
<section class="form-page">
    <h2>Thông tin tài khoản</h2>
    <form method="post" class="form-card" action="<?= e(url('profile.php')) ?>">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label>Email (không thay đổi)</label>
        <p class="read-only-field"><?= e($user['email']) ?></p>
        <label for="fullname">Họ và tên</label>
        <input id="fullname" name="fullname" type="text" minlength="2" maxlength="100" required value="<?= e($user['fullname']) ?>">
        <?php if (isset($errors['fullname'])): ?><span class="form-error"><?= e($errors['fullname']) ?></span><?php endif; ?>
        <label for="phone">Số điện thoại</label>
        <input id="phone" name="phone" type="tel" pattern="[0-9]{9,11}" required value="<?= e($user['phone']) ?>">
        <?php if (isset($errors['phone'])): ?><span class="form-error"><?= e($errors['phone']) ?></span><?php endif; ?>
        <label for="address">Địa chỉ</label>
        <textarea id="address" name="address" rows="3" maxlength="255"><?= e($user['address']) ?></textarea>
        <?php if (isset($errors['address'])): ?><span class="form-error"><?= e($errors['address']) ?></span><?php endif; ?>
        <button type="submit">Lưu thay đổi</button>
    </form>
    <p class="form-hint"><a href="<?= e(url('orders.php')) ?>">Xem lịch sử đơn hàng</a></p>
</section>
<p class="form-hint"><a class="btn secondary-button" href="<?= e(url('change-password.php')) ?>">Đổi mật khẩu</a></p>
<?php require __DIR__ . '/includes/footer.php'; ?>
