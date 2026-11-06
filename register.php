<?php
require __DIR__ . '/includes/init.php';
if (is_logged_in()) {
    redirect('index.php');
}

$errors = [];
$old = ['fullname' => '', 'email' => '', 'phone' => '', 'address' => ''];
if (is_post()) {
    verify_csrf();
    foreach ($old as $field => $_) {
        $old[$field] = trim((string)($_POST[$field] ?? ''));
    }
    $password = (string)($_POST['password'] ?? '');
    $password2 = (string)($_POST['password2'] ?? '');

    if (mb_strlen($old['fullname']) < 2 || mb_strlen($old['fullname']) > 100) {
        $errors['fullname'] = 'Họ tên phải từ 2 đến 100 ký tự.';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL) || strlen($old['email']) > 150) {
        $errors['email'] = 'Email không hợp lệ.';
    }
    if (!valid_phone($old['phone'])) {
        $errors['phone'] = 'Số điện thoại phải có 9–11 chữ số.';
    }
    if (strlen($password) < 6 || strlen($password) > 72) {
        $errors['password'] = 'Mật khẩu cần từ 6 đến 72 ký tự.';
    }
    if ($password !== $password2) {
        $errors['password2'] = 'Hai mật khẩu không khớp nhau.';
    }
    if ($old['address'] !== '' && mb_strlen($old['address']) > 255) {
        $errors['address'] = 'Địa chỉ không được quá 255 ký tự.';
    }

    if (!$errors) {
        $stmt = db()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$old['email']]);
        if ($stmt->fetch()) {
            $errors['email'] = 'Email này đã được đăng ký.';
        } else {
            try {
                $stmt = db()->prepare('INSERT INTO users (fullname, email, password_hash, phone, address) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([
                    $old['fullname'], $old['email'], password_hash($password, PASSWORD_DEFAULT),
                    $old['phone'], $old['address'],
                ]);
                set_flash('success', 'Đăng ký thành công. Mời bạn đăng nhập.');
                redirect('login.php');
            } catch (PDOException $exception) {
                if ((string)$exception->getCode() === '23000') {
                    $errors['email'] = 'Email này đã được đăng ký.';
                } else {
                    throw $exception;
                }
            }
        }
    }
}

$pageTitle = 'Đăng ký';
$activePage = 'register.php';
require __DIR__ . '/includes/header.php';
?>
<section class="form-page">
    <h2>Đăng ký tài khoản</h2>
    <form class="form-card" id="register-form" method="post" action="<?= e(url('register.php')) ?>">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label for="fullname">Họ và tên</label>
        <input id="fullname" name="fullname" type="text" required minlength="2" maxlength="100" autocomplete="name" value="<?= e($old['fullname']) ?>">
        <?php if (isset($errors['fullname'])): ?><span class="form-error"><?= e($errors['fullname']) ?></span><?php endif; ?>

        <label for="email">Email</label>
        <input id="email" name="email" type="email" required maxlength="150" autocomplete="email" value="<?= e($old['email']) ?>">
        <?php if (isset($errors['email'])): ?><span class="form-error"><?= e($errors['email']) ?></span><?php endif; ?>

        <label for="password">Mật khẩu</label>
        <input id="password" name="password" type="password" required minlength="6" maxlength="72" autocomplete="new-password">
        <?php if (isset($errors['password'])): ?><span class="form-error"><?= e($errors['password']) ?></span><?php endif; ?>

        <label for="password2">Nhập lại mật khẩu</label>
        <input id="password2" name="password2" type="password" required minlength="6" maxlength="72" autocomplete="new-password">
        <span id="password-error" class="form-error"><?= e($errors['password2'] ?? '') ?></span>

        <label for="phone">Số điện thoại</label>
        <input id="phone" name="phone" type="tel" required pattern="[0-9]{9,11}" value="<?= e($old['phone']) ?>">
        <?php if (isset($errors['phone'])): ?><span class="form-error"><?= e($errors['phone']) ?></span><?php endif; ?>

        <label for="address">Địa chỉ nhận hàng</label>
        <textarea id="address" name="address" maxlength="255" rows="3"><?= e($old['address']) ?></textarea>
        <?php if (isset($errors['address'])): ?><span class="form-error"><?= e($errors['address']) ?></span><?php endif; ?>

        <label class="check-row"><input type="checkbox" name="agree" required> Tôi đồng ý với điều khoản sử dụng của cửa hàng.</label>
        <button type="submit">Đăng ký</button>
    </form>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
